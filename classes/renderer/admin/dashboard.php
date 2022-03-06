<?php

class AFSARendererAdminWidgetDashboard extends AFSARendererDashboardView
{
    public static function shouldDisplay()
    {
        return AFSAConfig::AFSAEnabled();
    }

    public function __construct()
    {
        $this->embedded = true;
        parent::__construct();
    }

    public function initAFSAApi()
    {
        $this->afsa_api = $api = new AFSAApi();
        $api->simpleLogin();

        return $api->isLogged();
    }

    protected function renderJSConfig()
    {
        $cfg = parent::renderJSConfig();
        unset($cfg['dashboard']['container']);
        $cfg['dashboard']['do_not_parse'] = 0;

        return $cfg;
    }

    public function render()
    {
        if (!AFSAConfig::AFSAEnabled() && !AFSAConfig::isDemo()) {
            return '';
        }

        if (!$this->initAFSAApi()) {
            AFSATools::log('API not logged');

            return '';
        }

        return  AFSATools::renderTemplate(
            'dashboard.bo.tpl',
            $this->renderTemplateData()
        );
    }

    private function renderTemplateData()
    {
        return [
            'url' => [
                'api_home' => AFSAConfig::getAFSAAPIHome(),
                'js_dashboard' => AFSAConfig::getURL('/views/js/admin.widget.js'),
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
}
