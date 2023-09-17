<?php
session_start();
require_once(__DIR__ . '/models/ContactClass.php');
$db = PHPDBConnect::instantiateDB('', '', '', '', false, true);
$contact = new Contact($db);
$contacts = $contact->get_all_contacts();
$contactList = "";
if (is_array($contacts)) {
    foreach ($contacts as $cont) {
        if ($cont instanceof Contact) {
            $contactList .= "<option value='{$cont->id}'>{$cont->last_name}, {$cont->first_name} {$cont->middle_name}</option>";
        }
    }
}

$tracking = new Tracking($db, isset($_SESSION['abUser']) ? $_SESSION['abUser'] : "");
$events = $tracking->get_all_events(10, 0);
$trackingList = "";
if (is_array($events)) {
    foreach ($events as $event) {
        if (is_array($event) && isset($event['date_time']) && isset($event['action']) && isset($event['class_id'])) {
            $trackingList .= "<tr><td>{$event['date_time']}</td><td>{$event['action']}</td><td>{$event['class_id']}</td></tr>";
        }
    }
}
?>
<!DOCTYPE html>

<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Address Book</title>
        <meta name="viewport" content="width=100%, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
        <link rel="stylesheet" href="css/main.min.css">
        <!--[if lt IE 9]>
            <script src="js/vendor/html5shiv.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <header>
            <nav>
            </nav>
            <h1>Address Book</h1>
        </header>
        <div class="left-side">
            <form action="controllers/SearchController.php" method="post" class="search-form" enctype="multipart/form-data">
                <h2>Contacts</h2>
                <span class="name-search">
                    <label for="search-field">Search a Name</label>
                    <input class="search-field" name="name" type="search" results="5" placeholder="Search a Name" title="Search a Name">
                    <input class="search-button" type="submit" value="Go" tile="Perform the Search">
                </span>
                <div id="search-options" style="display:none">
                    <div class="form-group">
                        <label for="search-street">Street Address</label>
                        <input class="search-street" name="address[street]" type="text" placeholder="Street Address" disabled="disabled" title="Street Address">
                    </div>
                    <div class="form-group">
                        <label for="search-city">City</label>
                        <input class="search-city" name="address[city]" type="text" placeholder="City" disabled="disabled" title="City">
                    </div>
                    <div class="form-group">
                        <label for="search-province">Province/State</label>
                        <select class="search-province" name="address[province]" disabled="disabled">
                            <option value="" selected>Province/State &lt;none&gt;</option>
                            <optgroup label="Canada">
                                <option value="AB">Alberta</option>
                                <option value="BC">British Columbia</option>
                                <option value="MB">Manitoba</option>
                                <option value="NB">New Brunswick</option>
                                <option value="NL">Newfoundland and Labrador</option>
                                <option value="NS">Nova Scotia</option>
                                <option value="ON">Ontario</option>
                                <option value="PE">Prince Edward Island</option>
                                <option value="QC">Quebec</option>
                                <option value="SK">Saskatchewan</option>
                                <option value="NT">Northwest Territories</option>
                                <option value="NU">Nunavut</option>
                                <option value="YT">Yukon</option>
                            </optgroup>
                            <optgroup label="United States">
                                <option value="AL">Alabama</option>
                                <option value="AK">Alaska</option>
                                <option value="AZ">Arizona</option>
                                <option value="AR">Arkansas</option>
                                <option value="CA">California</option>
                                <option value="CO">Colorado</option>
                                <option value="CT">Connecticut</option>
                                <option value="DE">Delaware</option>
                                <option value="DC">District Of Columbia</option>
                                <option value="FL">Florida</option>
                                <option value="GA">Georgia</option>
                                <option value="HI">Hawaii</option>
                                <option value="ID">Idaho</option>
                                <option value="IL">Illinois</option>
                                <option value="IN">Indiana</option>
                                <option value="IA">Iowa</option>
                                <option value="KS">Kansas</option>
                                <option value="KY">Kentucky</option>
                                <option value="LA">Louisiana</option>
                                <option value="ME">Maine</option>
                                <option value="MD">Maryland</option>
                                <option value="MA">Massachusetts</option>
                                <option value="MI">Michigan</option>
                                <option value="MN">Minnesota</option>
                                <option value="MS">Mississippi</option>
                                <option value="MO">Missouri</option>
                                <option value="MT">Montana</option>
                                <option value="NE">Nebraska</option>
                                <option value="NV">Nevada</option>
                                <option value="NH">New Hampshire</option>
                                <option value="NJ">New Jersey</option>
                                <option value="NM">New Mexico</option>
                                <option value="NY">New York</option>
                                <option value="NC">North Carolina</option>
                                <option value="ND">North Dakota</option>
                                <option value="OH">Ohio</option>
                                <option value="OK">Oklahoma</option>
                                <option value="OR">Oregon</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="RI">Rhode Island</option>
                                <option value="SC">South Carolina</option>
                                <option value="SD">South Dakota</option>
                                <option value="TN">Tennessee</option>
                                <option value="TX">Texas</option>
                                <option value="UT">Utah</option>
                                <option value="VT">Vermont</option>
                                <option value="VA">Virginia</option>
                                <option value="WA">Washington</option>
                                <option value="WV">West Virginia</option>
                                <option value="WI">Wisconsin</option>
                                <option value="WY">Wyoming</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="search-country">Country</label>
                        <select class="search-country" name="address[country]" disabled="disabled">
                            <option value="" selected>Country &lt;none&gt;</option>
                            <option value="Canada">Canada</option>
                            <option value="United States"> United States</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="search-postal">Postal/Zip Code</label>
                        <input class="search-postal" name="address[postal]" type="text" placeholder="Postal/Zip" disabled="disabled" title="Postal/Zip">
                    </div>
                    <div class="form-group">
                        <label for="search-phone-type">Phone Type</label>
                        <select class="search-phone-type" name="phone[type]" disabled="disabled">
                            <option value="" selected>Phone Type &lt;none&gt;</option>
                            <option value="home">Home</option>
                            <option value="work">Work</option>
                            <option value="cell">Cell</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="search-phone">Phone Number</label>
                        <input class="search-phone" name="phone[number]" type="tel" placeholder="Phone Number" disabled="disabled" title="Phone Number">
                    </div>
                    <div class="form-group">
                        <label for="search-email">Email</label>
                        <input class="search-email" name="email" type="email" placeholder="Email" title="Email">
                    </div>
                    <div class="form-group">
                        <label for="search-notes">Notes</label>
                        <textarea class="search-notes" name="notes" placeholder="Remember something about them here&hellip;" title="Add a note about them here"></textarea>
                    </div>
                </div>
                <input class="more-filters" type="button" value="&#9660;">
                <select id="contact-list" size="25">
                    <?php echo $contactList; ?>
                </select>
                <input id="import" type="button" value="Import">
                <input id="export" type="button" value="Export">
            </form>
        </div>
        <div class="content">
            <form action="controllers/AddressbookController.php" method="post" class="edit-form">
                <input type="button" class="display-create" value="Add Contact">
                <div class="create-contact" style="display:none">
                    <h2>Create New</h2>
                    <input class="remove-display" type="button" value="-" title="Clear Fields and Close">
                    <span class="form-info"><p>Fields marked with an asterisk (*) are required.</p></span>
                    <div class="name-group">
                        <h3>Contact Name</h3>
                        <div class="form-group">
                            <label for="fname">First Name</label>
                            <div class="required">*
                                <input class="fname" name="first_name" type="text" placeholder="First Name" title="First Name" maxlength="20">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="mname">Middle Name</label>
                            <input class="mname" name="middle_name" type="text" placeholder="Middle Name" title="Middle Name" maxlength="20">
                        </div>
                        <div class="form-group">
                            <label for="lname">Last Name</label>
                            <div class="required">*
                                <input class="lname" name="last_name" type="text" placeholder="Last Name" title="Last Name" maxlength="20">
                            </div>
                        </div>
                    </div>
                    <div class="address-group">
                        <h3>Contact Address</h3>
                        <div class="address">
                            <h4 class="address-name">Address 1</h4><input class="remove-address" type="button" value="-" title="Remove this Address">
                            <div class="full-address">
                                <div class="street-address">
                                    <div class="form-group">
                                        <label for="number">Street Number</label>
                                        <div class="required">*
                                            <input class="number" name="address[number][]" type="text" placeholder="Street Number" title="Street Number" maxlength="12">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="street">Street Name</label>
                                        <div class="required">*
                                            <input class="street" name="address[street][]" type="text" placeholder="Street Name" title="Street Name" maxlength="40">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="apt">Unit/Apt./Suite</label>
                                        <input class="apartment" name="address[apartment][]" type="text" placeholder="Unit/Apt./Suite" title="Unit/Apt./Suite" maxlength="8">
                                    </div>
                                </div>
                                <div class="city-province">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <div class="required">*
                                            <input class="city" name="address[city][]" type="text" placeholder="City" title="City" maxlength="30">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="province"></label>
                                        <select class="province" name="address[province][]">
                                            <optgroup label="Canada">
                                                <option value="AB" selected>Alberta</option>
                                                <option value="BC">British Columbia</option>
                                                <option value="MB">Manitoba</option>
                                                <option value="NB">New Brunswick</option>
                                                <option value="NL">Newfoundland and Labrador</option>
                                                <option value="NS">Nova Scotia</option>
                                                <option value="ON">Ontario</option>
                                                <option value="PE">Prince Edward Island</option>
                                                <option value="QC">Quebec</option>
                                                <option value="SK">Saskatchewan</option>
                                                <option value="NT">Northwest Territories</option>
                                                <option value="NU">Nunavut</option>
                                                <option value="YT">Yukon</option>
                                            </optgroup>
                                            <optgroup label="United States">
                                                <option value="AL">Alabama</option>
                                                <option value="AK">Alaska</option>
                                                <option value="AZ">Arizona</option>
                                                <option value="AR">Arkansas</option>
                                                <option value="CA">California</option>
                                                <option value="CO">Colorado</option>
                                                <option value="CT">Connecticut</option>
                                                <option value="DE">Delaware</option>
                                                <option value="DC">District Of Columbia</option>
                                                <option value="FL">Florida</option>
                                                <option value="GA">Georgia</option>
                                                <option value="HI">Hawaii</option>
                                                <option value="ID">Idaho</option>
                                                <option value="IL">Illinois</option>
                                                <option value="IN">Indiana</option>
                                                <option value="IA">Iowa</option>
                                                <option value="KS">Kansas</option>
                                                <option value="KY">Kentucky</option>
                                                <option value="LA">Louisiana</option>
                                                <option value="ME">Maine</option>
                                                <option value="MD">Maryland</option>
                                                <option value="MA">Massachusetts</option>
                                                <option value="MI">Michigan</option>
                                                <option value="MN">Minnesota</option>
                                                <option value="MS">Mississippi</option>
                                                <option value="MO">Missouri</option>
                                                <option value="MT">Montana</option>
                                                <option value="NE">Nebraska</option>
                                                <option value="NV">Nevada</option>
                                                <option value="NH">New Hampshire</option>
                                                <option value="NJ">New Jersey</option>
                                                <option value="NM">New Mexico</option>
                                                <option value="NY">New York</option>
                                                <option value="NC">North Carolina</option>
                                                <option value="ND">North Dakota</option>
                                                <option value="OH">Ohio</option>
                                                <option value="OK">Oklahoma</option>
                                                <option value="OR">Oregon</option>
                                                <option value="PA">Pennsylvania</option>
                                                <option value="RI">Rhode Island</option>
                                                <option value="SC">South Carolina</option>
                                                <option value="SD">South Dakota</option>
                                                <option value="TN">Tennessee</option>
                                                <option value="TX">Texas</option>
                                                <option value="UT">Utah</option>
                                                <option value="VT">Vermont</option>
                                                <option value="VA">Virginia</option>
                                                <option value="WA">Washington</option>
                                                <option value="WV">West Virginia</option>
                                                <option value="WI">Wisconsin</option>
                                                <option value="WY">Wyoming</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="postal-code">
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <select class="country" name="address[country][]">
                                        <option value="Canada" selected>Canada</option>
                                        <option value="United States">United States</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="postal">Postal/Zip Code</label>
                                    <div class="required">*
                                        <input class="address-code postal" name="address[postal][]" type="text" placeholder="Postal Code" title="Postal/Zip Code" maxlength="7">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input class="add-address" type="button" value="+" title="Add Another Address">
                    </div>
                    <div class="phone-numbers">
                        <h3>Contact Phone Number</h3>
                        <div class="phone-number">
                            <div class="form-group">
                                <label for="phone-type">Phone Type</label>
                                <select class="phone-type" name="phone[type][]">
                                    <option value="home">Home</option>
                                    <option value="work" selected>*Work</option>
                                    <option value="cell">Cell</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <div class="required">*
                                    <input class="phone" name="phone[number][]" type="tel" placeholder="Phone Number" title="Phone Number" maxlength="12">
                                </div>
                            </div>
                            <div class="form-group">
                                <input class="remove-phone" type="button" value="-" title="Remove this phone number">
                            </div>
                        </div>
                        <input class="add-phone" type="button" value="+" title="Add Another Phone Number">
                    </div>
                    <div class="email-group">
                        <h3>Contact Email</h3>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input class="email" name="email" type="email" placeholder="Email" title="Email" maxlength="60">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="notes" name="notes" placeholder="Remember something about them here&hellip;" title="Add a note about them here"></textarea>
                    </div>
                    <div class="contact-controls">
                        <input type="submit" name="create" onclick="return verifyCreate()" value="Create" class="submit-create">
                        <input type="submit" name="delete" onclick="return verifyDelete()" value="Delete" style="display:none;" class="submit-delete">
                        <input type="hidden" name="submit" value="Create" class="submit-type">
                    </div>
                </div>
            </form>
            <span class="form-info"><h4></h4></span>
        </div>
        <div class="right-side">
            <div class="logs-title"><h2 class="logs" style="display:none;">Event Logs</h2></div>
            <input type="button" value="Show Logs" class="display-logs">
            <span class="logs" style="display:none;"><input class="display-all" type="button" value="Display All" style="display:none;"></span>
            <table class="logs" style="display:none;">
                <thead>
                    <tr class="pagers">
                        <td>
                            <input type="button" class="left-pager" value="&#9664;" style="display: none;">
                        </td>
                        <td>
                        </td>
                        <td>
                            <input type="button" class="right-pager" value="&#9654;">
                        </td>
                    <tr>
                    <tr>
                        <th>
                            Time
                        </th>
                        <th>
                            Occurance
                        </th>
                        <th>
                            Sequence
                        </th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td>
                            Time
                        </td>
                        <td>
                            Occurance
                        </td>
                        <td>
                            Sequence
                        </td>
                    </tr>
                    <tr class="pagers">
                        <td>
                            <input type="button" class="left-pager" value="&#9664;" style="display: none;">
                        </td>
                        <td>
                        </td>
                        <td>
                            <input type="button" class="right-pager" value="&#9654;">
                        </td>
                    <tr>
                </tfoot>
                <tbody>
                    <?php echo $trackingList; ?>
                </tbody>
            </table>
        </div>
        <footer>
            <h5>Address Book</h5>
        </footer>
        <div id="spinner-container"></div>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script>
                            window.jQuery || document.write('<script src="js/vendor/jquery-3.7.1.min.js"><\/script>');
        </script>
        <script src="js/spin.min.js"></script>
        <script src="js/main.min.js"></script>
    </body>
</html>
