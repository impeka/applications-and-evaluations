<?php

namespace Impeka\Tools\Forms;

if( class_exists( FormPolylang::class ) ) {
    return;
}

class FormPolylang {

    private static ?FormPolylang $_instance = null;

    private function __construct() {
        if( ! function_exists( 'pll_the_languages' ) ) {
            return;
        }

        add_filter( 'pll_the_language_link', [$this, 'add_pages_to_language_toggle'], 10, 2 );
    }

    public function add_pages_to_language_toggle( string $url = null, string $domain ) : string {

        if( ! $url ) {
            return home_url();
        }

        if( 
            isset( $_GET['pg'] ) 
        ) {
            return sprintf( '%s?pg=%s', $url, $_GET['pg'] );
        }

        return $url;
    }

    static function getInstance() : FormPolylang {
        if( ! self::$_instance ) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
}