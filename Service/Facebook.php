<?php
/**
 * Sample Service Implementation demoing a Facebook Login and user permissions.
 */
class Toolkit_Service_Facebook extends Toolkit_Service_Abstract
{
	protected $_access_token = null;
	protected $_graphUrl     = 'https://graph.facebook.com/me';
    protected $_basicFields  = array (
            'id', 
            'name', 
            'first_name', 
            'last_name', 
            'email', 
            'username', 
            'gender'
        );
    protected $_extraFields  = array (
            'birthday', 
            'education', 
            'location', 
            'relationship_status',
        );

	public function __construct($access_token)
	{
        if (!empty($access_token)) {
            $this->_access_token = $access_token;
        } else {
            throw new Zend_Auth_Exception('Required param "access_token" is missing param in config');
        }
	}

    public function _getInfo($fields)
    {
        if(!is_array($fields)) {
            $fields = array($fields);
        }

        if (empty($this->_access_token)) {
            return new Zend_Exception('Set Access Token for Requests to Facebook');
        }

        $args = array(
            'access_token' => $this->_access_token,
            'fields'       => implode(',', $fields),
        );

        $response = $this->_apiCall($this->_graphUrl, $args);

        if(isset($response['error'])) {
            return null;
        }

        return $response;
    }

    public function getAllInfo()
    {
        $fields = array_merge(
                $this->_basicFields,
                $this->_extraFields
            );
        return $this->_getInfo($fields);
    }

	public function getBasicInfo()
	{
        return $this->_getInfo($this->_basicFields);
	}

    public function getExtendedInfo()
    {
        return $this->_getInfo($this->_extraFields);
    }

    /**
     * Make post to url
     * @param string $url
     * @param array $params
     * @return array
     */
    protected function _apiCall($url, $params)
    {
        $r = new Zend_Http_Client($url);
        $r->setParameterGet($params);
        $response = $r->request(Zend_Http_Client::GET);
        
        $res = json_decode($response->getBody(), true);
        
        return $res;
    }
}