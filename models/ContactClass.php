<?php

require_once(__DIR__ . '/TrackingClass.php');

$tracking = new Tracking($db, isset($_SESSION['ab_user']) ? $_SESSION['ab_user'] : "");

class Contact {

    private $db;
    private $id;
    private $first_name;
    private $middle_name;
    private $last_name;
    private $address;
    private $phone_number;
    private $email;
    private $notes;

    public function __construct(&$db) {
        $ob_vars = get_object_vars($this);
        $args = func_get_args();
        $count = count($args);
        $i = 0;
        $this->id = -1;

        foreach ($ob_vars as $prop => $val) {
            if ($prop === 'db' && $args[$i] instanceof DBConnect) {
                $this->db = $db;
            } elseif ($prop !== 'db' && $i < $count && isset($args[$i]) && !empty($args[$i])) {
                $this->set($prop, $args[$i]);
            } elseif ($prop !== 'db') {
                $this->{$prop} = preg_match('/^(address|phone_number)/', $prop) ? array() : "";
            }
            ++$i;
        }
    }

    public function __get($property) {
        if (preg_match('/^(address|phone_number)/', $property)) {
            return $this->get_contact_info($property);
        } elseif (isset($this->{$property}) && $property !== 'db') {
            return $this->db->sanitizeOutput($this->{$property});
        }
    }

    public function set($property, $value) {
        if (is_array($value) && preg_match('/^(address|phone_number)/', $property)) {
            if ($this->db->testing || !$this->db->production) {
                $this->db->consoleOut("Setting {$property} to " . json_encode($value), 'CONTACT');
            }
            foreach ($value as $val) {
                if (is_array($val)) {
                    $this->set($property, $val);
                    continue;
                }
                $this->add_contact_info($val);
            }
        } elseif (preg_match('/^(address|phone_number)/', $property)) {
            if ($this->db->testing || !$this->db->production) {
                $this->db->consoleOut("Setting {$property} to " . json_encode($value), 'CONTACT');
            }
            $this->add_contact_info($value);
        } elseif (property_exists($this, $property) && $property !== 'db') {
            if ($this->db->testing || !$this->db->production) {
                $this->db->consoleOut("Setting {$property} to {$value}", 'CONTACT');
            }
            $this->{$property} = $this->db->sanitizeInput($value);
        }
    }

    public function get_contact_info($type, $contact_id = -1) {
        if ($contact_id < 1) {
            $contact_id = $this->id;
        }
        $info = $type === 'address' ? new ContactAddress($this->db, -1, $contact_id) : new ContactPhoneNumber($this->db, -1, $contact_id);
        $this->set($type, $info->get_all_{$type}());
        return $this->{$type};
    }

    public function add_contact_info($contact_info) {
        if (get_class($contact_info) == "ContactAddress") {
            $this->address[] = $contact_info;
        } elseif (get_class($contact_info) == "ContactPhoneNumber") {
            $phone_type = $contact_info->phone_type;
            if (empty($this->phone_number)) {
                $this->phone_number = array();
            }
            if (!isset($this->phone_number[$phone_type])) {
                $this->phone_number[$phone_type] = array();
            }
            $this->phone_number[$phone_type][] = $contact_info;
        }
    }

    private function create_contact_info($type) {
        if (empty($this->id) || $this->id < 1 || !preg_match('/^(address|phone_number)/', $type)) {
            return null;
        }
        foreach ($this->{$type} as $key => &$val) {
            if (!is_array($val)) {
                $val->set('contact_id', $this->id);
                $val->{"create_contact_{$type}"}();
                if ($this->db->testing || !$this->db->production) {
                    $this->db->consoleOut("Created an Address", 'CONTACT');
                }
                continue;
            }
            foreach ($val as &$v) {
                $v->set('contact_id', $this->id);
                $v->set('phone_type', $key);
                $v->{"create_contact_{$type}"}();
                if ($this->db->testing || !$this->db->production) {
                    $this->db->consoleOut("Created a Phone Number", 'CONTACT');
                }
            }
        }
        return $this->{$type};
    }

