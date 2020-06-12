var eventForm = {
    previousLocations: []
};

function setupEventForm() {

    // This is only applicable if a new event takes place on multiple dates
    var seriesTitleInput = $('#EventSeriesTitle');
    if (!seriesTitleInput.is(':visible')) {
        seriesTitleInput.removeAttr('required');
    }
    $('#add_end_time').click(function (event) {
        event.preventDefault();
        $('#eventform_hasendtime').show();
        $('#eventform_noendtime').hide();
        $('#eventform_hasendtime_boolinput').val('1');

        // Pre-select an end time one hour from the start time
        const timeStartHour = document.querySelector('select[name="time_start[hour]"]');
        const timeEndHour = document.querySelector('select[name="time_end[hour]"]');
        const timeStartMinute = document.querySelector('select[name="time_start[minute]"]');
        const timeEndMinute = document.querySelector('select[name="time_end[minute]"]');
        const timeStartMeridian = document.querySelector('select[name="time_start[meridian]"]');
        const timeEndMeridian = document.querySelector('select[name="time_end[meridian]"]');
        timeEndHour.selectedIndex = (timeStartHour.selectedIndex === timeStartHour.childElementCount - 1)
            ? 0
            : timeStartHour.selectedIndex + 1;
        timeEndMinute.selectedIndex = timeStartMinute.selectedIndex;
        timeEndMeridian.selectedIndex = (timeEndHour.selectedIndex === timeEndHour.childElementCount - 1)
            ? (timeStartMeridian.selectedIndex === 0 ? 1 : 0)
            : timeStartMeridian.selectedIndex;

        timeEndHour.focus();
    });
    $('#remove_end_time').click(function (event) {
        event.preventDefault();
        $('#eventform_noendtime').show();
        $('#eventform_hasendtime').hide();
        $('#eventform_hasendtime_boolinput').val('0');
    });
    if ($('#eventform_hasendtime_boolinput').val() === '1') {
        $('#eventform_hasendtime').show();
        $('#eventform_noendtime').hide();
    }
    setupLocationAutocomplete();
    setupAddressLookup();

    $('#series_editing_options').find('input[type=radio]').click(function () {
        if ($(this).val() !== '0') {
            $('#series_editing_warning').slideDown(300);
        } else {
            $('#series_editing_warning').slideUp(300);
        }
    });
    if ($('#EventUpdateSeries0').is(':checked')) {
        $('#series_editing_warning').hide();
    }

    $('#location_tips').popover({
        content: function () {
            return $('#location-tips-content').html();
        },
        html: true,
        title: 'Tips for Ball State locations'
    });

    var form = $('#EventForm').first();
    form.submit(function () {
        if ($('#datepicker_hidden').val() === '') {
            alert('Please select a date.');
            return false;
        }
        var description = CKEDITOR.instances.EventDescription.getData();
        if (description === '' || description === null) {
            alert('Please enter a description of this event.');
            return false;
        }

        return true;
    });

    $('#tag-rules-button').popover({
        content: function () {
            return $('#tag-rules-content').html();
        },
        html: true,
        title: 'Rules for creating new tags'
    });

    $('#image-help-button').popover({
        content: function () {
            return $('#image-help-content').html();
        },
        html: true,
        title: 'Images'
    });

    $('#example_selectable_tag').tooltip().click(function (event) {
        event.preventDefault();
    });

    const handleChangeEventType = function () {
        const virtualButton = document.querySelector('input[name="location_medium"][value="virtual"]');
        const isVirtual = virtualButton.checked;
        const locationNameField = document.getElementById('location');
        const addressHeader = document.querySelector('#eventform_address > label');
        const addressField = document.getElementById('EventAddress');
        const locationDetailsField = document.getElementById('location-details');
        const locationRow = document.getElementById('location-row');

        if (isVirtual) {
            locationNameField.value = 'Virtual Event';
            addressHeader.textContent = 'URL';
            addressField.placeholder = 'https://';
            addressField.setAttribute('type', 'url');
            addressField.required = true;
            locationDetailsField.parentElement.style.display = 'none';
            locationRow.style.display = 'none';

            return;
        }

        if (locationNameField.value === 'Virtual Event') {
            locationNameField.value = '';
        }
        addressHeader.textContent = 'Address';
        addressField.placeholder = '';
        addressField.setAttribute('type', 'text');
        addressField.required = false;
        locationDetailsField.parentElement.style.display = 'block';
        locationRow.style.display = 'flex';
    };
    const options = document.querySelectorAll('input[name="location_medium"]');
    for (let x = 0; x < options.length; x++) {
        options[x].addEventListener('click', handleChangeEventType)
    }
    handleChangeEventType();
}

