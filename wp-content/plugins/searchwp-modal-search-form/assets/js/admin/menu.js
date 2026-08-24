/**
 * SearchWP Modal Search Form Admin Menu.
 *
 * @since 0.5.0
 */

'use strict';

var SearchWPModalFormAdminMenu = window.SearchWPModalFormAdminMenu || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 0.5.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 0.5.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 0.5.0
		 */
		ready: function() {

			app.addParamsToUpgradeLink();
		},

		/**
		 * Add 'target="_blank"' and 'rel="noopener noreferrer"' to the "Upgrade to Pro" menu link.
		 *
		 * @since 0.5.0
		 */
		addParamsToUpgradeLink: function() {

			$( 'a.searchwp-sidebar-upgrade-pro' )
				.attr( 'target', '_blank' )
				.attr( 'rel', 'noopener noreferrer' );
		},
	};

	return app;

}( document, window, jQuery ) );

// Initialize.
SearchWPModalFormAdminMenu.init();
