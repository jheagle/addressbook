/**
 * Global / frequent use variables.
 */
var searchElements = $('.search-form .form-group').find('input, select, textarea'), // detect all inputs for searching
        contacts = $(), // global storage for contacts
        target = document.getElementById('spinner-container'), // create location for placing spinner
        spinner = new Spinner(); // initiate spinner for displaying loading time.
offset = 0, limit = 10000, contactSeq = -1;


/**
 * 
 * @param {type} param
 */
$(document).ready(function ()
{

    /**
     * Find length of event list
     */
    $.post('controllers/LogsController.php', function (data) {
        limit = data;
    });


    /**
     * All listeners associated with left pane - The Search fields.
     * Ordered as they appear in DOM
     */

    /*
     * Auto-load the full contact list with backspace in name search
     */
    $('.search-field').bind('keyup change', function () {
        if ($(this).val() === "") {
            $('.search-form').trigger('submit');
        }
    });

    /**
     * Actions on submit
     *
     * Ajax submit form, then retrieve results as a new contact list.
     * Build the options list from contacts.
     * If none found, offer to create new contact.
     */
    $('.search-form').submit(function () {
        spinner.spin();
        $('.search-phone').val(removePhoneFormat($('.search-phone').val()));
        target.appendChild(spinner.el);
        $.post($(this).attr('action'), $('.search-form').serialize(), function (data) {
            $('#contact-list').empty();
            if (data.length < 1) {
                $('#contact-list').html("<option value='-1'>Create New Contact</option>");
            }
            if (data.length === 1 && data[0].id === -1) {
                contacts.push(data[0]);
                $('#contact-list').html("<option value='-1'>Create Contact: " + data[0].first_name + " " + data[0].middle_name + " " + data[0].last_name + "</option>");
            } else {
                $.each(data, function (j, contact) {
                    $('#contact-list').append("<option value='" + contact.id + "'>" + contact.last_name + ", " + contact.first_name + " " + contact.middle_name + "</option>");
                });
            }
            if (!searchElements.attr('disabled'))
            {
                $('.more-filters').trigger('click'); // Close search form to display results
            }
            spinner.stop();
        }, 'json');
        return false;
    });

    /**
     * Toggle between search form and contact list.
     * When toggling, ensure button is aesthetically positioned.
     * Clear fields to reduce posted inputs.
     */
    $('.more-filters').click(function () {
        $(this).addClass("force-right");
        $('.search-form').trigger('reset');
        $('#search-options').slideToggle();
        $('#contact-list').slideToggle(function () {
            if (searchElements.attr('disabled')) {
                $(".more-filters").removeClass("force-right");
            }
        });
        if (searchElements.attr('disabled'))
        {
            searchElements.removeAttr('disabled');
        }
        else
        {
            searchElements.attr('disabled', 'disabled');
        }
        $(this).val($(this).val() === "\u25B2" ? "\u25BC" : "\u25B2");
    });

    /**
     * Respond to interaction with the contact list.
     * Display contact details when a contact has been selected.
     */
    $('#contact-list').on('click, change', function () {
        if ($('#contact-list option:selected').length) {
            contactSeq = $('#contact-list option:selected').val(); // Retrieve iduence of selected contact
            var hasContact = false;
            offset = 0;
            $('.left-pager').fadeOut();
            // check if contact is in local list
            $.each(contacts, function (j, contact)
            {
                if (contact.id === contactSeq)
                {
                    hasContact = true;
                    setContact(contact);
                    return;
                }
            });
            // search for contact if not stored locally 
            if (!hasContact) {
                $.post('controllers/AddressbookController.php', {contact_id: contactSeq}, function (contact) {
                    contacts.push(contact);
                    setContact(contact);
                    return;
                }, 'json');
            }
        }
    });

    /**
     * Import csv file
     * When clicked, create a temporary file input box and submit the file.
     */
    $("#import").click(function (e) {
        e.preventDefault();
        var fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.name = "csv-file";
        fileInput.className = "file-input";
        $('.search-form').append(fileInput);
        fileInput.click();
        fileInput.trigger('change');
        $('.file-input').remove();
    });

    /**
     * Export a csv representing the current list of contacts
     * 'Flatten' list of contacts to a single row each then export.
     */
    $("#export").click(function (e) {
        e.preventDefault();
        spinner.spin();
        target.appendChild(spinner.el);
        $.getJSON("resources/json_flatten.php", function (data) {
            var csv = genCSV(data);
            var uri = "data:text/csv;charset=utf-8," + escape(csv);
            var downloadLink = document.createElement("a");
            downloadLink.href = uri;
            downloadLink.download = "data.csv";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            spinner.stop();
        });
    });

    /*
     * Action for temporary file input field create by import
     */
    $('.search-form').on('change', '.file-input', function () {
        this.form.submit();
    });


    /**
     * All listeners associated with central content area - Create/Update area
     * Listed in order of appearance in the DOM
     */

    /**
     * Button for displaying a Create/Update form
     * Also resets some dynamic fields
     */
    $('.display-create').click(function () {
        $('.remove-address').trigger('click');
        $('.remove-phone').trigger('click');
        $('.form-info p').html('Fields marked with an asterisk (*) are required.');
        $(this).fadeOut(function () {
            $('.create-contact').fadeIn();
        });
        $('.form-info h4').html('');
    });

    /**
     * Opposite of display button, used to clear and close form
     * Fully resets and clears a Create/Update form and hides it.
     */
    $('.remove-display').click(function () {
        $('.form-info h4').html('');
        document.getElementById('contact-list').selectedIndex = -1;
        contactSeq = -1;
        $('.edit-form').trigger('reset');
        $('.contact-id').remove();
        $('.address-id').remove();
        $('.phone-id').remove();
        $('.success-status').removeClass('success-status');
        $('.error-status').removeClass('error-status');
        $('.error-msg').remove();
        $('.create-contact').fadeOut(function () {
            $('.display-create').fadeIn();
            $('.create-contact h2').html('Create New');
            $('.submit-create').val('Create');
            $('.create-contact').removeClass('update');
            $('.submit-delete').fadeOut();
            $('.address-code').removeClass('zip').addClass('postal');
            $('.address-code').attr('placeholder', 'Postal Code');
        });
    });

    /**
     * Removes the address that it is next to if there are more than one.
     */
    $('.address').on('.remove-address', 'click', function () {
        if ($('.address').length > 1) {
            $('.address').each(function (j) {
                var i = j + 1;
                $('.address-name', this).html('Address ' + i);
            });
            $(this).parent().detach();
        } else {
            $('.address:last .address-name').html('Address ' + $('.address').length);
        }
    });

    /**
     * Monitor state/province selection in order to update applicable fields for country specific validation
     * Change postal / zip code and country name based on selection.
     */
    $('.address').on('change', '.province', function () {
        var parent = $(this).closest('.address');
        if ($(':selected', this).parent().attr('label') === "Canada") {
            $('.address-code', parent).removeClass('zip').addClass('postal');
            $('.address-code', parent).attr('placeholder', 'Postal Code');
            $('.country', parent).val('Canada');
        } else {
            $('.address-code', parent).removeClass('postal').addClass('zip');
            $('.address-code', parent).attr('placeholder', 'Zip Code');
            $('.country', parent).val('United States');
        }
    });

    /**
     * Monitor country selection for changing country specific fields.
     * Default to Alberta / Alabama for province/state based on country selection
     */
    $('.address').on('change', '.country', function () {
        var parent = $(this).closest('.address');
        if ($(this).val() === "Canada") {
            $('.address-code', parent).removeClass('zip').addClass('postal');
            $('.address-code', parent).attr('placeholder', 'Postal Code');
            if ($('.province option:selected', parent).parent().attr('label') !== "Canada")
            {
                $(".province option[value='AB']", parent).prop('selected', true);
            }
        } else {
            $('.address-code', parent).removeClass('postal').addClass('zip');
            $('.address-code', parent).attr('placeholder', 'Zip Code');
            if ($('.province option:selected', parent).parent().attr('label') !== "United States")
            {
                $(".province option[value='AL']", parent).prop('selected', true);
            }
        }
    });

    /**
     * Create a copy of the first address as a new address section.
     * Add the same listeners as the first address section had.
     */
    $('.add-address').on('click', function () {
        $('.address').last().after($('.address').first().clone());
        $('.address:last .address-name').html('Address ' + $('.address').length);
        $('.address:last .address-id').remove();

        // Add remove this address listener
        $('.remove-address').on('click', function () {
            if ($('.address').length > 1) {
                $('.address').each(function (j) {
                    var i = j + 1;
                    $('.address-name', this).html('Address ' + i);
                });
                $(this).parent().detach();
            } else {
                $('.address:last .address-name').html('Address ' + $('.address').length);
            }
        });

        // Add province change listener
        $('.address').on('change', '.province', function () {
            var parent = $(this).closest('.address');
            if ($(':selected', this).parent().attr('label') === "Canada") {
                $('.address-code', parent).removeClass('zip').addClass('postal');
                $('.address-code', parent).attr('placeholder', 'Postal Code');
                $('.country', parent).val('Canada');
            } else {
                $('.address-code', parent).removeClass('postal').addClass('zip');
                $('.address-code', parent).attr('placeholder', 'Zip Code');
                $('.country', parent).val('United States');
            }
        });

        // Add country change listener
        $('.address').on('change', '.country', function () {
            var parent = $(this).closest('.address');
            if ($(this).val() === "Canada") {
                $('.address-code', parent).removeClass('zip').addClass('postal');
                $('.address-code', parent).attr('placeholder', 'Postal Code');
                if ($('.province option:selected', parent).parent().attr('label') !== "Canada")
                {
                    $(".province option[value='AB']", parent).prop('selected', true);
                }
            } else {
                $('.address-code', parent).removeClass('postal').addClass('zip');
                $('.address-code', parent).attr('placeholder', 'Zip Code');
                if ($('.province option:selected', parent).parent().attr('label') !== "United States")
                {
                    $(".province option[value='AL']", parent).prop('selected', true);
                }
            }
        });
    });

    /**
     * Display numerical only number while on focus
     */
    $('.phone-numbers, #search-options').on('focus', '.phone, .search-phone', function () {
        $(this).val(removePhoneFormat($(this).val()));
    });

    /**
     * Display formatted number when not in focus
     */
    $('.phone-numbers, #search-options').on('blur', '.phone, .search-phone', function () {
        $(this).val(addPhoneFormat($(this).val()));
    });

    /**
     * Remove the associated phone number section on button click
     */
    $('.phone-number').on('.remove-phone', 'click', function () {
        if ($('.phone-number').length > 1) {
            $(this).parent().parent().detach();
        }
    });

    /**
     * Add another phone number as a copy of the first phone section
     */
    $('.add-phone').on('click', function () {
        $('.phone-number').last().after($('.phone-number').first().clone());
        $('.phone-number:last .phone-id').remove();

        $('.remove-phone').on('click', function () {
            if ($('.phone-number').length > 1) {
                $(this).parent().parent().detach();
            }
        });
    });

    /**
     * Change view to warn user when they are about to click Delete
     */
    $('.submit-delete').hover(function () {
        $('.create-contact').addClass('delete')
    }, function () {
        $('.create-contact').removeClass('delete');
    }
    );

    /**
     * Respond to submision of Create/Update form
     * Submit via an Ajax post and return changes
     * Display results to user
     */
    $('.edit-form').submit(function (e) {
        e.preventDefault();
        if (validateSubmit()) {
            spinner.spin();
            target.appendChild(spinner.el);
            $.post($(this).attr('action'), $(this).serialize(), function (data) {
                $('#contact-list').empty();
                if (data.length < 1) {
                    $('#contact-list').html("<option>No Contacts Found</option>");
                }
                $.each(data, function (j, contact) {
                    $('#contact-list').append('<option value="' + contact.id + '">' + contact.last_name + ", " + contact.first_name + " " + contact.middle_name + '</option>');
                });
                contacts = $();
                spinner.stop();
                if ($('.submit-type').val() == 'Create') {
                    $('.remove-display').trigger('click');
                    $('.form-info h4').html('The contact has been created.');
                } else if ($('.submit-type').val() == 'Update') {
                    $('.form-info p').html('The contact has been updated.');
                } else if ($('.submit-type').val() == 'Delete') {
                    $('.remove-display').trigger('click');
                    $('.form-info h4').html('The contact has been deleted.');
                }
            }, 'json');
        }
        return false;
    });



    /**
     * All Listeners for right side for Event Logs
     */

    /**
     * Toggle displaying logs and hiding them
     */
    $('.display-logs').click(function () {
        $('.logs').fadeToggle();
        $(this).val($(this).val() === "Show Logs" ? "Hide Logs" : "Show Logs");
    });

    /**
     * Option to display all logs instead of logs per contact
     * Hide this button when it has been pressed and displayed paged logs.
     */
    $('.display-all').click(function () {
        spinner.spin();
        target.appendChild(spinner.el);
        contactSeq = -1;
        offset = 0;
        $.post('controllers/LogsController.php', function (data) {
            limit = data;
            if (limit > 10) {
                $('.right-pager').fadeIn();
            } else {
                $('.right-pager').fadeOut();
            }
        });
        $('.left-pager').fadeOut();
        $.post('controllers/LogsController.php', {contact: contactSeq}, function (data)
        {
            $('.logs tbody').empty();
            if (data.length < 1) {
                $('.logs tbody').html("<tr><td colspan='3'>No Events Found</td></tr>");
            }
            $.each(data, function (j, event) {
                $('.logs tbody').append('<tr><td>' + event.date_time + "</td><td>" + event.action + '</td><td>' + event.class_id + '</tr>');
            });
            spinner.stop();
        }, 'json');
        $(this).fadeOut();
    });

    /**
     * Event Logs pagination select previous group
     * If there are previous groups of logs then move back to those logs otherwise hide this
     */
    $('.left-pager').click(function () {
        if (offset >= 10) {
            spinner.spin();
            target.appendChild(spinner.el);
            offset -= 10;
            if (offset < 10) {
                offset = 0;
            }
            $.post('controllers/LogsController.php', {contact: contactSeq, offset: offset}, function (data)
            {
                var rows = "";
                $.each(data, function (j, event) {
                    rows += '<tr><td>' + event.date_time + "</td><td>" + event.action + '</td><td>' + event.class_id + '</tr>';
                });
                if (data.length < 1) {
                    rows = "<tr><td colspan='3'>No Events Found</td></tr>";
                }
                spinner.stop();
                $('.logs tbody').html(rows);
            }, 'json');
            if (offset < 10) {
                $('.left-pager').fadeOut();
            }
            if (offset < limit - 10) {
                $('.right-pager').fadeIn();
            }
        } else {
            $('.left-pager').fadeOut();
        }
    });

    /**
     * 
     */
    $('.right-pager').click(function () {
        if (offset < limit) {
            spinner.spin();
            target.appendChild(spinner.el);
            offset += 10;

            $.post('controllers/LogsController.php', {contact: contactSeq, offset: offset}, function (data)
            {
                var rows = "";
                $.each(data, function (j, event) {
                    rows += '<tr><td>' + event.date_time + "</td><td>" + event.action + '</td><td>' + event.class_id + '</tr>';
                });
                if (data.length < 1) {
                    rows = "<tr><td colspan='3'>No Events Found</td></tr>";
                }
                spinner.stop();
                $('.logs tbody').html(rows);
            }, 'json');
            if (offset >= limit - 10) {
                offset = limit;
                $('.right-pager').fadeOut();
            }
            if (offset > 0) {
                $('.left-pager').fadeIn();
            }
        } else {
            $('.right-pager').fadeOut();
        }
    });
});
// end on ready function


