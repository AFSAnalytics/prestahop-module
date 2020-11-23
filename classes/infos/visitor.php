<?php

/*
 * Fill Visitor Infos from
 * Customer Infos or Employee Infos
 *
 *
 */

class AFSAVisitorInfos
{
    private $data;

    public function __construct()
    {
        $this->data = array();
        $this->retrieve();
    }

    public function isLogged()
    {
        return !empty($this->data['logged']);
    }

    public function getID()
    {
        return empty($this->data['id']) ? 0 : $this->data['id'];
    }

    public function get()
    {
        return $this->data;
    }

    /*
     *
     *
     */

    private function getContext()
    {
        return AFSAConfig::getContext();
    }

    private function retrieve()
    {
        if (empty($context = $this->getContext())) {
            return;
        }

        if ($context->customer != null) {
            $this->retrieveCustomerInfos($context->customer);
        } elseif ($context->employee != null) {
            $this->retrieveEmployeeInfos($context->employee);
        }
    }

    private function retrieveCustomerInfos($o)
    {
        $this->data = array(
            'yourid' => $o->id,
            'role' => 'customer',
            'logged' => $o->isLogged(),
            'gender' => $o->id_gender == 1 ? 'M' : 'F',
            'lastname' => $this->renderLastname($o->lastname),
            'firstname' => $o->firstname,
            'displayedname' => $this->getUsername($o),
            'email' => $this->renderEmail($o->email),
        );

        if (AFSAConfig::anonymizeMembers()) {
            $this->data['anonymised'] = 1;
        }

        if ($o->birthday != '0000-00-00') {
            $this->data['birthday'] = $o->birthday;
        }

        if (!empty($o->company)) {
            $this->data['company'] = $o->company;
        }

        //$address = new Address($this->context->cart->id_address_delivery);
    }

    private function getUsername($o)
    {
        return trim($o->firstname) . ' ' . $this->renderLastname($o->lastname);
    }

    private function retrieveEmployeeInfos($o)
    {
        $this->data = array(
            'yourid' => 'ADM:' . $o->id,
            'role' => $this->getEmployeeProfileName($o->id_profile),
            'logged' => true,
            'lastname' => $this->renderLastname($o->lastname),
            'firstname' => $o->firstname,
            'displayedname' => $this->getUsername($o),
            'email' => $o->email,
        );
    }

    private function getEmployeeProfileName($id)
    {
        $context = $this->getContext();

        foreach (Profile::getProfiles($context->language->id) as $p) {
            if ($p['id_profile'] == $id) {
                return $p['name'];
            }
        }

        return 'employee';
    }

    // ANONYMIZE functions

    public static function renderLastname($name)
    {
        $n = trim($name);

        if (!AFSAConfig::anonymizeMembers()) {
            return $n;
        }

        return Tools::strtoupper(Tools::substr($n, 0, 1)) . '.';
    }

    public static function renderEmail($str)
    {
        $email = trim($str);

        if (!AFSAConfig::anonymizeMembers()) {
            return $email;
        }

        $p = explode('@', $email);
        $ret = '...';
        if (!empty($p[1])) {
            $ret .= '@' . $p[1];
        }

        return $ret;
    }

    public static function renderPhone($str)
    {
        $chars = str_split(trim($str));

        if (!AFSAConfig::anonymizeMembers()) {
            return $str;
        }

        $ret = [];
        $count = 0;
        foreach ($chars as $ch) {
            if (ctype_digit($ch)) {
                if (++$count > 4) {
                    $ch = 'x';
                }
            }
            $ret[] = $ch;
        }

        return implode('', $ret);
    }
}
