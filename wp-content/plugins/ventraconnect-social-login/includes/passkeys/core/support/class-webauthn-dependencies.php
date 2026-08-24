<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_WebAuthn_Dependencies {

	protected $vendor_loader;

	public function __construct( $vendor_loader = null ) {
		$this->vendor_loader = $vendor_loader instanceof VentraConnect_SL_Passkeys_Core_Vendor_Loader ? $vendor_loader : new VentraConnect_SL_Passkeys_Core_Vendor_Loader();
	}

	public function get_required_php_version() {
		return '8.2';
	}

	public function has_supported_php_version() {
		return version_compare( PHP_VERSION, $this->get_required_php_version(), '>=' );
	}

	public function get_required_extensions() {
		return array(
			'json',
			'openssl',
			'mbstring',
		);
	}

	public function get_recommended_extensions() {
		return array(
			'gmp',
			'bcmath',
		);
	}

	public function get_missing_required_extensions() {
		return $this->get_missing_extensions( $this->get_required_extensions() );
	}

	public function get_missing_recommended_extensions() {
		return $this->get_missing_extensions( $this->get_recommended_extensions() );
	}

	public function get_vendor_autoload_path() {
		return $this->vendor_loader->get_autoload_path();
	}

	public function has_vendor_autoload() {
		return $this->vendor_loader->has_autoload();
	}

	public function is_vendor_loaded() {
		return $this->vendor_loader->is_loaded();
	}

	public function has_expected_webauthn_class() {
		return $this->vendor_loader->has_expected_classes();
	}

	public function is_ready_for_webauthn() {
		$health_status = $this->get_health_status();

		return ! empty( $health_status['ready'] );
	}

	public function get_health_status() {
		$vendor_autoload_exists = $this->has_vendor_autoload();
		$vendor_loaded          = false;
		$expected_class_exists  = $this->has_expected_webauthn_class();
		$php_supported          = $this->has_supported_php_version();
		$missing_required       = $this->get_missing_required_extensions();
		$missing_recommended    = $this->get_missing_recommended_extensions();

		if ( $php_supported && empty( $missing_required ) && $vendor_autoload_exists ) {
			$vendor_loaded         = $this->vendor_loader->load();
			$expected_class_exists = $this->has_expected_webauthn_class();
		}

		return array(
			'php_version'                    => PHP_VERSION,
			'php_supported'                  => $php_supported,
			'missing_required_extensions'    => $missing_required,
			'missing_recommended_extensions' => $missing_recommended,
			'vendor_autoload_exists'         => $vendor_autoload_exists,
			'vendor_loaded'                  => $vendor_loaded,
			'expected_class_exists'          => $expected_class_exists,
			'ready'                          => $php_supported
				&& empty( $missing_required )
				&& $vendor_autoload_exists
				&& $vendor_loaded
				&& $expected_class_exists,
		);
	}

	protected function get_missing_extensions( array $extensions ) {
		$missing = array();

		foreach ( $extensions as $extension ) {
			if ( ! extension_loaded( $extension ) ) {
				$missing[] = $extension;
			}
		}

		return $missing;
	}
}
