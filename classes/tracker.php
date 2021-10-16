<?php

/**
 * Render AFSAnalytics tracker
 */
define('AFSA_TRACKER_CHEKOUT_OPTION_INIT', 1);
define('AFSA_TRACKER_CHEKOUT_OPTION_CARRIER', 2);
define('AFSA_TRACKER_CHEKOUT_OPTION_COMPLETE', 3);

class AFSATracker
{
    private $account_id;
    private $rendered = false;
    private $advanced_mode = true;
    private $_log;
    private $buffer;
    private static $instance = null;

    public static function get()
    {
        return static::$instance ?: static::$instance = new static();
    }

    public function __construct()
    {
        $this->account_id = Tools::safeOutput(AFSAConfig::getAccountID());
        $this->buffer = array();
        $this->advanced_mode = AFSAConfig::advancedECommerceEnabled();
    }

    public function __destruct()
    {
        $this->saveLog();
    }

    private function getInfosManager()
    {
        return AFSAConfig::getInfosManager();
    }

    private function getVisitorInfos()
    {
        return $this->getInfosManager()->visitor();
    }

    private function getAddressInfos($o)
    {
        return $this->getInfosManager()->address($o);
    }

    private function validate()
    {
        return !empty($this->account_id);
    }

    public function isAdvancedECommerceEnabled()
    {
        return $this->advanced_mode;
    }

    private function shouldTrackPageview()
    {
        return !(AFSAConfig::isBackOffice() && !AFSAConfig::trackAdminPages());
    }

    private function shouldTrack()
    {
        return AFSAConfig::shouldTrack() &&
            $this->validate();
    }

    // CODE RENDERING

    private function aaCreate($value)
    {
        return 'aa("create", "' . $this->account_id . '", "' . $value . '");';
    }

    private function aaSend($what)
    {
        switch ($what) {
            case 'refund':
                return `aa('send', 'event', 'Ecommerce', 'Refund', {'nonInteraction': 1});`;
            case 'checkout':
                return `aa('send', 'event', 'Checkout', 'Option');`;
        }

        return 'aa("send", "' . $what . '");';
    }

    // AA SET

    private function aaSet($what, $value)
    {
        return 'aa("set", "' . $what . '", '
            . (is_array($value) || is_string($value) ? json_encode($value) : "'$value'")
            . ');';
    }

    private function aaSetRaw($what, $value)
    {
        return 'aa("set", "' . $what . '", ' . $value . ');';
    }

    public function aaAddItem($data)
    {
        return $this->aaSet('addItem', $data);
    }

    public function aaAddProduct($data)
    {
        return $this->aaSet('addProduct', $data);
    }

    public function aaAddImpression($data)
    {
        return $this->aaSet('addImpression', $data);
    }

    public function aaSetAction($action_name, $data = null)
    {
        if (empty($data)) {
            return $this->aaSet('setAction', $action_name);
        }

        return 'aa("set", "setAction", "' . $action_name . '", '
            . (is_array($data) ? json_encode($data) : "'$data'")
            . ');';
    }

    // ADVANCED ECOM

    private function aaECAddImpression($data)
    {
        return 'aa("ec:addImpression", '
            . json_encode($data)
            . ');';
    }

    private function aaECAddProduct($data)
    {
        return 'aa("ec:addProduct", '
            . json_encode($data)
            . ');';
    }

    private function aaECAction($action, $param = [])
    {
        $ret = "aa('ec:setAction', '$action'";

        if (is_array($param)) {
            $param['beacon'] = 'enabled';
        }

        if ($param) {
            $ret .= ',' . json_encode($param);
        }

        return $ret . ');';
    }

    // CUSTOM

    private function aaSetOption($what, $data, $key)
    {
        return $this->aaSet($what, $data[$key]);
    }

    private function aaSetAutoTrackOption($what, $key)
    {
        $data = array(
            0 => 'dataset',
            1 => 'on',
            2 => 'off',
        );

        return $this->aaSet($what, $data[$key]);
    }

