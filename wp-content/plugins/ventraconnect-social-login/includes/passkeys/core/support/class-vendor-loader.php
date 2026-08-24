<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_Vendor_Loader {

	protected static $loaded = false;

	public function get_autoload_path() {
		return VENTRACONNECT_SL_PASSKEYS_CORE_DIR . 'vendor/autoload.php';
	}

	public function has_autoload() {
		return file_exists( $this->get_autoload_path() );
	}

	public function load() {
		if ( self::$loaded ) {
			return true;
		}

		if ( ! $this->has_autoload() ) {
			return false;
		}

		require_once $this->get_autoload_path();

		self::$loaded = true;

		return $this->has_expected_classes();
	}

	public function is_loaded() {
		return self::$loaded;
	}

	public function get_expected_classes() {
		return array(
			'Webauthn\\PublicKeyCredentialSource',
		);
	}

	public function has_expected_classes() {
		foreach ( $this->get_expected_classes() as $class_name ) {
			if ( class_exists( $class_name ) ) {
				return true;
			}
		}

		return false;
	}

	public function get_status() {
		return array(
			'autoload_path'         => $this->get_autoload_path(),
			'autoload_exists'       => $this->has_autoload(),
			'loaded'                => $this->is_loaded(),
			'expected_classes'      => $this->get_expected_classes(),
			'expected_class_exists' => $this->has_expected_classes(),
		);
	}
}
