<?php
class Wsu_Mediacontroll_Helper_Data extends Mage_Core_Helper_Abstract {
	/**
     * @param $value
     * @return Varien_Image_Adapter_Gd|Varien_Image_Adapter_Gd2|Varien_Image_Adapter_Imagemagic|Varien_Image_Adapter_ImagemagicExternal
     */
    public function getImageAdapter($value){
        return Varien_Image_Adapter::factory($value);
    }

	protected $result = array();
	protected $_mainTable;
	public $valdir = array();
	protected $prodBasedImgCollection = null;

	public function halt_indexing(){
		$this->processes = Mage::getSingleton('index/indexer')->getProcessesCollection(); 
		$this->processes->walk('setMode', array(Mage_Index_Model_Process::MODE_MANUAL)); 
		$this->processes->walk('save'); 
	}
	public function run_indexer(){
		exec("php shell/indexer.php -reindexall");
	}	
	public function restore_indexing(){
		$this->run_indexer();
		$this->processes->walk('setMode', array(Mage_Index_Model_Process::MODE_REAL_TIME)); 
		$this->processes->walk('save'); 
	}




	public function listDirectories($path){
		if(is_dir($path)){
			if ($dir = opendir($path)) {
				while (($entry = readdir($dir)) !== false) {
					if(preg_match('/^\./',$entry) != 1){
						if (is_dir($path . DS . $entry) && !in_array($entry,array('cache','watermark')) ){
							$this->listDirectories($path . DS . $entry);
						} elseif(!in_array($entry,array('cache','watermark')) && (strpos($entry,'.') != 0)) {
							$this->result[] = substr($path . DS . $entry, 21);
						}
					}
				}
				closedir($dir);
			}
		}
		return $this->result;
	}
	
	
	public function compareList() {
		$model = Mage::getModel('wsu_mediacontroll/imgclean');	
		$val	= $model->getCollection()->getImages();
		$prodImg = 'media' . DS . 'catalog' . DS . 'product';
		$imgList = $this->listDirectories($prodImg);
		foreach ($imgList as $item){
			try{
				$item	= strtr($item,'\\','/');
				if(!in_array($item, $val)){
					$valdir[]['filename'] = $item;
					$model->setData(array('filename'=>$item))->setId(null);
					$model->save();
				}
			} catch(Zend_Db_Exception $e){
			} catch(Exception $e){
				Mage::log($e->getMessage());
			}
		}
	}
	
	public function indexMissassignment() {
		$this->get_ProductImages('missassignments');
		try{
			
		} catch(Zend_Db_Exception $e){
		} catch(Exception $e){
			Mage::log($e->getMessage());
		}
	}

	public function indexUnsorted() {
		$this->get_ProductImages('unsorted');
		try{
			
		} catch(Zend_Db_Exception $e){
		} catch(Exception $e){
			Mage::log($e->getMessage());
		}
	}

	public function indexImgless() {
		$this->get_ProductImages('imgless');
		try{
			
		} catch(Zend_Db_Exception $e){
		} catch(Exception $e){
			Mage::log($e->getMessage());
		}
	}
	/**
	 * Creating new varien collection  
	 * for given array or object 
	 * 
	 * @param array|object $items   Any array or object 
	 * @return Varien_Data_Collection $collection 
	 */ 
	
