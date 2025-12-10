<?php

namespace Impeka\Applications;

use Impeka\Tools\Forms\PostForm;
use Impeka\Applications\FormSubmissionError;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Application implements FormSubmissionError {
    protected \WP_Post $_post;
    protected ?\WP_Term $_type;
    protected ?PostForm $_form = null;

    public function __construct( int $post_id ) {
        $post = get_post( $post_id );

        if ( ! $post ) {
            throw new \RuntimeException( sprintf( 'Invalid application post ID %s.', $post_id ) );
        }

        $this->_post = $post;
        $this->_type = $this->get_type_term();

        if ( $this->_type instanceof \WP_Term ) {
            $this->_form = ApplicationTypeFormBuilder::get_instance()->build_form_for_term( $this->_type );
        }
    }

    protected function get_type_term() : ?\WP_Term {
        $terms = wp_get_post_terms( $this->_post->ID, 'application_type' );
        return is_wp_error( $terms ) || empty( $terms ) ? null : $terms[0];
    }

    public function get_form() : ?PostForm {
        return $this->_form;
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

    public function is_page_completed( int $page ) : bool {
        $completed_pages = get_post_meta( $this->_post->ID, '_completed_pages', true );
        $completed_pages = is_array( $completed_pages ) ? $completed_pages : [];

        return in_array( $page, $completed_pages );
    }

    public function get_first_incomplete_page() : ?int {
        if ( ! $this->_form instanceof PostForm ) {
            return null;
        }

        $completed_pages = get_post_meta( $this->_post->ID, '_completed_pages', true );
        $completed_pages = is_array( $completed_pages ) ? $completed_pages : [];

        $form_pages = array_keys( $this->_form->get_pages() );
        sort( $form_pages );

        foreach ( $form_pages as $page ) {
            if ( ! in_array( $page, $completed_pages ) ) {
                return intval( $page );
            }
        }

        return null;
    }

    public function get_status() : string {
        return (string) get_post_meta( $this->_post->ID, '_application_status', true );
    }

    public function set_status( string $status ) : void {
        update_post_meta( $this->_post->ID, '_application_status', $status );
    }

    public function set_submit_date( \DateTimeImmutable $date ) : void {
        update_post_meta( $this->_post->ID, '_application_submit_date', $date->format( 'Y-m-d H:i:s' ) );
    }

    public function get_submit_date( string $format = 'Y-m-d H:i:s' ) : string {
        $raw = get_post_meta( $this->_post->ID, '_application_submit_date', true );

        if ( ! $raw ) {
            return 'N/A';
        }

        $date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $raw );

        if ( ! $date ) {
            return 'N/A';
        }

        return date_i18n( $format, $date->format( 'U' ) );
    }

    /**
     * Stub implementation to satisfy FormSubmissionError; replace with actual messages if needed.
     */
    public static function messages() : array {
        return [];
    }
}
