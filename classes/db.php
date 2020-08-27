<?php

class AFSADB
{
    private $order_table;
    private $cart_table;
    public static $instance;

    public static function get()
    {
        if (empty(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function __construct()
    {
        $this->order_table = _DB_PREFIX_ . 'afsa_processed_order';
        $this->cart_table = _DB_PREFIX_ . 'afsa_cart';
        $this->db = Db::getInstance();
    }

    // INSTALL / UNINSTALL

    public function createTables()
    {
        return $this->batchExecuteSQL(array(
                    'CREATE TABLE IF NOT EXISTS `' . $this->order_table . '` (
				`id_order` int(11) NOT NULL,
				`id_shop` int(11) NOT NULL,
				`date_add` datetime DEFAULT NULL,
				PRIMARY KEY (`id_order`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1',
                    'CREATE TABLE IF NOT EXISTS `' . $this->cart_table . '` (
				`id_cart` int(11) NOT NULL,
				`id_shop` int(11) NOT NULL,
				`data` TEXT DEFAULT NULL,
				PRIMARY KEY (`id_cart`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8',
        ));
    }

    public function dropTables()
    {
        return $this->batchExecuteSQL(array(
                    'DROP TABLE IF EXISTS `' . $this->order_table . '`',
                    'DROP TABLE IF EXISTS `' . $this->cart_table . '`',
        ));
    }

    private function batchExecuteSQL(array $arr)
    {
        if (empty($arr)) {
            return false;
        }

        $ret = true;

        foreach ($arr as $sql) {
            if (!(bool) $this->db->execute($sql)) {
                $ret = false;
            }
        }

        return $ret;
    }

    // CARTS PRODUCTS

    /**
     * Return all product for specified cart / shop
     *
     * @param int $id cart id
     * @param int $shop_id shop id
     *
     * @return array products data
     */
    public function getCart($id, $shop_id)
    {
        $ret = $this->db->getValue('SELECT data FROM `'
                . $this->cart_table
                . '` WHERE id_cart = \'' . $id
                . '\' AND id_shop = \'' . $shop_id . '\'')
        ;

        return $ret === false ?
                array() :
                json_decode($ret, true);
    }

    /**
     * Save products data from specified cart
     *
     * @param int $id cart id
     * @param int $shop_id shop id
     * @param array $data product data
     *
     * @return bool
     */
    public function saveCart($id, $shop_id, $data)
    {
        if (!empty($data) && $id && $shop_id) {
            $json_str = pSQL(json_encode($data));

            $q_str = 'INSERT INTO `'
                    . $this->cart_table . '` (id_cart, id_shop, data) VALUES(\''
                    . $id . '\',\''
                    . $shop_id . '\',\''
                    . $json_str
                    . '\') ON DUPLICATE KEY UPDATE data=\'' . $json_str . '\' ;'
            ;

            AFSATools::log($q_str);

            return $this->db->Execute($q_str);
        }
    }

    /**
     * Delete all products data for specified cart
     *
     * @param int $id cart id
     * @param int $shop_id shop id
     */
    public function deleteCart($id, $shop_id)
    {
        $q_str = 'DELETE FROM `'
                . $this->cart_table
                . '` WHERE id_cart=\'' . $id
                . '\' AND id_shop=\'' . $shop_id . '\'';

        AFSATools::log($q_str);

        $this->db->execute($q_str);
    }

    /**
     * Add extra products data to an already existing cart
     * or create a new one
     *
     * @param int $id cart id
     * @param int $shop_id shop id
     * @data new product data
     */
    public function addCartData($id, $shop_id, $data)
    {
        $ret = $this->getCart($id, $shop_id);
        if (empty($ret)) {
            $data_new = array($data);
        } else {
            $data_new = $ret;
            $data_new[] = $data;
        }

        $this->saveCart($id, $shop_id, $data_new);
    }

    // ORDERS

    public function getOrderID($id_order, $id_shop)
    {
        return $this->db->getValue('SELECT id_order FROM ' . $this->order_table . ' WHERE id_order="' . $id_order . '"' . ' AND  id_shop="' . $id_shop . '"');
    }

    public function saveOrder($id_order, $id_shop)
    {
        $this->db->Execute('INSERT IGNORE INTO ' . $this->order_table . ' (id_order, id_shop, date_add)' . ' VALUES (' . (int) $id_order . ', ' . (int) $id_shop . ', ' . ' NOW())');
    }

    public function wasOrderProcessed($id_order, $id_shop)
    {
        return $this->getOrderID($id_order, $id_shop) !== false;
    }

    public function saveOrderIfNotExists($id_order, $id_shop)
    {
        if (!$this->wasOrderProcessed($id_order, $id_shop)) {
            $this->saveOrder($id_order, $id_shop);
        }
    }

    public function getOrdersForShop($id_shop)
    {
        return $this->db->ExecuteS(
                        'SELECT * FROM `' . $this->order_table . '`'
                        . ' WHERE  id_shop = \'' . $id_shop . '\''
                        . ' AND DATE_ADD(date_add, INTERVAL 30 minute) < NOW()'
        );
    }

    public function cleanProcessedOrderTable()
    {
        $this->db->Execute(
                'DELETE FROM ' . $this->order_table . ' WHERE  DATE_ADD(date_add, INTERVAL 7 day) < NOW()'
        );
    }

    // STATS && INFOS ( CONTEXT )

    private function inStatementFromArray($arr)
    {
        $ret = '';

        foreach ($arr as $v) {
            $ret .= is_string($v) ?
                    '"' . trim($v) . '"' :
                    $v;
            $ret .= ',';
        }

        return '(' . chop($ret, ',') . ')';
    }

    public function getCustomerInfos(array $ids)
    {
        return count($ids) ?
                $this->db->ExecuteS(
                        'SELECT T1.id_customer as id, T1.lastname, T1.email, T1.birthday, T2.phone, T2.phone_mobile'
                        . ' FROM `' . _DB_PREFIX_ . 'customer` AS T1'
                        . ' LEFT JOIN `' . _DB_PREFIX_ . 'address` AS T2 ON T1.id_customer=T2.id_customer '
                        . ' WHERE  T1.id_customer in ' . $this->inStatementFromArray($ids)
                ) :
                array();
    }

    public function getProductsByRef(array $refs)
    {
        AFSATools::log('SELECT *'
                . ' FROM `' . _DB_PREFIX_ . 'product` '
                . ' WHERE reference in ' . $this->inStatementFromArray($refs));

        return count($refs) ?
                $this->db->ExecuteS(
                        'SELECT *'
                        . ' FROM `' . _DB_PREFIX_ . 'product` '
                        . ' WHERE reference in ' . $this->inStatementFromArray($refs)
                ) :
                array();
    }
}
