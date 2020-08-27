<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/renderer/dashboard.php';

class AdminAFSADashboardDemoController extends ModuleAdminController
{
    private $rdr;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        AFSAConfig::setDemoMode();

        $this->rdr = new AFSARendererDashboardView();

        parent::__construct();
    }

    public function initContent()
    {
        $this->context->controller->addCSS(AFSAConfig::getURL('/views/css/dashboard.css'));
        $this->context->controller->addCSS(AFSAConfig::getAFSAAPIHome() . '/assets/css/prestashop/current/packed.css?v=1');

        $this->context->controller->addJS(AFSAConfig::getURL('views/js/back.office.js'));

        parent::initContent();
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title = $this->l('AFS Analytics');
    }

    public function renderView()
    {
        $rdr = $this->rdr;

        return $rdr->renderView();
    }
}
