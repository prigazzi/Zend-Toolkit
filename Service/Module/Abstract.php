<?php
class Toolkit_Service_Module_Abstract extends Toolkit_Service_Abstract
{
	public function __construct() {
		$prefix = array_shift(explode('_', get_called_class()));
		if (!in_array($prefix, array('Default', 'Model'))) {
			$this->_prefix = $prefix . '_';
		}
	}
}