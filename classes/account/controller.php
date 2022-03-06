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

    // REQUEST HANDLING

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

        $account->save();

        $this->onAccountSet($id);

        $this->welcome();
    }

    private function onAccountSet($id)
    {
        // Initiate api login since user is authenticated
        if ($this->api_auto_login) {
            $api = new AFSAApi(array('account_id' => $id, 'callback_url' => AFSAConfig::getAccountManagerURL(array('afsa_action' => 'api_initial_login'))));
            $api->login();
        }
    }

    // WELCOME PAGE

    public function renderWelcome()
    {
        return $this->output = AFSATools::renderTemplate(
            'welcome.tpl',
            [
                'logo' => AFSAConfig::getURL('views/img/logo.small.png'),
                'txt' => [
                    'congratulations' => AFSAConfig::TR('congratulations'),
                    'module_configured' => AFSAConfig::TR('module_configured'),
                    'module_working' => AFSAConfig::TR('module_working'),
                    'account_id_available' => AFSAConfig::TR('account_id_available'),
                    'thanks_using_afsa' => AFSAConfig::TR('thanks_using_afsa'),
                    'advanced_configuration' => AFSAConfig::tr('advanced_configuration'),
                    'open_dashboard' => AFSAConfig::TR('open_dashboard'),
                    'visit_afsa' => AFSAConfig::tr('visit_afsa'),
                ],
                'url' => [
                    'config' => AFSAConfig::getConfigControllerURL(),
                    'dashboard' => AFSAConfig::getDashboardURL(),
                ],
            ]
        );
    }
}