    private function update_contact_info($type, $newInfoIn = null, $oldInfoIn = null) {
        if (empty($this->id) || $this->id < 1 || !preg_match('/^(address|phone_number)/', $type)) {
            return null;
        }

        $contact_info_ids = $tempArray = array();

        $newInfo = empty($newInfoIn) ? $this->{$type} : $newInfoIn;

        $cnt = count($newInfo);

        $class = "Contact" . $this->db->underscoreToCamel($type);

        $tempInfo = new $class($this->db, -1, $this->id);
        $oldInfo = empty($oldInfoIn) ? $tempInfo->{"get_all_contact_{$type}"}() : $oldInfoIn;

        $array = array_merge($newInfo, $oldInfo);

        foreach ($array as $i => $contact_info) {
            if (preg_match('/^(home|work|cell)/', $i) || is_array($contact_info)) {
                foreach ($contact_info as $info) {
                    $tempArray[] = $info;
                }
            } elseif (count($tempArray) && $cnt < 1) {
                continue;
            } elseif ($cnt > 0 && $contact_info->id > 0) {
                $contact_info_ids[] = $contact_info->id;
                $contact_info->{"update_contact_{$type}"}();
            } elseif ($cnt > 0) {
                $contact_info->{"create_contact_{$type}"}();
            } elseif ($cnt < 1 && !in_array($contact_info->id, $contact_info_ids)) {
                $contact_info->{"delete_contact_{$type}"}();
            }
            --$cnt;
        }

        return empty($tempArray) ? $this->{$type} : $this->update_contact_info($type, $tempArray, $oldInfo);
    }

    public function delete_contact_info($contact_info) {
        if (!preg_match('/^(ContactAddress|ContactPhoneNumber)/', get_class($contact_info)) || (!in_array($contact_info, $this->address) && !in_array($contact_info->phone_type, $this->phone_number))) {
            return null;
        }
        $type = get_class($contact_info) == "ContactAddress" ? 'address' : 'phone_number';
        $thisProp = $type === 'address' ? $this->{$type} : $this->{$type}[$contact_info->phone_type];
        foreach ($this->{$thisProp} as $i => $info) {
            if ($contact_info == $info) {
                $contact_info->delete_contact_{$type}();
                unset($this->{$thisProp}[$i]);
                return $info;
            }
        }
    }

    public function create_contact() {
        $ob_vars = get_object_vars($this);
        $columns = $values = array();

        foreach ($ob_vars as $prop => $val) {
            if ($prop !== 'id' && $prop !== 'db' && (!isset($val) || empty($val) || $val < 0) && preg_match('/^(first_name|address|phone_number)/', $prop)) {
                return null;
            } elseif (!preg_match('/^(db|id|address|phone_number)/', $prop)) {
                $columns[] = "`{$prop}`";
                $values[] = "'{$val}'";
            }
        }

        $table = $this->db->camelToUnderscore(get_class($this));
        $cols = implode(',', $columns);
        $vals = implode(',', $values);
        $this->db->insert("INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})");
        $this->set('id', end($this->search_contact_ids(null, true)));
        $GLOBALS['tracking']->add_event("Created {$this->first_name} {$this->middle_name} {$this->last_name}", $this, $this->id);
        $this->create_contact_info('address');
        $this->create_contact_info('phone_number');
        return $this;
    }

    public function get_as_json($array = null) {
        $ob_vars = is_array($array) ? $array : get_object_vars($this);
        if (isset($ob_vars['db'])) {
            unset($ob_vars['db']);
        }
        foreach ($ob_vars as &$val) {
            if (is_array($val)) {
                $val = $this->get_as_json($val);
            } elseif (is_object($val) && method_exists($val, 'get_as_json')) {
                $val = $val->get_as_json();
            }
        }
        //return json_encode($ob_vars);
        return $ob_vars;
    }

