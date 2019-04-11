/*
 * Creates a collapsible mechanic by adding data-toolset-collapsible="parent-container" to the trigger.
 * On click the toggleClass( 'toolset-collapsible-closed' ) is used on the "parent-container".
 *
 * Limitation: The trigger must be inside the parent.
 *
 * Example of use:
 * <div class="toolset-anyname">
 *      <h3 data-toolset-collapsible=".toolset-anyname">Click this to collapse</h3>
 *      <div data-toolset-collapsible=".toolset-anyname">Another trigger if you want</div>
 *      <div class="toolset-collapsible-inside">
 *          Content, which should be collapsed.
 *      </div>
 *  </div>
 *
 */
function tooset_collapsible( $ ) {
    $( 'body' ).on( 'click', '[data-toolset-collapsible]', function() {
        var collapsibleContainer = $( this ).parents( $( this ).data( 'toolset-collapsible' ) ).first();

        if( collapsibleContainer.length ) {
            collapsibleContainer.toggleClass( 'toolset-collapsible-closed' )
        } else {
            console.log( 'Collapsible container could not be found.' );
        }
    } );
}

// make sure it's working like loaded at the end of document, even if it's wrongly loaded to the header
jQuery( document ).ready( function() {
    tooset_collapsible( jQuery );
} );