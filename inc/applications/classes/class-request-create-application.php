<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RequestCreateApplication {
    private static ?RequestCreateApplication $instance = null;

    private function __construct() {
        add_action( 'admin_post_create_application', [ $this, 'create_application' ] );
        //add_action( 'admin_post_nopriv_create_application', [ $this, 'create_anonymous_application' ] );
    }


    public static function get_instance() : self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function create_application() : void {
        check_admin_referer( 'ae_new_application', 'ae_new_application_nonce' );

        $type_slug = isset( $_POST['application_type'] ) ? sanitize_title( wp_unslash( $_POST['application_type'] ) ) : '';
        $session_slug = isset( $_POST['application_session'] ) ? sanitize_title( wp_unslash( $_POST['application_session'] ) ) : '';

        $type = $type_slug ? get_term_by( 'slug', $type_slug, 'application_type' ) : null;
        $session = $session_slug ? get_term_by( 'slug', $session_slug, 'application_session' ) : null;

        if ( ! $type instanceof \WP_Term || ! $session instanceof \WP_Term ) {
            $this->redirect_with_error( 'invalid_application_type' );
        }

        if ( ! $this->session_matches_type( $session, $type ) ) {
            $this->redirect_with_error( 'invalid_application_session' );
        }

        if ( ! $this->session_is_active( $session ) ) {
            $this->redirect_with_error( 'inactive_session' );
        }

        if ( $this->user_limit_reached( get_current_user_id(), $type->term_id, $session->term_id ) ) {
            $this->redirect_with_error( 'application_limit_reached' );
        }

        $post_id = wp_insert_post(
            [
                'post_type'   => 'application',
                'post_status' => 'publish',
                'post_title'  => sprintf( '%s - %s', $type->name, wp_date( 'Y-m-d H:i', current_time( 'timestamp' ) ) ),
                'post_author' => get_current_user_id(),
            ],
            true
        );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            $this->redirect_with_error( 'post_error' );
        }

        wp_set_object_terms( $post_id, [ $type->term_id ], 'application_type', false );
        wp_set_object_terms( $post_id, [ $session->term_id ], 'application_session', false );

        $application = new Application( $post_id );
        $application->set_status( 'progress' );

        $permalink = get_permalink( $post_id );
        wp_safe_redirect( $permalink ? $permalink : $this->get_archive_link() );
        exit;
    }

    protected function session_matches_type( \WP_Term $session, \WP_Term $type ) : bool {
        $type_id = get_field( 'application_session_application_type', sprintf( 'application_session_%d', $session->term_id ) );
        return (int) $type_id === (int) $type->term_id;
    }

    protected function session_is_active( \WP_Term $session ) : bool {
        $always_visible = (bool) get_field( 'application_session_visibility_out_of_session', sprintf( 'application_session_%d', $session->term_id ) );

        if ( $always_visible ) {
            return true;
        }

        $now   = current_time( 'timestamp' );
        $start = get_field( 'application_session_start', sprintf( 'application_session_%d', $session->term_id ) );
        $end   = get_field( 'application_session_end', sprintf( 'application_session_%d', $session->term_id ) );

        $start_ts = $start ? strtotime( $start ) : null;
        $end_ts   = $end ? strtotime( $end ) : null;

        if ( $start_ts && $start_ts > $now ) {
            return false;
        }

        if ( $end_ts && $end_ts < $now ) {
            return false;
        }

        return true;
    }

    protected function user_limit_reached( int $user_id, int $type_id, int $session_id ) : bool {
        $limit_raw = get_field( 'application_session_submission_limit', sprintf( 'application_session_%d', $session_id ) );
        $limit     = is_numeric( $limit_raw ) ? (int) $limit_raw : 0;

        if ( $limit <= 0 ) {
            return false;
        }

        $query = new \WP_Query(
            [
                'post_type'      => 'application',
                'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
                'author'         => $user_id,
                'posts_per_page' => 1,
                'no_found_rows'  => true,
                'tax_query'      => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'application_type',
                        'field'    => 'term_id',
                        'terms'    => $type_id,
                    ],
                    [
                        'taxonomy' => 'application_session',
                        'field'    => 'term_id',
                        'terms'    => $session_id,
                    ],
                ],
            ]
        );

        return $query->found_posts >= $limit;
    }

    protected function redirect_with_error( string $error_type ) : void {
        $redirect = add_query_arg( 'err', urlencode( $error_type ), $this->get_archive_link() );
        wp_safe_redirect( $redirect );
        exit;
    }

    protected function get_archive_link() : string {
        return get_post_type_archive_link( 'application' ) ?: home_url( '/' );
    }
}
