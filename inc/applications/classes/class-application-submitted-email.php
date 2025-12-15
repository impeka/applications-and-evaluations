<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ApplicationSubmittedEmail extends EmailBase {
	public function send( Application $application ) : void {
		$post = $application->get_post();
		$user = get_userdata( $post->post_author );

		if ( ! $user || empty( $user->user_email ) ) {
			return;
		}

		$session_terms = wp_get_post_terms( $post->ID, 'application_session' );
		$session       = is_wp_error( $session_terms ) || empty( $session_terms ) ? null : $session_terms[0];

		$subject = __( 'Application Submitted', 'applications-and-evaluations' );
		$message = sprintf( __( 'Your application "%s" has been submitted.', 'applications-and-evaluations' ), get_the_title( $post ) );

		if ( $session instanceof \WP_Term ) {
			$term_key = sprintf( 'application_session_%d', $session->term_id );
			$subject  = get_field( 'application_session_email_subject', $term_key ) ?: $subject;
			$message  = get_field( 'application_session_email_message', $term_key ) ?: $message;
		}

		$this->mail( $user->user_email, $subject, $message );
	}
}
