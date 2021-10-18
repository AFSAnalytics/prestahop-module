<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/api/oauth/token.php';

class AFSAConfigFormDashboard extends AFSAConfigForm
{
    public function __construct($module)
    {
        $this->form_id = 'Dashboard';

        parent::__construct($module);

        $this->fields = array('token_state');

        $this->account = AFSAConfig::getAccountID();

        $this->token = null;
        if ((int) $this->account > 0) {
            $t = new AFSAOAuthToken($this->account);
            if ($t->load()) {
                $this->token = $t;
            }
        }
    }

    public function install()
    {
    }

    public function onSubmit($msg = null)
    {
        $module = $this->module;

        if (!Tools::isSubmit($this->getSubmitName())) {
            return null;
        }

        if ($this->token) {
            $this->token->clear();
            $msg = $this->l('Token cleared');
            $this->token = null;
        }

        return $module->displayConfirmation($module->l(empty($msg) ? 'Settings updated' : $msg));
    }

    public function getFieldsData()
    {
        $ret = array();

        $form = array(
            'legend' => array(
                'title' => $this->l('AFS Analytics embedded dashboard'),
            ),
        );

        if ($this->token) {
            $msg = $this->l('A valid authentication token has been found which will expire in')
                . ' ' . $this->token->daysBeforeExpiration() . ' '
                . $this->l('days') . '.';

            if (_PS_VERSION_ >= 1.7) {
                $form['success'] = $msg;
            } else {
                $form['input'] = array(
                    array(
                        'type' => 'free',
                        'label' => $msg,
                        'name' => 'token_state',
                        'value' => $this->token ? true : false,
                    ),
                );
            }

            $form['submit'] = array(
                'id' => 'refresh',
                'title' => $this->l('Refresh Token'),
                'icon' => 'icon-reset', // Icon to show, if any
                'class' => 'btn btn-default pull-right',
                'name' => 'refreshAndStay',
            );
        } else {
            $msg = $this->l('No valid authentication token has been found. Please open AFS Analytics dashboard to generate a new one.');

            if (_PS_VERSION_ >= 1.7) {
                $form['warning'] = $msg;
            } else {
                $form['input'] = array(
                    array(
                        'type' => 'free',
                        'label' => $msg,
                        'name' => 'token_state',
                        'value' => $this->token ? true : false,
                    ),
                );
            }

            $form['buttons'] = array(
                array(
                    'href' => AFSAConfig::getDashboardURL(),
                    'type' => 'submit',
                    'type' => 'button',         // Button type
                    'id' => 'open_afs_dashboard',
                    'name' => 'open_afs_dashboard',  // If not defined, this will take the value of "submitOptions{$table}"
                    'title' => $this->l('Open AFS Analytics Dashboard'),
                ),
            );
        }

        $ret['form'] = $form;

        return array($ret);
    }
}
