<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/infos/product.php';

class AFSAApiRequestResult
{
    private $request;
    private $data;
    private $visitor_ids;
    private $product_skus;
    private $db;

    public function __construct($request, $data = [])
    {
        $this->request = $request;
        $this->data = $data;
        $this->visitor_ids = array();
        $this->product_skus = array();
        $this->db = null;

        AFSATools::log('CTX', $request->context);
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    private function getDB()
    {
        return $this->db ?
            $this->db :
            $this->db = AFSADB::get();
    }

    public function render()
    {
        $data = $this->data;
        $actions = [];

        if (!empty($data['error'])) {
            $this->parseError($data['error']);
        }

        if (empty($data) || empty($data['performed_actions'])) {
            return array('error' => 'no action requested');
        }

        foreach ($data['performed_actions'] as $uid => $a) {
            $actions[$uid] = $this->parseAction($a, $uid);
        }

        $result = ['performed_actions' => $actions];

        // not rendering context infos if in demo mode

        try {
            return  AFSAConfig::isDemo() ?
                $result :
                $this->renderEnhancedInfos($result);
        } catch (\Throwable $th) {
            $result['_error'] = $th->getMessage();
        }

        return $result;
    }

    public function parseError($e)
    {
        switch ($e) {
                // Invalid token
            case 'access_denied':
                $this->request->logOut();
                break;
        }
    }

    public function parseAction(&$a, $uid)
    {
        try {
            // updating account info
            $terms = explode(':', $uid);
            if ($terms[0] == 'account_infos') {
                AFSAAccountManager::onAjaxInfosReceived($a);
            }
        } catch (Exception $ex) {
        }

        if (empty($a['metas'])) {
            return $a;
        }

        $m = &$a['metas'];

        if (!empty($m['custom'])) {
            foreach ($m['custom'] as $k => $v) {
                switch ($k) {
                    case 'user_id':
                        $this->registerVisitors($v);
                        break;
                    case 'product_sku':
                        $this->registerProducts($v);
                        break;
                }
            }
        }

        return $a;
    }

    public function registerVisitors(array $ids = [])
    {
        $this->visitor_ids = array_merge($this->visitor_ids, $ids);
    }

    public function registerProducts(array $ids = [])
    {
        $this->product_skus = array_merge($this->product_skus, $ids);
    }

    // CONTEXT INFOS

    public function renderEnhancedInfos($result)
    {
        $visitors = &$this->visitor_ids;
        $products = &$this->product_skus;
        $ret = $result;

        $metas = array('context' => array());
        if (!empty($visitors)) {
            $infos = $this->renderVisitorsInfos();
            if (!empty($infos)) {
                $metas['context']['visitors'] = $infos;
            }
        }

        if (!empty($products)) {
            $infos = $this->renderProductsInfos();
            if (!empty($infos)) {
                $metas['context']['products'] = $infos;
            }
        }

        if (!empty($metas['context'])) {
            $ret['metas'] = $metas;
        }

        return $ret;
    }

    public function renderVisitorsInfos()
    {
        $context = $this->request->context;

        if (empty($this->visitor_ids)) {
            return null;
        }

        $known_ids = empty($context['visitors']) ?
            [] :
            $context['visitors'];

        $result_ids = array_unique($this->visitor_ids);

        $ids = array_diff($result_ids, $known_ids);
        if (empty($ids)) {
            AFSATools::log('CTX all known', ['R' => $result_ids, 'K' => $known_ids]);

            return null;
        }

        AFSATools::log('CTX unknown', ['D' => $ids, 'R' => $result_ids, 'K' => $known_ids]);

        $ret = array();
        $items = $this->getDB()->getCustomerInfos($ids);

        if (!empty($items)) {
            foreach ($items as $item) {
                $ret[$item['id']] = $item;
            }
        }

        return $ret;
    }

    public function renderProductsInfos()
    {
        $context = $this->request->context;

        if (empty($this->product_skus)) {
            return null;
        }

        $known_skus = empty($context['products']) ?
            [] :
            $context['products'];

        $result_skus = array_unique($this->product_skus);

        $skus = array_diff($result_skus, $known_skus);
        if (empty($skus)) {
            AFSATools::log('CTX all known', ['R' => $result_skus, 'K' => $known_skus]);

            return null;
        }

        AFSATools::log('CTX unknown', ['D' => $skus, 'R' => $result_skus, 'K' => $known_skus]);

        $ret = array();
        $items = $this->getDB()->getProductsByRef($skus);

        AFSATools::log('CTX items found', [$skus, $items]);

        if (!empty($items)) {
            foreach ($items as $item) {
                $id = $item['id_product'];
                try {
                    $ret[$item['reference']] = AFSAProductInfos::getAjaxContextInfo($id);
                } catch (Exception $e) {
                }
            }
        }

        return $ret;
    }
}