	public function getVarienDataCollection($items) { 
		$collection = new Varien_Data_Collection();              
		foreach ($items as $item) { 
			$varienObject = new Varien_Object(); 
			$varienObject->setData($item); 
			$collection->addItem($varienObject); 
		} 
		return $collection; 
	} 

	
	
	
	
	
	
	
	
	
    /**
     * Get a collection of products that have images that are unassigned
     *	//@return array
     */
	public function get_ProductImages($type=""){
		
		/*$productBasedImgCollection = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
			//->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left')
			//->addAttributeToFilter('category_id', array('in' => $cats))
			//->addAttributeToFilter('small_image', array('neq' => ''))
			//->addAttributeToFilter('small_image', array('neq' => 'no_selection'))
			//->addAttributeToSelect('image')
			//->addAttributeToSelect('media_gallery'); 
		//$productBasedImgCollection->getSelect()->order(new Zend_Db_Expr('RAND()'));
		$productBasedImgCollection->getSelect()->order('updated_at','DESC');
		//$productBasedImgCollection->getSelect()->limit($totalProducts,$page);*/

		
		

		$model = Mage::getModel('wsu_mediacontroll/'.$type);	
		$collection = Mage::getModel('wsu_mediacontroll/'.$type)->getCollection();
		$val=array();
		$i=0;
		$tracked_products=array();
		foreach	($collection->getData() as $itemObj){
			$item=(array)$itemObj;
			$prod_id= $item['prod_id'];
			if(isset($item['imgprofile'])){
				$tracked_products[] = $prod_id;
			}
		}
		$prodcollection = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
		if(!empty($tracked_products))$prodcollection->addAttributeToFilter('entity_id', array('nin' => $tracked_products));
		$prodcollection->getSelect()->order('updated_at','ASC');

		print( $prodcollection->getSelect() );//die();
		var_dump(count($prodcollection));print('<br/>');
		
		if($type!='orphaned'){
		
			$productImgCollection=array();
			//$prodcollection = $this->prodBasedImgCollection->getSelect();
			//var_dump('here');var_dump($collection);
			foreach($prodcollection as $product){
				$prodID=(int)$product->getId();

				$productArray = $this->checkProductImgs($prodID);				
				$missingAssigned = $productArray['productImageProfile']['missingAssigned'];
				$missingSorted = $productArray['productImageProfile']['missingSorted'];
				$_prodImgObj = $productArray['productImageProfile']['imgs'];
				//var_dump(count($_prodImgObj)); print('<br/>');
				if( 
					   $type == 'imgless' && count($_prodImgObj)==0
					|| $type == 'missassignments' && $missingAssigned && count($_prodImgObj)>0
					|| $type == 'unsorted' && $missingSorted && count($_prodImgObj)>0
				){
					$newModel = Mage::getModel('wsu_mediacontroll/'.$type);	
					$newModel->setData(array('prod_id'=>$prodID,'imgprofile'=>json_encode($productArray)))->setId(null);
					$newModel->save();
				}
			}
		}
		if($type=='orphaned'){
			$model = Mage::getModel('wsu_mediacontroll/orphaned');	
			$val	= $model->getCollection()->getImages();
			$prodImg = 'media' . DS . 'catalog' . DS . 'product';
			$imgList = $this->listDirectories($prodImg);
			foreach ($imgList as $item){
				try{
					$item	= strtr($item,'\\','/');
					if(!in_array($item, $val)){
						$valdir[]['filename'] = $item;
						$model->setData(array('filename'=>$item))->setId(null);
						$model->save();
					}
				} catch(Zend_Db_Exception $e){
				} catch(Exception $e){
					Mage::log($e->getMessage());
				}
			}
		}//die('before final end');
	}
	public function checkProductImgs($prodID){
		$sortIndex=0;
		//var_dump($prodID);
		$productArray=array();
		$_prod = Mage::getModel('catalog/product')->load($prodID);
		$_images = $_prod->getMediaGallery('images');
		
		
		$productArray['prod_id']= (int)$product->getId();
		$productArray['name']= $_prod->getName();

		$types=array();
		foreach ($_prod->getMediaAttributes() as $attribute) {
			$types[] = $attribute->getAttributeCode();
		}
		$productArray['avialible_types']=$types;
		$attrImgs=array();
		foreach ($types as $typeof){
			$imgHelper = Mage::helper('catalog/image');
			$filename = "";
			try{
				$filename = Mage::helper('catalog/image')->init($_prod, $typeof);
			}catch(Exception $e){}

			if ($filename!="") {
				$attrImgs[$typeof] = $filename."";
			}	
		}
		$productArray['types']=$attrImgs;

		$_assignCount = 0;
		$_sortedCount = 0;
		$_excluded = 0;

		$_prodImgObj = array();
		$_sortedArray=array();
		if(count($_images)){
			foreach ($_images as $_image){
				$_imgObj=array();
				$IMGID=$_image['value_id'];
				
				$_imgObj['id']=(int)$IMGID;

				$typed_as=array();
				$filenameTest = basename($_image['file'], ".jpg").'/';
				foreach ($attrImgs as $code=>$setFile){	
					if(strpos($setFile,$filenameTest)>-1){
						$typed_as[]=$code;
						$_assignCount++;
					}
				}
				
				$position=$_image['position'];
				$disabled=$_image['disabled'];
					$_imgObj['disabled']=$disabled;
					$_imgObj['position']=$position;
					$_imgObj['lable']=$_image['label'];
					$_imgObj['file']=$_image['file'];
					$_imgObj['typed_as']=$typed_as;
				if($disabled>0){
					$_excluded++;
				}
				if($position>-1){
					$_sortedArray[$IMGID]=$position;
					$_sortedCount++;
				}
				$_prodImgObj[]=$_imgObj;
			}
		}
		
		$_sortIndexes=array();
		$_sortConflict=array();
		foreach($_sortedArray as $k=>$v){
			if(isset($_sortIndexes[$v])){
				unset($_sortedArray[$k]);
				$_sortConflict[$k]=$v;
			}else{
				$_sortIndexes[$v]=$k;	
			}
		}

		$missingSort = $_sortedCount>0 
						&& ( $_excluded>0 && $_excluded != count($_images) && $_excluded != $_assignCount )
						&& ( 
								count($_sortConflict) > 0 
							||	$_sortedCount != count($_images)
							||	count($_sortedArray) != count($_images) 
							||	!(
									count($_sortedArray) == count($_images) 
									&& $_sortedCount == count($_images)
									&& count($_sortedArray) == $_sortedCount
								)
							);
		$missingAssigned=true;
		if($_assignCount>0){
			if($_assignCount==count($types))$missingAssigned=false;
		}


		$imgObj = array();
		$imgObj['missingSorted'] = $missingSort;
		$imgObj['hasSorted'] = $_sortedCount>0;
		$imgObj['hasSortIndexStart'] = isset($_sortIndexes[$sortIndex]);
		$imgObj['missingAssigned'] = $missingAssigned;
		$imgObj['hasAssigned'] = $_assignCount>0;
		$imgObj['imgs'] =$_prodImgObj;
		
		$productArray['productImageProfile'] = $imgObj;
		return $productArray;
	}
}