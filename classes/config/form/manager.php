<?php

// Manage Configuration page forms

include_once 'form.php';

include_once 'main.php';
include_once 'autotrack.php';
include_once 'privacy.php';
include_once 'GDPR.php';
include_once 'dashboard.php';

class AFSAConfigFormManager
{
    private $form = array(
        'Main' => null,
        'AutoTrack' => null,
        'Privacy' => null,
        'GDPR' => null,
        'Dashboard' => null,
    );

    public function __construct($module)
    {
        $this->module = &$module;
        $this->account = AFSAConfig::getAccountID();
    }

    private function getForm($id)
    {
        if (empty($this->form[$id])) {
            $form = null;
            switch ($id) {
                case 'Main':
                    $form = new AFSAConfigFormMain($this->module);
                    break;

                case 'AutoTrack':
                    $form = new AFSAConfigFormAutoTrack($this->module);
                    break;

                case 'Privacy':
                    $form = new AFSAConfigFormPrivacy($this->module);
                    break;

                case 'GDPR':
                    $form = new AFSAConfigFormGDPR($this->module);
                    break;

                case 'Dashboard':
                    $form = (int) $this->account ?
                        new AFSAConfigFormDashboard($this->module) :
                        null;
                    break;
            }

            if (!$form) {
                return null;
            }

            return $this->form[$id] = $form;
        }

        return $this->form[$id];
    }

    public function install()
    {
        foreach ($this->form as $id => $f) {
            if (empty($f)) {
                $f = $this->getForm($id);
            }
            if ($f) {
                $f->install();
            }
        }
    }

    public function uninstall()
    {
        foreach ($this->form as $id => $f) {
            if (empty($f)) {
                $f = $this->getForm($id);
            }
            $f->uninstall();
        }
    }

    public function render()
    {
        $ret = AFSATools::renderJSData(array('AFSA_site_infos' => AFSAAccountManager::get()->getAccountCreationParams()));

        foreach ($this->form as $id => $f) {
            if (empty($f)) {
                $f = $this->getForm($id);
            }
            if ($f) {
                $ret .= $f->onSubmit()
                    . $f->render();
            }
        }

        return $ret;
    }
}
