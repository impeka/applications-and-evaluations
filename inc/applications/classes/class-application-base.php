<?php

namespace Impeka\Applications;

use Impeka\Tools\Forms\PostForm;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class ApplicationBase implements ApplicationInterface {

    abstract public function __construct( int $post_id );

    public function is_completed() : bool {
        $completed_pages = get_post_meta( $this->_post->ID, '_completed_pages', true );
        $completed_pages = is_array( $completed_pages ) ? $completed_pages : [];

        $form_pages = array_keys( $this->_form->get_pages() );

        return array_equal( $completed_pages, $form_pages );
    }

    public function get_progress_percentage() : float {
        $completed_pages = get_post_meta( $this->_post->ID, '_completed_pages', true );
        $completed_pages = is_array( $completed_pages ) ? $completed_pages : [];

        if ( ! $this->_form instanceof PostForm ) {
            return 0;
        }

        $form_pages_n      = count( $this->_form->get_pages() );
        $completed_pages_n = count( $completed_pages );

        if ( ! $form_pages_n || ! $completed_pages_n ) {
            return 0;
        }

        return round( ( $completed_pages_n / $form_pages_n ) * 100 );
    }

    public function get_form() : PostForm {
        return $this->_form;
    }

    public function is_page_completed( int $page ) : bool {
        $completed_pages = get_post_meta( $this->_post->ID, '_completed_pages', true );
        $completed_pages = is_array( $completed_pages ) ? $completed_pages : [];

        return in_array( $page, $completed_pages );
    }

    public function get_first_incomplete_page() : ?int {
        $completed_pages = get_post_meta( $this->_post->ID, '_completed_pages', true );
        $completed_pages = is_array( $completed_pages ) ? $completed_pages : [];
        
        $form_pages = array_keys( $this->_form->get_pages() );
        sort( $form_pages );

        foreach( $form_pages as $page ) {
            if( ! in_array( $page, $completed_pages ) ) {
                return intval( $page );
            }
        }

        return null;
    }

    public function get_status() : string {
        $status = get_post_meta( $this->_post->ID, '_application_status', true );
        return $status;
    }

    public function set_status( string $status ) : void {
        update_post_meta( $this->_post->ID, '_application_status', $status );
    }

    public function get_post() : \WP_Post {
        return $this->_post;
    }

    public function get_author_name() : string {
        
        $fname = get_the_author_meta( 'first_name', $this->_post->post_author );
        $lname = get_the_author_meta( 'last_name', $this->_post->post_author );

        $name_arr = array_filter( [$lname, $fname] );

        $fullname = implode( ', ', $name_arr );

        if( empty( $fullname ) ) {
            $fullname = get_the_author_meta( 'user_email', $this->_post->post_author );
        }

        return $fullname;
    }

    public function is_unlocked() : bool {
        $force_unlocked = get_field( 'force_unlock', $this->_post->ID );

        return $force_unlocked == true;
    }

    protected function _append_query_character( $url ) : string {
        if( strpos( $url, '?' ) ) {
            return '&';
        }
        
        return '?';
    }
}
