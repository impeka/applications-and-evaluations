<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {
    private static ?Plugin $instance = null;

    public static function instance() : self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init() : void {
        add_action( 'plugins_loaded', [ $this, 'bootstrap' ], 1 );
    }

    public function bootstrap() : void {
        require_once IMPEKA_AE_PLUGIN_DIR . 'inc/forms/forms.php';
        require_once IMPEKA_AE_PLUGIN_DIR . 'inc/applications/applications.php';
    }
}