    public function get_all_contacts($summary = true) {
        $table = $this->db->camelToUnderscore(get_class($this));
        $contact_ids = array();
        while ($row = $this->db->select_assoc("SELECT `id` FROM `{$table}`")) {
            $contact_ids[] = $row['id'];
        }

        $contact_list = $this->retrieve_contacts_by_ids($contact_ids, $summary);

        if (is_array($contact_list)) {
            usort($contact_list, array($this, "compare_contacts"));
        }

        return $contact_list;
    }

    public function get_contact() {
        $contact = new Contact($this->db);
        if (isset($this->id) && $this->id > 0) {
            $contact = $this->retrieve_contact_by_id($this->id, false);
        } else {
            $contacts = $this->search_contact();
            if (is_array($contacts)) {
                $contact = end($contacts);
            }
        }
        $ob_vars = get_object_vars($contact);

        foreach ($ob_vars as $prop => $val) {
            if ($prop !== 'db') {
                $this->set($prop, $val);
            }
        }

        return $this;
    }

    public function search_contact($name = "") {
        $contact_list = $this->retrieve_contacts_by_ids($this->search_contact_ids($name));
        if (is_array($contact_list)) {
            usort($contact_list, array($this, "compare_contacts"));
        }
        return $contact_list;
    }

    private function search_contact_ids($nameIn = "", $only_contact = false) {
        $ob_vars = get_object_vars($this);
        $name = $this->db->sanitizeInput($nameIn);
        $contact_ids = $have_value = array();

        foreach ($ob_vars as $prop => $val) {
            if (!empty($name) && $prop === 'first_name') {
                $have_value[] = "MATCH(`first_name`,`middle_name`,`last_name`) AGAINST('{$name}')";
            } elseif ((!preg_match('/^(db|id|first_name|middle_name|last_name|address|phone_number)/', $prop) || (empty($name) && preg_match('/^(first_name|middle_name|last_name)/', $prop))) && isset($val) && !empty($val)) {
                $have_value[] = "`{$prop}` LIKE '%{$val}%'";
            }
        }

        if (!empty($have_value)) {
            $table = $this->db->camelToUnderscore(get_class($this));
            $have = implode(" AND ", $have_value);
            $query = "SELECT `id` FROM `{$table}` WHERE {$have}";

            while ($row = $this->db->select_assoc($query)) {
                $contact_ids[] = $row['id'];
            }
        }
        return $only_contact ? $contact_ids : $this->search_contact_id_by_contact_info('phone_number', $this->search_contact_id_by_contact_info('address', $contact_ids));
    }

    private function search_contact_id_by_contact_info($type, $contact_ids = array(), $arrayIn = null) {
        $array = is_array($arrayIn) ? $arrayIn : $this->{$type};
        $ids = array();
        if (isset($array) && !empty($array)) {
            foreach ($array as $contact_info) {
                if (isset($contact_info) && !empty($contact_info)) {
                    $ids = is_array($contact_info) ? $this->search_contact_id_by_contact_info($type, $contact_ids, $contact_info) : $contact_info->{"get_all_contact_{$type}"}(true);
                }
            }
        }
        if (!empty($ids)) {
            return empty($contact_ids) ? $ids : array_intersect($contact_ids, $ids);
        } else {
            return $contact_ids;
        }
    }

    private function retrieve_contacts_by_ids($contact_ids = array(), $summary = true) {
        if (empty($contact_ids)) {
            return null;
        }
        $contacts = array();

        foreach ($contact_ids as $contact_id) {
            $contacts[] = $this->retrieve_contact_by_id($contact_id, $summary);
        }
        return $contacts;
    }

