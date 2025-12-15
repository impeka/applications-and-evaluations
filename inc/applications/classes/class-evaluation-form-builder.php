<?php

namespace Impeka\Applications;

use Impeka\Tools\Forms\FormPage;
use Impeka\Tools\Forms\PostForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides an ACF-driven form builder for Evaluation Sessions (taxonomy: evaluation_category).
 * Mirrors the Application form builder: pages -> field groups.
 */
class EvaluationFormBuilder {
	private static ?EvaluationFormBuilder $instance = null;
	private array $summary_config = [];

	private function __construct() {
		// Run after the evaluation taxonomy is registered so get_terms() succeeds.
		add_action( 'init', [ $this, 'prime_all_forms' ], 6 );
		add_action( 'acf/init', [ $this, 'register_form_builder_fields' ] );
		add_filter( 'acf/load_field/name=evaluation_form_field_group', [ $this, 'populate_field_group_choices' ] );
		add_filter( 'impeka/forms/success_url', [ $this, 'set_evaluation_success_url' ], 10, 3 );
		add_filter( 'impeka/forms/acf_form_args', [ $this, 'maybe_render_summary_page' ], 10, 3 );
	}

	public static function get_instance() : self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function register_form_builder_fields() : void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'group_evaluation_form_builder',
				'title'    => __( 'Evaluation Form Builder', 'applications-and-evaluations' ),
				'location' => [
					[
						[
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => 'evaluation_category',
						],
					],
				],
				'fields'   => [
					[
						'key'          => 'field_evaluation_form_pages',
						'label'        => __( 'Form Pages', 'applications-and-evaluations' ),
						'name'         => 'evaluation_form_pages',
						'type'         => 'repeater',
						'layout'       => 'block',
						'button_label' => __( 'Add Page', 'applications-and-evaluations' ),
						'sub_fields'   => [
							[
								'key'   => 'field_evaluation_form_page_label',
								'label' => __( 'Page Label', 'applications-and-evaluations' ),
								'name'  => 'evaluation_form_page_label',
								'type'  => 'text',
							],
							[
								'key'          => 'field_evaluation_form_page_field_groups',
								'label'        => __( 'Field Groups', 'applications-and-evaluations' ),
								'name'         => 'evaluation_form_page_field_groups',
								'type'         => 'repeater',
								'layout'       => 'block',
								'button_label' => __( 'Add Field Group', 'applications-and-evaluations' ),
								'sub_fields'   => [
									[
										'key'     => 'field_evaluation_form_field_group',
										'label'   => __( 'ACF Field Group', 'applications-and-evaluations' ),
										'name'    => 'evaluation_form_field_group',
										'type'    => 'select',
										'choices' => [],
										'ui'      => 1,
									],
								],
							],
						],
					],
					[
						'key'           => 'field_evaluation_form_append_summary',
						'label'         => __( 'Append Summary Page', 'applications-and-evaluations' ),
						'name'          => 'evaluation_form_append_summary',
						'type'          => 'true_false',
						'message'       => __( 'Add a final summary page showing all subtotals and total score.', 'applications-and-evaluations' ),
						'ui'            => 1,
						'default_value' => 0,
					],
				],
			]
		);
	}

	/**
	 * Populate the "ACF Field Group" select with available groups.
	 */
	public function populate_field_group_choices( array $field ) : array {
		$groups  = function_exists( 'acf_get_field_groups' ) ? acf_get_field_groups() : [];
		$choices = [];
		$excluded_keys = function_exists( '\ae_get_plugin_field_group_exclusions' ) ? \ae_get_plugin_field_group_exclusions() : [];

		foreach ( $groups as $group ) {
			$group_key   = $group['key'] ?? '';
			$group_title = $group['title'] ?? '';

			if ( $group_key === '' || $group_title === '' ) {
				continue;
			}

			if ( in_array( $group_key, $excluded_keys, true ) ) {
				continue;
			}

			$choices[ $group_key ] = sprintf( '%s (%s)', $group_title, $group_key );
		}

		$field['choices'] = $choices;

		return $field;
	}

	/**
	 * Redirect completed evaluation forms to the evaluation archive.
	 */
	public function set_evaluation_success_url( string $success_url, string $form_id, string $object_id ) : string {
		if ( strpos( $form_id, 'evaluation-session-' ) !== 0 ) {
			return $success_url;
		}

		$success_url = get_post_type_archive_link( 'evaluation' );
		$success_url = add_query_arg( 'success', true, $success_url );

		return $success_url;
	}

	public function form_id_from_term( \WP_Term $term ) : string {
		return sprintf( 'evaluation-session-%s', $term->slug );
	}

	/**
	 * Build all forms for every evaluation session to ensure hooks are registered early.
	 */
	public function prime_all_forms() : void {
		$sessions = get_terms(
			[
				'taxonomy'   => 'evaluation_category',
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $sessions ) ) {
			return;
		}

		foreach ( $sessions as $session ) {
			if ( $session instanceof \WP_Term ) {
				$this->build_form_for_term( $session );
			}
		}
	}

	public function build_form_for_term( \WP_Term $term ) : PostForm {
		$form_id = $this->form_id_from_term( $term );
		$form    = new PostForm( $form_id );
		$builder_group_keys = [];

		$pages = get_field( 'evaluation_form_pages', sprintf( 'evaluation_category_%d', $term->term_id ) );
		$pages = is_array( $pages ) ? $pages : [];

		if ( empty( $pages ) ) {
			$form->add_page( new FormPage() );
			return $form;
		}

		foreach ( $pages as $page_data ) {
			$page = new FormPage();

			if ( isset( $page_data['evaluation_form_page_field_groups'] ) && is_array( $page_data['evaluation_form_page_field_groups'] ) ) {
				foreach ( $page_data['evaluation_form_page_field_groups'] as $group_row ) {
					if ( empty( $group_row['evaluation_form_field_group'] ) ) {
						continue;
					}

					$builder_group_keys[] = $group_row['evaluation_form_field_group'];
					$page->add_field_group( $group_row['evaluation_form_field_group'] );
				}
			}

			$form->add_page( $page );
		}

		$append_summary = get_field( 'evaluation_form_append_summary', sprintf( 'evaluation_category_%d', $term->term_id ) );

		if ( $append_summary ) {
			$summary_labels = $this->collect_score_group_labels( $builder_group_keys );

			if ( ! empty( $summary_labels ) ) {
				$this->summary_config[ $form_id ] = [
					'labels' => $summary_labels,
					'term'   => $term->term_id,
				];

				$summary_page = new SummaryFormPage( $term->term_id, $summary_labels );
				$form->add_page( $summary_page );
			}
		}

		return $form;
	}

	private function collect_score_group_labels( array $group_keys ) : array {
		$labels = [];

		foreach ( $group_keys as $group_key ) {
			$fields = acf_get_fields( $group_key );
			if ( ! is_array( $fields ) ) {
				continue;
			}

			$this->walk_fields_for_labels( $fields, $labels );
		}

		return $labels;
	}

	private function walk_fields_for_labels( array $fields, array &$labels ) : void {
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['type'] ) ) {
				continue;
			}

			$type  = $field['type'];
			$group = $field['data-score-group'] ?? '';

			if ( $group !== '' && $type === 'score_subtotal' ) {
				// Use the sub-total field's own label, fallback to the group key if blank.
				$labels[ $group ] = ! empty( $field['label'] ) ? $field['label'] : $group;
			}

			if ( isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
				$this->walk_fields_for_labels( $field['sub_fields'], $labels );
			}
		}
	}

	public function maybe_render_summary_page( array $acf_form_args, string $form_id, int|string $post_id ) : array {
		if ( empty( $this->summary_config[ $form_id ] ) ) {
			return $acf_form_args;
		}

		$config = $this->summary_config[ $form_id ];
		$term   = $config['term'] ?? 0;

		if ( ! $term ) {
			return $acf_form_args;
		}

		$summary_group_key = sprintf( 'group_eval_summary_%d', $term );

		if ( in_array( $summary_group_key, $acf_form_args['field_groups'], true ) ) {
			$acf_form_args['field_groups'] = [ $summary_group_key ];
		}

		return $acf_form_args;
	}
}
