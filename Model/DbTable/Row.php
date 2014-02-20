<?php
/**
 * Metadata Store-aware Table Abstraction. Uses MetaStore. 
 */
class Toolkit_Model_DbTable_Row extends Zend_Db_Table_Row
{
	protected $_metaCache;

	public function init()
	{
		$this->_metaCache = new Zend_Config(array(), true);
	}

	public function getMetaCache()
	{
		return $this->_metaCache;
	}

	public function getMeta($key = null)
	{
		return $this->getTable()->getMetaStore()->getMeta($this, $key);
	}

	public function setMeta($key, $value = null)
	{
		return $this->getTable()->getMetaStore()->setMeta($this, $key, $value);
	}
        
    public function unsetMeta($key)
    {
        return $this->getTable()->getMetaStore()->unsetMeta($this, $key);
    }
}