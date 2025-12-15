<?php

// Place helper functions in the global namespace so theme overrides can call them directly.
namespace {
	use Impeka\Applications\ApplicationTemplateHelpers;

	if ( ! function_exists( 'ae_get_session_meta' ) ) {
		function ae_get_session_meta( \WP_Term $session, string $field ) : string {
			return ApplicationTemplateHelpers::get_session_meta( $session, $field );
		}
	}

	if ( ! function_exists( 'ae_session_start_ts' ) ) {
		function ae_session_start_ts( \WP_Term $session ) : ?int {
			return ApplicationTemplateHelpers::session_start_ts( $session );
		}
	}

	if ( ! function_exists( 'ae_session_end_ts' ) ) {
		function ae_session_end_ts( \WP_Term $session ) : ?int {
			return ApplicationTemplateHelpers::session_end_ts( $session );
		}
	}

	if ( ! function_exists( 'ae_is_session_active' ) ) {
		function ae_is_session_active( \WP_Term $session, int $now ) : bool {
			return ApplicationTemplateHelpers::is_session_active( $session, $now );
		}
	}

	if ( ! function_exists( 'ae_is_session_visible' ) ) {
		function ae_is_session_visible( \WP_Term $session, ?int $now = null ) : bool {
			return ApplicationTemplateHelpers::is_session_visible( $session, $now );
		}
	}

	if ( ! function_exists( 'ae_get_sessions_for_type' ) ) {
		function ae_get_sessions_for_type( \WP_Term $type, int $now ) : array {
			return ApplicationTemplateHelpers::get_sessions_for_type( $type, $now );
		}
	}

	if ( ! function_exists( 'ae_format_session_range' ) ) {
		function ae_format_session_range( \WP_Term $session ) : string {
			return ApplicationTemplateHelpers::format_session_range( $session );
		}
	}

	if ( ! function_exists( 'ae_get_user_applications_for_session' ) ) {
		function ae_get_user_applications_for_session( int $user_id, int $type_term_id, int $session_term_id ) : array {
			return ApplicationTemplateHelpers::get_user_applications_for_session( $user_id, $type_term_id, $session_term_id );
		}
	}

	if ( ! function_exists( 'ae_application_created_display' ) ) {
		function ae_application_created_display( int|\WP_Post $application ) : string {
			return ApplicationTemplateHelpers::application_created_display( $application );
		}
	}

	if ( ! function_exists( 'ae_render_application_archive_sections' ) ) {
		function ae_render_application_archive_sections() : void {
			ApplicationTemplateHelpers::render_application_archive_sections();
		}
	}

	if ( ! function_exists( 'ae_get_plugin_field_group_exclusions' ) ) {
		function ae_get_plugin_field_group_exclusions() : array {
			$keys = [
				'group_application_type_form_builder',
				'group_application_session_settings',
				'group_evaluation_form_builder',
				'group_evaluation_category_settings',
				'group_672a73e204b12',
			];

			return apply_filters( 'applications_and_evaluations/excluded_field_group_keys', $keys );
		}
	}

	if ( ! function_exists( 'ae_get_settings' ) ) {
		function ae_get_settings() : array {
			$defaults = [
				'sender_name'  => get_bloginfo( 'name' ),
				'sender_email' => get_bloginfo( 'admin_email' ),
				'disable_cc'   => 0,
			];

			$settings = get_option( 'ae_settings', [] );
			$settings = is_array( $settings ) ? array_merge( $defaults, $settings ) : $defaults;

			return $settings;
		}
	}

	if ( ! function_exists( 'ae_get_sender_name' ) ) {
		function ae_get_sender_name() : string {
			$settings = ae_get_settings();
			return $settings['sender_name'] ?? get_bloginfo( 'name' );
		}
	}

	if ( ! function_exists( 'ae_get_sender_email' ) ) {
		function ae_get_sender_email() : string {
			$settings = ae_get_settings();
			return $settings['sender_email'] ?? get_bloginfo( 'admin_email' );
		}
	}

	if ( ! function_exists( 'ae_cc_admin_emails_enabled' ) ) {
		function ae_cc_admin_emails_enabled() : bool {
			$settings = ae_get_settings();
			return empty( $settings['disable_cc'] );
		}
	}
}
