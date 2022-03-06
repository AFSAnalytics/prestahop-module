<?php

define('AFSA_ECOMMERCE_UNSUPPORTED', 0);
define('AFSA_ECOMMERCE_BASIC', 1);
define('AFSA_ECOMMERCE_ADVANCED', 2);

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/account/manager.php';

class AFSAConfig
{
    const DEMO_ACCOUNT_ID = 'PSHOPDEMO';

    public static $module;
    public static $tr = array();
    public static $data_store = array();
    public static $infos_manager = null;
    public static $log_enabled = false;
    public static $demo_mode_enabled = false;

    public static function CMS()
    {
        return 'prestashop';
    }

    public static function CMSVersion()
    {
        return _PS_VERSION_;
    }

    public static function pluginName()
    {
        return 'afsanalytics';
    }

    public static function pluginPath()
    {
        print_r(static::$module->getLocalPath(), static::$module->name);

        return static::$module->getLocalPath() . static::$module->name;
    }

    public static function getContext()
    {
        return static::$module->getContext();
    }

    public static function isDebug()
    {
        return static::$module->debug;
    }

    public static function isDemo()
    {
        return static::$demo_mode_enabled;
    }

    public static function setDemoMode($b = true)
    {
        static::$demo_mode_enabled = $b;
    }

    public static function isLogEnabled()
    {
        return static::$log_enabled && static::isDebug();
    }

    public static function isAjax()
    {
        $is_ajax = false;

        if (Tools::getValue('controller') == 'ajax') {
            return true;
        }

        try {
            $headers = AFSATools::getAllHeaders();
            if (!empty($headers['X-Requested-With']) && $headers['X-Requested-With'] == 'XMLHttpRequest') {
                return true;
            }
        } catch (Exception $ex) {
        }

        return $is_ajax;
    }

    // INFOS

    public static function getLNGId()
    {
        return static::getContext()->cookie->id_lang;
    }

    public static function getLNG()
    {
        return Language::getIsoById(static::getLNGID());
    }

    public static function getInfosManager()
    {
        if (empty(static::$infos_manager)) {
            static::$infos_manager = new AFSAInfosManager();
        }

        return static::$infos_manager;
    }

    // URLS
    public static function getAFSAHome()
    {
        return 'https://www.afsanalytics.com';
    }

    public static function getAFSAAPIHome()
    {
        return 'https://api.afsanalytics.com';
    }

    // root URL for plugin files
    public static function pluginURL($u = null)
    {
        return chop(_MODULE_DIR_ . static::pluginName(), '/')
            . '/'
            . ($u ? $u : '');
    }

    public static function getIMGServerURL()
    {
        return static::getAFSAAPIHome();
    }

    public static function getURL($u = null)
    {
        return static::pluginURL($u);
    }

    // URLS

    public static function getConfigControllerURL()
    {
        return static::getContext()->link->getAdminLink('AdminModules')
            . '&configure='
            . Tools::safeOutput(AFSAConfig::pluginName());
    }

    public static function getCurrentScheme()
    {
        return Tools::usingSecureMode() ? 'https' : 'http';
    }

    public static function getCurrentURL()
    {
        return static::getCurrentScheme()
            . '://' . $_SERVER['HTTP_HOST']
            . $_SERVER['REQUEST_URI'];
    }

    public static function getAJAXServerURL()
    {
        return static::getContext()->link->getAdminLink('AdminAFSAAjax');
    }

    public static function getDashboardDemoURL()
    {
        return static::getContext()->link->getAdminLink('AdminAFSADashboardDemo');
    }

    public static function getDashboardURL()
    {
        return static::getContext()->link->getAdminLink('AdminAFSADashboard');
    }

    public static function getAccountManagerURL($args = [])
    {
        return static::getContext()->link->getAdminLink('AdminAFSAAccountManager', true, [], $args);
    }

    public static function getAdminURL()
    {
        return static::getContext()->link->getAdminLink('AdminDashboard');
    }

    public static function getpluginFile($filename)
    {
        return _PS_MODULE_DIR_ . '/afsanalytics/' . $filename;
    }

    public static function getPageName()
    {
        return static::getInfosManager()->page()->getName();
    }

    public static function getPageCategories()
    {
        return static::getInfosManager()->page()->getCategories();
    }

    public static function isBackOffice()
    {
        return static::getContext()->controller->controller_type !== 'front';
    }

    public static function isEmployee()
    {
        return !empty(static::getContext()->employee);
    }

    public static function getShopName()
    {
        return Shop::isFeatureActive() ? static::getContext()->shop->name : Configuration::get('PS_SHOP_NAME');
    }

    public static function getShopAffiliation()
    {
        $ret = Configuration::get('AFS_ANALYTICS_SHOP_AFFILIATION');

        return $ret ?
            $ret :
            'Prestashop';
    }

    public static function getAccessKey()
    {
        return (string) Configuration::get('AFS_ANALYTICS_ACCESSKEY');
    }

    public static function getAccountID()
    {
        return static::isDemo() ?
            static::DEMO_ACCOUNT_ID :
            static::getStringOption('account');
    }

    public static function advancedECommerceEnabled()
    {
        return true;
    }

    public static function getECommerceLevel()
    {
        return AFSA_ECOMMERCE_ADVANCED;
    }

    public static function getPAARC()
    {
        return Configuration::get('AFS_ANALYTICS_PAA_RC');
    }

    public static function anonymizeMembers()
    {
        return (bool) Configuration::get('AFS_ANALYTICS_ANON_USER_INFOS');
    }

    public static function getGlobalCurrency()
    {
        return new Currency(static::getContext()->currency->id);
    }

    public static function getGlobalCurrencyCode()
    {
        return static::getGlobalCurrency()->iso_code;
    }

