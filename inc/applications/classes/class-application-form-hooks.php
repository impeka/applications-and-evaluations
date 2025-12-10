<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots ACF form handling on frontend application pages.
 */
class ApplicationFormHooks {
	public function __construct() {
		add_action( 'template_redirect', [ $this, 'maybe_bootstrap_acf' ], 0 );
	}

	public function maybe_bootstrap_acf() : void {
		if ( ! function_exists( 'acf_form_head' ) ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return;
		}

		if ( ! is_singular( 'application' ) ) {
			return;
		}

		// Prevent double output if run elsewhere.
		if ( did_action( 'acf/input/admin_enqueue_scripts' ) || did_action( 'acf_form_head' ) ) {
			return;
		}

		acf_form_head();
	}
}
