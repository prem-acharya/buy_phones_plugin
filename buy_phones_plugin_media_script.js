jQuery(document).ready(function($) {
    $('#select-image-button').on('click', function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        })
        .on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#image-id-input').val(attachment.id);
            $('#image-preview').html('<img src="' + attachment.url + '" style="max-width:100px;max-height:100px;" />');
        })
        .open();
    });
});