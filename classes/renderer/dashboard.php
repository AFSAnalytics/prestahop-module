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

        $this->prepareCommonTemplate();
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

    public function prepareCommonTemplate($content = '')
    {
        $this->template = $this->renderWidget('topmenubar')
                . '<div id=afsa_col_infos>'
                . $this->renderWidget('config')
                . '</div>'
                . '<div id=afsa_col_widgets>'
                . $content
                . '</div>'
        ;
    }

    public function renderWidget($type, $options = null)
    {
        $dataset = ' data-type="' . $type . '"';
        if ($options) {
            $dataset .= ' data-options=\'' . json_encode($options) . '\'';
        }

        return '<div class="afsa_requested_widget afsa_widget_' . $type . '"'
                . $dataset . '>'
                . '</div>';
    }

    // CONFIG

    protected function renderJSConfig()
    {
        $cfg = array(
            'lng' => AFSAConfig::getLNG(),
            'account_id' => $this->account_id,
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

    protected function renderJSData()
    {
        return AFSATools::renderJSData(array('AFSA_dashboard_config' => $this->renderJSConfig(),
                    's_data' => 1,
                    's_verif' => 1,
                    'logo_url' => AFSAConfig::getURL(
                            'views/img/logo.small.png'),
        ));
    }

    public function renderView($params = array())
    {
        AFSAAccountFormRenderer::redirectOnAccountLinked();

        if (!AFSAConfig::AFSAEnabled() && !AFSAConfig::isDemo()) {
            return $this->renderIntro();
        }

        if (!$this->initAFSAApi()) {
            AFSATools::log('API not logged');
        }

        if (!empty($params['widgets'])) {
            $this->addWidgets($params['widgets']);
        }

        $ret = $this->renderJSData();

        foreach (array('d3.min', 'c3.min', 'chart.engine', 'dashboard') as $n) {
            $ret .= '<script src=' . AFSAConfig::getAFSAAPIHome() . '/assets/js/prestashop/current/' . $n . '.js></script>';
        }

        return
                $this->renderNotice()
                . '<div id=afsa_container></div>'
                . $ret
                . '<script src="' . AFSAConfig::getURL('/views/js/dashboard.js') . '"></script>'
        ;
    }

    private function renderNotice()
    {
        return AFSAConfig::isDemo() ?
                '<div id=afsa_demo_notice>'
                . '<div class=afsa_logo_container>'
                . '<img class=afsa_logo src=' . AFSAConfig::getURL('views/img/logo.small.png') . '>'
                . '<div class=afsa_form>'
                . '<div class="afsa_create_account afsa_button"> ' . AFSAConfig::TR('create_your_own_account') . '</div>'
                . '</div>'
                . '</div>'
                . '<div class=afsa_content>'
                . '<div class=afsa_headline>'
                . AFSAConfig::TR('demo_notice_title')
                . '</div>'
                . '<div class=afsa_text>'
                . '<p>' . AFSAConfig::TR('demo_notice_help')
                . '</p><p>'
                . AFSAConfig::TR('demo_notice_help_more')
                . '</p>'
                . '</div>'
                . '</div>'
                . '</div>'
                . AFSATools::renderJSData(array('AFSA_site_infos' => AFSAAccountManager::get()->getAccountCreationParams())) :
                null;
    }

    // INTRO

    private function renderIntro()
    {
        return '<div class=afsa_intro_container>'
                . AFSAAccountFormRenderer::renderAccountForm()
                . AFSAAccountFormRenderer::renderDemoForm()
                . '</div>';
    }
}
