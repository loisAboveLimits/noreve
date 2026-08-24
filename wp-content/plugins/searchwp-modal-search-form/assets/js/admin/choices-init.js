/* global Choices */

( function () {
    'use strict';

    const app = {
        /**
         * Init.
         *
         * @since 0.5.6
         */
        init: () => {
            if ( document.readyState === 'loading' ) {
                document.addEventListener( 'DOMContentLoaded', app.ready );
                return;
            }

            app.ready();
        },

        /**
         * Document ready
         *
         * @since 0.5.6
         */
        ready: () => {
            app.events();
        },

        /**
         * Events.
         *
         * @since 0.5.6
         */
        events: () => {
            document.querySelectorAll( 'select.swp-choicesjs-select' ).forEach( app.initChoices );
        },

        /**
         * Init choices on select element.
         *
         * @since 0.5.6
         */
        initChoices: ( el ) => {
            if ( typeof Choices === 'undefined' ) {
                return;
            }

            const args = {
                searchEnabled: false,
                shouldSort: false,
                removeItemButton: false,
				allowHTML: false,
            };

            // Attach the Choices object to an element for easy access.
            el.data           = el.data || {};
            el.data.choicesjs = new Choices( el, args );
        },
    };

    app.init();

})();
