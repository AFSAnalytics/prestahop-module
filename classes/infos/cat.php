<?php

class AFSACategoryInfos
{
    private $data;

    public function __construct()
    {
        $this->data = array();
    }

    public function getFullName($c, $p = array())
    {
        if (empty($c)) {
            return null;
        }
        $ret = implode('/', $this->getParentNames($c, !empty($p['trim'])));

        return empty($p['escape']) ? $ret : AFSATools::normalizeString($ret);
    }

    /**
     * Get all parent name for specified Category
     *
     * @param Category $c
     * @param bool $trim_first remove root parent cat
     *
     * @return array names
     */
    public function getParentNames($c, $trim_first = false)
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
}
