<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ApplicationEvaluationsMetabox {
	private static ?ApplicationEvaluationsMetabox $instance = null;

	private function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'register_metabox' ] );
	}

	public static function get_instance() : self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function register_metabox() : void {
		add_meta_box(
			'ae-linked-evaluations',
			__( 'Linked Evaluations', 'applications-and-evaluations' ),
			[ $this, 'render_metabox' ],
			'application',
			'normal',
			'default'
		);
	}

	public function render_metabox( \WP_Post $post ) : void {
		try {
			$application = new Application( $post->ID );
		} catch ( \Throwable $e ) {
			echo '<p>' . esc_html__( 'Unable to load application details.', 'applications-and-evaluations' ) . '</p>';
			return;
		}

		if ( $application->get_status() !== 'submit' ) {
			echo '<p>' . esc_html__( 'This application has not been submitted yet.', 'applications-and-evaluations' ) . '</p>';
			return;
		}

		$evaluations = get_posts(
			[
				'post_type'   => 'evaluation',
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_query'  => [
					[
						'key'     => '_evaluation_application_id',
						'value'   => $post->ID,
						'compare' => '=',
					],
				],
			]
		);

		if ( empty( $evaluations ) ) {
			echo '<p>' . esc_html__( 'No evaluations have been submitted for this application yet.', 'applications-and-evaluations' ) . '</p>';
			return;
		}

		$all_groups   = [];
		$group_labels = [];
		$rows         = [];
		$total_totals = 0;

		// Prefer groups/labels defined on the evaluation form builder.
		$form_group_data = $this->get_form_group_labels_for_application( $post->ID );
		$all_groups      = $form_group_data['order'];
		$group_labels    = $form_group_data['labels'];

		foreach ( $evaluations as $evaluation_post ) {
			$score_data = function_exists( 'impeka_ae_collect_score_values' ) ? impeka_ae_collect_score_values( $evaluation_post->ID ) : [ 'groups' => [], 'all' => 0 ];
			$groups     = $score_data['groups'] ?? [];
			$overall    = $score_data['all'] ?? 0;

			// Ensure any groups present in data are included.
			foreach ( array_keys( $groups ) as $group_key ) {
				if ( ! in_array( $group_key, $all_groups, true ) ) {
					$all_groups[] = $group_key;
				}
				if ( ! isset( $group_labels[ $group_key ] ) ) {
					$group_labels[ $group_key ] = $group_key;
				}
			}

			$total_totals += $overall;

			$user    = get_userdata( $evaluation_post->post_author );

			$rows[] = [
				'eval'   => $evaluation_post,
				'user'   => $user,
				'groups' => $groups,
				'total'  => $overall,
			];
		}

		sort( $all_groups );
		$count = count( $rows );
		$headers = [];
		foreach ( $all_groups as $group_key ) {
			$headers[ $group_key ] = $group_labels[ $group_key ] ?? $group_key;
		}

		?>
		<style>
			.ae-evals-table {width:100%;border-collapse:collapse;margin-bottom:1em;}
			.ae-evals-table th, .ae-evals-table td {border:1px solid #e2e8f0;padding:8px;text-align:left;}
			.ae-evals-table th {background:#f8fafc;font-weight:600;}
			.ae-evals-table tfoot td {font-weight:700;}
		</style>
		<?php

		echo '<table class="ae-evals-table"><thead><tr>';
		echo '<th>' . esc_html__( 'Evaluator', 'applications-and-evaluations' ) . '</th>';
		foreach ( $headers as $label ) {
			echo '<th>' . esc_html( $label ) . '</th>';
		}
		echo '<th>' . esc_html__( 'Total', 'applications-and-evaluations' ) . '</th>';
		echo '</tr></thead><tbody>';

		$group_totals = array_fill_keys( $all_groups, 0 );

		foreach ( $rows as $row ) {
			$user_name = $row['user'] ? $row['user']->display_name : __( 'Unknown user', 'applications-and-evaluations' );
			echo '<tr>';
			echo '<td>' . esc_html( $user_name ) . '</td>';

			foreach ( $all_groups as $group_key ) {
				$value = $row['groups'][ $group_key ] ?? 0;
				$group_totals[ $group_key ] += $value;
				echo '<td>' . esc_html( $value ) . '</td>';
			}

			echo '<td>' . esc_html( $row['total'] ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody><tfoot>';

		echo '<tr><td>' . esc_html__( 'Sum of Totals', 'applications-and-evaluations' ) . '</td>';
		foreach ( $all_groups as $group_key ) {
			echo '<td>' . esc_html( $group_totals[ $group_key ] ) . '</td>';
		}
		echo '<td>' . esc_html( $total_totals ) . '</td></tr>';

		echo '<tr><td>' . esc_html__( 'Average Totals', 'applications-and-evaluations' ) . '</td>';
		foreach ( $all_groups as $group_key ) {
			$avg = $count ? $group_totals[ $group_key ] / $count : 0;
			echo '<td>' . esc_html( round( $avg, 2 ) ) . '</td>';
		}
		$average_total = $count ? $total_totals / $count : 0;
		echo '<td>' . esc_html( round( $average_total, 2 ) ) . '</td></tr>';

		echo '</tfoot></table>';
	}

	private function get_form_group_labels_for_application( int $application_id ) : array {
		$order  = [];
		$labels = [];

		// Get the linked application session(s).
		$app_sessions = wp_get_post_terms( $application_id, 'application_session' );
		$app_session  = is_array( $app_sessions ) && ! empty( $app_sessions ) ? $app_sessions[0] : null;

		if ( ! $app_session instanceof \WP_Term ) {
			return [ 'order' => $order, 'labels' => $labels ];
		}

		// Find evaluation sessions linked to this application session.
		$eval_sessions = [];
		if ( class_exists( EvaluationTemplateHelpers::class ) ) {
			$eval_sessions = EvaluationTemplateHelpers::get_evaluation_sessions_for_application_session( $app_session );
		}

		foreach ( $eval_sessions as $eval_session ) {
			if ( ! $eval_session instanceof \WP_Term ) {
				continue;
			}

			$pages = get_field( 'evaluation_form_pages', sprintf( 'evaluation_category_%d', $eval_session->term_id ) );
			if ( ! is_array( $pages ) ) {
				continue;
			}

			foreach ( $pages as $page ) {
				if ( empty( $page['evaluation_form_page_field_groups'] ) || ! is_array( $page['evaluation_form_page_field_groups'] ) ) {
					continue;
				}

				foreach ( $page['evaluation_form_page_field_groups'] as $group_row ) {
					if ( empty( $group_row['evaluation_form_field_group'] ) ) {
						continue;
					}

					$group_key = $group_row['evaluation_form_field_group'];
					$fields    = function_exists( 'acf_get_fields' ) ? acf_get_fields( $group_key ) : [];

					if ( is_array( $fields ) ) {
						$this->collect_group_labels_from_fields( $fields, $labels, $order );
					}
				}
			}
		}

		return [ 'order' => $order, 'labels' => $labels ];
	}

	private function collect_group_labels_from_fields( array $fields, array &$labels, array &$order ) : void {
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['type'] ) ) {
				continue;
			}

			$type  = $field['type'];
			$group = $field['data-score-group'] ?? '';

			if ( in_array( $type, [ 'score_subtotal', 'score_total' ], true ) && $group !== '' ) {
				if ( ! in_array( $group, $order, true ) ) {
					$order[] = $group;
				}

				if ( ! empty( $field['label'] ) ) {
					$labels[ $group ] = $field['label'];
				} elseif ( ! isset( $labels[ $group ] ) ) {
					$labels[ $group ] = $group;
				}
			}

			if ( isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
				$this->collect_group_labels_from_fields( $field['sub_fields'], $labels, $order );
			}
		}
	}
}
