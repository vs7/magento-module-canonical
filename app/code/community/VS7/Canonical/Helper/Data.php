<?php

class VS7_Canonical_Helper_Data extends Mage_Core_Helper_Abstract
{
    private function _getForceHttps()
    {
        return Mage::getStoreConfig('vs7_canonical/canonical_settings/https');
    }

    private function _getParamConfig()
    {
        $paramsString = Mage::getStoreConfig('vs7_canonical/canonical_settings/params');
        $params = explode(',', $paramsString);
        return $params;
    }

    private function _getCurrentLink()
    {
        $forceHttps = $this->_getForceHttps();
        return Mage::getUrl('*/*/*', array('_use_rewrite' => true, '_forced_secure' => $forceHttps));
    }

    public function getCanonicalRel()
    {
        $link = $this->_getCurrentLink();
        $neededParams = $this->_getParamConfig();
        $params = Mage::app()->getFrontController()->getRequest()->getParams();
        $foundParams = array();
        foreach ($neededParams as $key => $neededParam) {
            if (array_key_exists($neededParam, $params)) {
                if ($neededParam == 'p' && ($params['p'] == 0 || $params['p'] == 1)) {
                    continue;
                }
                $foundParams[$neededParam] = $params[$neededParam];
            }
        }
        if (!empty($foundParams)) {
            $link .= '?' . http_build_query($foundParams);
        }
        return $link;
    }

    public function getCanonicalsFromHead(Mage_Page_Block_Html_Head $headBlock)
    {
        $items = $headBlock->getItems();
        $canonicals = array();
        foreach ($items as $itemName => $itemParams) {
            if (substr($itemName, 0, 8) == 'link_rel') {
                if ($itemParams['params'] == 'rel="canonical"') {
                    $canonicals[$itemName] = $itemParams;
                }
            }
        }

        return $canonicals;
    }

    public function cropFirstPart($string, array $substringList, $isConsecutive = false)
    {
        $resultString = $string;

        foreach ($substringList as $substring) {
            $pos = $this->mbStrPosSafety($string, $substring);

            if ($pos === 0) {
                $strlen = $this->mbStrLenSafety($substring);

                $resultString = $this->mbSubStrSafety($string, $strlen);
                $string = $resultString;

                if (!$isConsecutive) {
                    break;
                }
            }
        }

        return $resultString;
    }

    public function mbStrLenSafety($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str);
        }

        return strlen($str);
    }

    public function mbSubStrSafety($str, $start, $length = null)
    {
        if (function_exists('mb_substr')) {
            return ($length !== null) ? mb_substr($str, $start, $length) : mb_substr($str, $start);
        }

        return ($length !== null) ? substr($str, $start, $length) : substr($str, $start);
    }

    public function mbStrPosSafety($haystack, $needle, $offset = 0)
    {
        if (function_exists('mb_strpos')) {
            return mb_strpos($haystack, $needle, $offset);
        }

        return strpos($haystack, $needle, $offset);
    }

    public function mbStrrPosSafety($haystack, $needle, $offset = 0)
    {
        if (function_exists('mb_strrpos')) {
            return mb_strrpos($haystack, $needle, $offset);
        }

        return strrpos($haystack, $needle, $offset);
    }
}