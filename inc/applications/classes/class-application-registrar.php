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
        add_filter( 'impeka/forms/success_url', [ $this, 'set_application_success_url' ], 10, 3 );
        add_action( 'init', [ $this, 'add_application_view_rewrite' ], 15 );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
    }

    public function set_application_success_url( string $success_url, string $form_id, string $object_id ) : string {
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

        foreach ( $groups as $group ) {
            if ( empty( $group['key'] ) || empty( $group['title'] ) ) {
                continue;
            }

            $choices[ $group['key'] ] = sprintf( '%s (%s)', $group['title'], $group['key'] );
        }

        $field['choices'] = $choices;

        return $field;
    }

    public function add_status_column( array $columns ) : array {
        $new_columns = [];

        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;

            if ( $key === 'title' ) {
                $new_columns['application_status'] = __( 'Status', 'applications-and-evaluations' );
            }
        }

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
                case 'progress':
                    $progress = $application->get_progress_percentage();
                    printf( '<progress class="application__progress" max="100" value="%1$s">%1$s</progress>', $progress );
                    break;
                case 'submit':
                    esc_html_e( 'Submitted', 'applications-and-evaluations' );
                    break;
                default:
                    echo $status !== '' ? esc_html( $status ) : '—';
                    break;
            }
        } catch ( \Throwable $e ) {
            echo '—';
        }
    }

    public function add_application_view_rewrite() : void {
        add_rewrite_rule(
            '^applications/([^/]+)/view/?$',
            'index.php?application=$matches[1]&view_only=1',
            'top'
        );
    }

    public function add_query_vars( array $vars ) : array {
        $vars[] = 'view_only';
        return $vars;
    }
}
