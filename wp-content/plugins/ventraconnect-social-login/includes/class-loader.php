<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * PSR-4-like simple autoloader for VCS classes.
 */
spl_autoload_register( function ( $class ) {
    if ( 0 !== strpos( $class, __NAMESPACE__ . '\\' ) ) {
        return;
    }

    $rel  = substr( $class, strlen( __NAMESPACE__ ) + 1 );
    // Normalise to lowercase + forward slashes for routing.
    $rel  = str_replace( '\\', '/', strtolower( $rel ) );
    $base = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR : plugin_dir_path( __FILE__ ) . '../';

    // Default: root classes map to includes/class-*.php using lowercase + dashes.
    $path = $base . 'includes/class-' . str_replace( '_', '-', $rel ) . '.php';

    if ( false !== strpos( $rel, 'providers/' ) ) {
        if ( $rel === 'providers/abstract_provider' ) {
            $path = $base . 'includes/providers/abstract-provider.php';
        } else {
            $path = $base . 'includes/' . $rel . '.php';
            $path = str_replace( '/providers/', '/providers/class-provider-', $path );
            $path = str_replace( '_', '-', $path );
        }
    } elseif ( false !== strpos( $rel, 'integrations/' ) ) {
        $slug = basename( $rel );
        $slug = preg_replace( '/_integration$/', '', $slug );
        $slug = str_replace( '_', '-', $slug );
        $path = $base . 'includes/integrations/class-integration-' . $slug . '.php';
    } elseif ( false !== strpos( $rel, 'diagnostics/' ) ) {
        $slug = basename( $rel );
        $slug = str_replace( '_', '-', $slug );
        $path = $base . 'includes/diagnostics/class-' . $slug . '.php';
    } elseif ( 0 === strpos( $rel, 'services/' ) ) {
        // Services\* → includes/services/class-*.php (Token_Auth → class-token-auth.php)
        $slug = basename( $rel );
        $slug = str_replace( '_', '-', $slug );
        $path = $base . 'includes/services/class-' . $slug . '.php';
    }

    if ( file_exists( $path ) ) {
        require_once $path;
    }
} );

