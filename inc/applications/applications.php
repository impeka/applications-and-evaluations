<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-application-type-form-builder.php';
require_once __DIR__ . '/class-application.php';
require_once __DIR__ . '/class-application-registrar.php';

ApplicationRegistrar::get_instance();
