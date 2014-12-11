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
		
		
		// Push "notes" to Buffer
                \Idno\Core\site()->addEventHook('post/note/buffer',function(\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($this->hasBuffer()) {
                        if ($bufferAPI = $this->connect()) {
                            $bufferAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->buffer['access_token']);
                            $message = strip_tags($object->getDescription());
			    
                            if (!empty($message) && substr($message,0,1) != '@') {
                                
                                try {
				    
				    $result = \Idno\Core\Webservice::post('https://api.bufferapp.com/1/updates/create.json?access_token=' . $bufferAPI->access_token, http_build_query([
					'text' => $message,
					'profile_ids' => \Idno\Core\site()->session()->currentUser()->buffer['profile_ids'],
				    ]));
				    
				    if ($result['response'] < 400) {
					
					// Success
					$link = 'https://bufferapp.com/app'; // We don't have a full posse link here, so we have to link to buffer account

					$object->setPosseLink('buffer', $link);
					$object->save();
					
				    }
				    else
				    {
					\Idno\Core\site()->logging->log("Buffer Syndication to " . print_r(\Idno\Core\site()->session()->currentUser()->buffer['profile_ids'], true) . " failed with " . print_r($result, true), LOGLEVEL_ERROR);
					
					$content = json_decode($result['content']);
					if (!empty($content->message))
					    throw new \Exception($content->message);
					
					throw new \Exception("Error code {$result['response']}");
				    }
				    
                                } catch (\Exception $e) {
                                    \Idno\Core\site()->session()->addErrorMessage('There was a problem posting to Buffer: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                });

                // Push "articles" to Buffer
                \Idno\Core\site()->addEventHook('post/article/buffer',function(\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($this->hasBuffer()) {
                        if ($bufferAPI = $this->connect()) {
                            $bufferAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->buffer['access_token']);
                            
			    try {
				$status     = $object->getTitle();
				if (strlen($status) > 110) { // Trim status down if required
				    $status = substr($status, 0, 106) . ' ...';
				}
				$status .= ' ' . $object->getURL();

				$result = \Idno\Core\Webservice::post('https://api.bufferapp.com/1/updates/create.json?access_token=' . $bufferAPI->access_token, http_build_query([
				    'text' => $status,
				    'profile_ids' => \Idno\Core\site()->session()->currentUser()->buffer['profile_ids'],
				]));

				if ($result['response'] < 400) {

				    // Success
				    $link = 'https://bufferapp.com/app'; // We don't have a full posse link here, so we have to link to buffer account

				    $object->setPosseLink('buffer', $link);
				    $object->save();

				}
				else
				{
				    \Idno\Core\site()->logging->log("Buffer Syndication to " . print_r(\Idno\Core\site()->session()->currentUser()->buffer['profile_ids'], true) . " failed with " . print_r($result, true), LOGLEVEL_ERROR);
				    
				    $content = json_decode($result['content']);
				    if (!empty($content->message))
					throw new \Exception($content->message);

				    throw new \Exception("Error code {$result['response']}");
				}

			    } catch (\Exception $e) {
				\Idno\Core\site()->session()->addErrorMessage('There was a problem posting to Buffer: ' . $e->getMessage());
			    }
                        }
                    }
                });

                // Push "images" to Buffer
                \Idno\Core\site()->addEventHook('post/image/buffer',function(\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($attachments = $object->getAttachments()) {
                        foreach($attachments as $attachment) {
                            if ($this->hasBuffer()) {
                                if ($bufferAPI = $this->connect()) {
				    $bufferAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->buffer['access_token']);

				    
				     try {
					$result = \Idno\Core\Webservice::post('https://api.bufferapp.com/1/updates/create.json?access_token=' . $bufferAPI->access_token, http_build_query([
					    'text' => $object->getTitle(),
					    'profile_ids' => \Idno\Core\site()->session()->currentUser()->buffer['profile_ids'],
					    'media' => ['photo' => $attachment['url'], 'thumbnail' => $attachment['url'], 'link' => $object->getUrl(), 'title' => $object->getTitle(), 'description' => $object->getDescription()]
					]));

					if ($result['response'] < 400) {

					    // Success
					    $link = 'https://bufferapp.com/app'; // We don't have a full posse link here, so we have to link to buffer account

					    $object->setPosseLink('buffer', $link);
					    $object->save();

					}
					else
					{
					    \Idno\Core\site()->logging->log("Buffer Syndication to " . print_r(\Idno\Core\site()->session()->currentUser()->buffer['profile_ids'], true) . " failed with " . print_r($result, true), LOGLEVEL_ERROR);

					    $content = json_decode($result['content']);
					    if (!empty($content->message))
						throw new \Exception($content->message);
					    
					    throw new \Exception("Error code {$result['response']}");
					}

				    } catch (\Exception $e) {
					\Idno\Core\site()->session()->addErrorMessage('There was a problem posting to Buffer: ' . $e->getMessage());
				    }
				    
				    
				}
                            }
                        }
                    }
                }); 
                
            }

            /**
             * Connect to Buffer
             * @return bool|\IdnoPlugins\Buffer\Client
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
