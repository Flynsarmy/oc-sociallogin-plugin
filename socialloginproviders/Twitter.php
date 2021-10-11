<?php

namespace Flynsarmy\SocialLogin\SocialLoginProviders;

use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\SocialLoginProviders\SocialLoginProviderBase;
use URL;

class Twitter extends SocialLoginProviderBase
{
    use \October\Rain\Support\Traits\Singleton;

    protected $driver = 'twitter';

    protected $callback;
    protected $adapter;

    /**
     * Initialize the singleton free from constructor parameters.
     */
    protected function init()
    {
        parent::init();

        $this->callback = URL::route('flynsarmy_sociallogin_provider_callback', ['Twitter'], true);
    }

    public function getAdapter()
    {
        if (!$this->adapter) {
            // Instantiate adapter using the configuration from our settings page
            $providers = $this->settings->get('providers', []);

            $this->adapter = new \Hybridauth\Provider\Twitter([
                'callback' => $this->callback,

                'keys' => [
                    'key'    => @$providers['Twitter']['identifier'],
                    'secret' => @$providers['Twitter']['secret'],
                ],

                'debug_mode' => config('app.debug', false),
                'debug_file' => storage_path('logs/flynsarmy.sociallogin.' . basename(__FILE__) . '.log'),
            ]);
        }

        return $this->adapter;
    }

    public function isEnabled()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Twitter']['enabled']);
    }

    public function isEnabledForBackend()
    {
        //$providers = $this->settings->get('providers', []);
        //
        //return !empty($providers['Twitter']['enabledForBackend']);

        return false;
    }

    public function extendSettingsForm(Form $form)
    {
        $form->addFields([
            'providers[Twitter][noop]' => [
                'type' => 'partial',
                'path' => '$/flynsarmy/sociallogin/partials/backend/forms/settings/_twitter_info.htm',
                'tab' => 'Twitter',
            ],

            'providers[Twitter][enabled]' => [
                'label' => 'Enabled on frontend?',
                'type' => 'checkbox',
                'comment' => 'Can frontend users log in with Twitter?',
                'default' => 'true',
                'span' => 'left',
                'tab' => 'Twitter',
            ],

            //'providers[Twitter][enabledForBackend]' => [
            //    'label' => 'Enabled on backend?',
            //    'type' => 'checkbox',
            //    'comment' => 'Can administrators log into the backend with Twitter?',
            //    'default' => 'false',
            //    'span' => 'right',
            //    'tab' => 'Twitter',
            //],

            'providers[Twitter][identifier]' => [
                'label' => 'API Key',
                'type' => 'text',
                'tab' => 'Twitter',
            ],

            'providers[Twitter][secret]' => [
                'label' => 'API Secret',
                'type' => 'text',
                'tab' => 'Twitter',
            ],
        ], 'primary');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToProvider()
    {
        if ($this->getAdapter()->isConnected()) {
            return \Redirect::to($this->callback);
        }

        $this->getAdapter()->authenticate();
    }

    /**
     * Handles redirecting off to the login provider
     *
     * @return array
     */
    public function handleProviderCallback()
    {
        $this->getAdapter()->authenticate();

        $token = $this->getAdapter()->getAccessToken();
        $profile = $this->getAdapter()->getUserProfile();

        // Don't cache anything or successive logins to different accounts
        // will keep logging in to the first account
        $this->getAdapter()->disconnect();

        return [
            'token' => $token,
            'profile' => $profile
        ];
    }
}
