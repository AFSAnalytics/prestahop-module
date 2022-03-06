<?php

class AFSAAccountFormRenderer
{
    public static function getTemplateData()
    {
        return   [
            'type' => 'intro',
            'img' => [
                'logo' => AFSAConfig::getURL('views/img/logo.small.png'),
                'screen' => AFSAConfig::getURL('views/img/intro.screen.png'),
            ],
            'txt' => [
                'configure_account' => AFSAConfig::TR('configure_account'),
                'existing_account_help' => AFSAConfig::TR('existing_account_help'),
                'my_account_id' => AFSAConfig::TR('my_account_id'),
                'link_existing_account' => AFSAConfig::TR('link_existing_account'),
                'create_new_account_help' => AFSAConfig::TR('create_new_account_help'),
                'create_new_account_help_more' => AFSAConfig::TR('create_new_account_help_more'),
                'start_free_trial' => AFSAConfig::TR('start_free_trial'),
                'live_demo' => AFSAConfig::TR('live_demo'),
                'live_demo_help' => AFSAConfig::TR('live_demo_help'),
                'launch_demo' => AFSAConfig::TR('launch_demo'),
            ],
            'url' => [
                'demo' => AFSAConfig::getDashboardDemoURL(),
            ],
            'jsCode' => 'var AFSA_site_infos=' . json_encode(AFSAAccountManager::get()->getAccountCreationParams()) . ';',
        ];
    }

    public static function redirectOnAccountLinked()
    {
        $account_id = Tools::getValue('afsa_linked_account_id');

        if (AFSAConfig::validateAccountID($account_id)) {
            AFSAConfig::saveAccountID($account_id);

            $url = AFSATools::buildURL(AFSAConfig::getAccountManagerURL(), array('afsa_action' => 'link_account', 'account_id' => $account_id));

            AFSATools::redirect($url);
        }
    }
}
