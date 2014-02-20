<?
/**
 * Metadata Model Class.
 * 
 * Receiving and working with an Zend_Db_Table_Abstract, this class provides access to
 * a new table that stores Metadata for such Table Abstraction, in a clear and transparent
 * way. 
 */

class Toolkit_Model_MetaStore
{
    protected $_table   = null;
    protected $_name    = '';
    protected $_primary = null;
    
    /**
     * @var Zend_Db_Table_Abstract
     */
    protected $_parent  = null;
    protected $_cache   = array();
  
    public function __construct(Zend_Db_Table_Abstract $dbtable)
    {
        $this->_parent  = $dbtable;
        $info           = $dbtable->info();
        $this->_primary = array_pop($info['primary']);
        $tablename      = substr($info['name'], 0, -1) . 'meta';
        $this->_name    = array_pop(explode('_', $tablename));

        $config = new Zend_Db_Table_Definition(array(
            $this->_name => array(
                'name'              => $tablename,
                'referenceMap'      => array(
                    'Parent' => array(
                        'columns'       => $this->_primary,
                        'refTableClass' => get_class($dbtable),
                        'refColumns'    => $this->_primary,
                    ),
                ),
                'dependentTables'   => array(),
            )
        ));

        $dbtable->setOptions(array(
            'dependentTables' => array($this->_name),
            'definition'      => $config,
        ));
        
        $this->_table = new Zend_Db_Table($this->_name, $config);
    }

    public function fetchBy($key, $value)
    {
        $store = $this->_table;
        $select = $store->select()
                        ->where('name = ?', $key)
                        ->where('value = ?', $value);

        if(null === ($meta = $store->fetchRow($select))) {
            return null;
        }

        return $meta->findParentRow($this->_parent);
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getMeta(Zend_Db_Table_Row_Abstract $row, $key = null)
    {
        $select = null;
        $cache  = $row->getMetaCache();

        if (is_array($key)) {
            /**
             * We first bring the Meta Keys we still don't have
             */
            foreach(array_diff($key, array_keys($cache->toArray())) as $miss_key) {
                $this->getMeta($row, $miss_key);
            }
            /**
             * Then we return the intersection between the cache keys and the keys
             */
            return array_intersect_key($cache->toArray(), array_flip($key));
        }

        if (is_string($key)) {
            if(isset($cache->$key)) {
                return $cache->$key;
            }
            $select = $this->_table->select()->where('name = ?', $key);
        }

        foreach($row->findDependentRowset($this->_name, 'Parent', $select) as $res) {
            $cache->{$res->name} = $res;
        }

        if(null !== $key && isset($cache->$key)) {
            return $cache->$key;
        } elseif (null === $key) {
            return $cache;
        }

        return null;
    }

    public function setMeta(Zend_Db_Table_Row_Abstract $row, $key, $value = null)
    {
        if(is_array($key)) {
            $prefix = null !== $value ? "$value:" : '';
            foreach($key as $k => $value) {
                $this->_setMeta($row, $prefix.$k, $value);
            }
        } else {
            $this->_setMeta($row, $key, $value);
        }
    }

    protected function _setMeta(Zend_Db_Table_Row_Abstract $row, $key, $value)
    {
        /**
         * If an specific method exists for
         * this metadata type then call it
         */
        $methodName = 'setMeta' . ucfirst(strtolower($key));
        if(in_array($methodName, get_class_methods($row))) {
            return $row->$methodName($key, $value);
        }

        $meta  = $this->getMeta($row, $key);
        $cache = $row->getMetaCache();
        $value = !(is_string($value) || is_numeric($value) || is_null($value)) ? 
                    serialize($value) : 
                    $value;

        if(null === $meta) {
            /**
             * Instead of a recently requested row, then we must 
             * create a new row from scratch
             */ 
            $meta = $this->_table->createRow(array(
                $this->_primary => $row[$this->_primary],
                'name'          => $key,
                'created'       => new Zend_Db_Expr('NOW()'),
            ));
        }

        if($meta['value'] !== $value) {
            $meta->value = $value;
            $meta->save();
            $cache->$key = $meta;
        }
        return $meta;
    }

    public function unsetMeta(Zend_Db_Table_Row_Abstract $row, $key = null)
    {
        $key = $row->getMeta($key);
        
        if(!$key) {
            return false;
        }
        
        return $key->delete();
    }
}