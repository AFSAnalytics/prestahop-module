<?php

include_once 'product.php';

class AFSCartInfos
{
    /*
     * Get Infos about an order
     *
     *
     */

    private $data;
    private $cart;
    private $cart_id;
    private $shop_id;

    public function __construct()
    {
        $this->data = array();
        $this->db = AFSADB::get();

        $this->cart_id = (int) $this->getContext()->cart->id;
        $this->shop_id = (int) $this->getContext()->shop->id;
    }

    private function getContext()
    {
        return AFSAConfig::getContext();
    }

    public function validate()
    {
        return Validate::isLoadedObject($this->cart);
    }

    /**
     * Process card items that have been saved to DB
     * and render js for added / deleted products
     */
    public function renderBottomJS()
    {
        $js = '';
        $cart_items = $this->db->getCard($this->cart_id, $this->shop_id);
        if (!empty($cart_items)) {
            foreach ($cart_items as $item) {
                if (isset($item['quantity'])) {
                    if ($item['quantity'] > 0) {
                        $js .= 'aa.addToCart(' . json_encode($item) . ');';
                    } elseif ($item['quantity'] < 0) {
                        $item['quantity'] = abs($item['quantity']);
                        $js .= 'aa.removeFromCart(' . json_encode($item) . ');';
                    }
                } else {
                    $js .= $item; // script
                }
            }
        }
        $this->db->deleteCard($this->cart_id, $this->shop_id);

        return $js;
    }
}
