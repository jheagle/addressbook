<?php

require_once(dirname(__DIR__) . '/models/ContactClass.php');
header('Content-Type: application/json');
$customers_json = array();
$customer = new Contact();
$customers = $customer->get_all_customers(false);
foreach ($customers as $cust) {
    $customers_json[] = $cust->get_as_json();
}

$customers = $customers_json;
$address_cnt = 0;
$home_phone_cnt = 0;
$work_phone_cnt = 0;
$cell_phone_cnt = 0;
$json = array();

foreach ($customers as $customer) {
    if (count($customer['address']) > $address_cnt) {
        $address_cnt = count($customer['address']);
    }
    if (count($customer['phone_number']['home']) > $home_phone_cnt) {
        $home_phone_cnt = count($customer['phone_number']['home']);
    }
    if (count($customer['phone_number']['work']) > $work_phone_cnt) {
        $work_phone_cnt = count($customer['phone_number']['work']);
    }
    if (count($customer['phone_number']['cell']) > $cell_phone_cnt) {
        $cell_phone_cnt = count($customer['phone_number']['cell']);
    }
}

foreach ($customers as $customer) {
    $cust = array();
    foreach ($customer as $key => $value) {
        if ($key === "address") {
            $j = 0;
            foreach ($value as $ky => $val) {
                ++$j;
                foreach ($val as $k => $v) {
                    if ($k !== "id" && $k !== "customer_id") {
                        $cust[$k . " " . $j] = $v;
                    }
                }
            }
            if ($j < $address_cnt) {
                $cnt = $j;
                for ($i = $address_cnt; $i > $j; --$i) {
                    ++$cnt;
                    $cust["street " . $cnt] = "";
                    $cust["city " . $cnt] = "";
                    $cust["province " . $cnt] = "";
                    $cust["postal_code " . $cnt] = "";
                }
            }
        } elseif ($key === "phone_number") {
            foreach ($value as $type => $phone_number) {
                if ($type === "home") {
                    $j = 0;
                    foreach ($phone_number as $phone) {
                        ++$j;
                        $cust["home " . $j] = $phone["phone_number"];
                    }
                    if ($j < $home_phone_cnt) {
                        $cnt = $j;
                        for ($i = $home_phone_cnt; $i > $j; --$i) {
                            ++$cnt;
                            $cust["home " . $cnt] = "";
                        }
                    }
                } elseif ($type === "work") {
                    $j = 0;
                    foreach ($phone_number as $phone) {
                        ++$j;
                        $cust["work " . $j] = $phone["phone_number"];
                    }
                    if ($j < $work_phone_cnt) {
                        $cnt = $j;
                        for ($i = $work_phone_cnt; $i > $j; --$i) {
                            ++$cnt;
                            $cust["work " . $cnt] = "";
                        }
                    }
                } elseif ($type === "cell") {
                    $j = 0;
                    foreach ($phone_number as $phone) {
                        ++$j;
                        $cust["cell " . $j] = $phone["phone_number"];
                    }
                    if ($j < $cell_phone_cnt) {
                        $cnt = $j;
                        for ($i = $cell_phone_cnt; $i > $j; --$i) {
                            ++$cnt;
                            $cust["cell " . $cnt] = "";
                        }
                    }
                }
            }
        } elseif ($key !== "id") {
            $cust[$key] = $value;
        }
    }
    $json[] = $cust;
}
echo json_encode($json);
return;
