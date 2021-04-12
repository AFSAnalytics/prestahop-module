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
            /*
              return '<section id=afsa_dashboard class="afsa_main afsa_bo_dashboard">'
              . '<div style="display: flex;align-items: center;" class=afsa_notice >'
              . '<img style="flex: 0 0 auto;margin-right:30px;"  class=afsa_logo src='
              . AFSAConfig::getURL('/views/img/logo.small.png') . '>'
              . '<div style="flex: 0 0 auto;max-width: 60%;" class=afsa_help>'
              . AFSAConfig::TR('days_trends_help')
              . ' <a href="' . AFSAConfig::getDashboardURL() . '">'
              . AFSAConfig::TR('dashboard')
              . '.</a>'
              . '</div></duv>'
              . '</section>';
             */
        }

        $ret = $this->renderJSData();

        foreach (array('dashboard') as $n) {
            $ret .= '<script src=' . AFSAConfig::getAFSAAPIHome() . '/assets/js/common/v2/' . $n . '.js></script>';
        }

        return '<section id=afsa_dashboard class="afsa_main afsa_bo_dashboard">'
                . $this->renderWidget('Overview')
                . '</section>'
                . $ret
                . '<script src="' . AFSAConfig::getURL('/views/js/admin.widget.js') . '"></script>'
        ;
    }
}
