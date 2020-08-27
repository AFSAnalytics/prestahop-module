<?php

define('AFSA_PLACE_HOLDER', '00837668 Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus. Suspendisse lectus tortor, dignissim sit amet, adipiscing nec, ultricies sed, dolor.');

class AFSAAccountFormRenderer
{
    public static function renderAccountForm($type = 'intro')
    {
        $logo = '<div class=afsa_logo_container>'
                . '<img class=afsa_logo src=' . AFSAConfig::getURL('views/img/logo.small.png') . '>'
                . '<div class=afsa_intro_title>'
                . AFSAConfig::TR('configure_account')
                . '</div>'
                . '</div>'
        ;

        $ret = $type == 'intro' ?
                '<div class=afsa_account_form>' . $logo :
                $logo . '<div class=afsa_account_form>'
        ;

        return $ret
                . '<form  method=post class=afsa_existing_account>'
                . '<div class="afsa_form_help">' . AFSAConfig::TR('existing_account_help') . '</div>'
                . '<input type="text" pattern="[0-9]{8}" maxlength="8" name="afsa_linked_account_id" '
                . 'value="" placeholder="'
                . AFSAConfig::TR('my_account_id') . '">'
                . '<input type=hidden name=page value=afsa_settings_page>'
                . '<input class="afsa_button" type=submit value="'
                . AFSAConfig::TR('link_existing_account') . '">'
                . '</form>'
                . '<div  class=afsa_new_account>'
                . '<div class="afsa_form_help">'
                . AFSAConfig::TR('create_new_account_help')
                . ' '
                . AFSAConfig::TR('create_new_account_help_more')
                . '</div>'
                . '<div class="afsa_create_account afsa_button"> '
                . AFSAConfig::TR('start_free_trial')
                . '</div>'
                . '</div>'
                . '</div>'
                . AFSATools::renderJSData(array('AFSA_site_infos' => AFSAAccountManager::get()->getAccountCreationParams()))
        ;
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

    // DEMO

    public static function renderDemoForm()
    {
        return '<div class=afsa_demo_form>'
                . '<div class=afsa_row>'
                . '<div class=afsa_text_container>'
                . '<div class=afsa_title>' . AFSAConfig::TR('live_demo') . '</div>'
                . '<div class="afsa_text">' . AFSAConfig::TR('live_demo_help') . '</div>'
                . '<a href="' . AFSAConfig::getDashboardDemoURL() . '" '
                . ' class="afsa_launch_demo afsa_button">' . AFSAConfig::TR('launch_demo') . '</a>'
                . '</div>'
                . '<div class=afsa_img_container>'
                . '<img class=afsa_screen src=' . AFSAConfig::getURL('views/img/intro.screen.png') . '>'
                . '</div>'
                . '</div>'
                . '<div class=afsa_row>'
                . '</div>'
                . '</div>';
    }
}
