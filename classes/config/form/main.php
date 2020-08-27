<?php

class AFSAConfigFormMain extends AFSAConfigForm
{
    public function __construct($module)
    {
        $this->form_id = 'Main';
        parent::__construct($module);

        $this->fields = array(
            'afs_analytics_account',
            'afs_analytics_shop_affiliation',
            'afs_analytics_accesskey',
            'afs_analytics_admin_pages_tracking',
            'afs_analytics_user_logged_tracking',
            'afs_analytics_user_profile_tracking',
            'afs_analytics_display_admin_summary',
            'afs_analytics_page_name_method',
        );
    }

    public function install()
    {
        Configuration::updateValue('AFS_ANALYTICS_ACCOUNT', '');
        Configuration::updateValue('AFS_ANALYTICS_ACCESSKEY', '');
        Configuration::updateValue('AFS_ANALYTICS_ADMIN_PAGES_TRACKING', 1);
        Configuration::updateValue('AFS_ANALYTICS_USER_LOGGED_TRACKING', 1);
        Configuration::updateValue('AFS_ANALYTICS_PAGE_NAME_METHOD', 'auto');
        Configuration::updateValue('AFS_ANALYTICS_SHOP_AFFILIATION', 'Prestahop');
        Configuration::updateValue('AFS_ANALYTICS_DISPLAY_ADMIN_SUMMARY', 1);
    }

    public function onSubmit($msg = null)
    {
        $module = $this->module;

        if (!Tools::isSubmit($this->getSubmitName())) {
            return null;
        }

        $account = trim((string) Tools::getValue('afs_analytics_account'));
        if (!empty($account)) {
            Configuration::updateValue('AFS_ANALYTICS_ACCOUNT', $account);
        } else {
            return $module->displayError($this->l('Invalid Configuration value'));
        }

        $accesskey = trim((string) Tools::getValue('afs_analytics_accesskey'));
        if (!empty($accesskey)) {
            Configuration::updateValue('AFS_ANALYTICS_ACCESSKEY', $accesskey);
        }

        Configuration::updateValue('AFS_ANALYTICS_ADMIN_PAGES_TRACKING', (bool) Tools::getValue('afs_analytics_admin_pages_tracking'));
        Configuration::updateValue('AFS_ANALYTICS_USER_LOGGED_TRACKING', (bool) Tools::getValue('afs_analytics_user_logged_tracking'));
        Configuration::updateValue('AFS_ANALYTICS_DISPLAY_ADMIN_SUMMARY', (bool) Tools::getValue('afs_analytics_display_admin_summary'));
        Configuration::updateValue('AFS_ANALYTICS_PAGE_NAME_METHOD', Tools::getValue('afs_analytics_page_name_method'));
        Configuration::updateValue('AFS_ANALYTICS_SHOP_AFFILIATION', trim((string) Tools::getValue('afs_analytics_shop_affiliation')));

        return $module->displayConfirmation($this->l('Settings updated'));
    }

    public function renderInfos()
    {
        return '';
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
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Website ID'),
                    'name' => 'afs_analytics_account',
                    'hint' => $this->l('Your AFS Analytics Website ID'),
                    'desc' => '<div class="afsa_create_account">'
                    . $this->l('Create an AFS Analytics account')
                    . '</div> '
                    . $this->l('to get one'),
                    'size' => 20,
                    'class' => 'input fixed-width-xl',
                    'required' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Access Key'),
                    'name' => 'afs_analytics_accesskey',
                    'hint' => 'An Access key allow you to access your AFS Analytics Dashboard without providing a password each time ',
                    'desc' => '<div class="afsa_warpto" data-target="_blank" data-to="' . AFSARouteManager::keys() . '">'
                    . $this->l('Create one')
                    . '</div>',
                    'size' => 20,
                    'class' => 'input fixed-width-xl',
                    'required' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Sale Chanel'),
                    'name' => 'afs_analytics_shop_affiliation',
                    'hint' => $this->l('The name of the ECommerce platform you want to be shown as origin of any order processed by Prestashop.'),
                    'desc' => '',
                    'size' => 20,
                    'class' => 'input fixed-width-xl',
                    'required' => false,
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Admin pages tracking'),
                    'name' => 'afs_analytics_admin_pages_tracking',
                    'values' => $radioOptions,
                    'hint' => $this->l('Track activity in Back Office'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Enable user tracking'),
                    'name' => 'afs_analytics_user_logged_tracking',
                    'values' => $radioOptions,
                    'hint' => $this->l('Track user information like name and email'),
      //              'desc' => $this->l('Note: A premium AFS Analytis subscription is needed for tracking customer\'s full profile.'),
                ),

                array(
                    'type' => 'switch',
                    'label' => $this->l('Display day trends summary in back office'),
                    'name' => 'afs_analytics_display_admin_summary',
                    'values' => $radioOptions,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Page Name Attribution'),
                    'name' => 'afs_analytics_page_name_method',
                    'hint' => $this->l('Method to determine Page Name used in AFS Analytics reporting tools. Auto: automatically build an optimised title, Title: use page title, Minimal: set page function as title.'),
                    'options' => array(
                        'query' => array(
                            array('key' => 'auto', 'name' => 'Auto'),
                            array('key' => 'title', 'name' => 'Title'),
                            array('key' => 'mini', 'name' => 'Minimal'),
                        ),
                        'id' => 'key',
                        'name' => 'name',
                    ),
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
