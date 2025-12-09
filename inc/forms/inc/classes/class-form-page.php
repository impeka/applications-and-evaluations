<?php

namespace Impeka\Tools\Forms;

if( class_exists( FormPage::class ) ) {
    return;
}

class FormPage {
    protected array $_field_groups = [];

    public function __construct() {
        add_action( 'acf/init', [$this, 'field_group_keys'] );
    }

    public function field_group_keys() : void {
        foreach( $this->_field_groups as $key => $field_group_key ) {
            $field_group_key = apply_filters( 'impeka/form_page/field_group', $field_group_key );
            $this->_field_groups[$key] = $field_group_key;
        }
    }

    public function add_field_group( string $group ) : void {
        $this->_field_groups[] = $group;
    }

    public function get_field_groups() : array {
        return $this->_field_groups;
    }
}