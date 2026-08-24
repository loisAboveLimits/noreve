/* global searchwp_modal_form_admin_notices */

/**
 * SearchWP Modal Search Form Dismissible Notices.
 *
 * @since 0.5.0
 */

'use strict';

var SearchWPModalFormAdminNotices = window.SearchWPModalFormAdminNotices || ( function( document, window, $ ) {

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

			app.events();
		},

		/**
		 * Dismissible notices events.
		 *
		 * @since 0.5.0
		 */
		events: function() {

			$( document ).on(
				'click',
				'.searchwp-modal-form-notice .notice-dismiss, .searchwp-modal-form-notice .searchwp-modal-form-notice-dismiss',
				app.dismissNotice
			);
		},

		/**
		 * Dismiss notice event handler.
		 *
		 * @since 0.5.0
		 *
		 * @param {object} e Event object.
		 * */
		dismissNotice: function( e ) {

			$.post( searchwp_modal_form_admin_notices.ajax_url, {
				action: 'searchwp_modal_form_notice_dismiss',
				nonce:   searchwp_modal_form_admin_notices.nonce,
				id: 	 ( $( this ).closest( '.searchwp-modal-form-notice' ).attr( 'id' ) || '' ).replace( 'searchwp-modal-form-notice-', '' ),
			} );
		},
	};

	return app;

}( document, window, jQuery ) );

// Initialize.
SearchWPModalFormAdminNotices.init();
