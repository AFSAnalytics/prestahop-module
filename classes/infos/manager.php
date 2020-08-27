<?php

include_once 'order.php';
include_once 'product.php';

include_once 'visitor.php';
include_once 'address.php';
include_once 'store.php';

include_once 'page.php';

class AFSAInfosManager
{
    private $store_infos;
    private $visitor_infos;
    private $page_infos;

    // Store
    public function page()
    {
        if (empty($this->page_infos)) {
            $this->page_infos = new AFSAPageInfos();
        }

        return $this->page_infos;
    }

    // Store
    public function site()
    {
        if (empty($this->store_infos)) {
            $this->store_infos = new AFSAStoreInfos();
        }

        return $this->store_infos;
    }

    // Customer or Employee
    public function visitor()
    {
        if (empty($this->visitor_infos)) {
            $this->visitor_infos = new AFSAVisitorInfos();
        }

        return $this->visitor_infos;
    }

    // Customer or Employee
    public function address($mixed)
    {
        return new AFSAAddressInfos($mixed);
    }
}
