<?php
/** 
 * Provide all functionality within the code, inside "Service" classes that later can be
 * exposed though API Calls. 
 * 
 * This Service Abstract allows us to keep logic inside Controllers really small an manageable.
 */
class Toolkit_Service_Abstract
{
	protected $_prefix = '';
	protected $_forms = array();
	protected $_model = array();
	protected $_pageCount = 10;
	protected $_configInstances = array();

	/**
	 * Get all elements from the Model associated with this service.
	 */
	public function fetchAll($name = NULL, $where = NULL, $order = NULL)
	{
		return $this->getModel($name)->fetchAll($where, $order);
	}

	/**
	 * Get a Paginator instance for an specific page of results.
	 */
	public function fetch($page = 1, $count = NULL)
	{
		return $this->getPaginator($this->getBaseSelect(), $page, $count);
	}

	/**
	 * Returns a base Select Object, and apply a default order if specified.
	 */
	public function getBaseSelect($withFormPart = Zend_Db_Table::SELECT_WITHOUT_FROM_PART)
	{
		$select = $this->getModel()->select($withFormPart);
        //TODO NOT WORKING
		if(!empty($this->_order))
			$select->order($this->_order);
			
		return $select;
	}


	/**
	 * Fetch one Element from the Model, by ID.
	 */
	public function fetchOne($id, $model = null)
	{
		return $this->getModel($model)->find($id)->current();
	}

	/**
	 * Delete one element though a Model, by Id.
	 */
	public function delete($id)
	{
		return $this->fetchOne($id)->delete();
	}

	/**
	 * Fetch the default Form for this service, with the same name as the Service.
	 * Or you can actually give a Form name if you have several.
	 */
	public function getForm($name)
	{
		if (empty($this->_forms[$name])) {
			$this->setForm($name);
		}
		return $this->_forms[$name];
	}

	/**
	 * Set an Specific Form inside the Service.
	 */
	public function setForm($name, $form = NULL)
	{
		if (NULL == $form) {
			$className = $this->_prefix.'Form_' . ucfirst($name);
			if (class_exists($className)) {
				$form = new $className;
			}
		}

		$this->_forms[$name] = $form;
		return $this;
	}
	
	/**
	 * Get the default model if no name is indicated. 
	 * We cache that model for later easy access.
	 * 
	 * @param type $name
	 * @return Toolkit_Model_DbTable_Abstract
	 */
	public function getModel($name = NULL)
	{
		if (NULL == $name) {
			$parts = explode('_', get_class($this));
			$name = array_pop($parts);
		}
		if (!isset($this->_model[$name])) {
			$this->setModel($name);
		}
		return $this->_model[$name];
	}

	/**
	 * Instanciates and caches a Model for use with this Service.
	 */
	public function setModel($model)
	{
		if (is_string($model)) {
			$className = $this->_prefix.'Model_DbTable_' . ucfirst($model);
			$class = new $className;
		}
		$this->_model[$model] = $class;
	}

	/**
	 * If you require another Service, you can access it with this method.
	 */
	public function getService($name)
	{
		if (!isset($this->_service[$name])) {
			$this->setService($name);
		}
		return $this->_service[$name];
	}

	/**
	 * Set a Service by name for later use.
	 */
	public function setService($name, $service = null)
	{
		if (null === $service) {
			$className = $this->_prefix.'Service_' . ucfirst($name);
			$service = new $className;
		}
		$this->_service[$name] = $service;
	}

	/**
	 * Get a clean Paginator object using a created Select Object. 
	 * Before returning it, we set the current page required and results count-by-page.
	 */
	public function getPaginator($select, $page, $count = NULL)
	{
		if (NULL == $count) {
			$count = $this->_pageCount;
		}
		$pag = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
		return $pag->setCurrentPageNumber($page)->setItemCountPerPage($count);
	}

	/**
	 * Basic implemntation of an Options method, that checks first if a "setName" is available,
	 * and call that method, otherwise it just stores the values inside an options array.
	 */
	public function setOption($name, $value)
	{
		$method = 'set' . ucfirst($name);
		if (in_array($method, get_class_methods($this))) {
			call_user_func(array($this, $method), $value);
		} else {
			$this->_options[$name] = $value;
		}

		return $this;
	}

	/**
	 * Set multiple options.
	 */
	public function setOptions($options)
	{
		foreach ($options as $key => $value) {
			$this->setOption($key, $value);
		}

		return $this;
	}

	/**
	 * Get an Option.
	 */
	public function getOption($name)
	{
		return !empty($this->_options[$name]) ? $this->_options[$name] : null;
	}

	/**
	 * Get a Config object if present as a .ini file.
	 */
	public function getConfig($filename,$environment)
	{
		if(array_key_exists($filename, $this->_configInstances) && array_key_exists($filename, $this->_configInstances)) {
			return $this->_configInstances[$filename][$environment];
		} else {
			$this->_configInstances[$filename][$environment] = new Zend_Config_Ini(APPLICATION_PATH . '/configs/'.$filename, $environment);
		}
		return $this->_configInstances[$filename][$environment];
	}
}