    private function retrieve_contact_by_id($idIn, $summary = true, $getMultiArray = false) {
        $ob_vars = get_object_vars($this);
        $table = $this->db->camelToUnderscore(get_class($this));
        $id = isset($idIn) ? $idIn : $this->id;
        $columns = $values = $numbers = array();

        foreach ($ob_vars as $prop => $val) {
            if (!preg_match('/^(db|address|phone_number|email|notes)/', $prop) || (!$summary && preg_match('/^(email|notes)/', $prop))) {
                $columns[] = "`{$prop}`";
            }
        }
        $cols = implode(', ', $columns);
        $row = $this->db->select_assoc("SELECT {$cols} FROM `{$table}` WHERE `id`={$id}");

        if (!$summary) {
            $address = new ContactAddress($this->db, -1, $id);
            $row['address'] = $address->get_all_contact_address();
            $phone_number = new ContactPhoneNumber($this->db, -1, $id);
            $phone_numbers = $phone_number->get_all_contact_phone_number();
            $row['phone_number'] = $phone_numbers;
            if ($getMultiArray) {
                foreach ($phone_numbers as $phone) {
                    if (!isset($numbers[$phone->phone_type])) {
                        $numbers[$phone->phone_type] = array();
                    }
                    $numbers[$phone->phone_type][] = $phone;
                }
                $row['phone_number'] = $numbers;
            }
        }

        foreach ($ob_vars as $prop => &$val) {
            $values[$prop] = isset($row[$prop]) ? $row[$prop] : $val;
        }

        $reflect = new ReflectionClass($this);
        return $reflect->newInstanceArgs($values);
    }

    public function update_contact() {
        if (!isset($this->id) || $this->id < 1 || empty($this->first_name) || empty($this->last_name) || empty($this->address) || empty($this->phone_number['work'])) {
            return null;
        }
        $ob_vars = get_object_vars($this);
        $table = $this->db->camelToUnderscore(get_class($this));
        $set_to = $columns = $changed_col = array();

        foreach ($ob_vars as $prop => $val) {
            if (!preg_match('/^db|address|phone_number/', $prop)) {
                $columns[] = "`{$prop}`";
            }
        }
        $cols = implode(',', $columns);
        $orig_cols = $this->db->select_assoc("SELECT {$cols} FROM `{$table}` WHERE `id` = {$this->id}");

        foreach ($ob_vars as $prop => $val) {
            if (!preg_match('/^db|address|phone_number/', $prop) && $val != $orig_cols[$prop]) {
                $changed_col[$prop] = $val;
                $GLOBALS['tracking']->add_event("Modified {$this->first_name} {$this->middle_name} {$this->last_name} {$prop} from {$orig_cols[$prop]} to {$val}", $this, $this->id);
            }
        }

        foreach ($changed_col as $prop => $val) {
            if ((!isset($val) || empty($val)) && preg_match('/^(id|first_name|last_name)/', $prop)) {
                return null;
            }
            $set_to[] = "`{$prop}` = '{$val}'";
        }

        if (!empty($set_to)) {
            $set = implode(',', $set_to);
            $this->db->update("UPDATE `{$table}` SET {$set} WHERE `id`={$this->id}");
        }

        $this->update_contact_info('address');
        $this->update_contact_info('phone_number');

        return $this;
    }

    public function delete_contact() {
        if (!isset($this->id) || $this->id < 1) {
            return null;
        }

        foreach ($this->address as &$address) {
            $address->delete_contact_address();
        }

        foreach ($this->phone_number as $phone_type => &$phone_number) {
            foreach ($phone_number as &$number) {
                $number->delete_contact_phone_number();
            }
        }

        $table = $this->db->camelToUnderscore(get_class($this));
        $this->db->delete("DELETE FROM `{$table}` WHERE `id`={$this->id}");
        $GLOBALS['tracking']->add_event("Deleted {$this->first_name} {$this->middle_name} {$this->last_name}", $this, $this->id);
        return $this;
    }

