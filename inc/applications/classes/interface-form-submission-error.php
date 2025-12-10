<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface FormSubmissionError {
	/**
	 * Return an array of error messages keyed by error type.
	 *
	 * @return array<string,string>
	 */
	public static function messages() : array;
}
