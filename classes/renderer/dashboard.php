<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/route.manager.php';
include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/config/main.php';
include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/api/api.php';
include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/renderer/account.form.php';

class AFSARendererDashboardView
{
    protected $account_id;
    protected $afsa_api = null;
    protected $widgets = array();
    protected $template = '';
    protected $embedded = false;

    public function __construct()
    {
        $this->account_id = AFSAConfig::getAccountID();
        AFSAConfig::set('gfonts', 'Lato:700|Open+sans:500');
    }

    public function initAFSAApi()
    {
        $this->afsa_api = $api = new AFSAApi();
        $api->login();

        return $api->isLogged();
    }

    // WIDGETS

    public function addWidgets($arr)
    {
        if (!empty($arr)) {
            foreach (array_keys($arr) as $n) {
                $this->addWidget($n);
            }
        }
    }

    public function addWidget($t)
    {
        $this->widgets[] = [
            'id' => $t,
            'options' => [],
        ];
    }

    // CONFIG

    protected function renderJSConfig()
    {
        $cfg = array(
            'lng' => AFSAConfig::getLNG(),
            'account_id' => (int) $this->account_id,
            'server_host' => AFSAConfig::getAFSAAPIHome(),
            'ecom' => array(),
            'ajax' => [
                'server' => AFSAConfig::getAJAXServerURL(),
            ],
            'dashboard' => [
                'host' => AFSAConfig::CMS(),
                'icon_engine' => 'FA4i',
                'container' => [
                    'template' => 'ecom',
                ],
            ],
            'url' => [
                'dashboard' => AFSAConfig::getDashboardURL(),
            ],
        );

        $access_key = AFSAConfig::getAccessKey();
        if ($access_key) {
            $cfg['access_key'] = $access_key;
        }

        if (AFSAConfig::advancedECommerceEnabled()) {
            $cfg['ecom'] = array(
                'enabled' => 1,
                'level' => 'advanced',
                'currency' => AFSAConfig::getGlobalCurrencyCode(),
            );

            $cfg['dashboard']['container']['template'] = 'ecom';
        }

        if (AFSAConfig::isDemo()) {
            $cfg['demo_mode'] = 1;
        }

        return $cfg;
    }

    public function renderView()
    {
        AFSAAccountFormRenderer::redirectOnAccountLinked();

        if (!AFSAConfig::AFSAEnabled() && !AFSAConfig::isDemo()) {
            return $this->renderIntro();
        }

        if (!$this->initAFSAApi()) {
            AFSATools::log('API not logged');
        }

        return  AFSATools::renderTemplate(
            'dashboard.tpl',
            $this->renderTemplateData()
        );
    }

    protected function renderJSData()
    {
        return AFSATools::renderJSData(array(
            'AFSA_dashboard_config' => $this->renderJSConfig(),
            's_data' => 1,
            's_verif' => 1,
            'logo_url' => AFSAConfig::getURL(
                'views/img/logo.small.png'
            ),
        ), false);
    }

    private function renderTemplateData()
    {
        return [
            'is_demo' => AFSAConfig::isDemo(),
            'img' => [
                'logo' => AFSAConfig::getURL('views/img/logo.small.png'),
            ],
            'txt' => [
                'create_your_own_account' => AFSAConfig::TR('create_your_own_account'),
                'demo_notice_title' => AFSAConfig::TR('demo_notice_title'),
                'demo_notice_help' => AFSAConfig::TR('demo_notice_help'),
                'demo_notice_help_more' => AFSAConfig::TR('demo_notice_help_more'),
            ],
            'url' => [
                'api_home' => AFSAConfig::getAFSAAPIHome(),
                'js_dashboard' => AFSAConfig::getURL('/views/js/dashboard.js'),
            ],
            'script' => [
                'dashboard' => array('d3.min', 'c3.min', 'dashboard'),
            ],
            'widget' => [
                'info' => false,
            ],
            'jsCode' => [
                'dashboard' => $this->renderJSData(),
                'demo' => 'var AFSA_site_infos=' . json_encode(AFSAAccountManager::get()->getAccountCreationParams()) . ';',
            ],
        ];
    }

    // INTRO

    private function renderIntro()
    {
        return AFSATools::renderTemplate(
            'intro.dashboard.tpl',
            AFSAAccountFormRenderer::getTemplateData()
        );
    }
}
