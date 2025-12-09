<?php

namespace Impeka\Tools\Forms;

if( class_exists( PostForm::class ) ) {
    return;
}

class PostForm extends FormBase {

    public function __construct( string $id ) {
        parent::__construct( $id );
    }

    protected function _validate_form_post_id( int|string $post_id ) : bool {
        return is_numeric( $post_id ) && get_post_status( $post_id );
    }

    protected function _save_meta( int|string $post_id, string $meta_key, Mixed $meta_value, Mixed $prev_value = null ) : void {
        update_post_meta( $post_id, $meta_key, $meta_value, $prev_value );
    }

    protected function _get_meta( int|string $post_id, string $meta_key, bool $single = false ) : Mixed {
        return get_post_meta( $post_id, $meta_key, $single );
    }

    protected function _get_object_id( int|string $post_id ) : int {
        return intval( $post_id );
    }

}