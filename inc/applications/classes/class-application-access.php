<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ApplicationAccess {
    public function __construct() {
        add_action( 'template_redirect', [ $this, 'maybe_restrict' ], 9 );
    }

    public function maybe_restrict() : void {
        if ( ! is_singular( 'application' ) ) {
            return;
        }

        $post = get_queried_object();

        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        $target_url = get_permalink( $post ) ?: home_url( '/' );

        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( $target_url ) );
            exit;
        }

        $user_id = get_current_user_id();

        if ( (int) $post->post_author === (int) $user_id ) {
            // If this is the author and the session is closed, send them to the view-only page instead of showing empty forms.
            if ( $post->post_type === 'application' && ! get_query_var( 'view_only' ) ) {
                $sessions = wp_get_post_terms( $post->ID, 'application_session' );
                $session  = is_array( $sessions ) && ! empty( $sessions ) ? $sessions[0] : null;

                if ( $session instanceof \WP_Term && ! ApplicationTemplateHelpers::is_session_active( $session ) ) {
                    $view_url = trailingslashit( get_permalink( $post ) ) . 'view/';
                    wp_safe_redirect( $view_url ?: $target_url );
                    exit;
                }
            }

            return;
        }

        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_safe_redirect( get_post_type_archive_link( 'application' ) ?: home_url( '/' ) );
        exit;
    }
}
