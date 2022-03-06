<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/config/main.php';
include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/route.manager.php';

class AdminRedirectController extends ModuleAdminController
{
    protected $dest;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->display = 'view';

        $this->dest = array(
            'url' => AFSARouteManager::getDashboardPage(),
            'label' => 'AFS Analytics dashboard',
        );

        parent::__construct();
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title = $this->l('AFS Analytics');
    }

    public function renderView()
    {
        try {
            return  AFSATools::renderTemplate(
                'redirect.tpl',
                [
                    'url' => $this->dest['url'],
                    'link' => $this->l($this->dest['label']),
                ]
            );
        } catch (\Throwable $th) {
            return null;
        }
    }
}
