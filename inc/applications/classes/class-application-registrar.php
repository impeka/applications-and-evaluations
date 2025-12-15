<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ApplicationRegistrar {
    private static ?ApplicationRegistrar $instance = null;

    public static function get_instance() : self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'register_post_type_and_tax' ], 4 );
        add_action( 'acf/init', [ $this, 'register_form_builder_fields' ] );
        add_filter( 'acf/load_field/name=application_form_field_group', [ $this, 'populate_field_group_choices' ] );
        add_filter( 'manage_application_posts_columns', [ $this, 'add_status_column' ] );
        add_action( 'manage_application_posts_custom_column', [ $this, 'render_status_column' ], 10, 2 );
        add_action( 'manage_application_posts_custom_column', [ $this, 'render_submit_date_column' ], 10, 2 );
        add_action( 'manage_application_posts_custom_column', [ $this, 'render_lock_column' ], 10, 2 );
        add_action( 'restrict_manage_posts', [ $this, 'add_status_admin_filter' ] );
        add_filter( 'parse_query', [ $this, 'apply_status_admin_filter' ] );
        add_filter( 'impeka/forms/success_url', [ $this, 'set_application_success_url' ], 10, 3 );
        add_action( 'init', [ $this, 'add_application_view_rewrite' ], 15 );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_filter( 'map_meta_cap', [ $this, 'prevent_delete_when_session_closed' ], 10, 4 );
    }

    public function set_application_success_url( string $success_url, string $form_id, string $object_id ) : string {
        // Only override for application-type forms; leave other forms (e.g. evaluations) untouched.
        if ( strpos( $form_id, 'application-type-' ) !== 0 ) {
            return $success_url;
        }

        $success_url = get_post_type_archive_link( 'application' );
        $success_url = add_query_arg( 'success', true, $success_url );
        return $success_url;
    }

    public function register_post_type_and_tax() : void {
        register_post_type(
            'application',
            [
                'labels'          => [
                    'name'          => __( 'Applications', 'applications-and-evaluations' ),
                    'singular_name' => __( 'Application', 'applications-and-evaluations' ),
                ],
                'public'          => true,
                'show_in_rest'    => true,
                'supports'        => [ 'title', 'author' ],
                'has_archive'     => true,
                'rewrite'         => [ 'slug' => 'applications' ],
                'show_in_menu'    => true,
                'menu_position'   => 25,
                'capability_type' => 'post',
            ]
        );

        register_taxonomy(
            'application_type',
            [ 'application' ],
            [
                'labels'            => [
                    'name'          => __( 'Application Types', 'applications-and-evaluations' ),
                    'singular_name' => __( 'Application Type', 'applications-and-evaluations' ),
                ],
                'public'            => true,
                'show_admin_column' => true,
                'hierarchical'      => false,
                'show_in_rest'      => true,
            ]
        );

        register_taxonomy(
            'application_session',
            [ 'application' ],
            [
                'labels'            => [
                    'name'          => __( 'Application Sessions', 'applications-and-evaluations' ),
                    'singular_name' => __( 'Application Session', 'applications-and-evaluations' ),
                ],
                'public'            => true,
                'show_admin_column' => true,
                'hierarchical'      => false,
                'show_in_rest'      => true,
            ]
        );
    }

    public function register_form_builder_fields() : void {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        acf_add_local_field_group(
            [
                'key'      => 'group_application_type_form_builder',
                'title'    => __( 'Application Form Builder', 'applications-and-evaluations' ),
                'location' => [
                    [
                        [
                            'param'    => 'taxonomy',
                            'operator' => '==',
                            'value'    => 'application_type',
                        ],
                    ],
                ],
                'fields'   => [
                    [
                        'key'          => 'field_application_form_pages',
                        'label'        => __( 'Form Pages', 'applications-and-evaluations' ),
                        'name'         => 'application_form_pages',
                        'type'         => 'repeater',
                        'layout'       => 'block',
                        'button_label' => __( 'Add Page', 'applications-and-evaluations' ),
                        'sub_fields'   => [
                            [
                                'key'   => 'field_application_form_page_label',
                                'label' => __( 'Page Label', 'applications-and-evaluations' ),
                                'name'  => 'application_form_page_label',
                                'type'  => 'text',
                            ],
                            [
                                'key'          => 'field_application_form_page_field_groups',
                                'label'        => __( 'Field Groups', 'applications-and-evaluations' ),
                                'name'         => 'application_form_page_field_groups',
                                'type'         => 'repeater',
                                'layout'       => 'block',
                                'button_label' => __( 'Add Field Group', 'applications-and-evaluations' ),
                                'sub_fields'   => [
                                    [
                                        'key'     => 'field_application_form_field_group',
                                        'label'   => __( 'ACF Field Group', 'applications-and-evaluations' ),
                                        'name'    => 'application_form_field_group',
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

        acf_add_local_field_group(
            [
                'key'      => 'group_application_session_settings',
                'title'    => __( 'Application Session Settings', 'applications-and-evaluations' ),
                'location' => [
                    [
                        [
                            'param'    => 'taxonomy',
                            'operator' => '==',
                            'value'    => 'application_session',
                        ],
                    ],
                ],
                'fields'   => [
                    [
                        'key'           => 'field_application_session_application_type',
                        'label'         => __( 'Application Type', 'applications-and-evaluations' ),
                        'name'          => 'application_session_application_type',
                        'type'          => 'taxonomy',
                        'taxonomy'      => 'application_type',
                        'field_type'    => 'select',
                        'return_format' => 'id',
                        'add_term'      => 0,
                        'multiple'      => 0,
                        'ui'            => 1,
                        'instructions'  => __( 'Select the application type this session belongs to.', 'applications-and-evaluations' ),
                        'required'      => 1,
                    ],
                    [
                        'key'            => 'field_application_session_start',
                        'label'          => __( 'Opens At', 'applications-and-evaluations' ),
                        'name'           => 'application_session_start',
                        'type'           => 'date_time_picker',
                        'display_format' => 'Y-m-d H:i',
                        'return_format'  => 'Y-m-d H:i:s',
                        'instructions'   => __( 'Applications can start at this date/time. Leave empty to open immediately.', 'applications-and-evaluations' ),
                    ],
                    [
                        'key'            => 'field_application_session_end',
                        'label'          => __( 'Closes At', 'applications-and-evaluations' ),
                        'name'           => 'application_session_end',
                        'type'           => 'date_time_picker',
                        'display_format' => 'Y-m-d H:i',
                        'return_format'  => 'Y-m-d H:i:s',
                        'instructions'   => __( 'Applications close after this date/time. Leave empty for no end date.', 'applications-and-evaluations' ),
                    ],
                    [
                        'key'           => 'field_application_session_submission_limit',
                        'label'         => __( 'Submissions Per User', 'applications-and-evaluations' ),
                        'name'          => 'application_session_submission_limit',
                        'type'          => 'number',
                        'min'           => 0,
                        'step'          => 1,
                        'instructions'  => __( 'Maximum applications a single user can submit for this session. Use 0 or leave empty for unlimited.', 'applications-and-evaluations' ),
                        'wrapper'       => [ 'width' => '50' ],
                        'default_value' => '',
                    ],
                    [
                        'key'           => 'field_application_session_visibility_out_of_session',
                        'label'         => __( 'Visibility When Out of Session', 'applications-and-evaluations' ),
                        'name'          => 'application_session_visibility_out_of_session',
                        'type'          => 'true_false',
                        'message'       => __( 'Keep this session selectable even when outside its open/close dates.', 'applications-and-evaluations' ),
                        'ui'            => 1,
                        'wrapper'       => [ 'width' => '50' ],
                        'default_value' => 0,
                    ],
                    [
                        'key'          => 'field_application_session_email_subject',
                        'label'        => __( 'Confirmation Email Subject', 'applications-and-evaluations' ),
                        'name'         => 'application_session_email_subject',
                        'type'         => 'text',
                        'instructions' => __( 'Email subject sent to applicants after submission.', 'applications-and-evaluations' ),
                    ],
                    [
                        'key'          => 'field_application_session_email_message',
                        'label'        => __( 'Confirmation Email Message', 'applications-and-evaluations' ),
                        'name'         => 'application_session_email_message',
                        'type'         => 'textarea',
                        'rows'         => 4,
                        'instructions' => __( 'Email body sent to applicants after submission.', 'applications-and-evaluations' ),
                    ],
                ],
            ]
        );

        acf_add_local_field_group(
            [
                'key' => 'group_672a73e204b12',
                'title' => __( 'Force Unlock', 'applications-and-evaluations' ),
                'fields' => [
                    [
                        'key' => 'field_672a73e3c3648',
                        'label' => __( 'Force Unlock', 'applications-and-evaluations' ),
                        'name' => 'force_unlock',
                        'aria-label' => '',
                        'type' => 'true_false',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'message' => __( 'Unlock this application so applicant can make changes', 'applications-and-evaluations' ),
                        'default_value' => 0,
                        'ui' => 0,
                        'ui_on_text' => '',
                        'ui_off_text' => '',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'application',
                        ],
                    ],
                ],
                'menu_order' => 0,
                'position' => 'side',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 0,
            ]
        );
    }

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

    public function add_status_column( array $columns ) : array {
        $new_columns = [];
        $date_label  = $columns['date'] ?? __( 'Date', 'applications-and-evaluations' );

        foreach ( $columns as $key => $label ) {
            if ( $key === 'date' ) {
                continue;
            }

            $new_columns[ $key ] = $label;

            if ( $key === 'title' ) {
                $new_columns['application_status'] = __( 'Status', 'applications-and-evaluations' );
                $new_columns['application_submit_date'] = __( 'Submitted', 'applications-and-evaluations' );
            }
        }

        $new_columns['is_unlocked'] = __( 'Unlocked', 'applications-and-evaluations' );
        $new_columns['date']        = $date_label;

        return $new_columns;
    }

    public function render_status_column( string $column, int $post_id ) : void {
        if ( $column !== 'application_status' ) {
            return;
        }

        try {
            $application = new Application( $post_id );
            $status      = $application->get_status();

            switch ( $status ) {
                case 'submit':
                    esc_html_e( 'Submitted', 'applications-and-evaluations' );
                    break;
                default:
                    // Unknown status: treat as zero progress.
                    $progress = $status === 'progress' ? $application->get_progress_percentage() : 0;
                    printf( '<progress class="application__progress" max="100" value="%1$s">%1$s</progress>', $progress );
                    break;
            }
        } catch ( \Throwable $e ) {
            printf( '<progress class="application__progress" max="100" value="%1$s">%1$s</progress>', 0 );
        }
    }

    public function render_submit_date_column( string $column, int $post_id ) : void {
        if ( $column !== 'application_submit_date' ) {
            return;
        }

        $application = new Application( $post_id );
        $format      = sprintf( '%s %s', get_option( 'date_format' ), get_option( 'time_format' ) );
        echo esc_html( $application->get_submit_date( $format ) );
    }

    public function render_lock_column( string $column, int $post_id ) : void {
        if ( $column !== 'is_unlocked' ) {
            return;
        }

        $application = new Application( $post_id );

        if( $application->is_unlocked() ) {
            echo '<i class="fa-regular fa-unlock success-green-color"></i>';
        }
        else {
            echo '<i class="fa-regular fa-lock error-red-color"></i>';
        }
    }

    /**
     * Adds a "Published only" filter dropdown on the Applications list table.
     */
    public function add_status_admin_filter() : void {
        global $typenow, $pagenow;

        if ( $pagenow !== 'edit.php' || $typenow !== 'application' ) {
            return;
        }

        $selected = isset( $_GET['application_status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['application_status_filter'] ) ) : '';
        ?>
        <select name="application_status_filter">
            <option value=""><?php esc_html_e( 'All statuses', 'applications-and-evaluations' ); ?></option>
            <option value="submitted" <?php selected( $selected, 'submitted' ); ?>><?php esc_html_e( 'Submitted only', 'applications-and-evaluations' ); ?></option>
        </select>
        <?php
    }

    /**
     * Applies the status filter to the Applications query.
     */
    public function apply_status_admin_filter( $query ) {
        global $pagenow;

        if ( ! is_admin() || $pagenow !== 'edit.php' || ! $query->is_main_query() ) {
            return $query;
        }

        $post_type = $query->get( 'post_type' );

        if ( $post_type !== 'application' ) {
            return $query;
        }

        $filter = isset( $_GET['application_status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['application_status_filter'] ) ) : '';

        if ( $filter === 'submitted' ) {
            $meta_query   = $query->get( 'meta_query' );
            $meta_query   = is_array( $meta_query ) ? $meta_query : [];
            $meta_query[] = [
                'key'     => '_application_status',
                'value'   => 'submit',
                'compare' => '=',
            ];
            $query->set( 'meta_query', $meta_query );
        }

        return $query;
    }

    public function add_application_view_rewrite() : void {
        // Most specific: single application view-only.
        add_rewrite_rule(
            '^applications/([^/]+)/view/?$',
            'index.php?application=$matches[1]&view_only=1',
            'top'
        );

        // Application type + session listing (namespaced to avoid single slug collisions).
        add_rewrite_rule(
            '^applications/type/([^/]+)/([^/]+)/?$',
            'index.php?post_type=application&application_type_slug=$matches[1]&application_session_slug=$matches[2]',
            'top'
        );

        // Application type listing (namespaced to avoid single slug collisions).
        add_rewrite_rule(
            '^applications/type/([^/]+)/?$',
            'index.php?post_type=application&application_type_slug=$matches[1]',
            'top'
        );
    }

    public function add_query_vars( array $vars ) : array {
        $vars[] = 'view_only';
        $vars[] = 'application_type_slug';
        $vars[] = 'application_session_slug';
        return $vars;
    }

    /**
     * Prevent users from deleting applications when their session is closed.
     * Admins (delete_others_posts/manage_options) are exempt.
     */
    public function prevent_delete_when_session_closed( array $caps, string $cap, int $user_id, array $args ) : array {
        if ( $cap !== 'delete_post' ) {
            return $caps;
        }

        $post_id = isset( $args[0] ) ? (int) $args[0] : 0;

        if ( ! $post_id ) {
            return $caps;
        }

        $post = get_post( $post_id );

        if ( ! $post instanceof \WP_Post || $post->post_type !== 'application' ) {
            return $caps;
        }

        // Let admins / editors with delete_others bypass.
        if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'delete_others_posts' ) ) {
            return $caps;
        }

        $sessions = wp_get_post_terms( $post_id, 'application_session' );
        $session  = is_array( $sessions ) && ! empty( $sessions ) && $sessions[0] instanceof \WP_Term ? $sessions[0] : null;

        if ( ! $session ) {
            // If no session linked, block deletion for safety.
            return [ 'do_not_allow' ];
        }

        $is_active = ApplicationTemplateHelpers::is_session_active( $session );

        if ( $is_active ) {
            return $caps;
        }

        // Block delete for closed sessions.
        return [ 'do_not_allow' ];
    }
}
