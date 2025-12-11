<?php

add_action('acf/init', function() {
    require_once( __DIR__ . '/classes/class-acf_field_score.php' );
    require_once( __DIR__ . '/classes/class-acf_field_score_subtotal.php' );
    require_once( __DIR__ . '/classes/class-acf_field_score_total.php' );

    // initialize
    acf_register_field_type( 'acf_field_score' );
    acf_register_field_type( 'acf_field_score_subtotal' );
    acf_register_field_type( 'acf_field_score_total' );
} );

/**
 * Enqueue shared JS that keeps score subtotals / totals in sync.
 */
function impeka_ae_enqueue_score_assets() {
    $asset_path = IMPEKA_AE_PLUGIN_DIR . 'assets/js/acf-score-fields.js';
    $asset_url  = IMPEKA_AE_PLUGIN_URL . 'assets/js/acf-score-fields.js';
    $version    = file_exists( $asset_path ) ? filemtime( $asset_path ) : '0.1.0';

    wp_register_script(
        'impeka-ae-acf-score-fields',
        $asset_url,
        array( 'acf-input' ),
        $version,
        true
    );

	$post_id = impeka_ae_score_get_post_id();

	if ( $post_id ) {
		$score_data = impeka_ae_collect_score_values( $post_id );

		wp_localize_script(
			'impeka-ae-acf-score-fields',
			'impekaAeScoreData',
			$score_data
		);
	}

    wp_enqueue_script( 'impeka-ae-acf-score-fields' );
}

add_action( 'acf/input/admin_enqueue_scripts', 'impeka_ae_enqueue_score_assets' );
add_action( 'acf/enqueue_scripts', 'impeka_ae_enqueue_score_assets' );

/**
 * Best-effort retrieval of the post ID currently being edited/viewed via ACF.
 *
 * @return int|string|null
 */
function impeka_ae_score_get_post_id() {
	$post_id = null;

	if ( function_exists( 'acf_get_form_data' ) ) {
		$form_post_id = acf_get_form_data( 'post_id' );

		if ( ! empty( $form_post_id ) ) {
			$post_id = $form_post_id;
		}
	}

	if ( ! $post_id && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only context
		$post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
	}

	if ( ! $post_id ) {
		global $post;

		if ( $post instanceof \WP_Post ) {
			$post_id = $post->ID;
		}
	}

	return $post_id;
}

/**
 * Collect stored score values (by field) for a given post.
 *
 * @param int|string $post_id
 * @return array{fields: array<string, array{group: string, value: float}>, groups: array<string, float>, all: float}
 */
function impeka_ae_collect_score_values( $post_id ) {
	$result = [
		'fields' => [],
		'groups' => [],
		'all'    => 0,
	];

	$field_objects = get_field_objects( $post_id );

	if ( empty( $field_objects ) || ! is_array( $field_objects ) ) {
		return $result;
	}

	impeka_ae_score_accumulate_values( $field_objects, $result );

	return $result;
}

/**
 * Recursively walk field objects (with values) and accumulate score totals.
 *
 * @param array $fields
 * @param array $result
 * @return void
 */
function impeka_ae_score_accumulate_values( array $fields, array &$result ) : void {
	foreach ( $fields as $field ) {
		if ( ! is_array( $field ) || empty( $field['type'] ) ) {
			continue;
		}

		$type  = $field['type'];
		$value = $field['value'] ?? null;

		// Direct score field.
		if ( $type === 'score' ) {
			$numeric_value = is_numeric( $value ) ? (float) $value : 0;
			$group         = $field['data-score-group'] ?? '';
			$key           = $field['key'] ?? uniqid( 'score_', true );

			$result['fields'][ $key ] = [
				'group' => $group,
				'value' => $numeric_value,
			];

			$result['all'] += $numeric_value;

			if ( $group !== '' ) {
				if ( ! isset( $result['groups'][ $group ] ) ) {
					$result['groups'][ $group ] = 0;
				}

				$result['groups'][ $group ] += $numeric_value;
			}

			continue;
		}

		// Group field.
		if ( $type === 'group' && is_array( $value ) && isset( $field['sub_fields'] ) ) {
			foreach ( $field['sub_fields'] as $sub_field ) {
				if ( ! is_array( $sub_field ) ) {
					continue;
				}

				$sub_field['value'] = $value[ $sub_field['name'] ] ?? null;
				impeka_ae_score_accumulate_values( [ $sub_field ], $result );
			}

			continue;
		}

		// Repeater field.
		if ( $type === 'repeater' && is_array( $value ) && isset( $field['sub_fields'] ) ) {
			foreach ( $value as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				foreach ( $field['sub_fields'] as $sub_field ) {
					if ( ! is_array( $sub_field ) ) {
						continue;
					}

					$sub_field['value'] = $row[ $sub_field['name'] ] ?? null;
					impeka_ae_score_accumulate_values( [ $sub_field ], $result );
				}
			}

			continue;
		}

		// Flexible content.
		if ( $type === 'flexible_content' && is_array( $value ) && isset( $field['layouts'] ) ) {
			$layouts = $field['layouts'];
			foreach ( $value as $row ) {
				if ( ! is_array( $row ) || empty( $row['acf_fc_layout'] ) ) {
					continue;
				}

				$layout_name = $row['acf_fc_layout'];
				$layout      = null;

				foreach ( $layouts as $layout_candidate ) {
					if ( isset( $layout_candidate['name'] ) && $layout_candidate['name'] === $layout_name ) {
						$layout = $layout_candidate;
						break;
					}
				}

				if ( ! $layout || empty( $layout['sub_fields'] ) ) {
					continue;
				}

				foreach ( $layout['sub_fields'] as $sub_field ) {
					if ( ! is_array( $sub_field ) ) {
						continue;
					}

					$sub_field['value'] = $row[ $sub_field['name'] ] ?? null;
					impeka_ae_score_accumulate_values( [ $sub_field ], $result );
				}
			}

			continue;
		}

		// Generic fallback: if sub_fields exist with array value, try to walk.
		if ( isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) && is_array( $value ) ) {
			foreach ( $field['sub_fields'] as $sub_field ) {
				if ( ! is_array( $sub_field ) ) {
					continue;
				}

				$sub_field['value'] = $value[ $sub_field['name'] ] ?? null;
				impeka_ae_score_accumulate_values( [ $sub_field ], $result );
			}
		}
	}
}

