<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/account/controller.php';

class AdminAFSAAccountManagerController extends ModuleAdminController
{
    private $rdr;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();

        $this->rdr = new AFSAAccountController();
        $this->rdr->onActionCompleted();
    }

    public function initContent()
    {
        $this->context->controller->addCSS(AFSAConfig::getURL('/views/css/welcome.css'));
        parent::initContent();
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title = $this->l('AFS Analytics');
    }

    public function renderView()
    {
        return $this->rdr->getOutput();
    }
}
