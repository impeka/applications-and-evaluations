<?php
/**
 * Plugin Name: Applications and Evaluations
 * Description: Moves all application and evaluation form functionality out of the theme.
 * Version: 1.0.0
 * Author: Impeka
 * Text Domain: applications-and-evaluations
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'IMPEKA_AE_PLUGIN_DIR' ) ) {
    define( 'IMPEKA_AE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'IMPEKA_AE_PLUGIN_URL' ) ) {
    define( 'IMPEKA_AE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'IMPEKA_AE_PLUGIN_FILE' ) ) {
    define( 'IMPEKA_AE_PLUGIN_FILE', __FILE__ );
}

require_once IMPEKA_AE_PLUGIN_DIR . 'inc/class-plugin.php';

\Impeka\Applications\Plugin::instance()->init();
