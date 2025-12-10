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
            return;
        }

        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_safe_redirect( get_post_type_archive_link( 'application' ) ?: home_url( '/' ) );
        exit;
    }
}
