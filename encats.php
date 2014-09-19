<?php

require_once rtrim(dirname(__FILE__), '/').'/../../abstract.php';

class Mage_Shell_Create extends Orb_Shell_Abstract
{

// public $_parentCatBuffer = array();
// public $_logFile   = '/var/www/magento/var/log/categoriesLog.log';
public $_catsArray = array();
public $_productCount = 0;
public $categoriesCollectionIds = array();
public $isChild = false;
public $topParent = null;
    /**
     * Run script
     *
     */
public function run()
{

set_time_limit(0);
ini_set('memory_limit','6120M');

// file_put_contents($this->_logFile,"");

Mage::app()->setCurrentStore(Mage::getModel('core/store')
->load(Mage_Core_Model_App::ADMIN_STORE_ID));

if ($this->getArg('r')) {
echo "[-";
$this->categoriesCollectionIds = $this->getCategories();
// $this->categoriesCollectionIds[]=3277; 
$this->processCat($this->categoriesCollectionIds, 1); 
echo "-]\n";

}

}

public function processCat($categories,$isNotMain = null) {
 foreach($categories as $category) {
 $count = 0;
if (is_object($category)) {
 $this->isChild = false;
}
else {
 $this->isChild = true;
}
 if ($this->isChild === false) {
   $catObj = Mage::getModel('catalog/category')->load($category->getId());
   $cat = Mage::getModel('catalog/category')->load($category->getId())
   ->getProductCollection()
   ->addAttributeToSelect('*')
   ->addAttributeToFilter(
      'status',
      array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
  );
  }
 else {
  $catObj = Mage::getModel('catalog/category')->load($category);
  $cat = Mage::getModel('catalog/category')->load($category)
   ->getProductCollection()
   ->addAttributeToSelect('*')
   ->addAttributeToFilter( 
      'status',
      array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
  );
  }
  
  // echo "[+] cat: ".$catObj->getName()."\n";
  $count += count($cat);
  if ($catObj->hasChildren()) {
     // echo "[+] category ".$catObj->getName()." has children ..\n";
     $children = $this->retrieveAllChildren($catObj->getId()); 
     //foreach ($children as $child) {
     //    echo "[===] child: ".$child."\n";
     //}
     $this->processCat($children,null);
  }
  else {
     // echo "[+] category ".$catObj->getName()." has no children.\n";
  }
  // echo "[+] found ".$count." enabled products under ".$catObj->getName()."\n";
  if ($count > 0) {
   // echo "[+] enabling category ".$catObj->getName()." ...\n";
    $catObj->setIsActive(1);
    $catObj->setIsAnchor(1);
    $catObj->save();
    while ($catObj->getLevel() != 2) {
        $catObj = $catObj->getParentCategory();
        if (!$catObj) break;
        // echo "[+] enabing next parent cat ".$catObj->getName()." ..\n";
        $catObj->setIsActive(1);
        $catObj->setIsAnchor(1);
        $catObj->save();
    }
    
  }
  else {
    if ($category != 2) { // avoid disabling top cat
    echo "-";
   // echo "[+] disabling category ".$catObj->getName()." ..\n";
    $catObj->setIsActive(0);
    $catObj->setIsAnchor(1);
    $catObj->save();
    }
  }
  } // foreach

  if (is_null($isNotMain)) {
      return $count;
  }

}

public function retrieveAllChildren($id = null, $childs = null) {
$category = Mage::getModel('catalog/category')->load($id);
return $category->getResource()->getChildren($category, true);
}

public function getCategories(){
$tree = Mage::getModel('catalog/category')
->getCollection()
->addAttributeToSelect('*');

$tree->load();

$ids = $tree->getAllIds();
$arr = array();

if ($ids){
foreach ($ids as $id){
if ($id != 1)
array_push($arr, $id);
}
}

return $arr;

}


} // Ends class

$obj = new Mage_Shell_Create;
$obj->run();
