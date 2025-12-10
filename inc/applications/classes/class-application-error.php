<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight error mapper for application workflows.
 */
class ApplicationError extends FormSubmissionErrorBase {

	public function __construct( string $type ) {
		$this->type = $type;
	}

	/**
	 * Message map with translatable strings.
	 */
	public static function messages() : array {
		return [
			'invalid_application_type'    => __( 'Invalid application type.', 'applications-and-evaluations' ),
			'invalid_application_session' => __( 'Invalid application session.', 'applications-and-evaluations' ),
			'inactive_session'            => __( 'This session is not currently accepting applications.', 'applications-and-evaluations' ),
			'application_limit_reached'   => __( 'You have reached the application limit for this session.', 'applications-and-evaluations' ),
			'post_error'                  => __( 'Unable to create the application. Please try again.', 'applications-and-evaluations' ),
		];
	}
}
