<?php

namespace Impeka\Tools\Forms;

if( class_exists( UserFormManager::class ) ) {
    return;
}

class UserFormManager {

    private static ?UserFormManager $_instance = null;
    private array $_user_forms = [];

    private function __construct() {
        add_shortcode( 'user_form', [$this, 'do_user_form'] );

        add_action( 'acf/input/admin_head', [$this, 'disable_javascript_required_fields']);
        add_action( 'acf/validate_save_post', [$this, 'disable_required_fields'], 10, 0);
    }

    public function disable_required_fields() : void {
        if( ! function_exists( 'get_current_screen' ) ) {
            return;
        }
        
        $user = wp_get_current_user();
        $screen = get_current_screen();

        if( 
            in_array( 'administrator', $user->roles ) 
            && (
                is_object( $screen ) 
                && is_a( $screen, 'WP_Screen' )
                && (
                    $screen->id == 'user'
                    || $screen->id == 'user-edit'
                )
            )
        ) {
            acf_reset_validation_errors();
        }
    }

    public function disable_javascript_required_fields() : void {
        if( ! function_exists( 'get_current_screen' ) ) {
            return;
        }
        
        $user = wp_get_current_user();
        $screen = get_current_screen();

        if( 
            is_object( $screen ) 
            && is_a( $screen, 'WP_Screen' ) 
        ) {
            if(
                in_array( 'administrator', $user->roles ) 
                && (
                    $screen->id == 'user'
                    || $screen->id == 'user-edit'
                )
            ) {
                echo '<script type="text/javascript">addEventListener("DOMContentLoaded", (event) => {acf.validation.active = false;});</script>';
            }
        }
    }

    public function do_user_form( array $atts ) : string {
        $atts = shortcode_atts(
            [
                'id' => ''
            ], 
            $atts, 
            'user_form' 
        );

        $user = wp_get_current_user();

        if( 
            ! $atts['id'] 
            || ! isset( $this->_user_forms[$atts['id']] )
            || ! $user->exists()
        )
            return '';

        $post_id = sprintf( 'user_%s', $user->ID );

        $output = '';

        if( 
            isset( $_GET['success'] ) 
        ) {
            if( $message = get_field( 'success_message', 'applicant-options' ) ) {
                $output .= sprintf( '<div class="notification notification-success">%s</div>', $message );
            }
        }
        
        $output .= $this->_user_forms[$atts['id']]->get_nav( $post_id );
        $output .= $this->_user_forms[$atts['id']]->get_form( $post_id );

        return $output;
    }
    
    public function register_form( UserForm $form ) : void {
        $this->_user_forms[$form->get_id()] = $form;
    }

    public function has_form( string $id ) : bool {
        return isset( $this->_user_forms[$id] );
    }

    public function get_form( string $id ) : FormBase {
        return isset( $this->_user_forms[$id] ) ? $this->_user_forms[$id] : null;
    }

    static function getInstance() : UserFormManager {
        if( ! self::$_instance ) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
}