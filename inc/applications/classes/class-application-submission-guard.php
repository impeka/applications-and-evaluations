<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prevent submitted applications from being modified via the frontend form.
 */
class ApplicationSubmissionGuard {
	public function __construct() {
		add_filter( 'impeka/forms/form_is_allowed', [ $this, 'block_submitted_applications' ], 10, 3 );
	}

	public function block_submitted_applications( bool $allowed, string $form_id, $post_id ) : bool {
		if ( ! $allowed ) {
			return false;
		}

		$post_id = $this->normalize_post_id( $post_id );

		if ( ! $post_id ) {
			return $allowed;
		}

		if ( get_post_type( $post_id ) !== 'application' ) {
			return $allowed;
		}

		$status = get_post_meta( $post_id, '_application_status', true );

		if ( $status === 'submit' ) {
			return false;
		}

		return $allowed;
	}

	private function normalize_post_id( $post_id ) : int {
		if ( is_numeric( $post_id ) ) {
			return (int) $post_id;
		}

		if ( is_string( $post_id ) && preg_match( '/(\\d+)/', $post_id, $match ) ) {
			return (int) $match[1];
		}

		return 0;
	}
}
