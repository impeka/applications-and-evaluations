<?php

namespace Impeka\Tools\Forms;

if( class_exists( FormHeader::class ) ) {
    return;
}

class FormHeader {

    private static ?FormHeader $_instance = null;

    private function __construct() {
        add_action( 'template_include', [$this, 'template_include'] );   
        //add_action( 'init', [$this, 'template_include2'] );
    }

    public function template_include( string $template ) : string {
        if ( ! wp_doing_ajax() ) {
            acf_form_head();
        }

        return $template;
    }

    public function template_include2() : void {

        if ( ! wp_doing_ajax() ) {
            acf_form_head();
        }

    }

    static function getInstance() : FormHeader {
        if( ! self::$_instance ) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
}