<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/renderer/dashboard.php';
include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/renderer/admin/dashboard.php';
include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/renderer/account.form.php';

class AFSARendererAdminWidget
{
    public static function shouldDisplay()
    {
        return (bool) Configuration::get('AFS_ANALYTICS_DISPLAY_ADMIN_SUMMARY');
    }

    public static function hookDisplayBackOfficeHeader($controller)
    {
        AFSAAccountFormRenderer::redirectOnAccountLinked();

        $controller->addCSS(AFSAConfig::getpluginFile('views/css/admin.widget.css'));

        if (AFSARendererAdminWidgetDashboard::shouldDisplay()) {
            $controller->addCSS(AFSAConfig::getAFSAAPIHome() . '/assets/css/prestashop/current/packed.css?v=1');
        } else {
            $controller->addCSS(AFSAConfig::getpluginFile('views/css/intro.forms.css'));
            $controller->addCSS(AFSAConfig::getpluginFile('views/css/intro.widget.css'));
        }
    }

    public function render()
    {
        if (AFSARendererAdminWidgetDashboard::shouldDisplay()) {
            $rdr = new AFSARendererAdminWidgetDashboard();

            return $rdr->render();
        }

        return '<section id=afsa_dashboard class="afsa_intro_container afsa_bo_dashboard">'
                . AFSAAccountFormRenderer::renderAccountForm()
                . AFSAAccountFormRenderer::renderDemoForm()
                . '</section>'
                . '<script src="' . AFSAConfig::getURL('/views/js/admin.widget.js') . '"></script>'
                . '<script src="' . AFSAConfig::getURL('/views/js/back.office.js') . '"></script>'
        ;
    }
}
