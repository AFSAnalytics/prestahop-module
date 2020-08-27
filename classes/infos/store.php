<?php

class AFSAStoreInfos
{
    private $data;

    public function __construct()
    {
        $this->data = array();
        $this->retrieve();
    }

    public function get()
    {
        return $this->data;
    }

    private function getContext()
    {
        return AFSAConfig::getContext();
    }

    private function retrieve()
    {
        if (empty($context = $this->getContext())) {
            return;
        }

        $shop = &$context->shop;

        $this->data = array(
            'name' => $shop->name,
            'desc' => '',
            'url' => $shop->getBaseURL(true),
            'domain' => parse_url($shop->getBaseURL(true), PHP_URL_HOST),
            'lng' => (string) $context->language->language_code,
            'email' => Configuration::get('PS_SHOP_EMAIL'),
            'cms' => AFSAConfig::CMS(),
            'ps_version' => AFSAConfig::CMSVersion(),
            'plugin_version' => AFSA_MODULE_VERSION,
        );

        try {
            $this->data['country_code'] = $context->country->iso_code;
        } catch (Exception $e) {
        }

        $currency = AFSAConfig::getGlobalCurrencyCode();
        if ($currency) {
            $this->data['currency'] = Tools::strtolower($currency);
        }
    }
}
