<?php
/*
Plugin Name: SearchWP Modal Search Form
Plugin URI: https://searchwp.com/extensions/modal-form/
Description: Lightweight and accessible search form
Version: 0.5.7
Requires PHP: 7.0
Author: SearchWP
Author URI: https://searchwp.com/
Text Domain: searchwp-modal-search-form
Tested up to: 6.9

Copyright 2019-2025 SearchWP, LLC

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>.
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SEARCHWP_MODAL_FORM_VERSION' ) ) {
	/**
	 * Plugin version.
	 *
	 * @since 0.1
	 */
	define( 'SEARCHWP_MODAL_FORM_VERSION', '0.5.7' );
}

if ( ! defined( 'SEARCHWP_MODAL_FORM_PLUGIN_DIR' ) ) {
	/**
	 * Plugin dir.
	 *
	 * @since 0.5.0
	 */
	define( 'SEARCHWP_MODAL_FORM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SEARCHWP_MODAL_FORM_PLUGIN_URL' ) ) {
	/**
	 * Plugin URL.
	 *
	 * @since 0.5.0
	 */
	define( 'SEARCHWP_MODAL_FORM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SEARCHWP_MODAL_FORM_PLUGIN_FILE' ) ) {
	/**
	 * Plugin file.
	 *
	 * @since 0.5.0
	 */
	define( 'SEARCHWP_MODAL_FORM_PLUGIN_FILE', __FILE__ );
}

/**
 * Returns an instance of the classes' container.
 *
 * @since 0.5.0
 *
 * @return SearchWPModalFormContainer
 */
function searchwp_modal_form() {

	static $instance;

	if ( ! ( $instance instanceof SearchWPModalFormContainer ) ) {
		require_once SEARCHWP_MODAL_FORM_PLUGIN_DIR . 'includes/Container.php';
		$instance = new SearchWPModalFormContainer();
	}

	return $instance;
}

searchwp_modal_form()
	->incl( 'Plugin.php' )
	->register( 'SearchWP_Modal_Form' )
	->setup();
