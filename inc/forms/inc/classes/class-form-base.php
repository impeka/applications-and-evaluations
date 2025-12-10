<?php

namespace Impeka\Tools\Forms;

if( class_exists( FormBase::class ) ) {
    return;
}

abstract class FormBase {
    protected string $_id;
    protected array $_pages = [];
    protected string $_success_url = '';

    public function __construct( string $id ) {
        $this->_id = $id;

        add_filter( 'acf/pre_save_post' , [$this, 'save_form_page'], 10, 2 );
        add_action( 'acf/submit_form', [$this, 'on_form_submit'], 10, 2 );
        add_filter( 'acf/load_field', [$this, 'check_for_save_for_later'], 10, 1 );
        add_filter( 'acf/pre_submit_form', [$this, 'update_field_on_save_for_later'], 10, 1 );
        add_action( 'acf/validate_save_post', [$this, 'check_for_complete_form'] );
        add_action( 'acf/validate_save_post', [$this, 'enforce_save_return_on_validate'], 1 );
    }

    public function check_for_complete_form() {

        if( ! $this->_is_this_form() )
            return;

        if( ! isset( $_POST['_acf_post_id'] ) )
            return;

        if( 
            ! apply_filters( 'impeka/forms/form_is_allowed', true, $this->_id, $_POST['_acf_post_id'] ) 
        ) {
            acf_add_validation_error( '', __( 'The form cannot be submitted at this time.', 'impeka-forms' ) );
        }

        if( 
            isset( $_POST['save_flag'] )
            || (
                is_admin()
            )
        ) {
            return;
        }

        $page = isset( $_GET['pg'] ) ? intval( $_GET['pg'] ) : 1;

        if( 
            $this->is_last_page( $page )
            && ! $this->is_submission_ready( $_POST['_acf_post_id'] )
        ) {
            acf_add_validation_error( '', __( 'You must complete the whole form to submit.', 'impeka-forms' ) );
        }
    }

    public function get_id() : string {
        return $this->_id;
    }

    private function _is_this_form() : bool {
        return isset( $_POST['_application_form_id'] ) && $_POST['_application_form_id'] == $this->_id;
    }

    public function check_for_save_for_later( array $field ) : array {
        global $pagenow;

        if (
            ( $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'acf-field-group' ) || 
            ( $pagenow === 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) === 'acf-field-group' )
        ) {
            return $field;
        }

        if( 
           (
                isset( $_POST['save_flag'] )
                || is_admin()
            )
        ) {
            $field['required'] = 0;
        }
    
