<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/api/request/request.php';

class AdminAFSAAjaxController extends ModuleAdminController
{
    public function init()
    {
        // ob_start();

        parent::init();

        $request = new AFSAApiRequest();
        $json = json_encode($request->run());

        //   ob_end_clean();

        header('Content-type: application/json');
        echo $json;
        exit;
    }
}
