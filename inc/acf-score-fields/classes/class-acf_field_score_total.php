<?php

if ( ! class_exists( 'acf_field_score_total' ) ) :

	class acf_field_score_total extends acf_field_text {

		public $max_scores = [];
		public $totals = [];
		public $max_score_instances = [];

		/**
		 * This function will setup the field type data
		 *
		 * @type    function
		 * @date    5/03/2014
		 * @since   5.0.0
		 *
		 * @param   n/a
		 * @return  n/a
		 */
		function initialize() {
            parent::initialize();

			// vars
			$this->name          = 'score_total';
			$this->label         = __( 'Score total', 'acf' );
			$this->description   = __( 'A basic text input, useful for displaying the total of all the score fields.', 'acf' );

			add_filter( 'acf/pre_render_fields', [ $this, 'calculate_max_score' ], 10, 2 );
			add_filter( 'acf/pre_render_fields', [ $this, 'add_max_score_suffix' ], 10, 2 );
        }

		function calculate_max_score( $fields, $post_id ) {
			$stored_maxes = function_exists( 'impeka_ae_collect_score_maxes' ) ? impeka_ae_collect_score_maxes( $post_id ) : [
				'all'       => 0,
				'groups'    => [],
				'instances' => [],
			];

			$this->max_scores[ $post_id ]          = [
				'all'    => $stored_maxes['all'],
				'groups' => $stored_maxes['groups'],
			];
			$this->max_score_instances[ $post_id ] = $stored_maxes['instances'];

			$flattened_fields = [];
			$this->_flatten_fields( $fields, $flattened_fields );

			foreach ( $flattened_fields as $field ) {
				if ( $field['type'] !== 'score' ) {
					continue;
				}

				$instance_key = ( $field['key'] ?? '' ) . '|' . ( $field['name'] ?? '' );

				if ( isset( $this->max_score_instances[ $post_id ][ $instance_key ] ) ) {
					continue;
				}

				$max_value = isset( $field['max'] ) && is_numeric( $field['max'] ) ? (float) $field['max'] : 0;

				$this->max_scores[ $post_id ]['all'] += $max_value;

				$this->max_score_instances[ $post_id ][ $instance_key ] = [
					'group' => $field['data-score-group'] ?? '',
					'max'   => $max_value,
				];

				if ( ! empty( $field['data-score-group'] ) ) {
					$group = $field['data-score-group'];

					if ( ! isset( $this->max_scores[ $post_id ]['groups'][ $group ] ) ) {
						$this->max_scores[ $post_id ]['groups'][ $group ] = 0;
					}

					$this->max_scores[ $post_id ]['groups'][ $group ] += $max_value;
				}
			}

			return $fields;
		}

		private function _flatten_fields( array $fields, array &$result = [] ): array {
			foreach ( $fields as $field ) {
				$result[] = $field;

				if ( isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
					$this->_flatten_fields( $field['sub_fields'], $result );
				}
			}

			return $result;
		}

		function add_max_score_suffix( $fields, $post_id ) {
			foreach ( $fields as $key => $field ) {
				if (
					isset( $field['sub_fields'] )
					&& is_array( $field['sub_fields'] )
				) {
					$fields[ $key ]['sub_fields'] = $this->add_max_score_suffix( $field['sub_fields'], $post_id );
					continue;
				}

				if ( $field['type'] !== 'score_total' ) {
					continue;
				}

				$group       = $field['data-score-group'] ?? '';
				$group_max   = $group && isset( $this->max_scores[ $post_id ]['groups'][ $group ] ) ? $this->max_scores[ $post_id ]['groups'][ $group ] : null;
				$overall_max = $this->max_scores[ $post_id ]['all'] ?? 0;
				$target_max  = $group_max ?? $overall_max;

				$fields[ $key ]['max']    = $target_max;
				$fields[ $key ]['append'] = sprintf( '/%s', $target_max );
			}

			return $fields;
		}

		/**
		 * Create the HTML interface for your field
		 *
		 * @param   $field - an array holding all the field's data
		 *
		 * @type    action
		 * @since   3.6
		 * @date    23/01/13
		 */
		function render_field( $field ) {
			$html = '';

			// Append text.
			if ( $field['append'] !== '' ) {
				$field['class'] .= ' acf-is-appended';
				$html .= '<div class="acf-input-append">' . acf_esc_html( $field['append'] ) . '</div>';
			}

			// Input.
			$input_attrs       = array();
			$field['readonly'] = true;
			$field['type']     = 'number';
			foreach ( array( 'type', 'id', 'class', 'name', 'value', 'readonly', 'data-score-group' ) as $k ) {
				if ( isset( $field[ $k ] ) ) {
					$input_attrs[ $k ] = $field[ $k ];
				}
			}

			if ( isset( $field['input-data'] ) && is_array( $field['input-data'] ) ) {
				foreach ( $field['input-data'] as $name => $attr ) {
					$input_attrs[ 'data-' . $name ] = $attr;
				}
			}

			$html .= '<div class="acf-input-wrap">' . acf_get_text_input( acf_filter_attrs( $input_attrs ) ) . '</div>';

			// Display.
			echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- only safe HTML output generated and escaped by functions above.
		}

		/**
		 * Renders the field settings used in the "Validation" tab.
		 *
		 * @since 6.0
		 *
		 * @param array $field The field settings array.
		 * @return void
		 */
		function render_field_validation_settings( $field ) {

		}

		/**
		 * Renders the field settings used in the "Presentation" tab.
		 *
		 * @since 6.0
		 *
		 * @param array $field The field settings array.
		 * @return void
		 */
		function render_field_settings( $field ) {

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Sub-total Group (optional)', 'acf' ),
					'instructions' => __( 'Leave blank to total every score field, or provide a group to total only that group.', 'acf' ),
					'type'         => 'text',
					'name'         => 'data-score-group',
				)
			);
        }

        /**
		 * Renders the field settings used in the "Presentation" tab.
		 *
		 * @since 6.0
		 *
		 * @param array $field The field settings array.
		 * @return void
		 */
		function render_field_presentation_settings( $field ) {
			
		}

		/**
		 * validate_value
		 *
		 * Validates a field's value.
		 *
		 * @date    29/1/19
		 * @since   5.7.11
		 *
		 * @param   (bool|string) Whether the value is vaid or not.
		 * @param   mixed                                          $value The field value.
		 * @param   array                                          $field The field array.
		 * @param   string                                         $input The HTML input name.
		 * @return  (bool|string)
		 */
		function validate_value( $valid, $value, $field, $input ) {
			return $valid;
		}

		/**
		 * Return the schema array for the REST API.
		 *
		 * @param array $field
		 * @return array
		 */
		function get_rest_schema( array $field ) {
			$schema = parent::get_rest_schema( $field );

			return $schema;
		}
	}

endif; // class_exists check
