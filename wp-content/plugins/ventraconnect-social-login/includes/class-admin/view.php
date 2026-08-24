<?php
namespace VentraConnect\SocialLogin\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Tiny PHP view helper to render partials with scoped variables.
 */
class View {
    /**
     * Render a PHP template and return its buffered output.
     *
     * @param string               $path Absolute path to the PHP view file
     * @param array<string,mixed>  $vars Variables to extract for the template
     * @return string
     */
    public static function render( string $path, array $vars = [] ): string {
        if ( ! $path || ! file_exists( $path ) ) {
            return '';
        }
        if ( ! empty( $vars ) ) {
            extract( $vars, EXTR_SKIP ); // make keys available as local vars
        }
        ob_start();
        require $path;
        return (string) ob_get_clean();
    }
}
