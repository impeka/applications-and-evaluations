<?php

namespace Impeka\Applications;

use Impeka\Tools\Forms\PostForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Evaluation extends ApplicationBase {
	protected \WP_Post $_post;
	protected ?\WP_Term $_category;
	protected ?PostForm $_form = null;

	public function __construct( int $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			throw new \RuntimeException( sprintf( 'Invalid evaluation post ID %s.', $post_id ) );
		}

		$this->_post     = $post;
		$this->_category = $this->get_category_term();

		if ( $this->_category instanceof \WP_Term ) {
			$this->_form = EvaluationFormBuilder::get_instance()->build_form_for_term( $this->_category );
		}
	}

	protected function get_category_term() : ?\WP_Term {
		$terms = wp_get_post_terms( $this->_post->ID, 'evaluation_category' );
		return is_wp_error( $terms ) || empty( $terms ) ? null : $terms[0];
	}

	public function get_application_id() : int {
		return (int) get_post_meta( $this->_post->ID, '_evaluation_application_id', true );
	}

	public function set_application_id( int $application_id ) : void {
		update_post_meta( $this->_post->ID, '_evaluation_application_id', $application_id );
	}

	protected function get_completed_pages() : array {
		$completed_pages = get_post_meta( $this->_post->ID, '_evaluation_completed_pages', true );
		return is_array( $completed_pages ) ? $completed_pages : [];
	}

	public function is_completed() : bool {
		$completed_pages = $this->get_completed_pages();
		$form_pages      = $this->_form instanceof PostForm ? array_keys( $this->_form->get_pages() ) : [];

		return array_equal( $completed_pages, $form_pages );
	}

	public function get_progress_percentage() : float {
		$completed_pages = $this->get_completed_pages();

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
		return in_array( $page, $this->get_completed_pages(), true );
	}

	public function get_first_incomplete_page() : ?int {
		$completed_pages = $this->get_completed_pages();

		if ( ! $this->_form instanceof PostForm ) {
			return null;
		}

		$form_pages = array_keys( $this->_form->get_pages() );
		sort( $form_pages );

		foreach ( $form_pages as $page ) {
			if ( ! in_array( $page, $completed_pages, true ) ) {
				return (int) $page;
			}
		}

		return null;
	}

	public function get_status() : string {
		$status = get_post_meta( $this->_post->ID, '_evaluation_status', true );
		return $status;
	}

	public function set_status( string $status ) : void {
		update_post_meta( $this->_post->ID, '_evaluation_status', $status );
	}

	public function get_post() : \WP_Post {
		return $this->_post;
	}

	public function get_author_name() : string {
		$fname = get_the_author_meta( 'first_name', $this->_post->post_author );
		$lname = get_the_author_meta( 'last_name', $this->_post->post_author );

		$name_arr = array_filter( [ $lname, $fname ] );
		$fullname = implode( ', ', $name_arr );

		if ( empty( $fullname ) ) {
			$fullname = get_the_author_meta( 'user_email', $this->_post->post_author );
		}

		return $fullname;
	}
}