    private function compare_contacts($a, $b) {
        for ($i = 0; $i < 4; ++$i) {
            switch ($i) {
                case 0:
                    $result = strcasecmp($a->last_name, $b->last_name);
                    break;
                case 1:
                    $result = strcasecmp($a->first_name, $b->first_name);
                    break;
                case 2:
                    $result = strcasecmp($a->middle_name, $b->middle_name);
                    break;
                default:
                    $result = $a->id < $b->id ? -1 : 1;
            }
            if ($result !== 0) {
                return $result;
            }
        }
        return $result;
    }

}

class ContactAddress {

    private $db;
    private $id;
    private $contact_id;
    private $street;
    private $city;
    private $province;
    private $country;
    private $postal_code;

    public function __construct(&$db) {
        $ob_vars = get_object_vars($this);
        $args = func_get_args();
        $count = count($args);
        $i = 0;
        $this->id = -1;
        $this->contact_id = -1;

        foreach ($ob_vars as $prop => $val) {
            if ($prop === 'db' && $args[$i] instanceof DBConnect) {
                $this->db = $db;
            } elseif ($prop !== 'db' && $i < $count && isset($args[$i]) && !empty($args[$i])) {
                $this->set($prop, $args[$i]);
            } elseif ($prop !== 'db') {
                $this->{$prop} = "";
            }
            ++$i;
        }
    }

    public function __get($property) {
        if (isset($this->{$property}) && $property !== 'db') {
            return $this->db->sanitizeOutput($this->{$property});
        }
    }

    public function set($property, $value) {
        if (property_exists($this, $property) && $property !== 'db') {
            if ($this->db->testing || !$this->db->production) {
                $this->db->consoleOut("Setting {$property} to {$value}", 'ADDRESS');
            }
            $this->{$property} = $this->db->sanitizeInput($value);
        }
    }

    public function create_contact_address() {
        if (!isset($this->contact_id) || $this->contact_id < 1 || empty($this->street) || empty($this->city) || empty($this->province) || empty($this->country) || empty($this->postal_code)) {
            return null;
        }
        $ob_vars = get_object_vars($this);
        $columns = $values = array();
        foreach ($ob_vars as $prop => $val) {
            if (!preg_match('/^(db|id)/', $prop)) {
                $columns[] = "`{$prop}`";
                $values[] = "'{$val}'";
            }
        }
        $cols = implode(', ', $columns);
        $vals = implode(', ', $values);
        $table = $this->db->camelToUnderscore(get_class($this));
        $this->db->insert("INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})");
        $GLOBALS['tracking']->add_event("Created {$this->street}, {$this->city}, {$this->province}, {$this->country}, {$this->postal_code}", $this, $this->contact_id);
        return $this;
    }

    public function get_as_json($array = null) {
        $ob_vars = is_array($array) ? $array : get_object_vars($this);
        if (isset($ob_vars['db'])) {
            unset($ob_vars['db']);
        }
        foreach ($ob_vars as &$val) {
            if (is_array($val)) {
                $val = $this->get_as_json($val);
            } elseif (is_object($val) && method_exists($val, 'get_as_json')) {
                $val = $val->get_as_json();
            }
        }
        //return json_encode($ob_vars);
        return $ob_vars;
    }

    public function get_all_contact_address($contact_ids_only = false) {
        $addresses = isset($this->id) && $this->id > 0 ? $this->retrieve_address_by_id() : $this->search_contact_address();
        if (!$contact_ids_only) {
            return $addresses;
        }
        $contact_ids = array();
        foreach ($addresses as $address) {
            if ($address->contact_id > 0) {
                $contact_ids[] = $address->contact_id;
            }
        }
        return $contact_ids;
    }

    public function get_contact_address() {
        $address = isset($this->id) && $this->id > 0 ? $this->retrieve_address_by_id() : $this->search_contact_address();

        if ($address->id < 1) {
            return null;
        }
        $ob_vars = get_object_vars($address);

        foreach ($ob_vars as $prop => $val) {
            if ($prop !== 'db') {
                $this->set($prop, $val);
            }
        }

        return $this;
    }

