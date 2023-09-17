<?php

require_once(dirname(__DIR__) . '/models/phpDBConnect.php');
$db = PHPDBConnect::instantiateDB('', '', '', '', false, true);
require_once(dirname(__DIR__) . '/models/ContactClass.php');

if (!empty($_POST['first_name']) && !empty($_POST['last_name']) && !empty($_POST['address']) && !empty($_POST['phone'])) {
    global $db;
    $contact_id = isset($_POST['contact_id']) ? $_POST['contact_id'] : -1;
    $addresses = array();
    $num_addresses = count($_POST['address']['postal']);
    foreach ($_POST['address'] as $key => $value) {
        if ((empty($value) && $key != "apartment") || count($value) != $num_addresses && $key != "id") {
            header('Location: ' . dirname(__DIR__));
            return;
        }
        foreach ($value as $val) {
            if (empty($val) && $key != "apartment") {
                header('Location: ' . dirname(__DIR__));
                return;
            }
        }
    }
    for ($i = 0; $i < $num_addresses; ++$i) {
        $apartment = empty($_POST['address']['apartment'][$i]) ? "" : $_POST['address']['apartment'][$i] . "-";
        $street = $apartment . $_POST['address']['number'][$i] . " " . $_POST['address']['street'][$i];
        $address_id = isset($_POST['address']['id'][$i]) ? $_POST['address']['id'][$i] : -1;
        $addresses[] = new ContactAddress($db, $address_id, $contact_id, $street, $_POST['address']['city'][$i], $_POST['address']['province'][$i], $_POST['address']['country'][$i], $_POST['address']['postal'][$i]);
    }
    $phones = array();
    $has_work_phone = FALSE;
    foreach ($_POST['phone']['type'] as $value) {
        if ($value == "work") {
            $has_work_phone = TRUE;
            break;
        }
    }
    if (!$has_work_phone || count($_POST['phone']['type']) != count($_POST['phone']['number'])) {
        header('Location: ' . __DIR__ . '/');
        return;
    }
    for ($i = 0; $i < count($_POST['phone']['number']); ++$i) {
        $phone_id = isset($_POST['phone']['id'][$i]) ? $_POST['phone']['id'][$i] : -1;
        $phones[] = new ContactPhoneNumber($db, $phone_id, $contact_id, $_POST['phone']['type'][$i], $_POST['phone']['number'][$i]);
    }
    $contact = new Contact($db, $contact_id, $_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $addresses, $phones, $_POST['email'], $_POST['notes']);

    if (isset($_POST['contact_id']) && isset($_POST['address']['id']) && isset($_POST['phone']['id'])) {
        if (isset($_POST['submit']) && $_POST['submit'] === 'Update') {
            $contact->update_contact();
        } elseif (isset($_POST['submit']) && $_POST['submit'] === 'Delete') {
            $contact->delete_contact();
        }
    } elseif (isset($_POST['submit']) && $_POST['submit'] === 'Create') {
        $contact->create_contact();
    }

    $contacts_json = array();
    $contact = new Contact($db);
    $contacts = array();
    $contacts = $contact->get_all_contacts();
    foreach ($contacts as $cust) {
        $contacts_json[] = $cust->get_as_json();
    }
    header('Content-Type: application/json');
    echo json_encode($contacts_json);
    return;
}

if (isset($_POST['contact_id'])) {
    global $db;
    $contact = new Contact($db, $_POST['contact_id']);
    $contact = $contact->get_contact();
    header('Content-Type: application/json');
    echo json_encode($contact->get_as_json());
    return;
}
