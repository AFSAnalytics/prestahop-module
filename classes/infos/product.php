<?php

define('AFSA_FORMAT_COMPACT', 'compact');
define('AFSA_FORMAT_TRANSACTION_ITEM', 'transaction');
define('AFSA_FORMAT_IMPRESSION', 'impression');
define('AFSA_FORMAT_PRODUCT', 'product');

class AFSAProductInfos
{
    /*
     * Get Infos about a Product
     *
     * ( Product object is not used here
     *   -- only Product infos key value array
     * )
     *
     */

    public static $position = 0;
    public $order_reference;
    public $add_url = false;
    public $infos; // extras infos from cart
    private $unique_id = 0;

    public function __construct()
    {
    }

    private function getContext()
    {
        return AFSAConfig::getContext();
    }

    private function getGlobalCurrency()
    {
        return AFSAConfig::getGlobalCurrency();
    }

    private function parseCurrency($p)
    {
        if (!empty($p['specific_prices']) && !empty($p['specific_prices']['id_currency'])) {
            return new Currency((int) $p['specific_prices']['id_currency']);
        }

        return $this->getGlobalCurrency();
    }

    public function getUniqueID()
    {
        return $this->unique_id;
    }

    public function setParseOptions($op)
    {
        $this->add_url = !empty($op['add_url']);
    }

    /**
     * Add extra product infos retrieved
     * from cart / order / or other sources
     * used when parsing product
     *
     * @param type $data info array
     */
    public function registerExtraData($data)
    {
        $this->infos = $data;
    }

    private function getProductAttributeID($p)
    {
        if (!empty($p['id_product_attribute'])) {
            return $p['id_product_attribute'];
        }
        if (!empty($p['product_attribute_id'])) {
            return$p['product_attribute_id'];
        }

        return null;
    }

    /**
     * Main Parse function
     *
     * @param array $p product infos
     * @param int $position
     * @param Currenct $currency
     * @param string $format item format (transaction, cart list, etc...)
     *
     * @return array
     */
    public function parseProduct($p, $position, $format = AFSA_FORMAT_ADD_ITEM)
    {
        if (empty($context = $this->getContext())) {
            return;
        }

        if ($p instanceof Product) {
            $p = (array) $p;
        }

        // Gathering DATA

        $lng_id = $context->language->id;
        $cat = new Category($p['id_category_default'], $lng_id);
        $manufacturer = new Manufacturer($p['id_manufacturer'], $lng_id);

        /* unused atm
          $type = 'typical';
          if (!empty($p['pack']))
          $type = 'pack';
          elseif (!empty($p['virtual']))
          $type = 'virtual';
         */

        $qty = 1;
        if (isset($this->infos['qty'])) {
            $qty = $this->infos['qty'];
        } elseif (isset($p['product_quantity'])) {
            $qty = $p['product_quantity'];
        } elseif (isset($p['cart_quantity'])) {
            $qty = $p['cart_quantity'];
        }

        if (isset($p['attributes_small'])) {
            $variant = $p['attributes_small'];
        } elseif (isset($this->infos['attributes_small'])) {
            $variant = $this->infos['attributes_small'];
        }

        // Unique ID to differentiate products
        //
        $uid = 0;
        if (!empty($p['id_product'])) {
            $uid = $p['id_product'];
        } elseif (!empty($p['id'])) {
            $uid = $p['id'];
        }

        $attribute_id = $this->getProductAttributeID($p);
        if ($attribute_id) {
            $uid .= '-' . $attribute_id;
        }

        $this->unique_id = $uid;

        if (!empty($p['name'])) {
            $name = $p['name'];
        } elseif (!empty($p['product_name'])) {
            $name = $p['product_name'];
        }

        $product_name = AFSATools::normalizeString($name);

        $sku = isset($p['reference']) ? $p['reference'] : null;
        $brand_name = AFSATools::normalizeString($manufacturer->name);
        $full_cat_name = AFSATools::normalizeString(implode('/', $this->getFullCatTree($cat)));

        if (!isset($p['price'])) {
            $p['price'] = $this->getPrice($p);
        }
        $price = $this->formatPrice($p['price']);

        $id = empty($sku) ?
                $uid :
                $sku;

        //$coupon = null;

        $list = null;
        if (!empty($this->infos['list'])) {
            $list = $this->infos['list'];
        } else {
            $list = AFSAConfig::getPageName();
        }

        if (empty($position)) {
            $position = static::$position++;
        }

        //
        // RENDERING
        // COMPACT DATA - Only add basic Product Infos (id, name)

        if ($format == AFSA_FORMAT_COMPACT) {
            return array(
                'id' => $id,
                'name' => json_encode($product_name),
            );
        }

        // TRANSACTION ITEM DATA

        if ($format == AFSA_FORMAT_TRANSACTION_ITEM) {
            $ret = array(
                'id' => $id,
                'name' => $product_name,
                'category' => $full_cat_name,
                'price' => $price,
                'quantity' => "$qty",
            );

            $op_fields = array('variant');
        }

        // IMPRESSION DATA

        if ($format == AFSA_FORMAT_IMPRESSION) {
            $ret = array(
                'id' => $id,
                'name' => $product_name,
                'list' => $list,
                'brand' => $brand_name,
                'category' => $full_cat_name,
                'position' => "$position",
                'price' => $price,
            );
            $op_fields = array('variant', 'position');
        }

        // PRODUCT DATA

        if ($format == AFSA_FORMAT_PRODUCT) {
            $ret = array(
                'id' => $id,
                'name' => $product_name,
                'brand' => $brand_name,
                'category' => $full_cat_name,
                'price' => $price,
                'quantity' => "$qty",
            );

            $op_fields = array('variant', 'coupon', 'position');
        }

        if (!empty($op_fields)) {
            foreach ($op_fields as $field) {
                switch ($field) {
                    case 'position':
                        if ($position != -1) {
                            $ret['position'] = "$position";
                        }
                        break;
                    default:
                        if (!empty(${$field})) {
                            $ret[$field] = ${$field};
                        }
                }
            }
        }

        // Product URL

        if ($this->add_url && !empty($p['link'])) {
            $ret['url'] = urlencode($p['link']);
        }

        // Currency if != global currency
        try {
            $currency_code = $this->parseCurrency($p)->iso_code;

            if ($currency_code != AFSAConfig::getGlobalCurrencyCode()) {
                $ret['currency'] = $currency_code;
            }
        } catch (Exception $e) {
            AFSATools::log($e);
        }

        return $ret;
    }