    private function search_contact_address() {
        $addresses = $need_value = $have_value = array();
        $ob_vars = get_object_vars($this);

        foreach ($ob_vars as $prop => $val) {
            if (!preg_match('/^(db|id|contact_id)/', $prop) && isset($val) && !empty($val) || ($prop === 'contact_id' && $val > 0)) {
                $have_value[] = $prop === 'contact_id' ? "`{$prop}` = {$val}" : "`{$prop}` LIKE '%{$val}%'";
            } elseif (!preg_match('/^(db|id)/', $prop)) {
                $need_value[] = "`{$prop}`";
            }
        }

        if (empty($have_value)) {
            return $addresses;
        }
        $table = $this->db->camelToUnderscore(get_class($this));
        $have = implode(" AND ", $have_value);
        $needs = empty($need_value) ? "" : ", " . implode(", ", $need_value);

        while ($row = $this->db->select_assoc("SELECT `id`{$needs} FROM `{$table}` WHERE {$have}")) {
            $values = array();
            foreach ($ob_vars as $prop => &$val) {
                $values[] = isset($row[$prop]) ? $row[$prop] : $val;
            }
            $reflect = new ReflectionClass($this);
            $addresses[] = $reflect->newInstanceArgs($values);
        }
        return $addresses;
    }

    private function retrieve_address_by_id($idIn = null) {
        $ob_vars = get_object_vars($this);
        $table = $this->db->camelToUnderscore(get_class($this));
        $id = isset($idIn) ? $idIn : $this->id;
        $columns = $values = array();

        foreach ($ob_vars as $prop => $val) {
            if (!preg_match('/^(db|id)/', $prop)) {
                $columns[] = "`{$prop}`";
            }
        }
        $cols = implode(', ', $columns);
        $row = $this->db->select_assoc("SELECT {$cols} FROM `{$table}` WHERE `id`={$id}");

        foreach ($ob_vars as $prop => $val) {
            $values[] = isset($row[$prop]) ? $row[$prop] : $val;
        }

        $reflect = new ReflectionClass($this);
        return $reflect->newInstanceArgs($values);
    }

    public function update_contact_address() {
        if (!isset($this->id) || $this->id < 0 || !isset($this->contact_id) || $this->contact_id < 1 || empty($this->street) || empty($this->city) || empty($this->country) || empty($this->postal_code)) {
            return null;
        }
        $ob_vars = get_object_vars($this);
        $table = $this->db->camelToUnderscore(get_class($this));
        $set_to = $columns = $changed_col = array();

        foreach ($ob_vars as $prop => $val) {
            if ($prop !== 'db') {
                $columns[] = "`{$prop}`";
            }
        }
        $cols = implode(',', $columns);
        $orig_cols = $this->db->select_assoc("SELECT {$cols} FROM `{$table}` WHERE `id` = {$this->id}");

        foreach ($ob_vars as $prop => $val) {
            if ($prop !== 'db' && $val != $orig_cols[$prop]) {
                $changed_col[$prop] = $val;
                $GLOBALS['tracking']->add_event("Modified {$this->street}, {$this->city}, {$this->province}, {$this->country}, {$this->postal_code} {$prop} from {$orig_cols[$prop]} to {$val}", $this, $this->contact_id);
            }
        }

        foreach ($changed_col as $prop => $val) {
            if ((!isset($val) || empty($val) || $val < 1) && preg_match('/^(id|contact_id)/', $prop)) {
                return null;
            }
            $set_to[] = "`{$prop}` = '{$val}'";
        }

        $set = implode(',', $set_to);
        $this->db->update("UPDATE `{$table}` SET {$set} WHERE `id`={$this->id}");

        return $this;
    }

