( function() {
	'use strict';

	var config = window.ventraConnectSlPasskeysProfile || {};

	function getMessage( key, fallback ) {
		return typeof config[ key ] === 'string' && config[ key ] ? config[ key ] : fallback;
	}

	function base64UrlToArrayBuffer( value ) {
		var normalized;
		var padding;
		var binary;
		var bytes;
		var index;

		if ( 'string' !== typeof value || ! value ) {
			throw new Error( 'Invalid passkey data received from the server.' );
		}

		normalized = value.replace( /-/g, '+' ).replace( /_/g, '/' );

		if ( /[^A-Za-z0-9+/=]/.test( normalized ) ) {
			throw new Error( 'Invalid passkey data received from the server.' );
		}

		padding = normalized.length % 4;

		if ( padding ) {
			normalized += '='.repeat( 4 - padding );
		}

		binary = window.atob( normalized );
		bytes = new Uint8Array( binary.length );

		for ( index = 0; index < binary.length; index += 1 ) {
			bytes[ index ] = binary.charCodeAt( index );
		}

		return bytes.buffer;
	}

	function arrayBufferToBase64Url( value ) {
		var bytes = value instanceof ArrayBuffer ? new Uint8Array( value ) : new Uint8Array( value.buffer || [] );
		var binary = '';

		bytes.forEach( function( byte ) {
			binary += String.fromCharCode( byte );
		} );

		return window.btoa( binary ).replace( /\+/g, '-' ).replace( /\//g, '_' ).replace( /=+$/g, '' );
	}

	function prepareCreationOptions( publicKey ) {
		var options = Object.assign( {}, publicKey );

		options.challenge = base64UrlToArrayBuffer( options.challenge );

		if ( options.user && options.user.id ) {
			options.user = Object.assign( {}, options.user, {
				id: base64UrlToArrayBuffer( options.user.id )
			} );
		}

		if ( Array.isArray( options.excludeCredentials ) ) {
			options.excludeCredentials = options.excludeCredentials.map( function( credential ) {
				var preparedCredential = Object.assign( {}, credential );

				if ( preparedCredential.id ) {
					preparedCredential.id = base64UrlToArrayBuffer( preparedCredential.id );
				}

				return preparedCredential;
			} );
		}

		return options;
	}

	function serializeCredentialForServer( credential ) {
		var serialized = {
			id: credential.id ? String( credential.id ) : '',
			rawId: credential.rawId ? arrayBufferToBase64Url( credential.rawId ) : '',
			type: credential.type ? String( credential.type ) : '',
			response: {
				clientDataJSON: credential.response && credential.response.clientDataJSON ? arrayBufferToBase64Url( credential.response.clientDataJSON ) : '',
				attestationObject: credential.response && credential.response.attestationObject ? arrayBufferToBase64Url( credential.response.attestationObject ) : ''
			}
		};

		if ( credential.response && typeof credential.response.getTransports === 'function' ) {
			serialized.response.transports = credential.response.getTransports();
		}

		if ( typeof credential.authenticatorAttachment === 'string' && credential.authenticatorAttachment ) {
			serialized.authenticatorAttachment = credential.authenticatorAttachment;
		}

		if ( typeof credential.getClientExtensionResults === 'function' ) {
			serialized.clientExtensionResults = credential.getClientExtensionResults();
		}

		return serialized;
	}

	function setStatus( statusElement, message, state ) {
		if ( ! statusElement ) {
			return;
		}

		statusElement.textContent = message || '';
		statusElement.hidden = ! message;
		statusElement.setAttribute( 'data-state', state || 'info' );
		statusElement.classList.remove( 'is-info', 'is-success', 'is-error', 'is-loading' );

		if ( state ) {
			statusElement.classList.add( 'is-' + state );
		}
	}

	function isSupported() {
		return (
			typeof window.PublicKeyCredential !== 'undefined' &&
			navigator.credentials &&
			typeof navigator.credentials.create === 'function'
		);
	}

	function isNoPasskeyBrowserError( error ) {
		var errorName = error && error.name ? String( error.name ) : '';
		var message = error && error.message ? String( error.message ) : '';

		return (
			'NotAllowedError' === errorName ||
			'AbortError' === errorName ||
			/security key|passkey|credential creation was canceled|timed out/i.test( message )
		);
	}

	function postAjax( payload ) {
		var requestBody = new window.URLSearchParams();

		Object.keys( payload ).forEach( function( key ) {
			requestBody.append( key, payload[ key ] );
		} );

		return window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: requestBody.toString()
		} ).then( function( response ) {
			return response.json().catch( function() {
				return {
					success: false,
					data: {
						message: getMessage( 'addFailedMessage', 'Passkey creation was cancelled or failed.' )
					}
				};
			} );
		} );
	}

	function getStatusElement( wrapper ) {
		return wrapper ? wrapper.querySelector( '.ventraconnect-sl-passkeys-status' ) : null;
	}

	function toggleButtons( wrapper, disabled ) {
		var buttons;

		if ( ! wrapper ) {
			return;
		}

		buttons = wrapper.querySelectorAll( '.ventraconnect-sl-passkeys-add-button, .ventraconnect-sl-passkeys-remove-button' );
		buttons.forEach( function( button ) {
			button.disabled = !! disabled;
		} );
	}

	function handleAddButtonClick( button ) {
		var wrapper = button.closest( '.ventraconnect-sl-passkeys-manage' );
		var statusElement = getStatusElement( wrapper );
		var serializedCredential;

		if ( ! isSupported() ) {
			setStatus( statusElement, getMessage( 'unsupportedMessage', 'This browser or device does not support passkeys.' ), 'error' );
			return;
		}

		toggleButtons( wrapper, true );
		setStatus( statusElement, getMessage( 'addLoadingMessage', 'Preparing passkey registration...' ), 'loading' );

		postAjax( {
			action: config.registrationOptionsAction,
			nonce: config.registrationOptionsNonce
		} )
			.then( function( payload ) {
				var publicKey;
				var message;

				if ( ! payload || ! payload.success || ! payload.data || ! payload.data.options || ! payload.data.options.publicKey ) {
					message = payload && payload.data && payload.data.message ? payload.data.message : getMessage( 'addFailedMessage', 'Passkey creation was cancelled or failed.' );
					throw new Error( message );
				}

				publicKey = prepareCreationOptions( payload.data.options.publicKey );
				setStatus( statusElement, getMessage( 'addPromptMessage', 'Follow your browser or device prompts to create a passkey.' ), 'loading' );

				return navigator.credentials.create( {
					publicKey: publicKey
				} );
			} )
			.then( function( credential ) {
				serializedCredential = serializeCredentialForServer( credential );

				return postAjax( {
					action: config.verifyRegistrationAction,
					nonce: config.verifyRegistrationNonce,
					credential: window.JSON.stringify( serializedCredential )
				} );
			} )
			.then( function( payload ) {
				var message;

				if ( ! payload || ! payload.success ) {
					message = payload && payload.data && payload.data.message ? payload.data.message : getMessage( 'addFailedMessage', 'Passkey creation was cancelled or failed.' );
					throw new Error( message );
				}

				setStatus( statusElement, payload.data.message || getMessage( 'addSuccessReloadMessage', 'Passkey registered successfully. Updating your passkey list...' ), 'success' );
				window.setTimeout( function() {
					window.location.reload();
				}, 300 );
			} )
			.catch( function( error ) {
				var message = error && error.message ? error.message : getMessage( 'addFailedMessage', 'Passkey creation was cancelled or failed.' );

				if ( isNoPasskeyBrowserError( error ) ) {
					message = getMessage( 'addCancelledMessage', 'Passkey setup was cancelled or timed out. Please try again when you are ready.' );
				}

				setStatus( statusElement, message, 'error' );
			} )
			.finally( function() {
				toggleButtons( wrapper, false );
			} );
	}

	function handleRemoveButtonClick( button ) {
		var wrapper = button.closest( '.ventraconnect-sl-passkeys-manage' );
		var statusElement = getStatusElement( wrapper );
		var passkeyId = button.getAttribute( 'data-passkey-id' ) || '';

		if ( ! passkeyId ) {
			setStatus( statusElement, getMessage( 'removeFailedMessage', 'The passkey could not be removed.' ), 'error' );
			return;
		}

		if ( ! window.confirm( getMessage( 'removeConfirmMessage', 'Remove this passkey?' ) ) ) {
			return;
		}

		toggleButtons( wrapper, true );
		setStatus( statusElement, getMessage( 'removeLoadingMessage', 'Removing passkey...' ), 'loading' );

		postAjax( {
			action: config.removePasskeyAction,
			nonce: config.removePasskeyNonce,
			passkey_id: passkeyId
		} )
			.then( function( payload ) {
				var message;

				if ( ! payload || ! payload.success ) {
					message = payload && payload.data && payload.data.message ? payload.data.message : getMessage( 'removeFailedMessage', 'The passkey could not be removed.' );
					throw new Error( message );
				}

				setStatus( statusElement, payload.data.message || getMessage( 'removeSuccessReloadMessage', 'Passkey removed. Updating your passkey list...' ), 'success' );
				window.setTimeout( function() {
					window.location.reload();
				}, 300 );
			} )
			.catch( function( error ) {
				setStatus(
					statusElement,
					error && error.message ? error.message : getMessage( 'removeFailedMessage', 'The passkey could not be removed.' ),
					'error'
				);
			} )
			.finally( function() {
				toggleButtons( wrapper, false );
			} );
	}

	function init() {
		var wrappers = document.querySelectorAll( '.ventraconnect-sl-passkeys-manage[data-context="wp_profile_passkeys"]' );

		wrappers.forEach( function( wrapper ) {
			var addButton = wrapper.querySelector( '.ventraconnect-sl-passkeys-add-button' );
			var removeButtons = wrapper.querySelectorAll( '.ventraconnect-sl-passkeys-remove-button' );

			if ( addButton ) {
				addButton.addEventListener( 'click', function( event ) {
					event.preventDefault();
					handleAddButtonClick( addButton );
				} );
			}

			removeButtons.forEach( function( removeButton ) {
				removeButton.addEventListener( 'click', function( event ) {
					event.preventDefault();
					handleRemoveButtonClick( removeButton );
				} );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
