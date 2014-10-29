<?php

    namespace IdnoPlugins\Buffer {

        class Main extends \Idno\Common\Plugin {
	    
	    public static $AUTHORIZATION_ENDPOINT = 'https://bufferapp.com/oauth2/authorize';
	    public static $TOKEN_ENDPOINT         = 'https://api.bufferapp.com/1/oauth2/token.json';
	    
	    public static function getRedirectUrl() {
		return \Idno\Core\site()->config()->url . 'buffer/callback';
	    }
	    
	    public static function getState() {
		return md5(\Idno\Core\site()->config()->site_secret . \Idno\Core\site()->config()->url . dirname(__FILE__));
	    }

            function registerPages() {
                // Register the callback URL
                    \Idno\Core\site()->addPageHandler('buffer/callback','\IdnoPlugins\Buffer\Pages\Callback');
                // Register admin settings
                    \Idno\Core\site()->addPageHandler('admin/buffer','\IdnoPlugins\Buffer\Pages\Admin');
                // Register settings page
                    \Idno\Core\site()->addPageHandler('account/buffer','\IdnoPlugins\Buffer\Pages\Account');

                /** Template extensions */
                // Add menu items to account & administration screens
                    \Idno\Core\site()->template()->extendTemplate('admin/menu/items','admin/buffer/menu');
                    \Idno\Core\site()->template()->extendTemplate('account/menu/items','account/buffer/menu');
            }

            function registerEventHooks() {
		
		// Register syndication services
		\Idno\Core\site()->syndication()->registerService('buffer', function() {
                    return $this->hasBuffer();
                }, ['note','article','image']);
		
		

                
            }

            /**
             * Connect to Buffer
             * @return bool|\Buffer
             */
            function connect() {
                if (!empty(\Idno\Core\site()->config()->buffer)) {
                    $api = new Client(
                            \Idno\Core\site()->config()->buffer['appId'],
                            \Idno\Core\site()->config()->buffer['secret']
                    );
                    return $api;
                }
                return false;
            }

            /**
             * Can the current user use Buffer?
             * @return bool
             */
            function hasBuffer() {
                if (\Idno\Core\site()->session()->currentUser()->buffer) {
                    return true;
                }
                return false;
            }

        }

    }