    public function render()
    {
        if (!$this->shouldTrack()) {
            return null;
        }

        if ($this->rendered) {
            AFSATools::log('tracker already rendered');

            return null;
        }
        $this->rendered = true;

        $ip_setting = AFSAConfig::getIntOption('ip_setting');
        $localization_setting = AFSAConfig::getIntOption('localization_setting');
        $user_consent = AFSAConfig::getIntOption('user_consent');
        $cookie_setting = AFSAConfig::getIntOption('cookie_setting');

        // CREATE

        $aa = array();

        $aa[] = $this->aaCreate($cookie_setting == 1 ? 'nocookie' : 'auto');

        // TRACKERNAME (page name)

        $trackername = AFSAConfig::getPageName();
        if ($trackername == 'title') {
            $aa[] = $this->aaSetRaw('title', 'document.title');
        } elseif (!empty($trackername)) {
            $aa[] = $this->aaSet('title', $trackername);
        }

        // CMS

        $aa[] = $this->aaSet('cms', AFSAConfig::CMS());
        $aa[] = $this->aaSet('api', AFSA_MODULE_VERSION);

        // ECOM INFOS

        $aa[] = $this->aaSet('currencyCode', AFSAConfig::getGlobalCurrencyCode());

        // PRIVACY

        if ($ip_setting != 0) {
            $aa[] = $this->aaSet('anonymizeip', $ip_setting);
        }

        if ($user_consent != 0) {
            $aa[] = $this->aaSetOption('cookieconsent_mode', array(1 => 'exemption', 2 => 'consent_auto'), $user_consent);

            $aa[] = $this->aaSetOption('cookieconsent_audience', array(0 => 'eu', 1 => 'all'), $localization_setting);
        }

        // PAGE

        $aa[] = $this->renderCategoryInfo(AFSAConfig::getPageCategories());

        // AUTOTRACK

        $autotrack_all = AFSAConfig::getAutoTrackAllOption();

        $aa[] = $this->aaSetAutoTrackOption('autotrack', $autotrack_all);

        $autotrack_options = AFSAConfig::getAutoTrackOptionArray();
        if (!empty($autotrack_options)) {
            foreach ($autotrack_options as $name => $value) {
                if ($value != $autotrack_all) {
                    $aa[] = $this->aaSetAutoTrackOption('autotrack.' . $name, $value);
                }
            }
        }

        // Should come before user infos

        if ($this->shouldTrackPageview()) {
            $aa[] = 'aa("send", "pageview");';
        }

        // USER / LOGIN infos
        if (AFSAConfig::getIntOption('user_logged_tracking') == 1) {
            $aa[] = $this->renderLoginInfo();
            $aa[] = $this->renderUserInfo();
        }

        $aa[] = $this->renderBuffer();

        $js_url = '//code.afsanalytics.com/js2/analytics.js';

        //$cms_infos = AFSAConfig::CMS() . ' ' . AFSAConfig::CMSVersion();

        $ret = "\n<!-- AFS Analytics V7 -"
            . ' Module ' . AFSA_MODULE_VERSION
            . ' Mode: ' . ($this->advanced_mode ? 'advanced' : 'simple')
            . " -->\n"
            . "\n<script data-keepinline=\"true\" type=\"text/javascript\">"
            . "\n(function(i,s,o,g,r,a,m){i['AfsAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','$js_url','aa');\n"
            . implode("\n", array_filter($aa))
            . "\n</script>\n"
            . "<!-- [ END ] Advanced ECommerce Analytics Code by AFSAnalytics.com -->\n";

        return $this->log($ret);
    }

    /**
     * Render additional tracker code at the bottom of the page
     *
     * @return string js code
     */
    public function renderBottomJS()
    {
        // only render if we have a valid AFSA Account number
        if (!$this->shouldTrack()) {
            return '<!-- AFS Analytics Bottom Code [ !ST ] -->';
        }

        if (AFSAConfig::AFSAEnabled()) {
            if (empty($js = $this->renderBuffer())) {
                AFSATools::log('empty bottom js');

                return '<!-- AFS Analytics Bottom Code [ Empty ] -->';
            }

            $ret = '<!-- AFS Analytics Bottom Code [ DBG START ] -->' . "\n"
                . AFSATools::renderJSScript($js)
                . "<!-- [ END ] Advanced ECommerce Analytics Code by AFSAnalytics.com -->\n";

            return $this->log($ret);
        }

        return '<!-- AFS Analytics Bottom Code [ !Enabled ] -->';
    }

    /**
     * render code buffer then reset it
     *
     * @return type
     */
    public function renderBuffer()
    {
        if (empty($this->buffer)) {
            return null;
        }

        $aa = empty($this->buffer['aa']) ? null : implode("\n", $this->buffer['aa']);
        $AFSA = empty($this->buffer['AFSA']) ? null : implode("\n", $this->buffer['AFSA']);

        $this->buffer = array();

        $ret = $aa
            . (empty($AFSA) ? null : "\nfunction onAFSATrackerLoaded() {\n$AFSA\n};");

        return empty($ret) ? null : $ret;
    }