    public function delete_contact_address() {
        if (!isset($this->id) || $this->id < 1) {
            return null;
        }
        $table = $this->db->camelToUnderscore(get_class($this));
        $this->db->delete("DELETE FROM `{$table}` WHERE `id`={$this->id}");
        $GLOBALS['tracking']->add_event("Deleted {$this->street}, {$this->city}, {$this->province}, {$this->country}, {$this->postal_code}", $this, $this->contact_id);
        return $this;
    }

}

class ContactPhoneNumber {

    private $db;
    private $id;
    private $contact_id;
    private $phone_type;
    private $phone_number;

    public function __construct(&$db) {
        $ob_vars = get_object_vars($this);
        $args = func_get_args();
        $count = count($args);
        $i = 0;
        $this->id = -1;
        $this->contact_id = -1;

        foreach ($ob_vars as $prop => $val) {
            if ($prop === 'db' && $args[$i] instanceof DBConnect) {
                $this->db = $db;
            } elseif ($prop !== 'db' && $i < $count && isset($args[$i]) && !empty($args[$i])) {
                $this->set($prop, $args[$i]);
            } elseif ($prop !== 'db') {
                $this->{$prop} = "";
            }
            ++$i;
        }
    }

    public function __get($property) {
        if (isset($this->{$property}) && $property !== 'db') {
            return $this->db->sanitizeOutput($this->{$property});
        }
    }

    public function set($property, $value) {
        if (property_exists($this, $property) && $property !== 'db') {
            if ($this->db->testing || !$this->db->production) {
                $this->db->consoleOut("Setting {$property} to {$value}", 'PHONE');
            }
            $this->{$property} = $this->db->sanitizeInput($value);
        }
    }

    public function create_contact_phone_number() {
        if (!isset($this->contact_id) || $this->contact_id < 1 || empty($this->phone_type) || empty($this->phone_number)) {
            return null;
        }
        $ob_vars = get_object_vars($this);
        $columns = $values = array();
        foreach ($ob_vars as $prop => $val) {
            if (!preg_match('/^(db|id)/', $prop)) {
                $columns[] = "`{$prop}`";
                $values[] = "'{$val}'";
            }
        }
        $cols = implode(', ', $columns);
        $vals = implode(', ', $values);
        $table = $this->db->camelToUnderscore(get_class($this));
        $this->db->insert("INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})");
        $GLOBALS['tracking']->add_event("Created {$this->phone_number}", $this, $this->contact_id);
        return $this;
    }

    public function get_as_json($array = null) {
        $ob_vars = is_array($array) ? $array : get_object_vars($this);
        if (isset($ob_vars['db'])) {
            unset($ob_vars['db']);
        }
        foreach ($ob_vars as &$val) {
            if (is_array($val)) {
                $val = $this->get_as_json($val);
            } elseif (is_object($val) && method_exists($val, 'get_as_json')) {
                $val = $val->get_as_json();
            }
        }
        //return json_encode($ob_vars);
        return $ob_vars;
    }

    public function get_all_contact_phone_number($contact_ids_only = false) {
        $phone_numbers = isset($this->id) && $this->id > 0 ? $this->retrieve_contact_phone_number_by_id() : $this->search_contact_phone_number();

        if (!$contact_ids_only) {
            return $phone_numbers;
        }
        $contact_ids = array();
        foreach ($phone_numbers as $phone_number) {
            if ($phone_number->contact_id > 0) {
                $contact_ids[] = $phone_number->contact_id;
            }
        }
        return $contact_ids;
    }

    public function get_contact_phone_number() {
        $ob_vars = get_object_vars($this);
        $phone_number = isset($this->id) && $this->id > 0 ? $this->retrieve_phone_numbers_by_id() : end($this->search_contact_phone_number());

        foreach ($ob_vars as $prop => $val) {
            if ($prop !== 'db') {
                $this->set($prop, $val);
            }
        }
        return $this;
    }

