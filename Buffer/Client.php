<?php

namespace IdnoPlugins\Buffer {
    
    class Client {
	
	private $key;
	private $secret;
	
	public $access_token;
	
	function __construct($apikey, $secret) {
	    $this->key = $apikey;
	    $this->secret = $secret;
	}
	
	public function getAuthenticationUrl($baseURL, $redirectURL, $parameters = []) {
	    
	    $parameters['redirect_uri'] = $redirectURL;
	    
	    $url = $baseURL . "?client_id={$this->key}";
	    foreach ($parameters as $key => $value)
		$url .= '&' . urlencode($key) . '=' . urlencode($value);
	    
	    return $url;
	}
	
	public function getAccessToken($endpointUrl, $grant_type = 'authorization_code', array $parameters) {
	    
	    if ($parameters['state'] != \Idno\Core\site()->plugins()->get('Buffer')->getState())
		throw new \Exception('State value not correct, possible CSRF attempt.');
		
	    unset($parameters['state']);
	    
	    
	    // TODO verify code
	    
	    
	    $parameters['client_id'] = $this->key;
	    $parameters['client_secret'] = $this->secret;
	    $parameters['grant_type'] = $grant_type;
	    	    
	    return \Idno\Core\Webservice::post(\IdnoPlugins\Buffer\Main::$TOKEN_ENDPOINT, $parameters);
	    
	}
	
	public function setAccessToken($token) {
	    $this->access_token = $token;
	}
	
	/**
	 * Retrieve profiles attached to their account.
	 */
	public function getProfileIDs() {
	    
	    if (!$this->access_token)
		$this->setAccessToken(\Idno\Core\site()->session()->currentUser()->buffer['access_token']);
	    
	    if ($result = \Idno\Core\Webservice::get('https://api.bufferapp.com/1/profiles.json', ['access_token' => $this->access_token])) {
		
		$profiles = [];
		
		$results = json_decode($result['content']);
		
		foreach ($results as $result) {
		    $profiles[] = $result->id;
		}	
		
		return $profiles;
	    }
	    
	    return false;
	}
	
    }
}