    /**
     * Save code to be rendered at the bottom of the page
     * in a tmp buffer.
     * ( see previous method  renderBuffer() )
     *
     * @param array $p js code lines
     * @param string $type aa for basic aa instructions, AFSA for functions requiring AFSA.tracker to be loaded
     *
     * @return bool success
     */
    public function assimilate($p, $type = 'aa')
    {
        if (empty($p)) {
            return false;
        }

        if (!isset($this->buffer[$type])) {
            $this->buffer[$type] = array();
        }

        $this->buffer[$type] = array_merge($this->buffer[$type], (array) $p);

        return true;
    }

    // USER INFO

    private function renderLoginInfo()
    {
        try {
            if ($this->getVisitorInfos()->isLogged() && !isset($_COOKIE['afslogged'])) {
                return
                    "aa('set','visitor.logged','1');\n"
                    . "var d = new Date();\n"
                    . "d.setTime(d.getTime() +(3600*1000));\n"
                    . "var expires = 'expires='+ d.toUTCString();\n"
                    . "document.cookie='afslogged=1;'+expires+';path=/';\n";
            }

            return null;
        } catch (Exception $e) {
            AFSATools::log('renderLoginInfo ' . $e->getMessage());
        }
    }

    private function renderUserInfo()
    {
        try {
            $infos = $this->getVisitorInfos();

            if ($infos->isLogged()) {
                if (!isset($_COOKIE['afssetuser'])) {
                    $ret = "var vdata={job:'update'};\n"
                        . "vdata.wpid='" . $infos->getID() . "';\n"; // ?WPID || YOUR ID

                    foreach ($infos->get() as $k => $v) {
                        if (!in_array($k, array('id', 'logged'))) { // excluded infos
                            $ret .= "vdata.$k='$v';\n";
                        }
                    }

                    return $ret
                        . "var ol= Object.keys(vdata).length;\n"
                        . "if (ol>2){\n"
                        . "aa('set','visitor',vdata);\n"
                        . "aa('send','visitor');\n"
                        . "var d = new Date();\n"
                        . "d.setTime(d.getTime() +((3600*12)*1000));\n"
                        . "var expires = 'expires='+ d.toUTCString();\n"
                        . "document.cookie = 'afssetuser=1;'+expires+';path=/';\n"
                        . "}\n";
                }
            }

            return null;
        } catch (Exception $e) {
            AFSATools::log('renderUserInfo ' . $e->getMessage());
        }
    }

    public function renderAddressInfo($mixed)
    {
        try {
            $infos = $this->getAddressInfos($mixed);

            if (!empty(($data = $infos->parse()))) {
                $data['job'] = 'update';
                $this->assimilate(array($this->aaSet('visitor', $data), $this->aaSend('visitor')));
            }

            return null;
        } catch (Exception $e) {
            AFSATools::log('renderAddressInfo ' . $e->getMessage());
        }
    }

    public function renderCategoryInfo($arr)
    {
        $aa = array();
        if (!empty($arr)) {
            foreach ($arr as $c) {
                $aa[] = $this->aaSet('contentGroup1', $c);
            }
        }

        return empty($aa) ? null : implode("\n", $aa);
    }

    // ADDITIONNALS INFOS (sent after main tracker has been rendered )
    // ECOMMERCE INFOS

    /**
     * Render order informations (including products)
     * on order confirmation page
     *
     * @param object|int $mixed order (object | id)
     *
     * @return string js code
     */
    public function renderOrderInfo($mixed, $p = array())
    {
        return $this->advanced_mode ?
            $this->renderAdvancedOrderInfo($mixed, $p) :
            $this->renderSimpleOrderInfo($mixed);
    }

    public function renderSimpleOrderInfo($mixed)
    {
        if (empty($mixed)) {
            return;
        }

        $aa = array();
        $o = new AFSAOrderInfos($mixed);
        $data = $o->parse(AFSA_FORMAT_TRANSACTION_ITEM);

        $aa[] = $this->aaSet('addTransaction', $data['order']);

        foreach ($data['items'] as $item) {
            $aa[] = $this->aaAddItem($item);
        }

        $aa[] = $this->aaSend('ecommerce');

        return $this->assimilate($aa);
    }

    // ADVANCED ECOMMERCE
    // ORDER

