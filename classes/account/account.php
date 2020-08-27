<?php

define('AFSA_ACCOUNTS_INFO', 'afsa_accounts');

class AFSAAccount
{
    private $id;

    public function __construct($id = null)
    {
        $this->setID($id);
    }

    public function getID()
    {
        return $this->id;
    }

    public function setID($id)
    {
        $this->id = $id ? $id : AFSAConfig::getAccountID();

        return $this->load();
    }

    public function validate()
    {
        return AFSAConfig::validateAccountID($this->id);
    }

    public function advancedEComEnabled()
    {
        return $this->getPlanID() == 4;
    }

    public function ecomEnabled()
    {
        $id = $this->getPlanID();

        return $id == 4 || $id == 2;
    }

    public function getPlanID()
    {
        return (int) $this->get('plan', 0);
    }

    public function isFree()
    {
        return $this->getPlanID() < 1;
    }

    // DATA STORE

    public function set($mixed, $value = null)
    {
        $arr = array();

        is_array($mixed) ?
                        $arr = $mixed :
                        $arr[$mixed] = $value;

        foreach ($arr as $k => $v) {
            $this->infos[$k] = $v;
        }

        return $this;
    }

    public function get($key, $default = null)
    {
        return empty($this->infos[$key]) ?
                $default :
                $this->infos[$key];
    }

    private function loadStore()
    {
        $store = Configuration::get(AFSA_ACCOUNTS_INFO);

        return $store ?
                json_decode($store, true) :
                [];
    }

    public function load()
    {
        $this->infos = array();

        if ($this->validate()) {
            $store = $this->loadStore();
            if (!empty($store[$this->id])) {
                $this->infos = $store[$this->id];
            }
        }

        return $this;
    }

    public function save()
    {
        if (!$this->validate()) {
            return;
        }

        $store = $this->loadStore();

        $store[$this->id] = $this->infos;

        Configuration::updateValue(AFSA_ACCOUNTS_INFO, json_encode($store));

        return $this;
    }

    public function dump()
    {
        AFSATools::dump($this->infos, true);
        AFSATools::dump($this->trialInfos(), true);
    }

    // TRIAL RELATED

    public function isTrial()
    {
        return !empty($this->infos['trial']);
    }

    private function daysUntil($date_string)
    {
        $from = new DateTime();
        $to = new DateTime($date_string);

        return abs($to->diff($from)->days);
    }

    private function daysSince($date_string)
    {
        $to = new DateTime();
        $from = new DateTime($date_string);

        return abs($to->diff($from)->days);
    }

    public function setTrial($type, $period = null)
    {
        $this->set('plan', $type)
                ->set('trial', 1);

        if (!empty($period)) {
            $date = new DateTime();
            $days = (int) $period;

            $this->set('trial_period', $days);
            $this->set('start_date', $date->format('Y-m-d'));
            $date->modify('+ ' . $days . ' day');
            $this->set('end_date', $date->format('Y-m-d'));
        }
    }

    public function infos()
    {
        return array(
            'remaining' => $this->daysUntil($this->get('end_date')),
            'used' => $this->daysSince($this->get('start_date')),
        );
    }

    // PLAN

    public function updatePlanFromData($data)
    {
        $this->set(array('plan' => $data['id'],
            'name' => $data['name'],
            'trial' => boolval($data['trial']),
        ));

        if (!empty($data['start_date'])) {
            $this->set(array('start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ));
        }

        $this->save();
    }

    public function planInfos()
    {
        $ret = $this->infos;
        // renaming 'plan' property to 'id' as its expected from js
        $ret['id'] = $ret['plan'];
        unset($ret['plan']);

        return $ret;
    }
}
