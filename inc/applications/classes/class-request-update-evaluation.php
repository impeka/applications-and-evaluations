<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Impeka\Tools\Forms\Form;

/**
 * Sync evaluation status + submitted date when its form is saved.
 */
class RequestUpdateEvaluation {
	private static ?RequestUpdateEvaluation $instance = null;

	private function __construct() {
		add_action( 'impeka/forms/page_saved', [ $this, 'on_form_save_status' ], 10, 5 );
	}

	public static function get_instance() : self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function on_form_save_status( Form $form, int|string $post_id, int $current_page, bool $form_status_before, bool $form_status ) : void {
		if ( get_post_type( $post_id ) !== 'evaluation' ) {
			return;
		}

		try {
			$evaluation = new Evaluation( (int) $post_id );
		} catch ( \Throwable $e ) {
			return;
		}

		$evaluation->set_status( $form_status ? 'submit' : 'progress' );

		if ( ! $form_status_before && $form_status ) {
			$evaluation->set_submit_date( new \DateTimeImmutable() );

			try {
				( new EvaluationSubmittedEmail() )->send( $evaluation );
			} catch ( \Throwable $e ) {
				// Ignore email failures to avoid interrupting submission.
			}
		}
	}
}
