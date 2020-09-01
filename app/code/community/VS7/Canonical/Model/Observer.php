<?php

class VS7_Canonical_Model_Observer
{
    public function addCanonicalRel($observer)
    {
        $action = $observer->getAction();
        $layout = $observer->getLayout();

        if (!$layout || !$action) {
            return;
        }

        $request = Mage::app()->getRequest();
        if ($request && $request->isXmlHttpRequest()) {
            return;
        }

        $headBlock = $layout->getBlock('head');
        if (empty($headBlock) || !is_a($headBlock, 'Mage_Page_Block_Html_Head')) return;

        $currentCanonicals = Mage::helper('vs7_canonical')->getCanonicalsFromHead($headBlock);

        $request = Mage::app()->getFrontController()->getRequest();
        $requestUrlRaw = $request->getOriginalPathInfo();
        $requestCases = $this->_prepareRequestCases($requestUrlRaw);
        $rewrite = Mage::getModel('core/url_rewrite')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->loadByRequestPath($requestCases);
        if ($rewrite->getId() !== null) {
            $model = Mage::getModel('vs7_canonical/canonical')->load($rewrite->getId(), 'url_rewrite_id');
            $canonicalUrl = $model->getCanonical();
            if (empty($canonicalUrl)) {
                if (count($currentCanonicals) == 0) {
                    $canonicalUrl = Mage::helper('vs7_canonical')->getCanonicalRel();

                    if (!empty($canonicalUrl)) {
                        $headBlock->addItem('link_rel', $canonicalUrl, 'rel="canonical"');
                    }
                }
            } else { //force
                foreach($currentCanonicals as $currentCanonical) {
                    $headBlock->removeItem($currentCanonical['type'], $currentCanonical['name']);
                }
                $headBlock->addItem('link_rel', $canonicalUrl, 'rel="canonical"');
            }
        } else {
            foreach($currentCanonicals as $currentCanonical) {
                $headBlock->removeItem($currentCanonical['type'], $currentCanonical['name']);
            }
            if (substr($currentCanonical['name'], -1) != '/') $currentCanonical['name'] .= '/';
            $headBlock->addItem('link_rel', $currentCanonical['name'], 'rel="canonical"');
        }
    }

    public function addField($observer)
    {
        $block = $observer->getBlock();
        if (empty($block) || !is_a($block, 'Mage_Adminhtml_Block_Urlrewrite_Edit_Form')) return;
        $form = $block->getForm();
        if (empty($form)) return;
        $fieldset = $form->getElement('base_fieldset');
        if (empty($fieldset)) return;
        $currentUrlrewrite = Mage::registry('current_urlrewrite');
        if (empty($currentUrlrewrite)) return;
        $model = Mage::getModel('vs7_canonical/canonical')->load($currentUrlrewrite->getId(), 'url_rewrite_id');
        $fieldset->addField('vs7_canonical_url', 'text', array(
            'name' => 'vs7_canonical_url',
            'label' => Mage::helper('vs7_canonical')->__('Force Canonical URL'),
            'note' => Mage::helper('vs7_canonical')->__('eg.: https://example.com/mycanonical/'),
        ));
        if ($model->getId() !== null) {
            $form->addValues(array('vs7_canonical_url' => $model->getData('canonical')));
        }
    }

    public function saveCanonical($observer)
    {
        $urlrewrite = $observer->getObject();
        if (empty($urlrewrite) || !is_a($urlrewrite, 'Mage_Core_Model_Url_Rewrite')) return;
        $data = Mage::app()->getRequest()->getPost();
        if (!isset($data['vs7_canonical_url']) || empty($data['vs7_canonical_url'])) return;
        Mage::getModel('vs7_canonical/canonical')
            ->setData('url_rewrite_id', $urlrewrite->getId())
            ->setData('canonical', $data['vs7_canonical_url'])
            ->save();
    }

    protected function _prepareRequestCases($requestUrlRaw)
    {
        $requestCases = array();
        $requestPath = $this->_prepareRequestPath($requestUrlRaw);

        $origSlash = (substr($requestUrlRaw, -1) == '/') ? '/' : '';
        $altSlash = $origSlash ? '' : '/';

        $requestCases[] = $requestPath . $origSlash;
        $requestCases[] = $requestPath . $altSlash;
        $storeId = Mage::app()->getStore()->getId();
        $requestCases[] = Mage::app()->getStore($storeId)
                ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . $requestPath . $origSlash;
        $requestCases[] = Mage::app()->getStore($storeId)
                ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . $requestPath . $altSlash;
        if (Mage::getStoreConfigFlag('web/url/use_store')) {
            $requestCases[] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK)
                . Mage::app()->getStore()->getCode() . $requestUrlRaw . $origSlash;
            $requestCases[] = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK)
                . Mage::app()->getStore()->getCode() . $requestUrlRaw . $altSlash;
        }
        return $requestCases;
    }

    protected function _prepareRequestPath($requestUrlRaw)
    {
        $stringHelper = Mage::helper('vs7_canonical');
        $pathInfo = $stringHelper->cropFirstPart(
            $requestUrlRaw,
            array('/', 'index.php/', Mage::app()->getStore()->getCode(), '/'),
            true
        );
        return trim($pathInfo, '/');
    }
}