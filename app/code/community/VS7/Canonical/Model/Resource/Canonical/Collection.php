<?php

class VS7_Canonical_Model_Resource_Canonical_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('vs7_canonical/canonical');
    }
}