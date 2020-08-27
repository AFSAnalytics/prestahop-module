<?php

include_once 'cat.php';

class AFSAPageInfos
{
    private $data;

    public function __construct()
    {
        $this->data = array();
        $this->retrieve();
    }

    // return page name
    public function getName($default = null)
    {
        return empty($this->data['name']) ? $default : $this->data['name'];
    }

    private function getContext()
    {
        return AFSAConfig::getContext();
    }

    private function retrieve()
    {
        if (empty($this->getContext())) {
            return;
        }

        $this->data['name'] = $this->setName();
    }

    private function setName()
    {
        switch (AFSAConfig:: getPageTitleDetectMethod()) {
            case 'auto':
                return $this->retrieveSmart();
            case 'mini':
                return $this->retrieveMini();
        }

        return $this->retrieveTitle();
    }

    private function setProductNameFrom(&$n, $mixed)
    {
        if (is_array($mixed) && count($mixed)) {
            foreach ($mixed as $str) {
                if (!empty($str)) {
                    $n = $str;
                }
            }
        } elseif (is_string($mixed)) {
            $n = $mixed;
        }
    }

    private function retrieveSmart()
    {
        switch (Tools::getValue('controller')) {
            case 'product':
                $product = new Product(Tools::getValue('id_product'));
                if (!empty($product)) {
                    $n = '';

                    //$this->setProductNameFrom($n, $product->link_rewrite);
                    //$this->setProductNameFrom($n, $product->meta_title);
                    $this->setProductNameFrom($n, $product->name);

                    return $this->retrieveMini() . ' - ' . $product->reference . ' - '
                            . Tools::substr($n, 0, 64);
                }

                break;

            case 'category':
                if (($id = Tools::getValue('id_category'))) {
                    $cat = new Category($id);
                    if (!empty($cat)) {
                        $infos = new AFSACategoryInfos();

                        return $this->retrieveMini() . ' - ' . $id . ' - ' . $infos->getFullName($cat, array('trim' => 1));
                    }
                }

                break;

            default:
                //AFSATools::log(Tools::getValue('controller'));
                break;
        }

        return $this->retrieveMini();
    }

    public function getCategories()
    {
        $ctrl = Tools::getValue('controller');
        $id = null;

        if ($ctrl == 'product') {
            $product = new Product(Tools::getValue('id_product'));
            $id = $product->id_category_default;
        } else {
            $id = Tools::getValue('id_category');
        }

        if (!empty($id)) {
            $cat = new Category($id);
            if (!empty($cat)) {
                $infos = new AFSACategoryInfos();

                return $infos->getParentNames($cat, true);
            }
        }

        return null;
    }

    private function retrieveMini()
    {
        $context = $this->getContext();

        if (!empty($context->controller->php_self)) {
            return $context->controller->php_self;
        }

        $ctrl = Tools::getValue('controller');

        $page = [
            'AdminAFSADashboard' => 'AFS Analytics Dashboard',
            'AdminAFSAAccountManager' => 'AFS Analytics Welcome',
        ];

        if (!empty($page[$ctrl])) {
            return $page[$ctrl];
        }

        return $this->retrieveTitle();
    }

    private function retrieveTitle()
    {
        return 'title'; // will inject title in JS
    }
}