    /**
     * Check if we have a valid AFSA account number
     *
     * ( validate on 8 numbers only chain )
     *
     * @return bool
     */
    public static function AFSAEnabled()
    {
        return static::validateAccountID(static::getAccountID());
    }

    public static function validateAccountID($id)
    {
        return !empty($id) && ctype_digit($id) && Tools::strlen($id) == 8 && (int) $id > 0;
    }

    // DATA STORE

    /**
     * set data store value
     *
     * @param string $k key
     * @param mixed $v initial value
     */
    public static function set($k, $v)
    {
        static::$data_store[$k] = $v;
    }

    /**
     * get data stored value
     *
     * @param string $k key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function get($k, $default = null)
    {
        return empty(static::$data_store[$k]) ? $default : static::$data_store[$k];
    }

    // SETTINGS / OPTIONS

    public static function getPageTitleDetectMethod()
    {
        $ret = Configuration::get('AFS_ANALYTICS_PAGE_NAME_METHOD');

        return empty($ret) ? 'auto' : $ret;
    }

    public static function saveAccountID($id)
    {
        AFSAAccountManager::get()->setCurrentID($id);
    }

    // TRACKER OPTIONS

    public static function shouldTrack()
    {
        if (static::isAjax() || static::isDemo() || !static::AFSAEnabled()) {
            return false;
        }

        if (AFSAConfig::isBackOffice() && !(AFSAConfig::areAdminTrackingInfosAvailable() || AFSAConfig::trackAdminPages())) {
            return false;
        }

        return true;
    }

    public static function trackAdminPages()
    {
        return (bool) Configuration::get('AFS_ANALYTICS_ADMIN_PAGES_TRACKING');
    }

    public static function getAutoTrackOption($key)
    {
        return static::getIntOption('AUTOTRACK_' . $key);
    }

    public static function getAutoTrackAllOption()
    {
        return static::getAutoTrackOption('ALL');
    }

    public static function getAutoTrackOptionArray()
    {
        $ret = array();
        foreach (array('outboundclick', 'insideclick', 'download', 'video', 'iframe') as $key) {
            $ret[$key] = AFSAConfig::getAutoTrackOption($key);
        }

        return $ret;
    }

    /*
     * Cart checkout Step saved in user session
     *
     */

    public static function setCheckoutStepForCart($cart_id, $step)
    {
        $context = static::getContext();

        $context->cookie->afsa_checkout_step = $step;
        $context->cookie->afsa_checkout_cart = $cart_id;
    }

    public static function getLastCheckoutStepForCart($cart_id)
    {
        $context = static::getContext();

        if (empty($context->cookie->afsa_checkout_cart) || $cart_id != $context->cookie->afsa_checkout_cart) {
            return 0;
        }

        return $context->cookie->afsa_checkout_step;
    }

    public static function clearCheckoutSteps()
    {
        static::setCheckoutStepForCart(0, 0);
    }

    // (LAST) PRODUCT LIST
    public static function setLastProductList($str)
    {
        static::getContext()->cookie->afsa_product_list = $str;
    }

    public static function getLastProductList()
    {
        $context = static::getContext();

        return empty($context->cookie->afsa_product_list) ?
            'product page' :
            $context->cookie->afsa_product_list;
    }

    public static function setRefundedOrder($data)
    {
        $context = static::getContext();

        $refunds = static::getRefundedOrders();
        $refunds[] = $data;

        $context->cookie->afsa_admin_refund = json_encode($refunds);

        AFSATools::log(__METHOD__, $context->cookie->afsa_admin_refund);
    }

    public static function getRefundedOrders()
    {
        $context = static::getContext();

        return empty($context->cookie->afsa_admin_refund) ?
            array() :
            json_decode($context->cookie->afsa_admin_refund, true);
    }

    public static function resetRefundedOrders()
    {
        static::getContext()->cookie->afsa_admin_refund = null;
    }

    // do we have some saved infos that need to be sent
    public static function areAdminTrackingInfosAvailable()
    {
        $context = static::getContext();

        return !empty($context->cookie->afsa_admin_refund);
    }

    // UTILS

    public static function getIntOption($key)
    {
        return (int) Configuration::get('AFS_ANALYTICS_' . Tools::strtoupper($key));
    }

    public static function getStringOption($key)
    {
        return (string) Configuration::get('AFS_ANALYTICS_' . Tools::strtoupper($key));
    }

    // OAUTH

    public static function getOauthServerURL()
    {
        return static::getAFSAAPIHome() . '/v1/';
    }

    public static function getOauthClientID()
    {
        return 'afsa_prestashop_plugin';
    }

    /**
     * Save Oauth callback url as we need
     * to resent the exact same url when requesting
     * access token from a received auth code
     *
     * @param string $u callback url
     */
    public static function saveOauthCallbackURL($u)
    {
        static::getContext()->cookie->oauth_callback_url = $u;
    }

    public static function getOauthCallbackURL()
    {
        try {
            return static::getContext()->cookie->oauth_callback_url;
        } catch (Exception $ex) {
        }

        return null;
    }

    // ACCOUNT

    public static function saveRequestState($state)
    {
        static::getContext()->cookie->afsa_request_state = $state;
    }

    public static function getRequestState()
    {
        try {
            return static::getContext()->cookie->afsa_request_state;
        } catch (Exception $ex) {
        }

        return null;
    }

    // TRANSLATION

    public static function setTR($k, $str)
    {
        static::$tr[$k] = $str;
    }

    public static function importTR($arr)
    {
        foreach ($arr as $k => $str) {
            static::$tr[$k] = $str;
        }
    }

    public static function TR($k)
    {
        return empty(static::$tr[$k]) ? null : static::$tr[$k];
    }
}
