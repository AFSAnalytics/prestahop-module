<?php

/**
 * 2019-2021 AFSAnalytics
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@afsanalytics.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 *  @author    AFSAnalytics.com Dev Team <devteam@afsanalytics.com>
 *  @copyright 2020 AFSAnalytics
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

define('AFSA_MODULE_VERSION', '1.0.2');

include_once 'classes/config/main.php';
include_once 'classes/db.php';
include_once 'classes/tools.php';
include_once 'classes/config/form/manager.php';
include_once 'classes/infos/manager.php';
include_once 'classes/tracker.php';
include_once 'classes/ui/tabs.php';
include_once 'classes/route.manager.php';
include_once 'classes/renderer/admin/widget.php';
include_once 'classes/account/manager.php';

class AFSAnalytics extends Module
{
    public $debug = true;
    private $form_manager;
    protected static $products = array();

    public function __construct()
    {
        $this->name = 'afsanalytics'; // do not change
        $this->tab = 'analytics_stats';
        $this->version = '1.0.2'; // must be a string
        $this->author = 'AFSAnalytics';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => _PS_VERSION_,
        );
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('AFS Analytics');
        $this->description = $this->l('Description of AFS module.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        AFSAConfig::$module = $this;
        AFSAConfig::set('path', $this->_path);

        $this->buildDics();
    }

    // MODULE INSTALL / UNINSTALL

    public function install()
    {
        if (ini_get('max_execution_time') < 300) {
            set_time_limit(300);
        }

        if (!parent::install()) {
            return false;
        }

        if (_PS_VERSION_ >= 1.7) {
            $hookHeader = 'displayHeader';
            $hookFooter = 'displayBeforeBodyClosingTag';
        } else {
            $hookFooter = 'footer';
            $hookHeader = 'header';
        }

        foreach (
        array(
            $hookHeader,
            'displayHome',
            'displayFooterProduct',
            'displayOrderConfirmation',
            'actionCartSave',
            'actionCarrierProcess',
            'actionProductCancel',
            'displayAdminOrder',
            'displayBackOfficeHeader',
            'dashboardZoneTwo',
            $hookFooter,
        ) as $hook) {
            if (!$this->registerHook($hook)) {
                //AFSATools::log('unable to register hooks ' . $hook);
            } else {
                //AFSATools::log('registered hook: ' . $hook);
            }
        }

        AFSAUITabs::install($this);
        AFSADB::get()->createTables();

        $this->getFormManager()->install();

        return true;
    }

    public function uninstall()
    {
        if (ini_get('max_execution_time') < 300) {
            set_time_limit(300);
        }

        if (!parent::uninstall()) {
            return false;
        }

        AFSAUITabs::uninstall($this);
        AFSADB::get()->dropTables();

        $this->getFormManager()->uninstall();

        foreach (array('AFS_ANALYTICS_OAUTH_TOKEN') as $name) {
            Configuration::deleteByName($name);
        }

        return true;
    }

    private function getFormManager()
    {
        if (empty($this->form_manager)) {
            $this->form_manager = new AFSAConfigFormManager($this);
        }

        return $this->form_manager;
    }

    // TRANSLATION HELPER

    public function buildDics()
    {
        AFSAConfig::importTR(array('oauth_invalid_state' => $this->l('Autorization was not provided by an official AFS Analytics server'),
            'no_account_set' => $this->l('No Account was set'),
            'please_set_account_id' => $this->l('Please enter a valid AFS Analytics website ID on Module configuration page.'),
            'my_account_id' => $this->l('My Website ID'),
            'link_existing_account' => $this->l('Link Existing Account'),
            'create_account' => $this->l('Create Account'),
            'launch_demo' => $this->l('Launch Demo'),
            'create_your_own_account' => $this->l('Create your very own Account'),
            'demo_account' => $this->l('Demo Account'),
            'existing_account_help' => $this->l('If you already possess an AFS analytics account, enter your Website ID below, click on "Link website", and you are done.'),
            'create_new_account_help' => $this->l('Or start a free 15 days trial and start experiencing without waiting the full power of our advanced ECommerce analytics solution. No credit card required'),
            'create_new_account_help_more' => $this->l(''),
            'live_demo' => $this->l('Live Demo'),
            'live_demo_help' => $this->l('Experience the full power of AFS Analytics with our live demo. No Account required.'),
            'configure_account' => $this->l('Configure your AFS Analytics module in one click'),
            'start_free_trial' => $this->l('Start free trial'),
            'demo_notice_title' => $this->l('Live Demo'),
            'demo_notice_help' => $this->l('This dashboard is displaying in real time 0the activity of a test website powered by prestahop.'),
            'demo_notice_help_more' => $this->l('To monitor your own prestahop installation, you will have to open your own account.'),
            'congratulations' => $this->l('Congratulations'),
            'module_configured' => $this->l('Your AFS Analytics module is now fully configured.'),
            'module_working' => $this->l('Traffic and activity on your shop is now monitored in real time. Detailled statistics will begin to appear in your module dashboard as soon as new visitors will be visiting it'),
            'account_id_available' => $this->l('You can retrieve your AFS Analytics Website ID at any time by visiting this module configuration page.'),
            'thanks_using_afsa' => $this->l('Thanks for using AFS analytics.'),
            'advanced_configuration' => $this->l('Advanced configuration'),
            'visit_afsa' => $this->l('Visit AFSAnalytics.com'),
            'open_dashboard' => $this->l('Open Dashboard'),
                // 'days_trends_help' => $this->l('Days Trends Summary will start to be displayed here as soon as you open the embedded')
                )
        );
    }

    public function getContext()
    {
        return $this->context;
    }

    private function getCartID()
    {
        return (int) $this->context->cart->id;
    }

    private function getShopID()
    {
        return (int) $this->context->shop->id;
    }

    public function getControllerName()
    {
        return Tools::getValue('controller');
    }

    public function isCurrentController($name)
    {
        return $this->getControllerName() == $name;
    }

    public function getAdminLink($name)
    {
        return $this->context->link->getAdminLink($name);
    }

    //  RENDERING

    /**
     * Render Module Configuration Form
     * see classes/config/form/*
     *
     * @return string html code
     */
    public function getContent()
    {
        return $this->getFormManager()->render();
    }

    // HOOKS
    // Backoffice widget
    public function hookDashboardZoneTwo()
    {
        if (\AFSARendererAdminWidget::shouldDisplay()) {
            $widget = new \AFSARendererAdminWidget();

            return $widget->render();
        }

        return null;
    }

    // BackOffice Header
    public function hookDisplayBackOfficeHeader()
    {
        $ret = '';

        $tracker = AFSATracker::get();

        // Refunds infos are tmp saved in cookie
        // render them if available
        $refunds = AFSAConfig::getRefundedOrders();

        if (!empty($refunds)) {
            foreach ($refunds as $refund) {
                $tracker->renderRefundedOrder($refund['id'], $refund['products']);
            }
        }

        AFSAConfig::resetRefundedOrders();

        // Add Custom JS / CSS
        // only on configure module page ATM
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->setMedia();
            $this->context->controller->addJS($this->_path . 'views/js/back.office.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.office.css');
        }

        switch (Tools::getValue('controller')) {
            case 'AdminDashboard':
                if (\AFSARendererAdminWidget::shouldDisplay()) {
                    AFSAConfig::set('gfonts', 'Lato:700|Open+sans:500');
                    \AFSARendererAdminWidget::hookDisplayBackOfficeHeader($this->context->controller);
                }
                break;
        }

        $this->context->controller->addJS($this->_path . 'views/js/admin.icon.js');

        $g_fonts = AFSAConfig::get('gfonts');
        if (!empty($g_fonts)) {
            $ret .= '<link href="https://fonts.googleapis.com/css?family='
                    . $g_fonts . '" rel="stylesheet" type="text/css">';
        }

        return $ret
                . "\n" . AFSATracker::get()->render()
                . "\n" . '<script>'
                . 'var afsa_plugin_base_url="' . AFSAConfig::getUrl()
                . '";</script>'
        ;
    }

    // HEADERS

    private function _onBeforeDisplayedHeader()
    {
        $controller_name = Tools::getValue('controller');

        switch ($controller_name) {
            case 'index':
            case 'search':
            case 'category':
                AFSAConfig::setLastProductList($controller_name);
                break;
        }

        AFSATools::log('last LIST ' . AFSAConfig::getLastProductList());
    }

    // PS < 1.7
    public function hookHeader($params)
    {
        $this->_onBeforeDisplayedHeader();

        return "\n"
                //. '<script type="text/javascript" src="' . $this->_path . 'views/js/AFSA.tracker.js"></script>'
                . AFSATracker::get()->render();
    }

    // PS > 1.7
    public function hookDisplayHeader()
    {
        $this->_onBeforeDisplayedHeader();

        // Unused ATM
        // $this->context->controller->addJS($this->_path . 'views/js/AFSA.tracker.js');

        return AFSATracker::get()->render();
    }

    // FOOTER

    public function hookFooter($params)
    {
        return _PS_VERSION_ < 1.7 ?
                $this->_hookFooterCommon() :
                null;
    }

    public function hookDisplayBeforeBodyClosingTag()
    {
        return _PS_VERSION_ >= 1.7 ?
                $this->_hookFooterCommon() :
                null;
    }

    /**
     * Gather additional infos and render bottom js
     *
     * @return type
     */
    private function _hookFooterCommon()
    {
        $controller_name = Tools::getValue('controller');

        AFSATools::log($controller_name);

        if (AFSAConfig::isAjax()) {
            AFSATools::log('TRACKER CART AJAX Detected, not rendering Bottom code');

            return;
        }

        $tracker = AFSATracker::get();

        $cart_id = $this->getCartID();
        $shop_id = $this->getShopID();

        if ($controller_name == 'product') {
            AFSATools::log('TRACKER CART PRODUCT Detected, not processing ADD Action');
        } else {
            $db = AFSADB::get();
            $cart_items = $db->getCart($cart_id, $shop_id);

            if (count($cart_items) > 0) {
                foreach ($cart_items as $data) {
                    if (empty($data)) {
                        continue;
                    }

                    if (isset($data['quantity'])) {
                        // $data contains product info
                        if ($data['quantity'] > 0) {
                            $tracker->renderAddToCart($data);
                        } elseif ($data['quantity'] < 0) {
                            $data['quantity'] = abs($data['quantity']);
                            $tracker->renderRemoveFromCart($data);
                        }
                    } else {
                        // $data contain raw js ( see hookactionCarrierProcess for example)
                        $tracker->assimilate($data);
                    }
                }
            }

            if ($cart_id) {
                $db->deleteCart($cart_id, $shop_id);
            }
        }

        // Checkout step 1

        AFSATools::log(json_encode($this->context->cookie));

        $products = $this->context->cart->getProducts(true);

        if ($controller_name == 'order' || $controller_name == 'orderopc') {
            AFSATools::log('=>' . $controller_name);

            $ignore_impression = 1;
            $ignore_click = 1;
            AFSATools::log(AFSAConfig::getLastCheckoutStepForCart($cart_id));
            AFSATools::log('ST:' . Tools::getValue('step'));

            $tracker->renderCheckoutStep(1, $cart_id, $products);
        }

        // Check if  displayOrderConfirmation has been executed

        $confirmation_hook_id = (int) Hook::getIdByName('displayOrderConfirmation');
        if (isset(Hook::$executed_hooks[$confirmation_hook_id])) {
            $ignore_impression = 1;
            $ignore_click = 1;
        }

        // Render listed product impression / click
        $listing = $this->context->smarty->getTemplateVars('listing');
        $listed_products = $listing['products'];

        if (!empty($listed_products) && $controller_name != 'index') {
            if (empty($ignore_click)) {
                $tracker->renderProductsClick($listed_products, $controller_name);
            }
            if (empty($ignore_impression)) {
                $tracker->renderProductsImpression($listed_products, $controller_name);
            }
        }

        return AFSATracker::get()->renderBottomJS();
    }

    /**
     * hook home to update the product listed in home featured,
     * news products and best sellers modules/sections
     */
    public function hookDisplayHome()
    {
        $tracker = AFSATracker::get();

        // Home featured products

        if (Module::isEnabled('ps_featuredproducts')) {
            $category = new Category($this->context->shop->getCategory(), $this->context->language->id);

            $products = $category->getProducts((int) Context::getContext()->language->id, 1, (Configuration::get('HOME_FEATURED_NBR') ? (int) Configuration::get('HOME_FEATURED_NBR') : 8), 'position');

            $list = 'home featured';
            $tracker->renderProductsImpression($products, $list);
            $tracker->renderProductsClick($products, $list);

            AFSAConfig::setLastProductList($list);
        }
    }

    /**
     *  Retrieve Order Infos from Order Confirmation Page
     */
    public function hookDisplayOrderConfirmation($params)
    {
        $tracker = AFSATracker::get();
        $db = AFSADB::get();

        if (!empty($params['order'])) {
            $order = $params['order'];
        } elseif (!empty($params['objOrder'])) {
            $order = $params['objOrder'];
        } else {
            AFSATools::log(__METHOD__ . ' unable to retrieve order');
        }

        if (!empty($order)) {
            if (// order by current customer
                    $order->id_customer == $this->context->cookie->id_customer
                    // less than one day old
                    && strtotime('+1 day', strtotime($order->date_add)) > time()
                    // not already in db
                    && !$db->wasOrderProcessed($order->id, $this->getShopID())
            ) {
                // track order and save it in db
                $tracker->renderOrderInfo($order);
                $db->saveOrderIfNotExists($order->id, $this->getShopID());
                $db->cleanProcessedOrderTable();

                $address_id = $order->id_address_invoice;
                if (!empty($address_id)) {
                    $tracker->renderAdressInfo($address_id);
                }
            }
        }
    }

    /**
     * hook save cart event to implement addtocart and remove from cart functionality
     */
    public function hookactionCartSave()
    {
        if (!isset($this->context->cart) || !Tools::getIsset('id_product')) {
            return;
        }

        $cart = array(
            'controller' => Tools::getValue('controller'),
            'addAction' => Tools::getValue('add') ? 'add' : '',
            'removeAction' => Tools::getValue('delete') ? 'delete' : '',
            'extraAction' => Tools::getValue('op'),
            'qty' => (int) Tools::getValue('qty', 1),
        );

        $cart_products = $this->context->cart->getProducts();
        if (isset($cart_products) && count($cart_products)) {
            foreach ($cart_products as $cart_product) {
                if ($cart_product['id_product'] == Tools::getValue('id_product')) {
                    $add_product = $cart_product;
                }
            }
        }

        // Retrieve product infos if not set

        if ($cart['removeAction'] == 'delete') {
            $add_product_object = new Product((int) Tools::getValue('id_product'), true, (int) Configuration::get('PS_LANG_DEFAULT'));
            if (Validate::isLoadedObject($add_product_object)) {
                $add_product['name'] = $add_product_object->name;
                $add_product['manufacturer_name'] = $add_product_object->manufacturer_name;
                $add_product['category'] = $add_product_object->category;
                $add_product['reference'] = $add_product_object->reference;
                $add_product['link_rewrite'] = $add_product_object->link_rewrite;
                $add_product['link'] = $add_product_object->link_rewrite;
                $add_product['price'] = $add_product_object->price;
                $add_product['ean13'] = $add_product_object->ean13;
                $add_product['id_product'] = Tools::getValue('id_product');
                $add_product['id_category_default'] = $add_product_object->id_category_default;
                $add_product['out_of_stock'] = $add_product_object->out_of_stock;
                $add_product['minimal_quantity'] = 1;
                $add_product['unit_price_ratio'] = 0;
                $add_product = Product::getProductProperties((int) Configuration::get('PS_LANG_DEFAULT'), $add_product);
                AFSATools::log('add_product ' . json_encode($add_product, JSON_PRETTY_PRINT));
            }
        }

        if (isset($add_product) && !in_array((int) Tools::getValue('id_product'), self::$products)) {
            self::$products[] = (int) Tools::getValue('id_product'); // add to processed products

            $infos = new AFSAProductInfos($this);
            $infos->registerExtraData($cart);
            $p_data = $infos->parseProduct($add_product, -1, AFSA_FORMAT_PRODUCT);

            $uid = $infos->getUniqueID();
            if (empty($uid)) {
                $uid = Tools::getValue('id_product');
            }

            $cart_id = $this->getCartID();
            $shop_id = $this->getShopID();

            $db = AFSADB::get();

            $cart_items = $db->getCart($cart_id, $shop_id);

            // Adjust quantity

            if ($cart['removeAction'] == 'delete') {
                AFSATools::log('remove ' . json_encode($p_data, JSON_PRETTY_PRINT));
                $p_data['quantity'] = empty($add_product['cart_quantity']) ? -1 : -$add_product['cart_quantity'];
            } elseif ($cart['extraAction'] == 'down') {
                if (array_key_exists($uid, $cart_items)) {
                    $p_data['quantity'] = $cart_items[$uid]['quantity'] - $cart['qty'];
                } else {
                    $p_data['quantity'] = $cart['qty'] * -1;
                }
            } elseif (Tools::getValue('step') <= 0) { // Sometimes cartsave is called in checkout
                if (array_key_exists($uid, $cart_items)) {
                    $p_data['quantity'] = $cart_items[$uid]['quantity'] + $cart['qty'];
                }
            }

            // Save cart item to DB

            $cart_items[$uid] = $p_data;
            $db->saveCart($cart_id, $shop_id, $cart_items);

            // DB saved carts will be processed by tracker
            // in  displayFooter Hook
        }
    }

    public function hookactionCarrierProcess($params)
    {
        $cart_id = $this->getCartID();
        AFSATools::log(__METHOD__);

        if (!empty($params['cart']->id_carrier)) {
            $carrier_name = Db::getInstance()->getValue('SELECT name FROM `' . _DB_PREFIX_ . 'carrier` WHERE id_carrier = ' . (int) $params['cart']->id_carrier);
            AFSATracker::get()->renderCheckoutStep(AFSA_TRACKER_CHEKOUT_OPTION_CARRIER, $cart_id, null, $carrier_name);

//            $db = AFSADB::get();
//            $db->addCartData(
//                    $this->getCartID(), $this->getShopID(), AFSATracker::get()->renderCheckoutOption(2, $carrier_name)
//            );
        }
    }

    /**
     * hook product page footer to render product details view
     */
    public function hookdisplayFooterProduct($params)
    {
        $tracker = AFSATracker::get();
        $controller_name = Tools::getValue('controller');
        if ($controller_name == 'product' && !empty($params['product'])) {
            $product = &$params['product'];
            $tracker->renderProductDetailView($product);

            if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0) {
                $tracker->renderProductClickByHttpReferal(array($product));
            }
        }
    }

    /**
     * Track refunds
     *
     * Hook is called when a product is cancelled from an Order
     * This hook gets called once for each product that has been cancelled
     * $params['order'] => Order object
     * $params['id_order_detail']
     *
     * @param array $params
     */
    public function hookActionProductCancel($params)
    {
        $qty_refunded = Tools::getValue('cancelQuantity');

        AFSATools::log(__METHOD__, array_keys($params));

        $products = array();
        foreach ($qty_refunded as $orderdetail_id => $qty) {
            $order_detail = new OrderDetail($orderdetail_id);

            if (!empty($qty)) {
                $products[] = array(
                    'id' => $order_detail->product_id,
                    'attribute_id' => empty($order_detail->product_attribute_id) ? null : $order_detail->product_attribute_id,
                    'sku' => $order_detail->product_reference,
                    'quantity' => $qty,
                );
            }
        }

        AFSAConfig::setRefundedOrder(array(
            'id' => $params['order']->id,
            'products' => $products,
        ));
    }
}
