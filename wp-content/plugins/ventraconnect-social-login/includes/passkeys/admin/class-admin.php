<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Admin {

	protected $manage_panel;
	protected $users_column;
	protected $user_profile;

	public function __construct() {
		$this->manage_panel = new VentraConnect_SL_Passkeys_Manage_Panel();
		$this->users_column = new VentraConnect_SL_Passkeys_Users_Column( $this->manage_panel );
		$this->user_profile = new VentraConnect_SL_Passkeys_User_Profile( $this->manage_panel );
	}

	public function init() {
		$this->users_column->register_hooks();
		$this->user_profile->register_hooks();
	}
}
