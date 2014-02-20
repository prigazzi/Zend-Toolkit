<?php
/**
 * Metadata Store-aware Table Abstraction. Uses MetaStore. 
 */
class Toolkit_Model_DbTable_Abstract extends Zend_Db_Table_Abstract
{
	protected $_hasMeta = false;
	protected $_metaStore = null;

	public function hasMeta($flag = null)
	{
		if(null !== $flag) {
			$this->_hasMeta = (bool) $flag;
		}

		return $this->_hasMeta;
	}

	public function getMetaStore()
	{
		if(!$this->hasMeta()) {
			require_once 'Zend/Exception.php';
			throw new Zend_Exception('Requested Metadata for a table that do not support it');
		}

		if(!$this->_metaStore) {
			$this->setMetaStore();
		}

		return $this->_metaStore;
	}

	public function setMetaStore($store = null)
	{
		if (null == $store) {
			$store = new Toolkit_Model_MetaStore($this);
		}

		$this->_metaStore = $store;

		return $this;
	}
}