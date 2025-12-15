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

	private function __construct() {
		// Run after the evaluation taxonomy is registered so get_terms() succeeds.
		add_action( 'init', [ $this, 'prime_all_forms' ], 6 );
		add_action( 'acf/init', [ $this, 'register_form_builder_fields' ] );
		add_filter( 'acf/load_field/name=evaluation_form_field_group', [ $this, 'populate_field_group_choices' ] );
		add_filter( 'impeka/forms/success_url', [ $this, 'set_evaluation_success_url' ], 10, 3 );
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

					$page->add_field_group( $group_row['evaluation_form_field_group'] );
				}
			}

			$form->add_page( $page );
		}

		return $form;
	}
}
