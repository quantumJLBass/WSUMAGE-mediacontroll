<?php
class Wsu_Mediacontroll_Block_Imgclean extends Mage_Adminhtml_Block_Widget_Grid_Container {
	public function __construct() {
		$this->_blockGroup = 'mediacontroll';
		$this->_controller = 'imgclean';
		$this->_headerText = Mage::helper('wsu_mediacontroll')->__('Items Manager. These files are not in database.');
		$this->_addButtonLabel = Mage::helper('wsu_mediacontroll')->__('Refresh');
		parent::__construct();
	}
/*	
	public function _prepareLayout() {
		return parent::_prepareLayout();
    }
    */
     public function getImgclean() { 
        if (!$this->hasData('imgclean')) {
            $this->setData('imgclean', Mage::registry('imgclean'));
        }
        return $this->getData('imgclean');
    }
}

