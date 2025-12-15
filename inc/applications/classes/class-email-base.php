<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class EmailBase {
	protected string $subject = '';
	protected string $message = '';
	protected ?string $to = null;

	protected function mail( string $to, string $subject, string $message, bool $cc_admin = true ) : void {
		if ( empty( $to ) ) {
			return;
		}

		$from_name  = function_exists( '\ae_get_sender_name' ) ? \ae_get_sender_name() : get_bloginfo( 'name' );
		$from_email = function_exists( '\ae_get_sender_email' ) ? \ae_get_sender_email() : get_bloginfo( 'admin_email' );

		$headers   = [];
		$headers[] = sprintf( 'From: %s <%s>', $from_name, $from_email );
		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		if ( $cc_admin && function_exists( '\ae_cc_admin_emails_enabled' ) && \ae_cc_admin_emails_enabled() ) {
			$admin_email = get_option( 'admin_email' );
			if ( $admin_email ) {
				$headers[] = sprintf( 'Cc: %s', $admin_email );
			}
		}

		// Basic formatting.
		$body = wpautop( wp_kses_post( $message ) );

		wp_mail( $to, $subject, $body, $headers );
	}
}
