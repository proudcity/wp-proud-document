/**
 * Callback function for the 'click' event of the 'Set Footer Image'
 * anchor in its meta box.
 *
 * Displays the media uploader for selecting an image.
 *
 * @since 0.1.0
 */alert('adsf');
function renderMediaUploader() {
    var file_frame, image_data;
    if ( undefined !== file_frame ) {
        file_frame.open();
        return;
    }
    file_frame = wp.media.frames.file_frame = wp.media({
        frame:    'post',
        state:    'insert',
        multiple: false
    });
    file_frame.on( 'insert', function() {
 
        /**
         * We'll cover this in the next version.
         */
 
    });
    file_frame.open();
}
 
(function( $ ) {
    'use strict';
    $(function() {
        $( '#document-upload' ).on( 'click', function( evt ) {
            // Stop the anchor's default behavior
            evt.preventDefault();
            // Display the media uploader
            renderMediaUploader();
        });
    });
})( jQuery );