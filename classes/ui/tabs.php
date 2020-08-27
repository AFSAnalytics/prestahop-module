<?php

class AFSAUITabs
{
    public static function install($module)
    {
        $sql = 'SELECT `id_tab` FROM `' . _DB_PREFIX_ . "tab` WHERE `class_name` LIKE 'SELL'";
        $t_id = Db::getInstance()->getValue($sql);

        $t = static::addTab($module, 'AFS Analytics', 'AFSAMenu', $t_id);

        $t_arr = array(
            array($module->l('Dashboard', 'tabs'), 'AdminAFSADashboard'),
            array($module->l('Plugin settings', 'tabs'), 'AdminRedirectCMSConfig'),
            array($module->l('AFSAnalytics.com', 'tabs'), 'AdminRedirectAFSA'),
            array($module->l('Contact us', 'tabs'), 'AdminRedirectContact'),
        );

        foreach ($t_arr as $r) {
            static::addTab($module, $r[0], $r[1], $t->id);
        }

        // Untabbed Controller
        foreach (['AdminAFSAAjax', 'AdminAFSADashboardDemo', 'AdminAFSAAccountManager'] as $ctrl) {
            static::addTab($module, $ctrl, $ctrl, -1);
        }
    }

    public static function addTab($module, $label, $class, $parent_id)
    {
        $t = new Tab();

        foreach (Language::getLanguages(true) as $lang) {
            $t->name[$lang['id_lang']] = $label;
        }
        $t->class_name = $class;
        $t->id_parent = $parent_id;
        $t->module = $module->name;
        $t->add();

        return $t;
    }

    public static function uninstall($module)
    {
        // Uninstall Tabs
        $moduleTabs = Tab::getCollectionFromModule($module->name);
        if (!empty($moduleTabs)) {
            foreach ($moduleTabs as $moduleTab) {
                $moduleTab->delete();
            }
        }
    }
}
