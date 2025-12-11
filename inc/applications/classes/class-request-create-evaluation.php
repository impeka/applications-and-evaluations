<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RequestCreateEvaluation {
	private static ?RequestCreateEvaluation $instance = null;

	private function __construct() {
		add_action( 'admin_post_create_evaluation', [ $this, 'create_evaluation' ] );
	}

	public static function get_instance() : self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function create_evaluation() : void {
		check_admin_referer( 'ae_new_evaluation', 'ae_new_evaluation_nonce' );

		$application_id = isset( $_POST['application_id'] ) ? (int) $_POST['application_id'] : 0;
		$category_id    = isset( $_POST['evaluation_category'] ) ? (int) $_POST['evaluation_category'] : 0;

		if ( ! $application_id || ! $category_id ) {
			$this->redirect_back();
		}

		$application = new Application( $application_id );
		$session_terms = wp_get_post_terms( $application_id, 'application_session' );
		$session       = is_wp_error( $session_terms ) || empty( $session_terms ) ? null : $session_terms[0];

		if ( ! $session instanceof \WP_Term ) {
			$this->redirect_back();
		}

		// Verify the evaluation session is linked to the application session.
		$linked_session = get_field( 'evaluation_category_session', sprintf( 'evaluation_category_%d', $category_id ) );
		if ( (int) $linked_session !== (int) $session->term_id ) {
			$this->redirect_back();
		}

		// Check access.
		$user_id = get_current_user_id();
		$category_term = get_term( $category_id, 'evaluation_category' );
		if ( ! $category_term instanceof \WP_Term || ! EvaluationTemplateHelpers::user_can_evaluate_session( $category_term, $user_id ) ) {
			$this->redirect_back();
		}

		// Ensure no existing evaluation for this user/application/category.
		$existing = EvaluationTemplateHelpers::get_user_evaluation_for_application( $user_id, $application_id );
		if ( $existing instanceof \WP_Post ) {
			wp_safe_redirect( get_permalink( $existing ) );
			exit;
		}

		$eval_title = sprintf( '%s - %s', get_the_title( $application_id ), __( 'Evaluation', 'applications-and-evaluations' ) );
		$post_id    = wp_insert_post(
			[
				'post_type'   => 'evaluation',
				'post_status' => 'publish',
				'post_title'  => $eval_title,
				'post_author' => $user_id,
			],
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			$this->redirect_back();
		}

		wp_set_object_terms( $post_id, [ $category_id ], 'evaluation_category', false );

		$evaluation = new Evaluation( $post_id );
		$evaluation->set_application_id( $application_id );
		$evaluation->set_status( 'progress' );

		wp_safe_redirect( get_permalink( $post_id ) );
		exit;
	}

	protected function redirect_back() : void {
		$redirect = wp_get_referer() ?: home_url( '/' );
		wp_safe_redirect( $redirect );
		exit;
	}
}
