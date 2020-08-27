<?php

class AFSAConfigFormGDPR extends AFSAConfigForm
{
    private $options;

    public function __construct($module)
    {
        $this->form_id = 'GDPR';

        parent::__construct($module);

        $this->fields = array(
            'afs_analytics_user_consent',
            'afs_analytics_localization_setting',
        );

        $this->options = array(
            'afs_analytics_user_consent' => array(
                array('id' => '0', 'name' => $this->l('Disabled')),
                array('id' => '1', 'name' => $this->l('Exemption')),
                array('id' => '2', 'name' => $this->l('Auto')),
            ),
            'afs_analytics_localization_setting' => array(
                array('id' => '0', 'name' => $this->l('Europe')),
                array('id' => '1', 'name' => $this->l('World')),
            ),
        );
    }

    public function install()
    {
        Configuration::updateValue('AFS_ANALYTICS_USER_CONSENT', 0);
        Configuration::updateValue('AFS_ANALYTICS_IP_SETTING', 0);
    }

    public function getFieldsData()
    {
        $ret = array();

        $gdpr_desc = $this->l('How to set-up analytic.js to comply with the European law on the deposit of cookies.')
                . ' <a href="https://www.afsanalytics.com/info/144/how-to-set-up-analytic-js-to-comply-with-european-law-about-cookies.html">'
                . $this->l('Read Guide')
                . '</a>';

        $ret[]['form'] = array(
            'legend' => array(
                'title' => $this->l('EU law on the deposit of cookies'),
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Ask user consent'),
                    'name' => 'afs_analytics_user_consent',
                    'required' => true,
                    'options' => array(
                        'query' => $this->options['afs_analytics_user_consent'],
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Located In'),
                    'name' => 'afs_analytics_localization_setting',
                    'required' => true,
                    'options' => array(
                        'query' => $this->options['afs_analytics_localization_setting'],
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'desc' => $gdpr_desc, ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ),
        );

        return $ret;
    }
}
