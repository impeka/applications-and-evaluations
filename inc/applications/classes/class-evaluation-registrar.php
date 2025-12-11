<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EvaluationRegistrar {
	private static ?EvaluationRegistrar $instance = null;

	public static function get_instance() : self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', [ $this, 'register_post_type_and_tax' ], 5 );
		add_action( 'acf/init', [ $this, 'register_field_groups' ] );
		add_filter( 'acf/fields/taxonomy/result/name=evaluation_category_session', [ $this, 'format_session_option_label' ], 10, 4 );
	}

	public function register_post_type_and_tax() : void {
		register_post_type(
			'evaluation',
			[
				'labels'          => [
					'name'          => __( 'Evaluations', 'applications-and-evaluations' ),
					'singular_name' => __( 'Evaluation', 'applications-and-evaluations' ),
				],
				'public'          => true,
				'show_in_rest'    => true,
				'supports'        => [ 'title', 'author' ],
				'has_archive'     => true,
				'rewrite'         => [ 'slug' => 'evaluations' ],
				'show_in_menu'    => true,
				'menu_position'   => 26,
				'capability_type' => 'post',
			]
		);

		register_taxonomy(
			'evaluation_category',
			[ 'evaluation' ],
			[
				'labels'            => [
					'name'          => __( 'Evaluation Sessions', 'applications-and-evaluations' ),
					'singular_name' => __( 'Evaluation Session', 'applications-and-evaluations' ),
				],
				'public'            => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'show_in_rest'      => true,
			]
		);
	}

	public function register_field_groups() : void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// Build role choices.
		$role_choices = [];
		foreach ( wp_roles()->roles as $role_key => $role_data ) {
			$role_choices[ $role_key ] = $role_data['name'] ?? $role_key;
		}

		acf_add_local_field_group(
			[
				'key'      => 'group_evaluation_category_settings',
				'title'    => __( 'Evaluation Session Settings', 'applications-and-evaluations' ),
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
						'key'           => 'field_evaluation_category_session',
						'label'         => __( 'Application Session', 'applications-and-evaluations' ),
						'name'          => 'evaluation_category_session',
						'type'          => 'taxonomy',
						'taxonomy'      => 'application_session',
						'field_type'    => 'select',
						'return_format' => 'id',
						'add_term'      => 0,
						'multiple'      => 0,
						'ui'            => 1,
						'instructions'  => __( 'Select the application session this evaluation category applies to.', 'applications-and-evaluations' ),
						'required'      => 1,
					],
					[
						'key'      => 'field_evaluation_category_access_mode',
						'label'    => __( 'Who Can Evaluate', 'applications-and-evaluations' ),
						'name'     => 'evaluation_category_access_mode',
						'type'     => 'radio',
						'layout'   => 'horizontal',
						'choices'  => [
							'roles' => __( 'By user role / group', 'applications-and-evaluations' ),
							'users' => __( 'By specific users', 'applications-and-evaluations' ),
						],
						'default_value' => 'roles',
						'return_format' => 'value',
					],
					[
						'key'               => 'field_evaluation_category_roles',
						'label'             => __( 'Allowed Roles', 'applications-and-evaluations' ),
						'name'              => 'evaluation_category_roles',
						'type'              => 'select',
						'choices'           => $role_choices,
						'multiple'          => 1,
						'ui'                => 1,
						'return_format'     => 'value',
						'conditional_logic' => [
							[
								[
									'field'    => 'field_evaluation_category_access_mode',
									'operator' => '==',
									'value'    => 'roles',
								],
							],
						],
						'instructions'      => __( 'Select the roles/groups allowed to evaluate applications in this evaluation session.', 'applications-and-evaluations' ),
					],
					[
						'key'               => 'field_evaluation_category_users',
						'label'             => __( 'Allowed Users', 'applications-and-evaluations' ),
						'name'              => 'evaluation_category_users',
						'type'              => 'user',
						'multiple'          => 1,
						'return_format'     => 'id',
						'role'              => [],
						'allow_null'        => 0,
						'ui'                => 1,
						'conditional_logic' => [
							[
								[
									'field'    => 'field_evaluation_category_access_mode',
									'operator' => '==',
									'value'    => 'users',
								],
							],
						],
						'instructions'      => __( 'Select specific evaluators. Uses a searchable multi-select to avoid long lists.', 'applications-and-evaluations' ),
					],
				],
			]
		);
	}

	/**
	 * Customize the session select label to include the parent application type for clarity.
	 */
	public function format_session_option_label( $term_title, $term, $field, $post_id ) {
		if ( ! $term instanceof \WP_Term ) {
			return $term_title;
		}

		$type_id = get_field( 'application_session_application_type', sprintf( 'application_session_%d', $term->term_id ) );
		$type    = $type_id ? get_term( (int) $type_id, 'application_type' ) : null;

		if ( $type instanceof \WP_Term ) {
			return sprintf( '%s — %s', $type->name, $term_title );
		}

		return $term_title;
	}
}
