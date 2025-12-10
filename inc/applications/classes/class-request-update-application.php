<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Impeka\Tools\Forms\Form;

/*
    Note: This is a unique request class because it hooks into the middle of an ACF form process. This is why it does not redirect or hook into admin_post_{$action}
*/

class RequestUpdateApplication {
    private static ?RequestUpdateApplication $instance = null;

    private function __construct() {
        add_action( 'impeka/forms/page_saved', [$this, 'on_form_save_status'], 10, 5 );
    }

    public static function get_instance() : self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function on_form_save_status( Form $form, int|string $post_id, int $current_page, bool $form_status_before, bool $form_status ) : void {
        $application = new Application( $post_id );

        $application->set_status( $form_status ? 'submit' : 'progress' );

        if(
            ! $form_status_before
            && $form_status
        ) {
            $application->set_submit_date( new \DateTimeImmutable() );
        }
    }
}
