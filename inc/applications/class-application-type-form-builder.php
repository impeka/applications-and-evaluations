<?php

namespace Impeka\Applications;

use Impeka\Tools\Forms\FormPage;
use Impeka\Tools\Forms\PostForm;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ApplicationTypeFormBuilder {
    public static function form_id_from_term( \WP_Term $term ) : string {
        return sprintf( 'application-type-%s', $term->slug );
    }

    public static function build_form_for_term( \WP_Term $term ) : PostForm {
        $form_id = self::form_id_from_term( $term );
        $form    = new PostForm( $form_id );

        $pages = get_field( 'application_form_pages', sprintf( 'application_type_%d', $term->term_id ) );
        $pages = is_array( $pages ) ? $pages : [];

        if ( empty( $pages ) ) {
            $form->add_page( new FormPage() );
            return $form;
        }

        foreach ( $pages as $page_data ) {
            $page = new FormPage();

            if ( isset( $page_data['application_form_page_field_groups'] ) && is_array( $page_data['application_form_page_field_groups'] ) ) {
                foreach ( $page_data['application_form_page_field_groups'] as $group_row ) {
                    if ( empty( $group_row['application_form_field_group'] ) ) {
                        continue;
                    }

                    $page->add_field_group( $group_row['application_form_field_group'] );
                }
            }

            $form->add_page( $page );
        }

        return $form;
    }
}
