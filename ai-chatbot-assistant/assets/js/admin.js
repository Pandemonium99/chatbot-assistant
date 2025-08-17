jQuery(document).ready(function($) {
    // Initialize all color pickers
    $('.ai-chatbot-color-picker').wpColorPicker();

    // Toggle visibility of the pages checklist
    $('.show-on-all').on('change', function() {
        if ($(this).is(':checked')) {
            $('#pages-checklist').hide();
            $('#pages-checklist input[type="checkbox"]').prop('checked', false);
        } else {
            $('#pages-checklist').show();
        }
    }).trigger('change');
});