    private function formatPrice($p)
    {
        if (strpos($p, '€') !== false || $this->getGlobalCurrency()->iso_code == 'EUR') {
            $p = str_replace(',', '.', $p);
        }

        return number_format((float) ($p), 2, '.', '');
    }

    private function getPrice($product)
    {
        $usetax = (Product::getTaxCalculationMethod((int) $this->getContext()->customer->id) != PS_TAX_EXC);

        return (float) Tools::displayPrice(Product::getPriceStatic((int) $product['id_product'], $usetax), $this->getGlobalCurrency());
    }

    public function parseProducts($products, $extras = array(), $format = AFSA_FORMAT_PRODUCT, $parse_options = array())
    {
        if (!is_array($products)) {
            return;
        }

        $this->registerExtraData($extras);
        $this->setParseOptions($parse_options);

        $ret = array();

        $context = $this->getContext();
        $currency = $this->getGlobalCurrency();

        $usetax = (Product::getTaxCalculationMethod((int) $context->customer->id) != PS_TAX_EXC);
        if (count($products) > 25) {
            $format = AFSA_FORMAT_COMPACT;
        }

        foreach ($products as $index => $product) {
            if ($product instanceof Product) {
                $product = (array) $product;
            }

            if (!isset($product['price'])) {
                $product['price'] = (float) Tools::displayPrice(Product::getPriceStatic((int) $product['id_product'], $usetax), $currency);
            }
            $ret[] = $this->parseProduct($product, $index, $format);
        }

        return $ret;
    }

    /**
     * Get all parent name for specified Category
     *
     * @param Category $c
     * @param bool $trim_first remove root parent cat
     *
     * @return array names
     */
    private function getFullCatTree($c, $trim_first = false)
    {
        $ret = array();
        $arr = array_reverse($c->getParentsCategories());

        if (!empty($arr)) {
            foreach ($arr as $row) {
                $ret[] = $row['name'];
            }

            if ($trim_first && count($ret) > 1) {
                array_shift($ret);
            }
        }

        return $ret;
    }

    public static function getAjaxContextInfo($id_product)
    {
        $context = AFSAConfig::getContext();
        $id_lang = $context->language->id;
        $link = $context->link;

        $product = new Product($id_product, false, $id_lang);

        $img = $product->getCover($product->id);
        $image_type = static::getFormattedName('home');

        $img_link = isset($product->link_rewrite) ?
                $product->link_rewrite :
                $product->name;

        return array(
            'url' => $link->getProductLink($id_product),
            'image' => array(
                'default' => $link->getImageLink($img_link, (int) $img['id_image'], $image_type),
            ),
            'name' => $product->name,
        );
    }

    private static function getFormattedName($name)
    {
        return method_exists('ImageType', 'getFormattedName') ?
         ImageType::getFormattedName($name) :
         ImageType::getFormatedName($name);
    }
}