/**
 * 
 * @param {type} contact
 * @returns {undefined}
 */
function setContact(contact)
{
    var isUpdate = true;
    $('.display-create').trigger('click');
    $('.create-contact h2').html('Edit ' + contact.first_name);
    if ($('.name-group .contact-id').length)
    {
        $('.name-group .contact-id').val(contact.id);
    }
    else
    {
        $('.name-group').append("<input class='contact-id' type='hidden' name='contact_id' value='" + contact.id + "'>");
    }
    if (contact.id > 0) {
        $('.submit-create').val('Update');
        $('.create-contact').removeClass('create');
        $('.create-contact').addClass('update');
        $('.submit-delete').fadeIn();
    } else {
        isUpdate = false;
        $('.submit-create').val('Create');
        $('.create-contact').removeClass('update');
        $('.create-contact').addClass('create');
        $('.submit-delete').fadeOut();
        $('.contact-id').remove();
        $('.address-id').remove();
        $('.phone-id').remove();
    }
    $('.success-status').removeClass('success-status');
    $('.error-status').removeClass('error-status');
    $('.error-msg').remove();
    $('.address-code').removeClass('postal').removeClass('zip');
    $('.create-contact .fname').val(contact.first_name);
    $('.create-contact .mname').val(contact.middle_name);
    $('.create-contact .lname').val(contact.last_name);
    $.each(contact.address, function (j, address) {
        if (j > 0) {
            $('.add-address').trigger('click');
        }
        if (isUpdate) {
            if ($('.address:eq(' + j + ') .address-id').length)
            {
                $('.address:eq(' + j + ') .address-id').val(address.id);
            }
            else
            {
                $('.address:eq(' + j + ')').append("<input class='address-id' type='hidden' name='address[id][]' value='" + address.id + "'>");
            }
        }
        var streetSplit = address.street.split("-");
        var apartment = streetSplit.length === 2 ? $.trim(streetSplit[0]) : "";
        var streetAddr = streetSplit.length === 2 ? $.trim(streetSplit[1]) : $.trim(address.street);
        var number = streetAddr.substr(0, streetAddr.indexOf(' '));
        var street = streetAddr.substr(streetAddr.indexOf(' ') + 1);
        $('.address:eq(' + j + ') .number').val(number.replace('~', '-'));
        $('.address:eq(' + j + ') .street').val(street.replace('~', '-'));
        $('.address:eq(' + j + ') .apartment').val(apartment.replace('~', '-'));
        $('.address:eq(' + j + ') .city').val(address.city);
        $('.address:eq(' + j + ') .province').val(address.province);
        $('.address:eq(' + j + ') .address-code').val(address.postal_code);

        $(".address:eq(" + j + ") .province option[value='" + address.province + "']").prop('selected', true);
        if ($('.address:eq(' + j + ') .province option:selected').parent().attr('label') === "Canada") {
            $('.address:eq(' + j + ') .address-code').removeClass('zip').addClass('postal');
            $('.address:eq(' + j + ') .address-code').attr('placeholder', 'Postal Code');
            $('.country').val('Canada');
        } else {
            $('.address:eq(' + j + ') .address-code').removeClass('postal').addClass('zip');
            $('.address:eq(' + j + ') .address-code').attr('placeholder', 'Zip Code');
            $('.country').val('United States');
        }
    });
    var cnt = 0;
    $.each(contact.phone_number, function (j, phone_number) {
        $.each(phone_number, function (i, number) {
            if (cnt > 0) {
                $('.add-phone').trigger('click');
            }
            if (isUpdate) {
                if ($('.phone-number:eq(' + cnt + ') .phone-id').length)
                {
                    $('.phone-number:eq(' + cnt + ') .phone-id').val(number.id);
                }
                else
                {
                    $('.phone-number:eq(' + cnt + ')').append("<input class='phone-id' type='hidden' name='phone[id][]' value='" + number.id + "'>");
                }
            }
            $('.phone-number:eq(' + cnt + ') .phone-type').val(number.phone_type);
            $('.phone-number:eq(' + cnt + ') .phone').val(addPhoneFormat(number.phone_number));
            ++cnt;
        });
    });
    $('.create-contact .email').val(contact.email);
    $('.create-contact .notes').val(contact.notes);

    $.post('controllers/LogsController.php', {count: contact.id}, function (data) {
        limit = data;
        if (limit > 10) {
            $('.right-pager').fadeIn();
        } else {
            $('.right-pager').fadeOut();
        }
    });

    $.post('controllers/LogsController.php', {contact: contact.id}, function (data) {
        var rows = "";
        $.each(data, function (j, event) {
            rows += '<tr><td>' + event.date_time + "</td><td>" + event.action + '</td><td>' + event.class_id + '</tr>';
        });
        if (data.length < 1) {
            rows = "<tr><td colspan='3'>No Events Found</td></tr>";
        }
        $('.logs tbody').html(rows);
    }, 'json');

    $('.display-all').fadeIn();
}

