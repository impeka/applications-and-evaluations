<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EvaluationTemplateHelpers {
	/**
	 * Find evaluation sessions (taxonomy: evaluation_category) linked to an application session.
	 */
	public static function get_evaluation_sessions_for_application_session( \WP_Term $application_session ) : array {
		$terms = get_terms(
			[
				'taxonomy'   => 'evaluation_category',
				'hide_empty' => false,
				'meta_query' => [
					[
						'key'   => 'evaluation_category_session',
						'value' => (string) $application_session->term_id,
					],
				],
			]
		);

		return is_wp_error( $terms ) ? [] : $terms;
	}

	/**
	 * Check if the current user can evaluate in a given evaluation session (category).
	 */
	public static function user_can_evaluate_session( \WP_Term $evaluation_session, int $user_id ) : bool {
		$mode  = get_field( 'evaluation_category_access_mode', $evaluation_session );
		$mode  = $mode ?: 'roles';
		$user  = get_userdata( $user_id );

		if ( ! $user instanceof \WP_User ) {
			return false;
		}

		if ( $mode === 'users' ) {
			$allowed_users = get_field( 'evaluation_category_users', $evaluation_session );
			$allowed_users = is_array( $allowed_users ) ? array_map( 'intval', $allowed_users ) : [];
			return in_array( $user_id, $allowed_users, true );
		}

		$allowed_roles = get_field( 'evaluation_category_roles', $evaluation_session );
		$allowed_roles = is_array( $allowed_roles ) ? $allowed_roles : [];

		if ( empty( $allowed_roles ) ) {
			return false;
		}

		return (bool) array_intersect( $allowed_roles, $user->roles );
	}

	/**
	 * Get submitted applications for a type+session.
	 */
	public static function get_submitted_applications( int $type_id, int $session_id ) : array {
		$query = new \WP_Query(
			[
				'post_type'      => 'application',
				'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'tax_query'      => [
					'relation' => 'AND',
					[
						'taxonomy' => 'application_type',
						'field'    => 'term_id',
						'terms'    => $type_id,
					],
					[
						'taxonomy' => 'application_session',
						'field'    => 'term_id',
						'terms'    => $session_id,
					],
				],
				'meta_query'     => [
					[
						'key'     => '_application_status',
						'value'   => 'submit',
						'compare' => '=',
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

	/**
	 * Return the user's evaluation (if any) for an application (any evaluation session).
	 */
	public static function get_user_evaluation_for_application( int $user_id, int $application_id ) : ?\WP_Post {
		$query = new \WP_Query(
			[
				'post_type'      => 'evaluation',
				'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
				'author'         => $user_id,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_query'     => [
					[
						'key'     => '_evaluation_application_id',
						'value'   => $application_id,
						'compare' => '=',
					],
				],
			]
		);

		$post = $query->have_posts() ? $query->posts[0] : null;
		wp_reset_postdata();

		return $post;
	}
}
