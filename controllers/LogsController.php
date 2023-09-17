<?php

session_start();

require_once(dirname(__DIR__) . '/models/TrackingClass.php');
$db = PHPDBConnect::instantiateDB('', '', '', '', false, true);

$limit = isset($_POST['limit']) ? $_POST['limit'] : 10;
$offset = isset($_POST['offset']) ? $_POST['offset'] : 0;
if (isset($_POST['contact']) && $_POST['contact'] < 0) {
    header('Content-Type: application/json');
    $tracking = new Tracking($db, isset($_SESSION['ab_user']) ? $_SESSION['ab_user'] : "");

    $events = $tracking->get_all_events($limit, $offset);
    echo json_encode($events);
    return;
} elseif (isset($_POST['contact']) && $_POST['contact'] > 0) {
    header('Content-Type: application/json');
    $contact = isset($_POST) ? "`class_id`='" . intval($_POST['contact']) . "'" : '';
    $tracking = new Tracking($db, isset($_SESSION['ab_user']) ? $_SESSION['ab_user'] : "");
    $events = $tracking->get_all_events($limit, $offset, $contact, "", "");
    echo json_encode($events);
    return;
} else {
    header('Content-Type: application/json');
    $count = isset($_POST['count']) ? "`class_id`='" . intval($_POST['count']) . "'" : '';
    $tracking = new Tracking($db, isset($_SESSION['ab_user']) ? $_SESSION['ab_user'] : "");
    echo $tracking->get_events_count($count);
    return;
}
