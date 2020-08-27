<?php

include_once 'AdminRedirect.php';

class AdminRedirectCMSConfigController extends AdminRedirectController
{
    public function renderView()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . Tools::safeOutput(AFSAConfig::pluginName()));

        return null;
    }
}
