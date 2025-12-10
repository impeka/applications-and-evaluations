<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ApplicationViewManager {
    private static ?ApplicationViewManager $_instance = null;

    private function __construct() {
        add_filter( 'impeka/forms/acf_form_args', [$this, 'acf_form_args'], 10, 3 );
        add_filter( 'impeka/forms/get_form', [$this, 'get_form'], 10, 3 );
        add_filter( 'impeka/forms/get_form/page', [$this, 'page'], 10, 3 );
        add_filter( 'impeka/forms/get_nav', [$this, 'get_nav'], 10, 3 );

        foreach( ['text', 'textarea', 'email', 'tel', 'number', 'maskfield'] as $field_type ) {
            add_action( sprintf( 'acf/render_field/type=%s', $field_type ), [$this, 'render_value_only'], 9 );
            add_action( sprintf( 'acf/render_field/type=%s', $field_type ), [$this, 'render_value_only_exit'], 11 );
        }

        add_action( 'acf/render_field/type=date_picker', [$this, 'render_date_value_only'], 9 );
        add_action( 'acf/render_field/type=date_picker', [$this, 'render_date_value_only_exit'], 11 );

        add_action( 'acf/render_field/type=select', [$this, 'render_select_value_only'], 9 );
        add_action( 'acf/render_field/type=select', [$this, 'render_select_value_only_exit'], 11 );
    }

    public function render_select_value_only( array $field ) : void {
        global $wp_query;

        if( isset( $wp_query->query_vars['show_applicant'] ) ) {
            $post_id = acf_get_form_data( 'post_id' );
            $field_object = get_field_object( $field['key'], $post_id );
            $value = get_field( $field['key'], $post_id );

            if( $value === false ) {
                $value = array_values($field_object['choices'])[0];
            }

            $label = $value;
            if( $field_object && isset( $field_object['choices'][$value] ) ) {
                $label = $field_object['choices'][$value];
            }

            $value = ! empty( $value ) ? $label : '&nbsp;';
            echo sprintf( '<div class="acf-input-wrap acf-input-view-only">%s</div>', $value );
            echo '<div style="display:none">';
        }
    }

    public function render_select_value_only_exit( array $field ) : void {
        global $wp_query;

        if( isset( $wp_query->query_vars['show_applicant'] ) ) {
            echo '</div>';
        }
    }

    public function render_value_only( array $field ) : void {
        global $wp_query;

        if( isset( $wp_query->query_vars['view_only'] ) ) {
            $value = ! empty( $field['value'] ) ? $field['value'] : '&nbsp;';
            echo sprintf( '<div class="acf-input-wrap acf-input-view-only">%s</div>', apply_filters( 'the_content', $value ) );
            ob_start();
        }
    }

    public function render_date_value_only_exit( array $field ) : void {
        global $wp_query;

        if( isset( $wp_query->query_vars['view_only'] ) ) {
            ob_get_clean();
        }
    }

    public function render_date_value_only( array $field ) : void {
        global $wp_query;

        if( isset( $wp_query->query_vars['view_only'] ) ) {
            $value = ! empty( $field['value'] ) ? $field['value'] : '&nbsp;';
            echo sprintf( '<div class="acf-input-wrap acf-input-view-only">%s</div>', apply_filters( 'acf/format_value', $value, '', $field ) );
            ob_start();
        }
    }

    public function render_value_only_exit( array $field ) : void {
        global $wp_query;

        if( isset( $wp_query->query_vars['view_only'] ) ) {
            ob_get_clean();
        }
    }

    public function acf_form_args( array $acf_form_args, string $form_id, int|string $post_id ) : array {
        global $wp_query;
        
        if( isset( $wp_query->query_vars['view_only'] ) ) {
            $acf_form_args['form'] = false;
            $acf_form_args['html_before_fields'] = sprintf( '<div class="print-controls"><button class="button-default" data-print-grant><i class="fa-light fa-print"></i> %s</button></div><div data-print-only="1">', __( 'Print', 'fasmc' ) );
            $acf_form_args['html_after_fields'] = '</div>';
        }

        return $acf_form_args;
    }

    public function get_form( string $form_output, string $form_id, int|string $post_id ) : string {
        global $wp_query;
    
        return $form_output;
    }

    public function page( int $page, string $form_id, int|string $post_id ) : int {
        global $wp_query;

        if( isset( $wp_query->query_vars['view_only'] ) ) {
            return 0;
        }

        return $page;
    }

    public function get_nav( string $nav_output, string $form_id, int|string $post_id ) : string {
        global $wp_query;

        if( isset( $wp_query->query_vars['view_only'] ) ) {
            return '';
        }

        return $nav_output;
    }

    public static function get_instance() : ApplicationViewManager {
        if( self::$_instance === null ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
}