<?php

abstract class AFSAConfigForm
{
    protected $module;
    protected $fields;
    protected $form_id = 'main';
    protected $l_name;

    public function __construct($module)
    {
        $this->module = $module;
        $this->l_name = Tools::strtolower($this->form_id);
    }

    public function l($str)
    {
        // Translation section (key|source) field = file basename
        // so need to make sure l_name is correctly set
        return Translate::getModuleTranslation($this->module, $str, $this->l_name);
    }

    public function uninstall()
    {
        foreach ($this->fields as $k) {
            Configuration::deleteByName(Tools::strtoupper($k));
        }
    }

    public function onSubmit($msg = null)
    {
        $module = $this->module;

        if (!Tools::isSubmit($this->getSubmitName())) {
            return null;
        }

        foreach ($this->fields as $k) {
            Configuration::updateValue(Tools::strtoupper($k), Tools::getValue($k));
        }

        return $module->displayConfirmation($module->l(empty($msg) ? 'Settings updated' : $msg));
    }

    /*
     *
     *
     *
     */

    protected function getSubmitName()
    {
        return 'submitConfig' . $this->form_id . $this->module->name;
    }

    abstract public function getFieldsData();

    protected function setFieldsValues($helper)
    {
        foreach ($this->fields as $k) {
            $helper->fields_value[$k] = Configuration::get(Tools::strtoupper($k));
        }
    }

    // override me
    public function renderInfos()
    {
    }

    public function render()
    {
        $module = $this->module;

        // Get default language
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $module;
        $helper->name_controller = $module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $module->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $module->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = $this->getSubmitName();
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $module->name . '&save' . $module->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ),
        );

        $this->setFieldsValues($helper);

        return
                $this->renderInfos()
                . $helper->generateForm($this->getFieldsData());
    }
}
