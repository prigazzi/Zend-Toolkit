<?php
class Toolkit_Auth_Adapter_Facebook implements Zend_Auth_Adapter_Interface
{
    private $_appId;
    private $_secret;
    private $_permissions;
    private $_token;
    private $_redirect_uri;
    private $_url           = 'https://graph.facebook.com/oauth/access_token';

    /**
    * List of query parameters that get automatically dropped when rebuilding
    * the current URL.
    */
    protected static $DROP_QUERY_PARAMS = array(
        'session',
        'signed_request',
    );

    /**
     * @param mixed $config - Zend_Config or array
     */
    public function __construct($config)
    {
        if($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
        
        if (isset($config['appId'])) {
            $this->_appId = $config['appId'];
            unset($config['appId']);
        } else {
            throw new Zend_Auth_Exception('Required param "appId" is missing param in config');
        }

        if (isset($config['secret'])) {
            $this->_secret = $config['secret'];
            unset($config['secret']);
        } else {
            throw new Zend_Auth_Exception('Required param "secret" is missing param in config');
        }
        
        if (isset($config['redirect_uri'])) {
            $this->_redirect_uri = $config['redirect_uri'];
            unset($config['redirect_uri']);
        } else {
            $this->_redirect_uri = null;
        }

        if (isset($config['permissions'])) {
            $this->_permissions = $config['permissions'];
            unset($config['permissions']);
        } else {
            $this->_permissions = '';
        }
    }

    /**
     * Set token You have received from Facebook
     * @param string $token
     * @return Zend_Auth_Adapter_Facebook
     */
    public function setToken($token)
    {
        $this->_token = $token;
        return $this;
    }

    /**
     * Exchange token to access_token
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (empty($this->_token)) {
            $code = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
            return new Zend_Auth_Result($code, false, array('Set Token for authorization'));
        }

        $args = array(
            'client_id'     => $this->_appId,
            'client_secret' => $this->_secret,
            'redirect_uri'  => $this->getRedirectUri(),
            'code'          => $this->_token,
        );

        $response = $this->_apiCall($this->_url, $args);
        if (isset($response['error'])) {
            $code = Zend_Auth_Result::FAILURE;
            $msgs[] = $response['error']['message'];
            $result = new Zend_Auth_Result($code, false, $msgs);
        } else {
            $code = Zend_Auth_Result::SUCCESS;
            $result = new Zend_Auth_Result($code, $response);
        }
        return $result;
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
        $r->setParameterPost($params);
        $res = $r->request(Zend_Http_Client::POST)->getBody();
        
        $info = json_decode($res, true);

        if(!is_array($info)) {
            /* Check the string for parameter form */
            if(false !== strpos($res, '&')) {
                parse_str($res, $info);
            } else {
                $info = array($res);
            }
        }
        return $info;
    }

    /**
     * Redirect to login page
     */
    public function redirect()
    {
        $params = array(
            'scope'         => $this->_permissions,
            'client_id'     => $this->_appId,
            'redirect_uri'  => $this->getRedirectUri(),
        );
        $url = 'https://www.facebook.com/dialog/oauth?';
        $url .= http_build_query($params, null, '&');
        echo("<script>top.location.href='" . $url . "'</script>");
        exit();
    }

    /**
    * Returns the Current URL, stripping it of known FB parameters that should
    * not persist.
    *
    * @return String the current URL
    */
    protected function getCurrentUrl($includeQuery = false)
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'
          ? 'https://'
          : 'http://';
        $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parts = parse_url($currentUrl);

        // drop known fb params
        $query = '';
        if (!empty($parts['query']) && $includeQuery) {
          $params = array();
          parse_str($parts['query'], $params);
          foreach(self::$DROP_QUERY_PARAMS as $key) {
            unset($params[$key]);
          }
          if (!empty($params)) {
            $query = '?' . http_build_query($params, null, '&');
          }
        }

        // use port if non default
        $port =
          isset($parts['port']) &&
          (($protocol === 'http://' && $parts['port'] !== 80) ||
           ($protocol === 'https://' && $parts['port'] !== 443))
          ? ':' . $parts['port'] : '';

        // rebuild
        return $protocol . $parts['host'] . $port . $parts['path'] . $query;
    }
    
    /**
    *Checks to see if _redirect_uri is set, if not return current URL
    */
    protected function getRedirectUri()
    {
    	if($this->_redirect_uri != null)
    		return $this->_redirect_uri;
    	else
    		return $this->getCurrentUrl();
    }
    
    /**
    *Public function to allow users to set _redirect_uri after constructing the adapter
    */
    public function setRedirectUri($redirect_uri)
    {
    	$this->_redirect_uri = $redirect_uri;
    	return $this;
    }
    	

}