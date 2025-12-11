<?php

if ( ! class_exists( 'acf_field_score' ) ) :

    class acf_field_score extends acf_field_number {
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
            $this->name = 'score';
            $this->label = __( 'Score', 'acf' );
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
            // vars
            $atts  = array();
            $keys  = array( 'type', 'id', 'class', 'name', 'value', 'min', 'max', 'step', 'placeholder', 'pattern', 'data-score-group', 'data-score-key' );
            $keys2 = array( 'readonly', 'disabled', 'required' );
            $html  = '';
            // step
            if ( ! $field['step'] ) {
                $field['step'] = 'any';
            }
            // prepend
            if ( $field['prepend'] !== '' ) {
                $field['class'] .= ' acf-is-prepended';
                $html           .= '<div class="acf-input-prepend">' . acf_esc_html( $field['prepend'] ) . '</div>';
            }
            // append
            if( ! empty( $field['max'] ) ) {
                $field['append'] = sprintf( '/%s', $field['max'] );
            }
            else {
                $field['append'] = '';
            }
            
            if ( $field['append'] !== '' ) {
                $field['class'] .= ' acf-is-appended';
                $html .= '<div class="acf-input-append">' . acf_esc_html( $field['append'] ) . '</div>';
            }

            // atts (value="123")
            $field['type'] = 'number';
			$field['data-score-key'] = $field['key'] ?? '';
            foreach ( $keys as $k ) {
                if ( isset( $field[ $k ] ) ) {
                    $atts[ $k ] = $field[ $k ];
                }
            }
            // atts2 (disabled="disabled")
            foreach ( $keys2 as $k ) {
                if ( ! empty( $field[ $k ] ) ) {
                    $atts[ $k ] = $k;
                }
            }
            // remove empty atts
            $atts = acf_clean_atts( $atts );
            // render
            $html .= '<div class="acf-input-wrap">' . acf_get_text_input( $atts ) . '</div>';
            // return
            echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by individual html functions above.
        }
        /**
         * Create extra options for your field. This is rendered when editing a field.
         *
         * @type    action
         * @since   3.6
         * @date    23/01/13
         *
         * @param   $field  - an array holding all the field's data
         */
        function render_field_settings( $field ) {
            parent::render_field_settings( $field );

            acf_render_field_setting(
                $field,
                array(
                    'label'        => __( 'Sub-total Group', 'acf' ),
                    'instructions' => __( 'You can group score fields together to see a sub-total of a particular set of score fields.', 'acf' ),
                    'type'         => 'text',
                    'name'         => 'data-score-group',
                )
            );
        }
    }

endif; // class_exists check
