( function($) {

	'use strict';

	const app = {

		/**
		 * Init.
		 *
		 * @since 4.4.0
		 */
		init: () => {

			$( app.ready );
		},

		/**
		 * Document ready
		 *
		 * @since 4.4.0
		 */
		ready: () => {

			app.events();
		},

		/**
		 * Plugin events.
		 *
		 * @since 4.4.0
		 */
		events: () => {
			// Delegated binding off `document` so the handler survives
			// DOM replacement: WPFilters' AJAX refresh re-renders the
			// `.wpfilters-results` wrapper (which contains the load-more
			// button after the issue #969 fix), so a direct
			// `.click()` binding to the original button would be lost
			// after the first filter narrowing. With delegation, the
			// selector is re-matched at click time against whatever
			// `.swp-load-more-button` is currently in the DOM.
			$( document ).on(
				'click',
				'.swp-load-more-button',
				( e ) =>
				{
					e.preventDefault();

					const $button  = $( e.currentTarget );
					const query    = $button.data( 'query' );
					const engine   = $button.data( 'engine' );
					const page     = $button.data( 'page' );
					const template = $button.data( 'template' );
					const containerId = $button.data( 'container-id' );

					// Show spinner
					$button.find('.swp-load-more-spinner').removeClass('swp-hidden');

					// Disable button while loading
					$button.prop('disabled', true);

					const data = {
						action: 'swp_template_load_more',
						query: query,
						engine: engine,
						page: page,
						template: template,
						url_query_string: window.location.search ? window.location.search.substring( 1 ) : '',
					};

					$.post(
						searchwpTemplates.ajaxurl,
						data,
						( response ) =>
						{
							if ( response.success ) {

								const $container = $( `#${ containerId }` );

								// Check if we have any results to append
								if (response.data && response.data.html && response.data.html.trim() !== '') {
									$container.append( response.data.html );
									$button.data( 'page', response.data.page );

									// If we've reached the last page, remove the button
									if (response.data.page > response.data.max_pages) {
										$button.closest('.swp-load-more').hide();
									}
								} else {
									// No more results, remove the button
									$button.closest('.swp-load-more').hide();
								}
							} else {
								$button.closest('.swp-load-more').hide();
							}

							// Hide spinner and enable button regardless of outcome
							$button.find('.swp-load-more-spinner').addClass('swp-hidden');
							$button.prop('disabled', false);
						}
					);
				}
			);
		},
	};

	app.init();

	window.searchwp = window.searchwp || {};

	window.searchwp.searchTemplates = app;

}( jQuery ) );
