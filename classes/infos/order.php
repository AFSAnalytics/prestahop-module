<?php

include_once 'product.php';

define('AFSA_ORDER_STATE_CANCELLED', 'cancelled');
define('AFSA_ORDER_STATE_PENDING', 'pending');
define('AFSA_ORDER_STATE_COMPLETED', 'completed');

class AFSAOrderInfos
{
    private $data;
    private $order;

    public function __construct($mixed = null)
    {
        $this->data = array();

        if (is_int($mixed)) {
            $this->setOrderByID($mixed);
        } else {
            $this->setOrder($mixed);
        }
    }

    public function validate()
    {
        return Validate::isLoadedObject($this->order);
    }

    public function setOrder($o)
    {
        if (Validate::isLoadedObject($o)) {
            $this->order = $o;
        }
    }

    public function setOrderByID($id)
    {
        if ($id) {
            $this->order = new Order($id, true, (int) Configuration::get('PS_LANG_DEFAULT'));
        }
    }

    /**
     * return collected data
     *
     * @return array
     */
    public function get()
    {
        return $this->data;
    }

    public function getPayment()
    {
        return $this->order->payment;
    }

    private function getContext()
    {
        return AFSAConfig::getContext();
    }

    // ORDER

    private function getCurrency()
    {
        $id = $this->getContext()->currency->id;

        return new Currency($id);
    }

    public function parse($format = AFSA_FORMAT_TRANSACTION_ITEM)
    {
        if (!$this->validate()) {
            return false;
        }

        $o = $this->order;

        $state = $this->parseOrderState($o);
        if ($state == AFSA_ORDER_STATE_CANCELLED) {
            return false;
        }

        $shipping_tax = (float) ($o->total_shipping_tax_incl - $o->total_shipping_tax_excl);
        $revenue_tax = (float) ($o->total_paid_tax_incl - $o->total_paid_tax_excl);

        $this->data['order'] = array(
            'id' => $o->reference,
            'affiliation' => AFSAConfig::getShopAffiliation(),

            'revenue' => $o->total_paid_tax_incl, // TTC
            //
            'revenue_net' => $o->total_paid_tax_excl, // H.T.
            'revenue_tax' => $revenue_tax,
            'shipping' => $o->total_shipping_tax_incl,
            'shipping_net' => $o->total_shipping_tax_excl,
            'shipping_tax' => $shipping_tax,
            'tax' => $shipping_tax + $revenue_tax,
            'customer' => $o->id_customer,
        );

        // CURRENCY

        try {
            $currency = new CurrencyCore($this->getCurrency());
            if (!empty($currency->iso_code)) {
                $this->data['order']['currency'] = $currency->iso_code;
            }
        } catch (Exception $ex) {
        }

        // COUPON

        try {
            $this->parseCoupons($o);
        } catch (Exception $ex) {
        }

        // ITEMS

        $this->data['items'] = array();

        $cart = new Cart($o->id_cart);

        $index = 0;
        foreach ($cart->getProducts() as $p) {
            $infos = new AFSAProductInfos();
            $infos->order_reference = $this->data['order']['id'];
            $this->data['items'][] = $infos->parseProduct($p, $index++, $format);
        }

        return $this->data;
    }

    public function parseCoupons($o)
    {
        $rules = $o->getCartRules();
        if (empty($rules)) {
            return;
        }

        $names = array();
        $values = array();
        $value = 0.00;

        foreach ($rules as $item) {
            if (empty($item['name'])) {
                continue;
            }

            $names[] = $item['name'];
            $values[] = $item['value'];
            $value += (float) $item['value'];
        }

        if (!empty($names)) {
            $this->data['order']['coupon'] = implode(',', $names);
            $this->data['order']['coupon_value'] = $value;
            if (count($values) > 1) {
                $this->data['order']['coupon_value_distinct'] = implode(',', $values);
            }
        }
    }

    public function parseOrderState($o)
    {
        switch ($o->getCurrentState()) {
            case Configuration::get('PS_OS_CANCELED'): //canceled
            case Configuration::get('PS_OS_REFUND'): //refund
            case Configuration::get('PS_OS_ERROR'): //payment error
                return AFSA_ORDER_STATE_CANCELLED;

            case Configuration::get('PS_OS_PAYMENT'): //accepted
            case Configuration::get('PS_OS_WS_PAYMENT'): // remotely accepted
                return AFSA_ORDER_STATE_COMPLETED;

            default:
                return AFSA_ORDER_STATE_PENDING;
        }

        /*
          PS_OS_BANKWIRE virement

          PS_OS_CHEQUE cheque

          PS_OS_COD_VALIDATION  ashondelivery

          PS_OS_DELIVERED delivered

          PS_OS_OUTOFSTOCK

          PS_OS_OUTOFSTOCK_PAID

          PS_OS_OUTOFSTOCK_UNPAID

          PS_OS_PAYMENT  payé / completed

          PS_OS_PREPARATION

          PS_OS_SHIPPING

          PS_OS_WS_PAYMENT
         */
    }

    /*

      registerHook('actionOrderStatusPostUpdate'));

      public function hookActionOrderStatusPostUpdate($p) {
      $o = new Order($p['id_order']);
      }
     */
}
