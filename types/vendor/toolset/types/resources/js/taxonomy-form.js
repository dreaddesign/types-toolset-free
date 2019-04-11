/**
 *
 * Taxonomies form JS
 *
 *
 */

jQuery( document ).ready( function( $ ) {
    $( '.wpcf-tax-form' ).on( 'submit', function() {
        return $( this ).wpcfProveSlug();
    } );
} );