function setupLocationAutocomplete() {
    if (eventForm.previousLocations.length === 0) {
        return;
    }
    const locationFieldId = 'location';
    const resultsContainerId = locationFieldId + '-results';

    new autoComplete({
        data: {
            src: async () => {
                const query = document.getElementById(locationFieldId).value.trim();
                if (query === '') {
                    return [];
                }
                return eventForm.previousLocations;
            },
            cache: false,
            key: ['label'],
        },
        selector: '#' + locationFieldId,
        threshold: 2,
        debounce: 100,
        resultsList: {
            render: true,
            container: source => {
                source.setAttribute('id', resultsContainerId);
                document.getElementById(locationFieldId).addEventListener('autoComplete', function (event) {
                    function hideSearchResults() {
                        const searchResults = document.getElementById(resultsContainerId);
                        while (searchResults.firstChild) {
                            searchResults.removeChild(searchResults.firstChild);
                        }
                        document.removeEventListener('click', hideSearchResults);
                    }

                    document.addEventListener('click', hideSearchResults);
                })
            },
            destination: document.getElementById(locationFieldId),
            position: 'afterend',
            element: 'ul'
        },
        searchEngine: 'strict',
        maxResults: 6,
        highlight: true,
        resultItem: {
            content: (data, source) => {
                source.innerHTML = data.match;
            },
            element: 'li'
        },
        noResults: () => {
        },
        onSelection: feedback => {
            // Update location name
            document.getElementById(locationFieldId).value = feedback.selection.value.label;

            // Update address
            document.getElementById('EventAddress').value = feedback.selection.value.value;
        }
    });
}

function setupAddressLookup() {
    $('#location').change(function () {
        var locationField = $(this);
        var locationName = locationField.val();
        var addressField = $('#EventAddress');

        // Take no action if the address has already been entered
        if (addressField.val() !== '') {
            return;
        }

        // Take no action if location name is blank
        if (locationName === '') {
            return;
        }

        // Attempt to look up address from the previousLocations object
        var matches = jQuery.grep(eventForm.previousLocations, function (locationObj) {
            return locationObj.label === locationName;
        });
        if (matches.length > 0) {
            addressField.val(matches[0].value);
        }
    });
}

function setupDatepickerMultiple(defaultDate, preselectedDates) {
    var options = {
        defaultDate: defaultDate,
        altField: '#datepicker_hidden',
        onSelect: function () {
            var dates = $('#datepicker').multiDatesPicker('getDates');
            if (dates.length > 1) {
                showSeriesRow();
                var seriesTitleField = $('#EventSeriesTitle');
                seriesTitleField.attr('required', 'required');
                console.log(seriesTitleField.val());
                if (seriesTitleField.val() === '') {
                    seriesTitleField.val($('#EventTitle').val());
                }
            } else {
                hideSeriesRow();
                $('#EventSeriesTitle').removeAttr('required');
            }
        }
    };
    if (preselectedDates.length > 0) {
        options.addDates = preselectedDates;
    }
    $('#datepicker').multiDatesPicker(options);
}

function showSeriesRow() {
    var row = $('#series_row');
    if (row.is(':visible')) {
        return;
    }
    if (row.children().children('div.slide_helper').length === 0) {
        row.children().wrapInner('<div class="slide_helper" />');
    }
    var slideHelpers = row.find('div.slide_helper');
    slideHelpers.hide();
    row.show();
    slideHelpers.slideDown(300);
}

function hideSeriesRow() {
    var row = $('#series_row');
    if (row.children().children('div.slide_helper').length === 0) {
        row.children().wrapInner('<div class="slide_helper" />');
    }
    row.find('div.slide_helper').slideUp(300, function () {
        row.hide();
    });
}

function setupDatepickerSingle(defaultDate) {
    $('#datepicker').datepicker({
        defaultDate: defaultDate,
        onSelect: function (date) {
            $('#datepicker_hidden').val(date);
        }
    });
}
