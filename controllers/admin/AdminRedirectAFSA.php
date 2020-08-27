<?php

include_once 'AdminRedirect.php';
include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/route.manager.php';

class AdminRedirectAFSAController extends AdminRedirectController
{
    public function renderView()
    {
        Tools::redirect(
                AFSARouteManager::home()
        );
        exit();
    }
}
