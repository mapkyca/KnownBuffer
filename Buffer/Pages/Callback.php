<?php

/**
 * Buffer pages
 */

namespace IdnoPlugins\Buffer\Pages {

    /**
     * Default class to serve the Buffer callback
     */
    class Callback extends \Idno\Common\Page {

	function getContent() {
	    $this->gatekeeper(); // Logged-in users only

	    try {
		if ($buffer = \Idno\Core\site()->plugins()->get('Buffer')) {
		    if ($bufferAPI = $buffer->connect()) {

			if ($response = $bufferAPI->getAccessToken(\IdnoPlugins\Buffer\Main::$TOKEN_ENDPOINT, 'authorization_code', [
			    'code' => $this->getInput('code'), 
			    'redirect_uri' => \IdnoPlugins\Buffer\Main::getRedirectUrl(), 
			    'state' => \IdnoPlugins\Buffer\Main::getState()])) {

			    $response = json_decode($response['content']);
			    
			    $user = \Idno\Core\site()->session()->currentUser();
			    $user->buffer = ['access_token' => $response->access_token];
			    
			    // Get profiles
			    $profiles = $bufferAPI->getProfileIDs();
			    if (empty($profiles)) 
				throw new \Exception('No profiles were associated with that account.');
			    
			    $user->buffer['profile_ids'] = $profiles;

			    $user->save();
			    \Idno\Core\site()->session()->addMessage('Your Buffer account was connected.');
			}
		    }
		}
	    } catch (\Exception $e) {
		\Idno\Core\site()->session()->addErrorMessage($e->getMessage());
	    }
	    
	    $this->forward('/account/buffer/');
	}

    }

}