    public function renderAdvancedOrderInfo($mixed, $p)
    {
        try {
            if (empty($mixed)) {
                return;
            }

            $aa = array();
            $o = new AFSAOrderInfos($mixed);
            $data = $o->parse(AFSA_FORMAT_PRODUCT);

            foreach ($data['items'] as $item) {
                $aa[] = $this->aaECAddProduct($item);
            }

            // setting clear options to no as checkout will follow
            $aa[] = 'aa("ec:SetOption", {"clear": "no"});';
            $aa[] = $this->aaECAction('purchase', $data['order']);

            // setting clear options to default value;
            $aa[] = 'aa("ec:SetOption", {"clear": "yes"});';

            $ret = $this->assimilate($aa); // should come before vv

            if (empty($p['nostep'])) {
                $this->renderCheckoutStep(AFSA_TRACKER_CHEKOUT_OPTION_COMPLETE, 0, null, $o->getPayment());
                AFSAConfig::clearCheckoutSteps();
            }

            return $ret;
        } catch (Exception $e) {
            AFSATools::log('renderAdvancedOrderInfo ' . $e->getMessage());
        }
    }

    public function renderRefundedOrder($order_id, $products = null)
    {
        try {
            if (!$this->advanced_mode) {
                return null;
            }

            $aa = array();

            if (!empty($products)) {
                foreach ($products as $product) {
                    $aa[] = $this->aaAddProduct(array(
                        'id' => $product['sku'],
                        'quantity' => $product['quantity'],
                    ));
                }
            }

            $aa[] = $this->aaSetAction('refund', json_encode(array('id' => $order_id)));
            $aa[] = $this->aaSend('refund');

            return $this->assimilate($aa);
        } catch (Exception $e) {
            AFSATools::log('renderRefundedOrder ' . $e->getMessage());
        }
    }

    // CHECKOUT
    /*
     * Step 1 : on order Controller displayed (with last step < 1)
     * Step 2 : on carrier selected
     * Step 3 : on order confirmation page
     *
     *
     */

    private function renderCheckoutActionParams($step, $option = null)
    {
        try {
            $ret = array(
                'step' => $step,
            );
            if (!empty($option)) {
                $ret['option'] = $option;
            }

            return $ret;
        } catch (Exception $e) {
            AFSATools::log('renderCheckoutActionParams ' . $e->getMessage());
        }
    }

    //
    //    public function renderCheckoutOption($step, $option = null) {
    //        if (!$this->advanced_mode)
    //            return null;
    //
    //        $this->assimilate(array(
    //            $this->aaECAction('checkout', $this->renderCheckoutActionParams($step, $option)),
    //                )
    //        );
    //    }

    public function renderCheckoutStep($step, $cart_id, $products, $option = null)
    {
        try {
            if (!$this->advanced_mode) {
                return null;
            }

            $aa = array();

            $last_step = AFSAConfig::getLastCheckoutStepForCart($cart_id);
            if ($last_step > $step) {
                // Ignoring duplicate step
                return;
            }

            AFSAConfig::setCheckoutStepForCart($cart_id, $step);

            // only add products on first step
            if ($step == AFSA_TRACKER_CHEKOUT_OPTION_INIT) {
                $infos = new AFSAProductInfos();
                $p_data = $infos->parseProducts($products, null, AFSA_FORMAT_PRODUCT);

                foreach ($p_data as $data) {
                    $aa[] = $this->aaECAddProduct($data);
                }
            }

            $aa[] = $this->aaECAction('checkout', $this->renderCheckoutActionParams($step, $option));

            return $this->assimilate($aa);
        } catch (Exception $e) {
            AFSATools::log('renderCheckoutStep ' . $e->getMessage());
        }
    }

    // CART

    /**
     * Render js For AddToCart Event
     *
     * @param type $product_data product data (format:  AFSA_FORMAT_PRODUCT)
     *
     * @return string js code
     */
    public function renderAddToCart($product_data)
    {
        try {
            if (!$this->advanced_mode) {
                return null;
            }

            return $this->assimilate(array($this->aaECAddProduct($product_data), $this->aaECAction('add')));
        } catch (Exception $e) {
            AFSATools::log('renderAddToCart ' . $e->getMessage());
        }
    }

    /**
     * Render js For RemoveFromCart Event
     *
     * @param type $product_data product data (format:  AFSA_FORMAT_PRODUCT)
     *
     * @return string js code
     */
    public function renderRemoveFromCart($product_data)
    {
        try {
            if (!$this->advanced_mode) {
                return null;
            }

            return $this->assimilate(array($this->aaECAddProduct($product_data), $this->aaECAction('remove')));
        } catch (Exception $e) {
            AFSATools::log('renderRemoveFromCart ' . $e->getMessage());
        }
    }

    // PRODUCT

