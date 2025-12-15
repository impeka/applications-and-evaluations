<?php

namespace Impeka\Applications;

use Impeka\Tools\Forms\FormPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SummaryFormPage extends FormPage {
	private int $term_id;
	private array $labels;
	private string $group_key;
	private string $field_key;
	private bool $group_registered = false;

	public function __construct( int $term_id, array $labels ) {
		parent::__construct();

		$this->term_id   = $term_id;
		$this->labels    = $labels;
		$this->group_key = sprintf( 'group_eval_summary_%d', $term_id );
		$this->field_key = sprintf( 'field_eval_summary_%d', $term_id );

		// Register the field group immediately (and on acf/init if not yet available).
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			$this->register_field_group();
		} else {
			add_action( 'acf/init', [ $this, 'register_field_group' ] );
		}

		// Populate the message field with dynamic HTML.
		add_filter( "acf/render_field/key={$this->field_key}", [ $this, 'render_summary_table' ], 10, 1 );
		add_filter( "acf/load_field/key={$this->field_key}", [ $this, 'render_summary_table' ], 10, 1 );

		// Attach this group's key so FormPage renders it.
		$this->add_field_group( $this->group_key );
	}

	public function register_field_group() : void {
		if ( $this->group_registered || ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'    => $this->group_key,
				'title'  => __( 'Evaluation Summary', 'applications-and-evaluations' ),
				'fields' => [
					[
						'key'     => $this->field_key,
						'label'   => __( 'Summary', 'applications-and-evaluations' ),
						'name'    => 'evaluation_summary_html',
						'type'    => 'message',
						'message' => '',
						'esc_html' => 0,
					],
				],
				'location' => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'evaluation',
						],
					],
				],
			]
		);

		$this->group_registered = true;
	}

	public function render_summary_table( array $field ) : array {
		$post_id = acf_get_form_data( 'post_id' );
		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$scores = function_exists( 'impeka_ae_collect_score_values' ) ? impeka_ae_collect_score_values( $post_id ) : [ 'groups' => [], 'all' => 0 ];
		$groups = $scores['groups'] ?? [];
		$total  = $scores['all'] ?? 0;

		$html  = '<table class="ae-eval-summary-table" style="width:100%;border-collapse:collapse;margin:1em 0;">';
		$html .= '<thead><tr>';
		$html .= '<th style="border:1px solid #e2e8f0;padding:8px;text-align:left;">' . esc_html__( 'Subtotal', 'applications-and-evaluations' ) . '</th>';
		$html .= '<th style="border:1px solid #e2e8f0;padding:8px;text-align:left;">' . esc_html__( 'Score', 'applications-and-evaluations' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $this->labels as $group_key => $label ) {
			$value = $groups[ $group_key ] ?? 0;
			$html .= '<tr>';
			$html .= '<td style="border:1px solid #e2e8f0;padding:8px;text-align:left;">' . esc_html( $label ) . '</td>';
			$html .= '<td style="border:1px solid #e2e8f0;padding:8px;text-align:left;">' . esc_html( $value ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '<tr>';
		$html .= '<td style="border:1px solid #e2e8f0;padding:8px;text-align:left;font-weight:700;">' . esc_html__( 'Total', 'applications-and-evaluations' ) . '</td>';
		$html .= '<td style="border:1px solid #e2e8f0;padding:8px;text-align:left;font-weight:700;">' . esc_html( $total ) . '</td>';
		$html .= '</tr>';

		$html .= '</tbody></table>';

		$field['message'] = $html;
		return $field;
	}
}
