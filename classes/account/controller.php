<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/account/account.php';

class AFSAAccountController
{
    private $output = '';
    private $api_auto_login = true;

    public function getOutput()
    {
        return $this->output;
    }

    // REQUEST HANLING

    private function validateState()
    {
        $state = Tools::getValue('afsa_state');

        if (!$state || AFSAConfig::getRequestState() !== $state) {
            AFSATools::log(__METHOD__, 'invalid state');
            Tools::redirectAdmin(AFSAConfig::getAdminURL());
            exit;
        }

        return $state;
    }

    public function onActionCompleted()
    {
        switch (Tools::getValue('afsa_action')) {
            case 'account_created':
                $this->onAccountCreated();
                break;

            case 'api_initial_login':
                // completing login process
                $api = new AFSAApi();
                $api->simpleLogin();

                $this->welcome();
                break;

            case 'link_account':
                $this->linkAccount();
                break;

            case 'welcome':
                $this->renderWelcome();
                break;
        }
    }

    public function getWelcomeURL($is_new)
    {
        return AFSAConfig::getAccountManagerURL(array('afsa_action' => 'welcome', 'afsa_new' => (int) $is_new));
    }

    public function welcome($is_new = true)
    {
        AFSATools::redirect($this->getWelcomeURL($is_new));
    }

    // EXITING ACCOUNT LINK ACTION

    public function linkAccount()
    {
        $account_id = Tools::getValue('account_id');

        if ($account_id && AFSAAccountManager::get()->setCurrent($account_id)) {
            $this->onAccountSet($account_id);

            $this->welcome();

            return true;
        }

        return false;
    }

    // ACCOUNT CREATION

    public function onAccountCreated()
    {
        $this->validateState();

        $id = Tools::getValue('afsa_account_id');

        // Saving account infos
        $account = $id ? AFSAAccountManager::get()->setCurrent($id) : null;
        if (!$account) {
            return;
        }

        if (!empty($r['afsa_trial_type'])) {
            $account->setTrial($r['afsa_trial_type'], empty($r['afsa_trial_period']) ? 0 : $r['afsa_trial_period']);
        }
        $account->save();

        $this->onAccountSet($id);

        $this->welcome();
    }

    private function onAccountSet($id)
    {
        // Initiate api login since user is authentified
        if ($this->api_auto_login) {
            $api = new AFSAApi(array('account_id' => $id, 'callback_url' => AFSAConfig::getAccountManagerURL(array('afsa_action' => 'api_initial_login'))));
            $api->login();
        }
    }

    // WELCOME PAGE

    public function renderWelcome()
    {
        return $this->output = '<script>'
                . "document.cookie = 'afssetuser=0;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/';\n"
                . '</script>'

                . '<div class=afsa_welcome_container>'
                . '<div class=afsa_content>'
                . '<div class=afsa_header>'
                . '<div class=afsa_title>'
                . '<img class=afsa_logo src=' . AFSAConfig::getURL('views/img/logo.small.png') . '>'
                . '<div class=afsa_label>' . AFSAConfig::TR('congratulations') . '!</div>'
                . '</div>'
                . '<div class=afsa_headline>'
                . AFSAConfig::TR('module_configured')
                . '</div>'
                . '</div>'
                . '<p>' . AFSAConfig::TR('module_working') . '</p>'
                . '<p>' . AFSAConfig::TR('account_id_available') . '</p>'
                . '<div class=afsa_footer>'
                . '<div class=afsa_thanks>' . AFSAConfig::TR('thanks_using_afsa') . '</div>'
                . '</div>'
                . '<div class=afsa_button_bar>'
                . '<a href="' . AFSAConfig::getConfigControllerURL() . '" '
                . ' class="afsa_button">'
                . AFSAConfig::tr('advanced_configuration')
                . '</a>'
                . '<a href="' . AFSAConfig::getDashboardURL() . '" '
                . ' class="afsa_button afsa_open">'
                . AFSAConfig::TR('open_dashboard')
                . '</a>'
                . '<a href="' . AFSARouteManager::getDashboardURL() . '" '
                . ' class="afsa_button">' . AFSAConfig::tr('visit_afsa') . '</a>'
                . '</div>'
                . '</div>' // afsa_content
                . '</div>'; // container
    }
}
