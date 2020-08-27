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
        $u = &$this->dest['url'];
        $link = $this->l($this->dest['label']);

        return
                '<div style="text-align:center" class="wait_notice" id="afsa_browser" data-to="' . $u . '">'
                . '<p><b>'
                . 'Chargement en cours AFS Analytics. Merci de patienter quelques instants. '
                // . $this->l('Please be patient, AFS Analytics is loading ...')
                . '</b></p>'
                . '</div>'

                . '<div style="text-align:center" class="wait_notice"><p>'
                . '<a href="' . $u . '" target="_blank">' . $link . '</a>'
                . '</p></div>'

                . "\n<script type='text/javascript'>\n"
                . "window.location.href='$u';\n"
                . "</script>\n"
        ;
    }
}