/**
 * Collect score max metadata (per instance, per group, overall).
 *
 * @param int|string $post_id
 * @return array{instances: array<string, array{group: string, max: float}>, groups: array<string, float>, all: float}
 */
function impeka_ae_collect_score_maxes( $post_id ) : array {
	$result = [
		'instances' => [],
		'groups'    => [],
		'all'       => 0,
	];

	$field_objects = get_field_objects( $post_id );

	if ( empty( $field_objects ) || ! is_array( $field_objects ) ) {
		return $result;
	}

	impeka_ae_score_accumulate_maxes( $field_objects, $result );

	return $result;
}

/**
 * Recursively walk field objects and accumulate score max totals.
 *
 * @param array  $fields
 * @param array  $result
 * @param string $path
 * @return void
 */
function impeka_ae_score_accumulate_maxes( array $fields, array &$result, string $path = '' ) : void {
	foreach ( $fields as $field ) {
		if ( ! is_array( $field ) || empty( $field['type'] ) ) {
			continue;
		}

		$type  = $field['type'];
		$value = $field['value'] ?? null;
		$name  = $field['name'] ?? '';
		$key   = $field['key'] ?? $name;

		$current_path = $path !== '' ? "{$path}.{$name}" : $name;

		if ( $type === 'score' ) {
			$max_value  = isset( $field['max'] ) && is_numeric( $field['max'] ) ? (float) $field['max'] : 0;
			$group      = $field['data-score-group'] ?? '';
			$instance   = "{$key}|{$current_path}";

			$result['instances'][ $instance ] = [
				'group' => $group,
				'max'   => $max_value,
			];

			$result['all'] += $max_value;

			if ( $group !== '' ) {
				if ( ! isset( $result['groups'][ $group ] ) ) {
					$result['groups'][ $group ] = 0;
				}

				$result['groups'][ $group ] += $max_value;
			}

			continue;
		}

		if ( $type === 'group' && is_array( $value ) && isset( $field['sub_fields'] ) ) {
			foreach ( $field['sub_fields'] as $sub_field ) {
				if ( ! is_array( $sub_field ) ) {
					continue;
				}

				$sub_field['value'] = $value[ $sub_field['name'] ] ?? null;
				impeka_ae_score_accumulate_maxes( [ $sub_field ], $result, $current_path );
			}

			continue;
		}

		if ( $type === 'repeater' && is_array( $value ) && isset( $field['sub_fields'] ) ) {
			foreach ( $value as $index => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$row_path = "{$current_path}[{$index}]";

				foreach ( $field['sub_fields'] as $sub_field ) {
					if ( ! is_array( $sub_field ) ) {
						continue;
					}

					$sub_field['value'] = $row[ $sub_field['name'] ] ?? null;
					impeka_ae_score_accumulate_maxes( [ $sub_field ], $result, $row_path );
				}
			}

			continue;
		}

		if ( $type === 'flexible_content' && is_array( $value ) && isset( $field['layouts'] ) ) {
			$layouts = $field['layouts'];
			foreach ( $value as $index => $row ) {
				if ( ! is_array( $row ) || empty( $row['acf_fc_layout'] ) ) {
					continue;
				}

				$layout_name = $row['acf_fc_layout'];
				$layout      = null;

				foreach ( $layouts as $layout_candidate ) {
					if ( isset( $layout_candidate['name'] ) && $layout_candidate['name'] === $layout_name ) {
						$layout = $layout_candidate;
						break;
					}
				}

				if ( ! $layout || empty( $layout['sub_fields'] ) ) {
					continue;
				}

				$layout_path = "{$current_path}[{$index}]{$layout_name}";

				foreach ( $layout['sub_fields'] as $sub_field ) {
					if ( ! is_array( $sub_field ) ) {
						continue;
					}

					$sub_field['value'] = $row[ $sub_field['name'] ] ?? null;
					impeka_ae_score_accumulate_maxes( [ $sub_field ], $result, $layout_path );
				}
			}

			continue;
		}

		if ( isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) && is_array( $value ) ) {
			foreach ( $field['sub_fields'] as $sub_field ) {
				if ( ! is_array( $sub_field ) ) {
					continue;
				}

				$sub_field['value'] = $value[ $sub_field['name'] ] ?? null;
				impeka_ae_score_accumulate_maxes( [ $sub_field ], $result, $current_path );
			}
		}
	}
}
