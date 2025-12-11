<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {
    const VERSION = '0.1.0';
    private static ?Plugin $instance = null;


    public static function instance() : self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init() : void {
        add_action( 'init', [ $this, 'bootstrap' ], 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function bootstrap() : void {
        require_once IMPEKA_AE_PLUGIN_DIR . 'inc/forms/forms.php';
        require_once IMPEKA_AE_PLUGIN_DIR . 'inc/applications/applications.php';
        require_once IMPEKA_AE_PLUGIN_DIR . 'inc/acf-score-fields/acf-score-fields.php';
    }

    public function enqueue_assets() : void {
        wp_enqueue_style(
            'fontawesome',
            '//impekacdn.s3.us-east-2.amazonaws.com/fontawesome6/css/all.min.css',
            [],
            '6.4.2'
        );

        $style_handle = 'applications-and-evaluations';
        $style_src    = IMPEKA_AE_PLUGIN_URL . 'assets/css/applications-and-evaluations.css';
        wp_enqueue_style( $style_handle, $style_src, [], self::VERSION );

        $script_handle = 'applications-and-evaluations';
        $script_src    = IMPEKA_AE_PLUGIN_URL . 'assets/js/applications-and-evaluations.js';
        wp_enqueue_script( $script_handle, $script_src, [ 'jquery' ], self::VERSION, true );
    }

    public function enqueue_admin_assets() : void {
        wp_enqueue_style(
            'fontawesome',
            '//impekacdn.s3.us-east-2.amazonaws.com/fontawesome6/css/all.min.css',
            [],
            '6.4.2'
        );

        $style_handle = 'admin-applications-and-evaluations';
        $style_src    = IMPEKA_AE_PLUGIN_URL . 'assets/css/admin-applications-and-evaluations.css';
        wp_enqueue_style( $style_handle, $style_src, [], self::VERSION );
    }
    
}
