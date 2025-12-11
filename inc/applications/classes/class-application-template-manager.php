<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ApplicationTemplateManager {
    public function __construct() {
        add_filter( 'template_include', [ $this, 'maybe_use_plugin_template' ] );
    }

    public function maybe_use_plugin_template( string $template ) : string {
        if ( is_post_type_archive( 'application' ) ) {
            $located = $this->locate_template( 'archive-application.php' );
            return $located ?: $template;
        }

        if ( is_post_type_archive( 'evaluation' ) ) {
            $located = $this->locate_template( 'archive-evaluation.php' );
            return $located ?: $template;
        }

        if ( is_singular( 'application' ) ) {
            $located = $this->locate_template( 'single-application.php' );
            return $located ?: $template;
        }

        if ( is_singular( 'evaluation' ) ) {
            $located = $this->locate_template( 'single-evaluation.php' );
            return $located ?: $template;
        }

        return $template;
    }

    protected function locate_template( string $filename ) : ?string {
        $theme_dir = trailingslashit( get_stylesheet_directory() ) . 'applications-and-evaluations/';
        $theme_path = $theme_dir . $filename;

        if ( file_exists( $theme_path ) ) {
            return $theme_path;
        }

        $plugin_path = IMPEKA_AE_PLUGIN_DIR . 'templates/' . $filename;

        if ( file_exists( $plugin_path ) ) {
            return $plugin_path;
        }

        return null;
    }
}
