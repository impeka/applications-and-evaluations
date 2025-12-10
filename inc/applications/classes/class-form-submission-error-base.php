<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for form submission errors.
 */
abstract class FormSubmissionErrorBase implements FormSubmissionError {
	protected string $type;

	public function __construct( string $type ) {
		$this->type = $type;
	}

	public function get_type() : string {
		return $this->type;
	}

	public function get_message() : string {
		$messages = static::messages();
		$message  = $messages[ $this->type ] ?? __( 'An error occurred.', 'applications-and-evaluations' );

		/**
		 * Filter the application error message.
		 *
		 * @param string                   $message Mapped message.
		 * @param string                   $type    Error type key.
		 * @param FormSubmissionErrorBase  $error   Error instance.
		 */
		$message = apply_filters( 'ae/application_error/message', $message, $this->type, $this );

		return $message;
	}

	public static function message_for( string $type ) : string {
		$error = new static( $type );
		return $error->get_message();
	}
}
