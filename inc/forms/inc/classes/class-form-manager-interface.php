<?php

namespace Impeka\Tools\Forms;

if( class_exists( FormManager::class ) ) {
    return;
}

interface FormManager {
    public function register_form( FormBase $form ) : void;
    public function get_form( string $id ) : FormBase;
}