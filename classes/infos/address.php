<?php

include_once 'visitor.php';

class AFSAAddressInfos
{
    private $data;
    private $o;

    public function __construct($mixed = null)
    {
        $this->data = array();

        if (is_object($mixed)) {
            $this->setObject($mixed);
        } else {
            $this->setObjectByID((int) $mixed);
        }

        $this->retrieve();
    }

    public function validate()
    {
        return
                Validate::isLoadedObject($this->o) &&
                empty($this->o->deleted) &&
                !empty($this->o->id_customer) // make sure its a customer address
        ;
    }

    public function setObject($o)
    {
        if (Validate::isLoadedObject($o)) {
            $this->o = $o;
        }
    }

    public function setObjectByID($id)
    {
        if ($id) {
            $this->o = new Address($id);
        }
    }

    /**
     * return collected data
     *
     * @return array
     */
    public function get()
    {
        return $this->data;
    }

    private function retrieve()
    {
        if (!$this->validate()) {
            return false;
        }

        $this->parse($this->o);

        return true;
    }

    public function parse()
    {
        $o = &$this->o;

        if (!$this->validate()) {
            return null;
        }

        $ret = array(
            'yourid' => $o->id_customer,
        );

        $fields = array(
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'company' => 'company',
            'address1' => 'address',
            'address2' => 'addressplus',
            'city' => 'city',
            'postcode' => 'zipcode',
            'country' => 'country',
            'phone' => 'phone',
            'phone_mobile' => 'phone',
        );

        try {
            $fields['country_code'] = Country::getIsoById($o->id_country);
        } catch (Exception $ex) {
        }

        foreach ($fields as $k => $v) {
            if (!empty($o->{$k})) {
                switch ($k) {
                    case 'lastname':
                        $ret[$v] = AFSAVisitorInfos::renderLastname($o->{$k});
                        break;

                    case 'phone':
                    case 'phone_mobile':
                        $ret[$v] = AFSAVisitorInfos::renderPhone($o->{$k});
                        break;

                    default:
                        $ret[$v] = $o->{$k};
                        break;
                }
            }
        }

        try {
            if (isset($o->id_country)) {
                $country = new Country($o->id_country);
                if ($country) {
                    $ret['country_code'] = Tools::strtolower($country->iso_code);
                }
            }
        } catch (Exception $ex) {
        }

        return $this->data = $ret;
    }
}
