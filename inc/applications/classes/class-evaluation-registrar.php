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
		add_filter( 'manage_evaluation_posts_columns', [ $this, 'add_admin_columns' ] );
		add_action( 'manage_evaluation_posts_custom_column', [ $this, 'render_status_column' ], 10, 2 );
		add_action( 'manage_evaluation_posts_custom_column', [ $this, 'render_submit_date_column' ], 10, 2 );
		add_action( 'manage_evaluation_posts_custom_column', [ $this, 'render_lock_column' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'add_status_admin_filter' ] );
		add_filter( 'parse_query', [ $this, 'apply_status_admin_filter' ] );
		add_action( 'init', [ $this, 'add_evaluation_view_rewrite' ], 15 );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
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
					[
						'key'          => 'field_evaluation_category_email_subject',
						'label'        => __( 'Confirmation Email Subject', 'applications-and-evaluations' ),
						'name'         => 'evaluation_category_email_subject',
						'type'         => 'text',
						'instructions' => __( 'Email subject sent to evaluators after they submit an evaluation.', 'applications-and-evaluations' ),
					],
					[
						'key'          => 'field_evaluation_category_email_message',
						'label'        => __( 'Confirmation Email Message', 'applications-and-evaluations' ),
						'name'         => 'evaluation_category_email_message',
						'type'         => 'textarea',
						'rows'         => 4,
						'instructions' => __( 'Email body sent to evaluators after submission.', 'applications-and-evaluations' ),
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

	public function add_admin_columns( array $columns ) : array {
		$new_columns = [];
		$date_label  = $columns['date'] ?? __( 'Date', 'applications-and-evaluations' );

		foreach ( $columns as $key => $label ) {
			if ( $key === 'date' ) {
				continue;
			}

			$new_columns[ $key ] = $label;

			if ( $key === 'title' ) {
				$new_columns['evaluation_status']       = __( 'Status', 'applications-and-evaluations' );
				$new_columns['evaluation_submit_date']  = __( 'Submitted', 'applications-and-evaluations' );
			}
		}

		$new_columns['is_unlocked'] = __( 'Unlocked', 'applications-and-evaluations' );
		$new_columns['date']        = $date_label;

		return $new_columns;
	}

	public function render_status_column( string $column, int $post_id ) : void {
		if ( $column !== 'evaluation_status' ) {
			return;
		}

		try {
			$evaluation = new Evaluation( $post_id );
			$status     = $evaluation->get_status();

			switch ( $status ) {
				case 'submit':
					esc_html_e( 'Submitted', 'applications-and-evaluations' );
					break;
				default:
					// Unknown status: treat as zero progress.
					$progress = $status === 'progress' ? $evaluation->get_progress_percentage() : 0;
					printf( '<progress class="application__progress" max="100" value="%1$s">%1$s</progress>', $progress );
					break;
			}
		} catch ( \Throwable $e ) {
			printf( '<progress class="application__progress" max="100" value="%1$s">%1$s</progress>', 0 );
		}
	}

	public function render_submit_date_column( string $column, int $post_id ) : void {
		if ( $column !== 'evaluation_submit_date' ) {
			return;
		}

		$evaluation = new Evaluation( $post_id );
		$format     = sprintf( '%s %s', get_option( 'date_format' ), get_option( 'time_format' ) );
		echo esc_html( $evaluation->get_submit_date( $format ) );
	}

	public function render_lock_column( string $column, int $post_id ) : void {
		if ( $column !== 'is_unlocked' ) {
			return;
		}

		$evaluation = new Evaluation( $post_id );

		if ( $evaluation->is_unlocked() ) {
			echo '<i class="fa-regular fa-unlock success-green-color"></i>';
		} else {
			echo '<i class="fa-regular fa-lock error-red-color"></i>';
		}
	}

	public function add_evaluation_view_rewrite() : void {
		add_rewrite_rule(
			'^evaluations/([^/]+)/view/?$',
			'index.php?evaluation=$matches[1]&view_only=1',
			'top'
		);
	}

	public function add_query_vars( array $vars ) : array {
		if ( ! in_array( 'view_only', $vars, true ) ) {
			$vars[] = 'view_only';
		}

		return $vars;
	}

	/**
	 * Adds a "Published only" filter dropdown on the Evaluations list table.
	 */
	public function add_status_admin_filter() : void {
		global $typenow, $pagenow;

		if ( $pagenow !== 'edit.php' || $typenow !== 'evaluation' ) {
			return;
		}

		$selected = isset( $_GET['evaluation_status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['evaluation_status_filter'] ) ) : '';
		?>
		<select name="evaluation_status_filter">
			<option value=""><?php esc_html_e( 'All statuses', 'applications-and-evaluations' ); ?></option>
			<option value="submitted" <?php selected( $selected, 'submitted' ); ?>><?php esc_html_e( 'Submitted only', 'applications-and-evaluations' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Applies the status filter to the Evaluations query.
	 */
	public function apply_status_admin_filter( $query ) {
		global $pagenow;

		if ( ! is_admin() || $pagenow !== 'edit.php' || ! $query->is_main_query() ) {
			return $query;
		}

		$post_type = $query->get( 'post_type' );

		if ( $post_type !== 'evaluation' ) {
			return $query;
		}

		$filter = isset( $_GET['evaluation_status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['evaluation_status_filter'] ) ) : '';

		if ( $filter === 'submitted' ) {
			$meta_query   = $query->get( 'meta_query' );
			$meta_query   = is_array( $meta_query ) ? $meta_query : [];
			$meta_query[] = [
				'key'     => '_evaluation_status',
				'value'   => 'submit',
				'compare' => '=',
			];
			$query->set( 'meta_query', $meta_query );
		}

		return $query;
	}
}
