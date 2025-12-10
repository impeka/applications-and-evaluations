<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/classes/class-application-type-form-builder.php';
require_once __DIR__ . '/classes/class-application.php';
require_once __DIR__ . '/classes/class-application-registrar.php';
require_once __DIR__ . '/classes/class-application-template-manager.php';
require_once __DIR__ . '/classes/interface-form-submission-error.php';
require_once __DIR__ . '/classes/class-form-submission-error-base.php';
require_once __DIR__ . '/classes/class-application-error.php';
require_once __DIR__ . '/classes/class-request-create-application.php';
require_once __DIR__ . '/classes/class-application-access.php';
require_once __DIR__ . '/classes/class-application-form-hooks.php';
require_once __DIR__ . '/classes/class-application-template-helpers.php';
require_once __DIR__ . '/helper-functions.php';

ApplicationRegistrar::get_instance();
ApplicationTypeFormBuilder::get_instance();
RequestCreateApplication::get_instance();
new ApplicationTemplateManager();
new ApplicationAccess();
new ApplicationFormHooks();
