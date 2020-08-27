<?php

class AFSAConfigFormPrivacy extends AFSAConfigForm
{
    private $options;

    public function __construct($module)
    {
        $this->form_id = 'Privacy';

        parent::__construct($module);

        $this->fields = array(
            'afs_analytics_cookie_setting',
            'afs_analytics_ip_setting',
            'afs_analytics_anon_user_infos',
        );

        $this->options = array(
            'afs_analytics_cookie_setting' => array(
                array('id' => '0', 'name' => $this->l('First party')),
                array('id' => '1', 'name' => $this->l('No Cookies')),
            ),
            'afs_analytics_ip_setting' => array(
                array('id' => '0', 'name' => $this->l('Disabled')),
                array('id' => '1', 'name' => $this->l('1 Byte')),
                array('id' => '2', 'name' => $this->l('2 Bytes')),
                array('id' => '3', 'name' => $this->l('3 Bytes')),
                array('id' => '4', 'name' => $this->l('4 Bytes')),
            ),
        );
    }

    public function install()
    {
        Configuration::updateValue('AFS_ANALYTICS_COOKIE_SETTING', 0);
        Configuration::updateValue('AFS_ANALYTICS_IP_SETTING', 0);
        Configuration::updateValue('AFS_ANALYTICS_ANON_USER_INFOS', 0);
    }

    public function getFieldsData()
    {
        $ret = array();

        $radioOptions = array(
            array(
                'id' => 'active_on',
                'value' => 1,
                'label' => $this->l('Enabled'),
            ),
            array(
                'id' => 'active_off',
                'value' => 0,
                'label' => $this->l('Disabled'),
        ), );

        $ret[]['form'] = array(
            'legend' => array(
                'title' => $this->l('Privacy Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Cookie Settings'),
                    'name' => 'afs_analytics_cookie_setting',
                    'required' => true,
                    'options' => array(
                        'query' => $this->options['afs_analytics_cookie_setting'],
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Anonymize IP addresses'),
                    'name' => 'afs_analytics_ip_setting',
                    'hint' => $this->l('Use this option to anonymize the visitor’s IP to comply with data privacy laws in some countries'),
                    'required' => true,
                    'options' => array(
                        'query' => $this->options['afs_analytics_ip_setting'],
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Anonymize Users Information'),
                    'name' => 'afs_analytics_anon_user_infos',
                    'values' => $radioOptions,
                    'hint' => $this->l('Use this option to anonymize visitor Information to comply with data privacy laws in some countries'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ),
        );

        return $ret;
    }
}
