<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/renderer/dashboard.php';

class AdminAFSADashboardController extends ModuleAdminController
{
    private $rdr;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        $this->rdr = new AFSARendererDashboardView();

        parent::__construct();

        // need to come after => parent::__construct();

        $id = Tools::getValue('account_id');
        if (!empty($id)) {
            if (AFSAConfig::validateAccountID($id)) {
                Configuration::updateValue('AFS_ANALYTICS_ACCOUNT', $id);
                Tools::redirectAdmin(AFSAConfig::getConfigControllerURL());
            }
        }
    }

    public function initContent()
    {
        if (AFSAConfig::AFSAEnabled()) {
            $this->context->controller->addCSS(AFSAConfig::getURL('/views/css/dashboard.css'));
            $this->context->controller->addCSS(AFSAConfig::getAFSAAPIHome() . '/assets/css/prestashop/current/packed.css?v=1');
        } else { // INTRO
            $this->context->controller->addCSS(AFSAConfig::getURL('/views/css/intro.forms.css'));
            $this->context->controller->addCSS(AFSAConfig::getURL('/views/css/intro.css'));
        }

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
