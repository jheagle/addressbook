<?php

$screenOut = true;
require_once(dirname(__DIR__) . '/models/phpDBConnect.php');
$db = PHPDBConnect::instantiateDB('', '', '', '', false, false);
require_once(dirname(__DIR__) . '/models/ContactClass.php');

// generate_customers(10000);

if (preg_match('/^192\.168\./', $ip)) {
    generate_customers(9400);
}

function generate_customers($amount = 10) {
    global $db;
    for ($i = 0; $i < $amount; ++$i) {
        $address = array();
        $address[] = new ContactAddress($db, -1, -1, generate_street($i), generate_city(), generate_province($i), generate_country($i), generate_postal($i));
        $phones = array();
        if ($i % 3 === 0) {
            $phones[] = new ContactPhoneNumber($db, -1, -1, 'home', generate_phone());
        }
        $phones[] = new ContactPhoneNumber($db, -1, -1, 'work', generate_phone());
        if ($i % 2 === 0) {
            $phones[] = new ContactPhoneNumber($db, -1, -1, 'cell', generate_phone());
        }
        $first_name = generate_name();
        $middle_name = $i % 2 === 0 ? generate_name() : "";
        $last_name = generate_name();
        $customer = new Contact($db, -1, $first_name, $middle_name, $last_name, $address, $phones, generate_email($first_name, $last_name), "");
        $customer->create_contact();
    }
    echo "Completed Creating {$amount} Contacts<br>";
}

function generate_street($seed) {
    $street = "";
    if ($seed % 3 === 0) {
        $street .= mt_rand(1, 2412) . "-" . mt_rand(1, 9999) . " " . generate_name(4, 20) . " Street";
    } else {
        $street .= mt_rand(1, 9999) . " " . generate_name(4, 20) . " Street";
    }
    return $street;
}

function generate_city() {
    return generate_name(4, 20);
}

function generate_province($seed) {
    $province = "";
    if ($seed % 2 === 0) {
        $locations = array("AB", "BC", "MB", "NB", "NL", "NS", "ON", "PE", "QC", "SK", "NT", "NU", "YT");
        $province = $locations[mt_rand(0, 12)];
    } else {
        $locations = array("AL", "AK", "AZ", "AR", "CA", "CO", "CT", "DE", "DC", "FL", "GA", "HI", "ID", "IL", "IN", "IA", "KS", "KY", "LA", "ME", "MD", "MA", "MI", "MN", "MS", "MO", "MT", "NE", "NV", "NH", "NJ", "NM", "NY", "NC", "ND", "OH", "OK", "OR", "PA", "RI", "SC", "SD", "TN", "TX", "UT", "VT", "VA", "WA", "WV", "WI", "WY");
        $province = $locations[mt_rand(0, 50)];
    }
    return $province;
}

function generate_country($seed) {
    return $seed % 2 === 0 ? "Canada" : "United States";
}

function generate_postal($seed) {
    $postal = "";
    if ($seed % 2 === 0) {
        $alpha = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $postal = $alpha[mt_rand(0, 25)] . mt_rand(0, 9) . $alpha[mt_rand(0, 25)] . " " . mt_rand(0, 9) . $alpha[mt_rand(0, 25)] . mt_rand(0, 9);
    } else {
        $postal = mt_rand(10000, 99999);
    }
    return $postal;
}

function generate_phone() {
    return mt_rand(1000000000, 9999999999);
}

function generate_name($min = 4, $max = 12) {
    $alpha_caps = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $alpha_lower = "abcdefghijklmnopqrstuvwxyz";
    $count = mt_rand(intval($min), intval($max));
    $name = "";
    for ($i = 0; $i < $count; ++$i) {
        if ($i === 0) {
            $name .= $alpha_caps[mt_rand(0, 25)];
        } else {
            $name .= $alpha_lower[mt_rand(0, 25)];
        }
    }
    return $name;
}

function generate_email($first, $last) {
    return $first[0] . "." . $last . "@domain.com";
}
