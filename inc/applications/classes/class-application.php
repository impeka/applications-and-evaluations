<?php

namespace Impeka\Applications;

use Impeka\Tools\Forms\PostForm;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Application extends ApplicationBase {
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
}