    private function search_contact_phone_number() {
        $ob_vars = get_object_vars($this);
        $phone_numbers = $have_value = $need_value = array();

        foreach ($ob_vars as $prop => $val) {
            if (!preg_match('/^(db|id|contact_id)/', $prop) && isset($val) && (!empty($val) || $val > 0) || ($prop === 'contact_id' && $val > 0)) {
                $have_value[] = $prop === 'contact_id' ? "`{$prop}` = {$val}" : "`{$prop}` LIKE '%{$val}%'";
            } elseif (!preg_match('/^(db|id)/', $prop)) {
                $need_value[] = "`{$prop}`";
            }
        }

        if (empty($have_value)) {
            return $phone_numbers;
        }

        $table = $this->db->camelToUnderscore(get_class($this));
        $have = implode(" AND ", $have_value);
        $needs = empty($need_value) ? "" : ", " . implode(", ", $need_value);

        while ($row = $this->db->select_assoc("SELECT `id`{$needs} FROM `{$table}` WHERE {$have}")) {
            $values = array();
            foreach ($ob_vars as $prop => &$val) {
                $values[] = isset($row[$prop]) ? $row[$prop] : $val;
            }
            $reflect = new ReflectionClass($this);
            $phone_numbers[] = $reflect->newInstanceArgs($values);
        }

        return $phone_numbers;
    }

    private function retrieve_contact_phone_number_by_id($idIn = null) {
        $ob_vars = get_object_vars($this);
        $table = $this->db->camelToUnderscore(get_class($this));
        $id = isset($idIn) ? $idIn : $this->id;
        $columns = $values = array();
        foreach ($ob_vars as $prop => $val) {
            if ($prop !== 'db') {
                $columns[] = "`$prop'";
            }
        }
        $cols = implode(", ", $columns);
        $row = $this->db->select_assoc("SELECT {$cols} FROM `{$table}` WHERE `id` = {$id}");
        foreach ($row as $prop => $val) {
            $values[] = isset($row[$prop]) ? $row[$prop] : $val;
        }
        $reflect = new ReflectionClass($this);
        return $reflect->newInstanceArgs($values);
    }

    public function update_contact_phone_number() {
        if (!isset($this->id) && $this->id < 1 || !isset($this->contact_id) || $this->contact_id < 1 && empty($this->phone_type) || empty($this->phone_number)) {
            return null;
        }
        $ob_vars = get_object_vars($this);
        $table = $this->db->camelToUnderscore(get_class($this));
        $set_to = $columns = $changed_col = array();

        foreach ($ob_vars as $prop => $val) {
            if ($prop !== 'db') {
                $columns[] = "`{$prop}`";
            }
        }
        $cols = implode(',', $columns);
        $orig_cols = $this->db->select_assoc("SELECT {$cols} FROM `{$table}` WHERE `id` = {$this->id}");

        foreach ($ob_vars as $prop => $val) {
            if ($prop !== 'db' && $val != $orig_cols[$prop]) {
                $changed_col[$prop] = $val;
                $GLOBALS['tracking']->add_event("Modified {$this->phone_number} {$prop} from {$orig_cols[$prop]} to {$val}", $this, $this->contact_id);
            }
        }

        foreach ($changed_col as $prop => $val) {
            if ((!isset($val) || empty($val) || $val < 1) && preg_match('/^(id|contact_id)/', $prop)) {
                return null;
            }
            $set_to[] = "`{$prop}` = '{$val}'";
        }

        $set = implode(',', $set_to);
        $this->db->update("UPDATE `{$table}` SET {$set} WHERE `id` = {$this->id}");

        return $this;
    }

    public function delete_contact_phone_number() {
        if (!isset($this->id) || $this->id < 1) {
            return null;
        }
        $table = $this->db->camelToUnderscore(get_class($this));
        $query = "DELETE FROM `{$table}` WHERE `id` = {$this->id}";
        $this->db->delete($query);
        $GLOBALS['tracking']->add_event("Deleted {$this->phone_number}", $this, $this->contact_id);
        return $this;
    }

}
