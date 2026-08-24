<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free Passkeys core foundation bootstrap.
 *
 * Important:
 * - These files must never be included below the PHP 8.2 support gate.
 * - Do not load vendor/autoload.php here.
 * - Public runtime is booted separately and must stay native WordPress only.
 */
if ( ! defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) || ! VENTRACONNECT_PASSKEYS_CORE_SUPPORTED ) {
	return;
}

if ( ! defined( 'VENTRACONNECT_SL_PASSKEYS_CORE_DIR' ) ) {
	define( 'VENTRACONNECT_SL_PASSKEYS_CORE_DIR', trailingslashit( VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/core' ) );
}

require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'class-database.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'class-installer.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'repositories/class-passkey-repository.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'repositories/class-challenge-repository.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'repositories/class-log-repository.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'services/class-challenge-service.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'services/class-pending-user-cleanup.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'services/class-redirect-resolver.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'support/class-vendor-loader.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'support/class-webauthn-dependencies.php';
require_once VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'services/class-webauthn-service.php';

// Logged-in/admin management foundation.
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/core/support/class-messages.php';
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/public/class-manage-panel.php';
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/public/class-management-ajax.php';
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/admin/class-users-column.php';
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/admin/class-user-profile.php';
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/admin/class-admin.php';

// Native WordPress public login/register foundation only.
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/public/class-email-verification.php';
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/public/class-public-ajax.php';
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/public/class-public-frontend.php';
