( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-qhm-copy]' );
		var target;
		var status;

		if ( ! button ) {
			return;
		}

		target = document.getElementById( button.getAttribute( 'data-qhm-copy' ) );
		if ( ! target ) {
			return;
		}

		status = document.createElement( 'span' );
		status.className = 'qhm-copy-status';
		status.setAttribute( 'role', 'status' );

		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( target.value ).then( function () {
				status.textContent = qhmAdmin.copied;
			}, function () {
				status.textContent = qhmAdmin.copyFailed;
				target.focus();
				target.select();
			} );
		} else {
			target.focus();
			target.select();
			status.textContent = document.execCommand( 'copy' ) ? qhmAdmin.copied : qhmAdmin.copyFailed;
		}

		if ( button.nextElementSibling && button.nextElementSibling.classList.contains( 'qhm-copy-status' ) ) {
			button.nextElementSibling.remove();
		}
		button.insertAdjacentElement( 'afterend', status );
	} );

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;
		var message;

		if ( ! form.matches || ! form.matches( '.qhm-delete-form' ) ) {
			return;
		}

		message = form.getAttribute( 'data-qhm-confirm' );
		if ( message && ! window.confirm( message ) ) {
			event.preventDefault();
		}
	}, true );
}() );
