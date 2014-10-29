<?php

    /**
     * Buffer pages
     */

    namespace IdnoPlugins\Buffer\Pages {

        /**
         * Default class to serve Buffer-related account settings
         */
        class Account extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->gatekeeper(); // Logged-in users only
                if ($buffer = \Idno\Core\site()->plugins()->get('Buffer')) {
                    if (!$buffer->hasBuffer()) {
                        if ($bufferAPI = $buffer->connect()) {
                            $login_url = $bufferAPI->getAuthenticationUrl(
				\IdnoPlugins\Buffer\Main::$AUTHORIZATION_ENDPOINT,
				\IdnoPlugins\Buffer\Main::getRedirectUrl(),
				['response_type' => 'code', 'state' => \IdnoPlugins\Buffer\Main::getState()] 
                            );
			    
                        }
                    } else {
                        $login_url = '';
                    }
                }
                $t = \Idno\Core\site()->template();
                $body = $t->__(['login_url' => $login_url])->draw('account/buffer');
                $t->__(['title' => 'Buffer', 'body' => $body])->drawPage();
            }

            function postContent() {
                $this->gatekeeper(); // Logged-in users only
                if (($this->getInput('remove'))) {
                    $user = \Idno\Core\site()->session()->currentUser();
                    $user->buffer = [];
                    $user->save();
                    \Idno\Core\site()->session()->addMessage('Your Buffer settings have been removed from your account.');
                }
                $this->forward('/account/buffer/');
            }

        }

    }