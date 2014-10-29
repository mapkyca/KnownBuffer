<?php

    /**
     * Buffer pages
     */

    namespace IdnoPlugins\Buffer\Pages {

        /**
         * Default class to serve Buffer settings in administration
         */
        class Admin extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->adminGatekeeper(); // Admins only
                $t = \Idno\Core\site()->template();
                $body = $t->draw('admin/buffer');
                $t->__(['title' => 'Buffer', 'body' => $body])->drawPage();
            }

            function postContent() {
                $this->adminGatekeeper(); // Admins only
                $appId = $this->getInput('appId');
                $secret = $this->getInput('secret');
                \Idno\Core\site()->config->config['buffer'] = [
                    'appId' => $appId,
                    'secret' => $secret
                ];
                \Idno\Core\site()->config()->save();
                \Idno\Core\site()->session()->addMessage('Your Buffer application details were saved.');
                $this->forward('/admin/buffer/');
            }

        }

    }