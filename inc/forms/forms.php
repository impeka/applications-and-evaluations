<?php

namespace Impeka\Tools\Forms;

require_once( __DIR__ . '/inc/classes/class-form-base.php' );
require_once( __DIR__ . '/inc/classes/class-form-header.php' );
require_once( __DIR__ . '/inc/classes/class-form-manager-interface.php' );
require_once( __DIR__ . '/inc/classes/class-form-page.php' );
require_once( __DIR__ . '/inc/classes/class-post-form.php' );
require_once( __DIR__ . '/inc/classes/class-user-form-manager.php' );
require_once( __DIR__ . '/inc/classes/class-user-form.php' );
require_once( __DIR__ . '/inc/classes/class-form-polylang.php' );

FormPolylang::getInstance();

$locale = determine_locale();
$mofile = sprintf( '%s/languages/forms-%s.mo', __DIR__, $locale );
load_textdomain( 'impeka-forms', $mofile, $locale );

if ( ! function_exists( 'set_url_query_vars' ) ) {
    /**
     * Helper to append/replace query args on a URL (defaults to current URL).
     *
     * @param array       $args Query args to add/replace.
     * @param string|null $url  Optional base URL; defaults to current request URL.
     *
     * @return string
     */
    function set_url_query_vars( array $args, ?string $url = null ) : string {
        if ( empty( $url ) ) {
            $scheme = ( ! empty( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) !== 'off' ) ? 'https://' : 'http://';
            $host   = $_SERVER['HTTP_HOST'] ?? '';
            $uri    = $_SERVER['REQUEST_URI'] ?? '';
            $url    = $scheme . $host . $uri;
        }

        return add_query_arg( $args, $url );
    }
}
