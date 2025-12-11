<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ApplicationTemplateHelpers {
	public static function get_session_meta( \WP_Term $session, string $field ) : string {
		return (string) get_field( $field, sprintf( 'application_session_%d', $session->term_id ) );
	}

	public static function session_start_ts( \WP_Term $session ) : ?int {
		$start = self::get_session_meta( $session, 'application_session_start' );
		$ts    = $start ? strtotime( $start ) : false;
		return $ts ? $ts : null;
	}

	public static function session_end_ts( \WP_Term $session ) : ?int {
		$end = self::get_session_meta( $session, 'application_session_end' );
		$ts  = $end ? strtotime( $end ) : false;
		return $ts ? $ts : null;
	}

	public static function is_session_active( \WP_Term $session, ?int $now = null ) : bool {
		$now      = $now ?? current_time( 'timestamp' );
		$start_ts = self::session_start_ts( $session );
		$end_ts   = self::session_end_ts( $session );

		if ( $start_ts && $start_ts > $now ) {
			return false;
		}

		if ( $end_ts && $end_ts < $now ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns true if the session is either active or explicitly visible when out of session.
	 */
	public static function is_session_visible( \WP_Term $session, ?int $now = null ) : bool {
		$always_show = (bool) get_field( 'application_session_visibility_out_of_session', sprintf( 'application_session_%d', $session->term_id ) );

		if ( $always_show ) {
			return true;
		}

		return self::is_session_active( $session, $now );
	}

	public static function get_sessions_for_type( \WP_Term $type, ?int $now = null ) : array {
		$now      = $now ?? current_time( 'timestamp' );
		$sessions = get_terms(
			[
				'taxonomy'   => 'application_session',
				'hide_empty' => false,
				'meta_query' => [
					[
						'key'   => 'application_session_application_type',
						'value' => (string) $type->term_id,
					],
				],
				'orderby'    => 'meta_value',
				'meta_key'   => 'application_session_start',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $sessions ) ) {
			return [];
		}

		$active = array_values(
			array_filter(
				$sessions,
				static function ( $session ) use ( $now ) {
					return self::is_session_visible( $session, $now );
				}
			)
		);

		usort(
			$active,
			static function ( $a, $b ) {
				$a_start = self::session_start_ts( $a ) ?? 0;
				$b_start = self::session_start_ts( $b ) ?? 0;
				return $a_start <=> $b_start;
			}
		);

		return $active;
	}

	public static function get_user_applications_for_session( int $user_id, int $type_term_id, int $session_term_id ) : array {
		if ( ! $user_id ) {
			return [];
		}

		$query = new \WP_Query(
			[
				'post_type'      => 'application',
				'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
				'author'         => $user_id,
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'tax_query'      => [
					'relation' => 'AND',
					[
						'taxonomy' => 'application_type',
						'field'    => 'term_id',
						'terms'    => $type_term_id,
					],
					[
						'taxonomy' => 'application_session',
						'field'    => 'term_id',
						'terms'    => $session_term_id,
					],
				],
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$posts = $query->have_posts() ? $query->posts : [];
		wp_reset_postdata();

		return $posts;
	}

	public static function format_session_range( \WP_Term $session, ?string $format = null ) : string {
		$format   = $format ?: get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$start_ts = self::session_start_ts( $session );
		$end_ts   = self::session_end_ts( $session );

		if ( ! $start_ts && ! $end_ts ) {
			return '';
		}

		if ( $start_ts && $end_ts ) {
			return sprintf( '%s - %s', wp_date( $format, $start_ts ), wp_date( $format, $end_ts ) );
		}

		if ( $start_ts ) {
			return sprintf( '%s %s', __( 'Opens', 'applications-and-evaluations' ), wp_date( $format, $start_ts ) );
		}

		return sprintf( '%s %s', __( 'Closes', 'applications-and-evaluations' ), wp_date( $format, $end_ts ) );
	}

	public static function application_created_display( int|\WP_Post $application, ?string $format = null ) : string {
		$format     = $format ?: get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$created_ts = get_post_time( 'U', false, $application );
		return $created_ts ? wp_date( $format, $created_ts ) : '';
	}

	public static function get_datetime_display_format() : string {
		return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	public static function render_application_archive_sections() : void {
		$current_user_id = get_current_user_id();
		$now             = current_time( 'timestamp' );
		$types           = get_terms(
			[
				'taxonomy'   => 'application_type',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $types ) ) {
			$types = [];
		}

		$template = self::locate_template_part( 'application-archive-loop.php' );

		if ( ! $template ) {
			return;
		}

		include $template;
	}

	protected static function locate_template_part( string $file ) : ?string {
		$theme_path = trailingslashit( get_stylesheet_directory() ) . 'applications-and-evaluations/' . $file;
		if ( file_exists( $theme_path ) ) {
			return $theme_path;
		}

		$plugin_path = IMPEKA_AE_PLUGIN_DIR . 'templates/parts/' . $file;
		if ( file_exists( $plugin_path ) ) {
			return $plugin_path;
		}

		return null;
	}
}
