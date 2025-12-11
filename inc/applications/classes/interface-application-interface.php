<?php

namespace Impeka\Applications;

use Impeka\Tools\Forms\PostForm;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface ApplicationInterface {
    public function is_completed() : bool;
    public function get_progress_percentage() : float;
    public function get_form() : PostForm;
    public function is_page_completed( int $page ) : bool;
    public function get_first_incomplete_page() : ?int;
    public function get_status() : string;
    public function set_status( string $status ) : void;
}
