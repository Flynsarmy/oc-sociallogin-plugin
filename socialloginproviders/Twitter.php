<?php namespace Flynsarmy\SocialLogin\SocialLoginProviders;

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

        // Instantiate adapter using the configuration from our settings page
        $providers = $this->settings->get('providers', []);

        $this->callback = URL::route('flynsarmy_sociallogin_provider_callback', ['Twitter'], true);
        $this->adapter = new \Hybridauth\Provider\Google([
            'callback' => $this->callback,

            'keys' => [
                'key'    => @$providers['Twitter']['identifier'],
                'secret' => @$providers['Twitter']['secret'],
            ],

            'debug_mode' => config('app.debug', false),
            'debug_file' => storage_path('logs/flynsarmy.sociallogin.'.basename(__FILE__).'.log'),
        ]);
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
			'noop' => [
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
        if ($this->adapter->isConnected() )
            return \Redirect::to($this->callback);

        $this->adapter->authenticate();
    }

    /**
     * Handles redirecting off to the login provider
     *
     * @return array
     */
    public function handleProviderCallback()
    {
        $this->adapter->authenticate();

        $token = $this->adapter->getAccessToken();
        $profile = $this->adapter->getUserProfile();

        return [
            'token' => $token,
            'profile' => $profile
        ];
    }
}