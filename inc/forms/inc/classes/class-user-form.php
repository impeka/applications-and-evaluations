<?php

namespace Impeka\Tools\Forms;

if( class_exists( UserForm::class ) ) {
    return;
}

class UserForm extends FormBase {

    protected function _validate_form_post_id( int|string $post_id ) : bool {
        if( ! stristr( $post_id, 'user_' ) ) {
            return false;
        }

        $user_id = str_replace( 'user_', '', $post_id );

        return is_numeric( $user_id ) && get_userdata( $user_id );
    }

    protected function _save_meta( int|string $post_id, string $meta_key, Mixed $meta_value, Mixed $prev_value = null ) : void {
        $post_id = $this->_get_object_id( $post_id );
        update_user_meta( $post_id, $meta_key, $meta_value, $prev_value );
    }

    protected function _get_meta( int|string $post_id, string $meta_key, bool $single = false ) : Mixed {
        $post_id = $this->_get_object_id( $post_id );
        return get_user_meta( $post_id, $meta_key, $single );
    }

    protected function _get_object_id( int|string $post_id ) : int {
        if( ! stristr( $post_id, 'user_' ) ) {
            return intval( $post_id );
        }

        $user_id = str_replace( 'user_', '', $post_id );

        return intval( $user_id );
    }

}