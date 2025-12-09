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