/**
 * 
 * @param {type} json
 * @returns {String}
 */
function genCSV(json)
{
    var array = json;
    var str = '';
    var line = '';

    for (var index in array[0]) {
        var value = index + "";
        line += '"' + value.replace(/"/g, '""') + '",';
    }

    line = line.slice(0, -1);
    str += line + '\r\n';
    for (var i = 0; i < array.length; i++) {
        var line = '';
        for (var index in array[i]) {
            var value = array[i][index] + "";
            line += '"' + value.replace(/"/g, '""') + '",';
        }

        line = line.slice(0, -1);
        str += line + '\r\n';
    }
    return str;
}

/**
 * 
 * @param {type} location
 * @param {type} options
 * @returns {unresolved}
 */
function addPhoneFormat(number) {
    number = removePhoneFormat(number);
    var s = '';
    if (number.length > 10) {
        var x = number.length - 4;
        var front = x % 3;
        if (front > 0) {
            x -= front;
            s = number.slice(number.length * -1, front) + ' (';
        } else {
            s = '(';
        }
        var limit = 4 + front - 1;
        for (var j = x; j > 0; j -= 3) {
            var y = (j + 4) * -1;
            s += number.slice(y, limit);
            if (j === x) {
                s += ') ';
            } else {
                s += '-';
            }
            limit += 3;
        }
        s += number.slice(-4, number.length);
    } else if (number.length > 7) {
        s = '(' + number.slice(0, 3) + ') ' + number.slice(3, 6) + '-' + number.slice(6, 10);
    } else {
        s = number.slice(0, 3) + '-' + number.slice(3, 7);
    }

    return s;
}

/**
 *
 */
function removePhoneFormat(number) {
    return number.replace(/\D/g, '');
}

/**
 * Validation
 * 
 * @returns {Boolean}
 */

/**
 * 
 * @returns {Boolean}
 */
function verifyCreate() {
    $('.sumbit-type').remove();
    $('.submit-type').val('Create');
    if ($('.contact-id').length) {
        $('.submit-type').val('Update');
        return confirm("Are you sure you want to modify this contact?");
    }

    return true;
}

/**
 * 
 * @returns {unresolved}
 */
function verifyDelete() {
    $('.sumbit-type').remove();
    $('.submit-type').val('Delete');
    return confirm("Are you sure you want to delete this contact?");
}

/**
 * 
 * @returns {Boolean}
 */
function validateSubmit() {
    $('.success-status').removeClass('success-status');
    $('.error-status').removeClass('error-status');
    $(".error-msg").remove();
    var error = false, workPhoneError = false;
    if ($(".fname").val() === "") {
        error = true;
        $(".fname").addClass("error-status");
        $(".fname").parent().append("<ul class='error-msg'><li>Please enter the first name.</li></ul>");
    } else {
        $(".fname").addClass("success-status");
    }

    if ($(".lname").val() === "") {
        error = true;
        $(".lname").addClass("error-status");
        $(".lname").parent().append("<ul class='error-msg'><li>Please enter the last name.</li></ul>");
    } else {
        $(".lname").addClass("success-status");
    }

    $('.number').each(function () {
        if ($(this).val() === "") {
            error = true;
            $(this).addClass("error-status");
            $(this).parent().append("<ul class='error-msg'><li>Please enter the street number.</li></ul>");
        } else {
            $(this).addClass("success-status");
        }
    });

    $('.street').each(function () {
        var streetError = "";
        if ($(this).val() === "") {
            streetError += "<li>Please enter the street name.</li>";
        }

        if ($(this).val().split(" ").length < 2) {
            streetError += "<li>Please enter Name and Type (Street, Road, Avenue, etc.).</li>";
        }

        if (streetError.length > 0) {
            error = true;
            $(this).addClass("error-status");
            $(this).parent().append("<ul class='error-msg'>" + streetError + "</ul>");
        } else {
            $(this).addClass("success-status");
        }
    });

    $('.city').each(function () {
        if ($(this).val() === "") {
            error = true;
            $(this).addClass("error-status");
            $(this).parent().append("<ul class='error-msg'><li>Please enter the city name.</li></ul>");
        } else {
            $(this).addClass("success-status");
        }
    });

    $('.province').each(function () {
        if (!$('option:selected', this).length) {
            error = true;
            $(this).addClass("error-status");
            $(this).parent().append("<ul class='error-msg'><li>Please select the province/state.</li></ul>");
        } else {
            $(this).addClass("success-status");
        }
    });

    $('.country').each(function () {
        if (!$('option:selected', this).length) {
            error = true;
            $(this).addClass("error-status");
            $(this).parent().append("<ul class='error-msg'><li>Please select the Country.</li></ul>");
        } else {
            $(this).addClass("success-status");
        }
    });

    $('.postal').each(function () {
        var postalError = "";
        if ($(this).val() === "") {
            postalError += "<li>Please enter the postal code.</li>";
        }

        if ($(this).val().length < 6) {
            postalError += "<li>The postal code must be six (6) digits</li>";
        }

        if (!$(this).val().match(/[a-zA-Z][0-9][a-zA-Z](-| |)[0-9][a-zA-Z][0-9]/)) {
            postalError += "<li>The postal code must be of format A9A 9A9</li>";
        }

        if (postalError.length > 0) {
            error = true;
            $(this).addClass("error-status");
            $(this).parent().append("<ul class='error-msg'>" + postalError + "</ul>");
        } else {
            $(this).addClass("success-status");
        }
    });

    $('.zip').each(function () {
        var zipError = "";
        if ($(this).val() === "") {
            zipError += "<li>Please enter the zip code.</li>";
        }

        if ($(this).val().length < 5) {
            zipError += "<li>The zip code must be five (5) digits</li>";
        }

        if (!$.isNumeric($(this).val())) {
            zipError += "<li>The zip code can only contain numbers.</li>";
        }

        if (zipError.length > 0) {
            error = true;
            $(this).addClass("error-status");
            $(this).parent().append("<ul class='error-msg'>" + zipError + "</ul>");
        } else {
            $(this).addClass("success-status");
        }
    });

    $('.phone-type').each(function () {
        if ($(this).val() === "work") {
            workPhoneError = true;
        } else {
            $(this).addClass("error-status");
            $(this).parent().append("<ul class='error-msg'><li>Please create at least one work number.</li></ul>");
        }
    });

    if (!workPhoneError) {
        error = true;
    } else {
        $('.phone-type').removeClass("error-status");
        $('.phone-type').parent().find('.error-msg').remove();
        $('.phone-type').addClass("success-status");
    }

    $('.phone').each(function () {
        var phoneError = "",
                num = $(this).val().replace(/\D/g, '');
        if ($(this).val() === "") {
            phoneError += "<li>Please enter the phone number.</li>";
        }

        if (num.length < 10) {
            phoneError += "<li>The phone number must be at least ten (10) digits</li>";
        }

        if (!$.isNumeric(num)) {
            phoneError += "<li>The phone number can only contain numbers.</li>";
        }

        if (phoneError.length > 0) {
            error = true;
            $(this).addClass("error-status");
            $(this).parent().append("<ul class='error-msg'>" + phoneError + "</ul>");
        } else {
            $(this).addClass("success-status");
        }
    });

    /*if ($('.email').val() === "") {
     error = true;
     $('.email').addClass('error-status');
     $('.email').parent().append("<ul class='error-msg'><li>Please enter the email address.</li></ul>");
     } else*/
    if (($('.email').val().match(/.+@.+\..+/i) || []).length < 1 && $('.email').val() !== "") {
        error = true;
        $('.email').addClass('error-status');
        $('.email').parent().append("<ul class='error-msg'><li>The email address must match similar format to 'name@domain.com'.</li></ul>");
    } else {
        $('.email').addClass('success-status');
    }

    if (error) {
        return false;
    }

    $('.phone').each(function () {
        $(this).val(removePhoneFormat($(this).val()));
    });

    $('.number, .street, .apartment').each(function () {
        $(this).val($(this).val().replace('-', '~'));
    });

    return true;
}

