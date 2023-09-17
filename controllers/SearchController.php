<?php

if (isset($_FILES['csv-file']['name']) && $_FILES['csv-file']['name'] != '') {
    require_once(dirname(__DIR__) . '/models/phpDBConnect.php');
    $db = PHPDBConnect::instantiateDB('', '', '', '', false, true);
    require_once(dirname(__DIR__) . '/models/ContactClass.php');
    $files = $_FILES['csv-file'];
    $allowedExts = array('csv');
    $types = array('application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv');
    $temp = explode(".", $files['name']);
    $extension = end($temp);

    if (in_array($files['type'], $types) && in_array($extension, $allowedExts)) {
        if ($files['error'] > 0) {
            //echo "Return Code: ".$files ["error"]."<br>";
        } else {
            //echo "Upload: ".$files ["name"]."<br>";
            //echo "Type: ".$files ["type"]."<br>";
            //echo "Size: ".($files ["size"] / 1024)." kB<br>";
            //echo "Temp file: ".$files ["tmp_name"]."<br>";
            if (file_exists(dirname(__DIR__) . '/resources/" . $files['name'])) {
                //echo $files['name']." already exists. ";
            } else {
                move_uploaded_file($files['tmp_name'], dirname(__DIR__) . 'resources/" . $files['name']);

                //echo "Stored in: "."/resources/".$files['name'];

                $fnameDex = 0;
                $mnameDex = 0;
                $lnameDex = 0;
                $streetDex = 0;
                $cityDex = 0;
                $provinceDex = 0;
                $countryDex = 0;
                $postalDex = 0;
                $homePhDex = 0;
                $workPhDex = 0;
                $cellPhDex = 0;
                $emailDex = 0;
                $notesDex = 0;
                $cnt = 0;
                $file = fopen(__DIR__ . '/resources/" . $files['name'], 'r');
                while (($row = fgetcsv($file)) !== FALSE) {
                    if ($cnt == 0) {
                        // the first row is used as a reference for where the required data is stored.
                        $fnameDex = array_search("first_name", $row);
                        $mnameDex = array_search("middle_name", $row);
                        $lnameDex = array_search("last_name", $row);
                        $streetDex = array_search("street", $row);
                        $cityDex = array_search("city", $row);
                        $provinceDex = array_search("province", $row);
                        $countryDex = array_search("country", $row);
                        $postalDex = array_search("postal_code", $row);
                        $homePhDex = array_search("home", $row);
                        $workPhDex = array_search("work", $row);
                        $cellPhDex = array_search("cell", $row);
                        $emailDex = array_search("email", $row);
                        $notesDex = array_search("notes", $row);
                        $cnt ++;
                    } else {
                        $address = array(new ContactAddress($db, -1, -1, $row[$streetDex], $row[$cityDex], $row[$provinceDex], $row[$countryDex], $row[$postalDex]));
                        $phones = array();
                        $phones[] = new ContactPhoneNumber($db, -1, -1, 'home', $row[$homePhDex]);
                        $phones[] = new ContactPhoneNumber($db, -1, -1, 'work', $row[$workPhDex]);
                        $phones[] = new ContactPhoneNumber($db, -1, -1, 'cell', $row[$cellPhDex]);
                        $contact = new Contact($db, -1, $row[$fnameDex], $row[$mnameDex], $row[$lnameDex], $address, $phones, $row[$emailDex], $row[$notesDex]);
                        $contact->create_contact();
                    }
                }
                fclose($file);
            }
        }
    } else {
        //echo "Invalid file";
    }
    header('Location: ../index.php');
    return;
}

require_once('../models/ContactClass.php');
header('Content-Type: application/json');

$contacts_json = array();
$is_empty = true;

if (is_array($_POST) && !empty($_POST)) {
    foreach ($_POST as $post) {
        if (is_array($post)) {
            foreach ($post as $pos) {
                if (is_array($pos)) {
                    foreach ($pos as $p) {
                        if (!empty($p) && trim($p) !== '') {
                            $is_empty = false;
                            break;
                        }
                    }
                } else {
                    if (!empty($pos) && trim($pos) !== '') {
                        $is_empty = false;
                        break;
                    }
                }
            }
        } else {
            if (!empty($post) && trim($post) !== '') {
                $is_empty = false;
                break;
            }
        }
    }
}

if ($is_empty) {
    $db = PHPDBConnect::instantiateDB('', '', '', '', false, true);
    $contact = new Contact($db);
    $contacts = $contact->get_all_contacts();
    foreach ($contacts as $cust) {
        $contacts_json[] = $cust->get_as_json();
    }
} else {
    $db = PHPDBConnect::instantiateDB('', '', '', '', false, true);
    $address = array();
    $phone = array();

    $street = isset($_POST['address']['street']) ? trim($_POST['address']['street']) : "";
    $city = isset($_POST['address']['city']) ? trim($_POST['address']['city']) : "";
    $province = isset($_POST['address']['province']) ? trim($_POST['address']['province']) : "";
    $country = isset($_POST['address']['country']) ? trim($_POST['address']['country']) : "";
    $postal = isset($_POST['address']['postal']) ? trim($_POST['address']['postal']) : "";

    $address[] = new ContactAddress($db, -1, -1, $street, $city, $province, $country, $postal);

    $type = isset($_POST['phone']['type']) ? trim($_POST['phone']['type']) : "";
    $number = isset($_POST['phone']['number']) ? trim($_POST['phone']['number']) : "";

    $phone[] = new ContactPhoneNumber($db, -1, -1, $type, $number);



    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : "";
    $middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : "";
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : "";
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : "";

    $contact = new Contact($db, -1, $first_name, $middle_name, $last_name, $address, $phone, $email, $notes);
    $name = trim($_POST['name']);
    $contacts = $contact->search_contact($name);
    if (is_array($contacts) && count($contacts) > 0) {
        foreach ($contacts as $cust) {
            $contacts_json[] = $cust->get_as_json();
        }
    } else {
        $names = explode(" ", $name);
        $cnt = count($names);
        switch ($cnt) {
            case 0:
                break;
            case 1:
                $contact->set('first_name', $names[0]);
                break;
            case 2:
                $contact->set('first_name', $names[0]);
                $contact->set('last_name', $names[1]);
                break;
            case 3:
                $contact->set('first_name', $names[0]);
                $contact->set('middle_name', $names[1]);
                $contact->set('last_name', $names[2]);
                break;
            default:
                $contact->set('first_name', $names[0]);
                $mid_names = "";
                for ($i = 1; $i < $cnt - 1; ++$i) {
                    $mid_names .= $names[$i];
                }
                $contact->set('middle_name', $mid_names);
                $contact->set('last_name', $names[$cnt - 1]);
        }
        $contacts_json[] = $contact->get_as_json();
    }
}
echo json_encode($contacts_json);
return;