    /**
     * Render Product Impressions
     *
     *
     * @param array $products raw product data
     * @param string $src name of the product list (search result, hom, etc.)
     *
     * @return string rendered js code
     */
    public function renderProductsImpression($products, $src = null)
    {
        try {
            if (!$this->advanced_mode) {
                return null;
            }

            $aa = array();

            $infos = new AFSAProductInfos();
            $p_data = $infos->parseProducts(
                $products,
                array(
                    'list' => $src ? $src : AFSAConfig::getLastProductList(),
                ),
                AFSA_FORMAT_IMPRESSION
            );

            foreach ($p_data as $data) {
                $aa[] = $this->aaECAddImpression($data);
            }

            print_r($aa);

            return $this->assimilate($aa);
        } catch (Exception $e) {
            AFSATools::log('renderProductsImpression ' . $e->getMessage());
        }
    }

    public function renderProductDetailView($product)
    {
        try {
            if (!$this->advanced_mode) {
                return null;
            }

            $aa = array();
            $infos = new AFSAProductInfos();
            $p_data = $infos->parseProduct($product, null, AFSA_FORMAT_PRODUCT);
            unset($p_data['url']);

            $aa[] = $this->aaECAddProduct($p_data);

            $aa[] = $this->aaECAction('detail', array('list' => AFSAConfig::getLastProductList()));

            return $this->assimilate($aa); //code...
        } catch (Exception $e) {
            AFSATools::log('renderProductDetailView ' . $e->getMessage());
        }
    }

    /**
     * Render Product Click tracking code
     * (call AFSA.trackProductClick from AFSA.js)
     *
     *
     * @param array $products raw product data
     * @param string $src name of the product list (search result, hom, etc.)
     *
     * @return string rendered js code
     */
    public function renderProductsClick($products, $src)
    {
        try {
            if (!$this->advanced_mode) {
                return null;
            }

            $aa = array();

            //need to make sure that products
            //contains url for product detail ( as $p['link'])

            $infos = new AFSAProductInfos();
            $p_data = $infos->parseProducts(
                $products,
                array(
                    'list' => $src,
                ),
                AFSA_FORMAT_PRODUCT,
                array(
                    'add_url' => 1,
                )
            );

            foreach ($p_data as $data) {
                $aa[] = 'AFSA.tracker.listenProductClick(' . json_encode($data) . ');';
            }

            return $this->assimilate($aa, 'AFSA');
        } catch (Exception $e) {
            AFSATools::log('renderProductsClick ' . $e->getMessage());
        }
    }

    /**
     * @param array $products raw product data
     * @param string $src name of the product list (search result, hom, etc.)
     *
     * @return string rendered js code
     */
    public function renderProductClickByHttpReferral($products, $src = 'detail')
    {
        try {
            if (!$this->advanced_mode) {
                return null;
            }

            $infos = new AFSAProductInfos();
            $p_data = $infos->parseProducts(
                $products,
                array(
                    'list' => $src,
                ),
                AFSA_FORMAT_PRODUCT
            );
            $aa = array();
            foreach ($p_data as $data) {
                $aa[] = 'AFSA.tracker.sendProductClickByHttpReferral(' . json_encode($data) . ');';
            }

            return $this->assimilate($aa, 'AFSA');
        } catch (Exception $e) {
            AFSATools::log('renderProductClickByHttpReferral ' . $e->getMessage());
        }
    }

    // DEBUG

    private function getLogFilename()
    {
        return _PS_MODULE_DIR_ . '/afsanalytics/logs/'
            . (AFSAConfig::isAjax() ? 'ajax/' : '')
            . time() . '.' . AFSAConfig::getPageName() . '.txt';
    }

    private function saveLog()
    {
        if (!AFSAConfig::isLogEnabled() || empty($this->_log)) {
            return false;
        }

        file_put_contents($this->getLogFilename(), str_replace("\n", PHP_EOL, $this->_log));
        $this->_log = null;

        return true;
    }

    public function log($data, $title = '')
    {
        $title .= "\n[" . Tools::getValue('controller') . '/' . Tools::getValue('action') . ']';

        if (AFSAConfig::isAjax()) {
            $title .= ' [ CALLED VIA AJAX ] ';
        }

        $str = '';

        try {
            $str = json_encode(AFSATools::getAllHeaders(), JSON_PRETTY_PRINT) . "\n\n";
        } catch (Exception $ex) {
        }

        $str .= is_array($data) ?
            json_encode($data, JSON_PRETTY_PRINT) :
            $data;

        $this->_log .= empty($title) ?
            "\n$str\n" :
            "\n" . Tools::strtoupper($title) . "\n----\n$str\n";

        return $data;
    }
}
