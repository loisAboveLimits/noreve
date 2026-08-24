( function() {
	'use strict';

	var config = window.ventraConnectSlPasskeysPublic || {};

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

	function isSupported() {
		return (
			typeof window.PublicKeyCredential !== 'undefined' &&
			navigator.credentials &&
			typeof navigator.credentials.get === 'function' &&
			typeof navigator.credentials.create === 'function'
		);
	}

	function isNoPasskeyBrowserError( error ) {
		var errorName = error && error.name ? String( error.name ) : '';
		var message = error && error.message ? String( error.message ) : '';

		return (
			'NotAllowedError' === errorName ||
			'AbortError' === errorName ||
			/security key|passkey|credential creation was canceled|timed out|no available authenticator/i.test( message )
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
						message: getMessage( 'publicRegisterFailedMessage', 'Passkey request failed.' )
					}
				};
			} );
		} );
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

	function getStatusElement( wrapper, selector ) {
		var statusElement;

		if ( ! wrapper ) {
			return null;
		}

		statusElement = selector ? wrapper.querySelector( selector ) : null;

		if ( statusElement ) {
			return statusElement;
		}

		return wrapper.querySelector( '.ventraconnect-sl-passkeys-public-status' );
	}

	function toggleButtons( wrapper, disabled ) {
		var buttons;

		if ( ! wrapper ) {
			return;
		}

		buttons = wrapper.querySelectorAll(
			'.ventraconnect-sl-passkeys-login-button, .ventraconnect-sl-passkeys-register-button, .ventraconnect-sl-passkeys-verified-button'
		);

		buttons.forEach( function( button ) {
			button.disabled = !! disabled;
		} );
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

	function prepareRequestOptions( publicKey ) {
		var options = Object.assign( {}, publicKey );

		options.challenge = base64UrlToArrayBuffer( options.challenge );

		if ( Array.isArray( options.allowCredentials ) ) {
			options.allowCredentials = options.allowCredentials.map( function( credential ) {
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
		var response = credential.response || {};
		var serialized = {
			id: credential.id ? String( credential.id ) : '',
			rawId: credential.rawId ? arrayBufferToBase64Url( credential.rawId ) : '',
			type: credential.type ? String( credential.type ) : '',
			response: {}
		};

		if ( response.clientDataJSON ) {
			serialized.response.clientDataJSON = arrayBufferToBase64Url( response.clientDataJSON );
		}

		if ( response.attestationObject ) {
			serialized.response.attestationObject = arrayBufferToBase64Url( response.attestationObject );
		}

		if ( response.authenticatorData ) {
			serialized.response.authenticatorData = arrayBufferToBase64Url( response.authenticatorData );
		}

		if ( response.signature ) {
			serialized.response.signature = arrayBufferToBase64Url( response.signature );
		}

		if ( response.userHandle ) {
			serialized.response.userHandle = arrayBufferToBase64Url( response.userHandle );
		}

		if ( typeof response.getTransports === 'function' ) {
			serialized.response.transports = response.getTransports();
		}

		if ( typeof credential.authenticatorAttachment === 'string' && credential.authenticatorAttachment ) {
			serialized.authenticatorAttachment = credential.authenticatorAttachment;
		}

		if ( typeof credential.getClientExtensionResults === 'function' ) {
			serialized.clientExtensionResults = credential.getClientExtensionResults();
		}

		return serialized;
	}

	function getRedirectValue( wrapper ) {
		var value = wrapper && wrapper.getAttribute ? wrapper.getAttribute( 'data-redirect' ) : '';

		return value ? String( value ) : '';
	}

	function getRegisterEmailField( wrapper ) {
		var form = wrapper ? wrapper.closest( 'form' ) : null;

		if ( form ) {
			return form.querySelector( 'input[type="email"], input[name="user_email"], input[id="user_email"]' );
		}

		return document.querySelector( 'form#registerform input[name="user_email"], form#registerform input[type="email"]' );
	}

	function getRegisterUsernameField( wrapper ) {
		var form = wrapper ? wrapper.closest( 'form' ) : null;

		if ( form ) {
			return form.querySelector( 'input[name="user_login"], input[id="user_login"]' );
		}

		return document.querySelector( 'form#registerform input[name="user_login"]' );
	}

	function handleDiscoverableLogin( button ) {
		var wrapper = button.closest( '.ventraconnect-sl-passkeys-wp-login' );
		var statusElement = getStatusElement( wrapper, '.ventraconnect-sl-passkeys-wp-login-runtime-message' );
		var redirectUrl = getRedirectValue( wrapper );

		if ( ! isSupported() ) {
			setStatus( statusElement, getMessage( 'unsupportedMessage', 'This browser or device does not support passkeys.' ), 'error' );
			return;
		}

		toggleButtons( wrapper, true );
		setStatus( statusElement, getMessage( 'discoverableAuthLoadingMessage', 'Preparing passkey login...' ), 'loading' );

		postAjax( {
			action: config.discoverableAuthenticationOptionsAction,
			nonce: config.discoverableAuthenticationOptionsNonce
		} )
			.then( function( payload ) {
				var publicKey;
				var message;

				if ( ! payload || ! payload.success || ! payload.data || ! payload.data.options || ! payload.data.options.publicKey ) {
					message = payload && payload.data && payload.data.message ? payload.data.message : getMessage( 'discoverableAuthFailedMessage', 'Passkey login failed. Please try again or use another sign-in method.' );
					throw new Error( message );
				}

				publicKey = prepareRequestOptions( payload.data.options.publicKey );
				setStatus( statusElement, getMessage( 'discoverableAuthPromptMessage', 'Follow your browser or device prompts to continue.' ), 'loading' );

				return navigator.credentials.get( {
					publicKey: publicKey
				} );
			} )
			.then( function( credential ) {
				setStatus( statusElement, getMessage( 'discoverableAuthLoadingMessage', 'Preparing passkey login...' ), 'loading' );

				return postAjax( {
					action: config.discoverableVerifyAuthenticationAction,
					nonce: config.discoverableVerifyAuthenticationNonce,
					assertion: window.JSON.stringify( serializeCredentialForServer( credential ) ),
					context: 'wp_login',
					redirect_url: redirectUrl
				} );
			} )
			.then( function( payload ) {
				var message;

				if ( ! payload || ! payload.success || ! payload.data ) {
					message = payload && payload.data && payload.data.message ? payload.data.message : getMessage( 'discoverableVerifyFailedMessage', 'Passkey login could not be verified. Please try again or use another sign-in method.' );
					throw new Error( message );
				}

				setStatus( statusElement, payload.data.message || getMessage( 'discoverableAuthSuccessMessage', 'Passkey login successful. Redirecting...' ), 'success' );

				window.setTimeout( function() {
					window.location.href = payload.data.redirect_url || window.location.href;
				}, 200 );
			} )
			.catch( function( error ) {
				var message = error && error.message ? error.message : getMessage( 'discoverableAuthFailedMessage', 'Passkey login failed. Please try again or use another sign-in method.' );

				if ( isNoPasskeyBrowserError( error ) ) {
					message = getMessage( 'discoverableNoPasskeyMessage', 'No passkey was found or selected. Use another sign-in method, then add a passkey from your profile.' );
				}

				setStatus( statusElement, message, 'error' );
			} )
			.finally( function() {
				toggleButtons( wrapper, false );
			} );
	}

	function handleRegisterStart( button ) {
		var wrapper = button.closest( '.ventraconnect-sl-passkeys-wp-register' );
		var statusElement = getStatusElement( wrapper, '.ventraconnect-sl-passkeys-wp-register-runtime-message' );
		var emailField = getRegisterEmailField( wrapper );
		var usernameField = getRegisterUsernameField( wrapper );
		var email = emailField && emailField.value ? String( emailField.value ).trim() : '';
		var username = usernameField && usernameField.value ? String( usernameField.value ).trim() : '';
		var redirectUrl = getRedirectValue( wrapper );

		if ( ! email ) {
			setStatus( statusElement, getMessage( 'publicRegisterEmailRequiredMessage', 'Please enter your email address.' ), 'error' );
			if ( emailField && typeof emailField.focus === 'function' ) {
				emailField.focus();
			}
			return;
		}

		if ( ! isSupported() ) {
			setStatus( statusElement, getMessage( 'unsupportedMessage', 'This browser or device does not support passkeys.' ), 'error' );
			return;
		}

		toggleButtons( wrapper, true );
		setStatus( statusElement, getMessage( 'publicRegisterLoadingMessage', 'Preparing passkey setup...' ), 'loading' );

		postAjax( {
			action: config.startEmailVerificationAction,
			nonce: config.startEmailVerificationNonce,
			email: email,
			username: username,
			display_name: username,
			redirect_url: redirectUrl
		} )
			.then( function( payload ) {
				var message;

				if ( ! payload || ! payload.success || ! payload.data ) {
					message = payload && payload.data && payload.data.message ? payload.data.message : getMessage( 'publicRegisterFailedMessage', 'Passkey setup could not be completed. Please try again.' );
					throw new Error( message );
				}

				setStatus( statusElement, payload.data.message || getMessage( 'passkeyVerifyEmailToContinueMessage', 'Check your email to continue setting up your passkey.' ), 'success' );
			} )
			.catch( function( error ) {
				var message = error && error.message ? error.message : getMessage( 'publicRegisterFailedMessage', 'Passkey setup could not be completed. Please try again.' );

				setStatus( statusElement, message, 'error' );
			} )
			.finally( function() {
				toggleButtons( wrapper, false );
			} );
	}

	function handleVerifiedRegistration( button ) {
		var wrapper = button.closest( '.ventraconnect-sl-passkeys-verified-registration' );
		var statusElement = getStatusElement( wrapper, '.ventraconnect-sl-passkeys-verified-runtime-message' );
		var token = wrapper && wrapper.getAttribute ? String( wrapper.getAttribute( 'data-verification-token' ) || '' ) : '';

		if ( ! token ) {
			setStatus( statusElement, getMessage( 'passkeyVerificationInvalidMessage', 'This passkey verification link is invalid. Please start again.' ), 'error' );
			return;
		}

		if ( ! isSupported() ) {
			setStatus( statusElement, getMessage( 'unsupportedMessage', 'This browser or device does not support passkeys.' ), 'error' );
			return;
		}

		toggleButtons( wrapper, true );
		setStatus( statusElement, getMessage( 'verifiedPasskeyCreateLoadingMessage', 'Preparing passkey registration...' ), 'loading' );

		postAjax( {
			action: config.verifiedRegistrationOptionsAction,
			nonce: config.verifiedRegistrationOptionsNonce,
			verification_token: token
		} )
			.then( function( payload ) {
				var publicKey;
				var message;

				if ( ! payload || ! payload.success || ! payload.data || ! payload.data.options || ! payload.data.options.publicKey ) {
					message = payload && payload.data && payload.data.message ? payload.data.message : getMessage( 'publicRegisterFailedMessage', 'Passkey setup could not be completed. Please try again.' );
					throw new Error( message );
				}

				publicKey = prepareCreationOptions( payload.data.options.publicKey );
				setStatus( statusElement, getMessage( 'verifiedPasskeyCreatePromptMessage', 'Create a passkey for this account. Your device will save it securely so you can sign in without a password next time.' ), 'loading' );

				return navigator.credentials.create( {
					publicKey: publicKey
				} );
			} )
			.then( function( credential ) {
				return postAjax( {
					action: config.verifiedVerifyRegistrationAction,
					nonce: config.verifiedVerifyRegistrationNonce,
					verification_token: token,
					credential: window.JSON.stringify( serializeCredentialForServer( credential ) )
				} );
			} )
			.then( function( payload ) {
				var message;

				if ( ! payload || ! payload.success || ! payload.data ) {
					message = payload && payload.data && payload.data.message ? payload.data.message : getMessage( 'publicRegisterFailedMessage', 'Passkey setup could not be completed. Please try again.' );
					throw new Error( message );
				}

				setStatus( statusElement, payload.data.message || getMessage( 'verifiedPasskeyCreateSuccessMessage', 'Passkey registration successful. Redirecting...' ), 'success' );

				window.setTimeout( function() {
					window.location.href = payload.data.redirect_url || window.location.href;
				}, 200 );
			} )
			.catch( function( error ) {
				var message = error && error.message ? error.message : getMessage( 'publicRegisterFailedMessage', 'Passkey setup could not be completed. Please try again.' );

				if ( isNoPasskeyBrowserError( error ) ) {
					message = getMessage( 'publicRegisterCancelledMessage', 'Passkey setup was cancelled. No passkey was added. Please try again when you are ready.' );
				}

				setStatus( statusElement, message, 'error' );
			} )
			.finally( function() {
				toggleButtons( wrapper, false );
			} );
	}

	function init() {
		var loginButtons = document.querySelectorAll( '.ventraconnect-sl-passkeys-login-button' );
		var registerButtons = document.querySelectorAll( '.ventraconnect-sl-passkeys-register-button' );
		var verifiedButtons = document.querySelectorAll( '.ventraconnect-sl-passkeys-verified-button' );

		loginButtons.forEach( function( button ) {
			button.addEventListener( 'click', function( event ) {
				event.preventDefault();
				handleDiscoverableLogin( button );
			} );
		} );

		registerButtons.forEach( function( button ) {
			button.addEventListener( 'click', function( event ) {
				event.preventDefault();
				handleRegisterStart( button );
			} );
		} );

		verifiedButtons.forEach( function( button ) {
			button.addEventListener( 'click', function( event ) {
				event.preventDefault();
				handleVerifiedRegistration( button );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
