<?php

/**
 * Build different URLs to Dashboard
 * and other pages on AFSAnalytics.com
 *
 * Insert Access Key if set
 */
include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/tools.php';

class AFSARouteManager
{
    public static $host = 'https://www.afsanalytics.com';

    /**
     * Return url to a dashboard page
     * Insert Access Key if set
     *
     * @param string|null $url page
     *
     * @return string url absolute
     */
    public static function getDashboardURL($u = '', $extra = array())
    {
        /*        $args = array(
                    'utm_source' => Tools::strtolower(AFSAConfig::CMS() . '_plugin'),
                    'utm_medium' => 'backoffice',
                );
        */

        $args = array();

        foreach ($extra as $k => $v) {
            $args[$k] = $v;
        }

        $paa = AFSAConfig::getPAARC();
        if ($paa) {
            $args['paa_rc'] = $paa;
        }

        $access_key = AFSAConfig::getAccessKey();
        if ($access_key) {
            $args['accesskey'] = $access_key;
        } elseif (AFSAConfig::getAccountID()) {
            $args['usr'] = AFSAConfig::getAccountID();
        }

        return AFSATools::buildURL(static::$host . '/' . $u, $args);
    }

    public static function getDashboardPage($w = null)
    {
        $arr = array(
            'rightnow' => 'rightnow.php',
            'lastvisitors' => 'lastvisitors.php',
            'heatmaps' => 'heatmaps.php',
            'keywordchecker' => 'keywords_monitoring.php',
            'pdf' => 'edpdf.php',
        );

        if (!empty($arr[$w])) {
            return static::getDashboardURL($arr[$w]);
        }

        if (empty($w)) {
            $w = 'dashboard.php';
        }

        return static::getDashboardURL($w);
    }

    // Various URLS
    public static function home()
    {
        return static::getDashboardURL('');
    }

    public static function keys()
    {
        return static::getDashboardURL('accesskeys.php');
    }

    public static function profile()
    {
        return static::getDashboardURL('edprofile.php');
    }

    public static function options()
    {
        return static::getDashboardURL('edaccounts.php');
    }

    public static function password()
    {
        return static::getDashboardURL('', array('lostpass' => 1));
    }

    public static function upgrade()
    {
        return static::getDashboardURL('pricing.php');
    }

    public static function onlineHelp()
    {
        return static::getDashboardURL('articles/web-statistics-reports/');
    }

    public static function contact()
    {
        return static::getDashboardURL('contact.html');
    }
}
