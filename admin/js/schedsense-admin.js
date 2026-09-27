( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-schedsense-copy]' );
		var target;
		var status;

		if ( ! button ) {
			return;
		}

		target = document.getElementById( button.getAttribute( 'data-schedsense-copy' ) );
		if ( ! target ) {
			return;
		}

		status = document.createElement( 'span' );
		status.className = 'schedsense-copy-status';
		status.setAttribute( 'role', 'status' );

		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( target.value ).then( function () {
				status.textContent = schedsense_admin.copied;
			}, function () {
				status.textContent = schedsense_admin.copyFailed;
				target.focus();
				target.select();
			} );
		} else {
			target.focus();
			target.select();
			status.textContent = document.execCommand( 'copy' ) ? schedsense_admin.copied : schedsense_admin.copyFailed;
		}

		if ( button.nextElementSibling && button.nextElementSibling.classList.contains( 'schedsense-copy-status' ) ) {
			button.nextElementSibling.remove();
		}
		button.insertAdjacentElement( 'afterend', status );
	} );

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;
		var message;

		if ( ! form.matches || ! form.matches( '.schedsense-delete-form' ) ) {
			return;
		}

		message = form.getAttribute( 'data-schedsense-confirm' );
		if ( message && ! window.confirm( message ) ) {
			event.preventDefault();
		}
	}, true );
}() );
