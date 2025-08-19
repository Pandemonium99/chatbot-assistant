jQuery(document).ready(function($) {
    // Initialize all WordPress color pickers on the settings page.
    $('.ai-chatbot-color-picker').wpColorPicker();

    // Logic to toggle the visibility of the specific pages checklist.
    const showOnAllCheckbox = $('.show-on-all');
    const pagesChecklist = $('#pages-checklist');

    function togglePagesChecklist() {
        if (showOnAllCheckbox.is(':checked')) {
            // If "Show on all pages" is checked, hide the list and uncheck all pages.
            pagesChecklist.hide();
            pagesChecklist.find('input[type="checkbox"]').prop('checked', false);
        } else {
            // If unchecked, show the list of pages.
            pagesChecklist.show();
        }
    }

    // Bind the change event and trigger it on page load to set the initial state.
    showOnAllCheckbox.on('change', togglePagesChecklist).trigger('change');
});
