<?php
/**
 * Plugin Name: Applications and Evaluations
 * Description: Moves all application and evaluation form functionality out of the theme.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'IMPEKA_AE_PLUGIN_DIR' ) ) {
    define( 'IMPEKA_AE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

require_once IMPEKA_AE_PLUGIN_DIR . 'inc/class-plugin.php';

\Impeka\Applications\Plugin::instance()->init();