        return $field;
    }

    public function update_field_on_save_for_later( array $form ) : array {
        // Use the form array directly; do not rely solely on POST presence of the id.
        if ( empty( $form['id'] ) || $form['id'] !== $this->_id ) {
            return $form;
        }

        if ( isset( $_POST['save_flag'] ) ) {
            $current_url   = ( empty( $_SERVER['HTTPS'] ) ? 'http' : 'https' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $form['return'] = $current_url;
        }

        return $form;
    }

    /**
     * Backup safeguard: ensure return url is set for save (runs even if pre_submit filter missed).
     */
    public function enforce_save_return_on_validate() : void {
        if( ! $this->_is_this_form() )
            return;

        if( isset( $_POST['save_flag'], $_POST['_acf_form'] ) && is_array( $_POST['_acf_form'] ) ) {
            $current_url = ( empty( $_SERVER['HTTPS'] ) ? 'http' : 'https' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $_POST['_acf_form']['return'] = $current_url;
        }
    }

    public function on_form_submit( array $form, int|string $post_id ) : void {
        if( ! $this->_is_this_form() )
            return;

        do_action( 'fasmc/submit_form', $form, $post_id );
    }

    public function is_last_page( int $pg ) : bool {
        return $pg == count( $this->_pages );
    }

    public function add_page( FormPage $page ) : void {
        $this->_pages[count( $this->_pages ) + 1] = $page;
    }

    public function has_page( int $page ) : bool {
        return isset( $this->_pages[$page] );
    }
    
    public function get_page( int $page ) : FormPage {
        if( ! $this->has_page( $page ) ) {
            throw new \Exception( 'Invalid form page.' );
        }

        return $this->_pages[$page];
    }

    public function get_pages() : array {
        return $this->_pages;
    }

    public function set_success_url( string $url ) : void {
        $this->_success_url = $url;
    }

    public function get_nav( int|string $post_id ) : string {
        $output = '';

        if( ! $this->_validate_form_post_id( $post_id ) )
            return '';

        $pg = isset( $_GET['pg'] ) ? $_GET['pg'] : 1;

        if( $pages = $this->get_pages() ) {
            $output = '<ul class="pages-list">';
            $output .= apply_filters( 'fasmc/nav__label', sprintf( '<li class="pages-list__item">%s</li>', __( 'Pages: ', 'impeka-forms' ) ) );

            $items = [];

            foreach( $pages as $page_n => $page ) {
                $css_class = '';
                $icon = $this->is_page_completed( $post_id, $page_n ) ? '<i class="fa-light fa-check success"></i>' : '<i class="fa-light fa-square-exclamation warning"></i>';

                if( $pg == $page_n ) {
                    $css_class .= ' current';
                }

                $item = sprintf( 
                    '<li class="pages-list__item%3$s"><a class="button-small" href="%1$s">%4$s %2$s</a></li>', 
                    set_url_query_vars( ['pg' => $page_n] ), 
                    $page_n,
                    $css_class,
                    $icon
                );

                $item = apply_filters( 'fasmc/nav__item', $item, $page_n, $css_class, $icon );

                $items[] = $item;
            }

            $items = apply_filters( 'fasmc/nav__items', $items, $pg );

            $output .= implode( '', $items );
            
            $output .= '</ul>';
        }

        return apply_filters( 'impeka/forms/get_nav', $output, $this->get_id(), $this->_get_object_id( $post_id ) );
    }

    public function show_nav( int|string $post_id ) : void {
        echo $this->get_nav( $post_id );
    }

    public function show_form( int|string $post_id ) : void {
        echo $this->get_form( $post_id );
    }

    public function get_form( int|string $post_id ) : string {
        $field_groups = [];
        $input_page_value = [];
        
        $page = isset( $_GET['pg'] ) ? intval( $_GET['pg'] ) : 1;

        if( apply_filters( 'impeka/forms/get_form/page', $page, $this->get_id(), $this->_get_object_id( $post_id ) ) ) {
            
            if( ! $this->has_page( $page ) ) {
                throw new \Exception( 'Invalid form page.' );
            }

            $field_groups = $this->get_page( $page )->get_field_groups();
            $input_page_value[] = $page;
        }
        else {
            foreach( $this->_pages as $page_n => $form_page ) {
                $field_groups = array_merge( $field_groups, $form_page->get_field_groups() );
                $input_page_value = array_keys( $this->_pages );
            }
        }

        if( empty( $field_groups ) ) {
            $field_groups[] = 'impossible_form_group_id_quick_hack'; //if field_groups is empty then acf_form shows all form groups associated with the post type, we don't want that
        }

        if( ! $this->_validate_form_post_id( $post_id ) ) {
            throw new \Exception( 'Invalid post.' );
        }

        if( $this->has_page( $page + 1 ) ) {
            $return = set_url_query_vars( ['pg' => ( $page + 1 )] );
        }
        else {
            $return = apply_filters( 'impeka/forms/success_url', $this->_success_url, $this->get_id(), $this->_get_object_id( $post_id ) );
        }

        $input_page = sprintf( '<input type="hidden" name="current_page" value="%s" />', implode( ',', $input_page_value ) );
        $input_form_id = sprintf( '<input type="hidden" name="_application_form_id" value="%s" />', $this->_id );
        $save_button = sprintf( '<input type="checkbox" class="save_flag" name="save_flag" value="1" /><button class="form-button" name="save">%s</button>', __( 'Save', 'impeka-forms' ) );
        $submit_value = __( 'Save & Continue', 'impeka-forms' );

        if( $this->is_last_page( $page ) ) {
            $submit_value = __( 'Save & Complete', 'impeka-forms' );
        }

        $form = true;
        $button_group = '';

        if( $this->is_completed( $post_id ) ) {
            // This might be a little primitive but if the form has been completed fully any future modification is an update
            $submit_value = __( 'Update', 'impeka-forms' );
            $button_group = '<div class="form__buttons"><button type="submit" class="form-button">%s</button></div>';
        }
        else {
            $button_group = sprintf( '<div class="form__buttons">%s<button type="submit" class="form-button">%%s</button></div>', $save_button );
        }

        if( 
            $this->is_last_page( $page ) 
            && ! $this->is_submission_ready( $post_id )
        ) {
            $form = false;
            $button_group = '<div class="form__buttons"><button type="submit" class="form-button">%s</button></div>';
            $button_group = str_replace( '<button ', '<button disabled="1" inert="1" ', $button_group );
            $button_group = sprintf( '<div class="notification notification-error"><p>%1$s</p><p><a href="%3$s">%2$s</a></p></div>', __( 'You must complete every page of the form before it can be submitted.', 'impeka-forms' ), _x( 'You can use the form navigation above or click here.', 'giving the url of the first incomplete page of the form', 'impeka-forms' ), $this->get_first_incomplete_page_url( $post_id ) ) . $button_group;
        }

        ob_start();

        $acf_form_args = [
            'id' => $this->_id,
            'post_id' => $post_id,
            'field_groups' => $field_groups,
            'html_before_fields' => $input_page.$input_form_id,
            'form' => true,
            'submit_value' => $submit_value,
            'updated_message' => __( 'Your submission has been saved.', 'impeka-forms'),
            'html_updated_message' => '<div class="notification notification-success"><p>%s</p></div>',
            'html_submit_button' => $button_group,
            'return' => $return
        ];

        $acf_form_args = apply_filters( 'impeka/forms/acf_form_args', $acf_form_args, $this->get_id(), $this->_get_object_id( $post_id ) );

        acf_form( $acf_form_args );

        return apply_filters( 'impeka/forms/get_form', ob_get_clean(), $this->get_id(), $this->_get_object_id( $post_id ) );
    }

    public function is_submission_ready( int|string $post_id ) : bool {
        foreach( $this->_pages as $page_n => $form_page ) {
            if( 
                ! $this->is_last_page( $page_n )
                && ! $this->is_page_completed( $post_id, $page_n )
            )
                return false;
        }

        return true;
    }

    public function get_first_incomplete_page_url( int|string $post_id ) : string {
        $page_n = $this->get_first_incomplete_page( $post_id );

        return set_url_query_vars( ['pg' => $page_n] );
    }

    public function save_form_page( int|string $post_id ) : int|string {
        
        if( ! $this->_is_this_form() )
            return $post_id;

        if( ! $this->_validate_form_post_id( $post_id ) ) {
            return $post_id;
        }

        if( 
            ! apply_filters( 'fasmc/form_is_allowed', true, $this->_id, $post_id )  //this should be deprecated but now sure if in use
            || ! apply_filters( 'impeka/forms/form_is_allowed', true, $this->_id, $post_id ) 
        ) {
            return $post_id;
        }

        if( ! isset( $_POST['current_page'] ) ) {
            return $post_id;
        }

        $completed_pages = $this->_get_meta( $post_id, '_completed_pages', true );
        $completed_pages = is_array( $completed_pages ) ? $completed_pages : [];

        $current_page = $_POST['current_page'];
        $current_page_arr = explode( ',', $current_page );

        foreach( $current_page_arr as $key => $value ) {
            if( ! is_numeric( $value ) )
                unset( $current_page_arr[$key] );
        }

        $completed_pages = array_unique( array_merge( $completed_pages, $current_page_arr ) );
        $completed_pages = array_intersect( array_keys( $this->_pages ), $completed_pages );

        if( isset( $_POST['save_flag'] ) ) {
            foreach( $current_page_arr as $key => $value ) {
                if ( ( $key = array_search( $value, $completed_pages ) ) !== false ) {
                    unset( $completed_pages[$key] );
                }
            }
        }

        $this->_save_meta( $post_id, '_completed_pages', $completed_pages );

        $form_status_before = $this->_get_meta( $post_id, sprintf( '_form_%s_is_completed', $this->_id ), true );

        if( $form_status_before != true ) {
            $form_status_before = false; //I don't want a null value
        }

        $form_status = $form_status_before;

        if( 
            $this->is_last_page( $current_page ) 
            && $this->is_submission_ready( $post_id )
            && ! isset( $_POST['save_flag'] )
        ) {
            // if last page and is ready to be submitted and the form is being submitted -- it means the form is completed
            $this->_save_meta( $post_id, sprintf( '_form_%s_is_completed', $this->_id ), true );
            $form_status = true;
        }

        do_action( 'impeka/forms/page_saved', $this->_id, $post_id, $current_page, $form_status_before, $form_status );

        $this->_save_meta( $post_id, sprintf( '_form_%s_last_updated', $this->_id ), date( 'Y-m-d H:i:s' ) );

        return $post_id;
    }

    public function is_completed( int|string $post_id ) : bool {
        return $this->_get_meta( $post_id, sprintf( '_form_%s_is_completed', $this->_id ), true );
    }

    public function is_page_completed( int|string $post_id, int $page ) : bool {
        $completed_pages = $this->_get_meta( $post_id, '_completed_pages', true );
        $completed_pages = is_array( $completed_pages ) ? $completed_pages : [];

        return in_array( $page, $completed_pages );
    }

    public function get_first_incomplete_page( int|string $post_id ) : int {
        $completed_pages = $this->_get_meta( $post_id, '_completed_pages', true );
        $completed_pages = is_array( $completed_pages ) ? $completed_pages : [];
        
        $form_pages = array_keys( $this->get_pages() );
        sort( $form_pages );

        foreach( $form_pages as $page ) {
            if( ! in_array( $page, $completed_pages ) ) {
                return intval( $page );
            }
        }

        return end( $form_pages );
    }

    abstract protected function _validate_form_post_id( int|string $post_id ) : bool;
    abstract protected function _save_meta( int|string $post_id, string $meta_key, Mixed $meta_value, Mixed $prev_value = null ) : void;
    abstract protected function _get_meta( int|string $post_id, string $meta_key, bool $single = false ) : Mixed;
    abstract protected function _get_object_id( int|string $post_id ) : int;
}
