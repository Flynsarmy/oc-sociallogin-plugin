<?php namespace Flynsarmy\SocialLogin\SocialLoginProviders;

use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\SocialLoginProviders\SocialLoginProviderBase;
use URL;

class Google extends SocialLoginProviderBase
{
	use \October\Rain\Support\Traits\Singleton;
	protected $driver = 'google';

	protected $callback;
	protected $adapter;

	/**
	 * Initialize the singleton free from constructor parameters.
	 */
	protected function init()
	{
		parent::init();

        $this->callback = URL::route('flynsarmy_sociallogin_provider_callback', ['Google'], true);

	}

	public function getAdapter()
    {
        if ( !$this->adapter )
        {
            // Instantiate adapter using the configuration from our settings page
            $providers = $this->settings->get('providers', []);

            $this->adapter = new \Hybridauth\Provider\Google([
                'callback' => $this->callback,

                'keys' => [
                    'id'     => @$providers['Google']['client_id'],
                    'secret' => @$providers['Google']['client_secret'],
                ],

                'scope' => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',

                'debug_mode' => config('app.debug', false),
                'debug_file' => storage_path('logs/flynsarmy.sociallogin.'.basename(__FILE__).'.log'),
            ]);
        }

        return $this->adapter;
    }

	public function isEnabled()
	{
		$providers = $this->settings->get('providers', []);

		return !empty($providers['Google']['enabled']);
	}

    public function isEnabledForBackend()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Google']['enabledForBackend']);
    }

	public function extendSettingsForm(Form $form)
	{
		$form->addFields([
			'noop' => [
				'type' => 'partial',
				'path' => '$/flynsarmy/sociallogin/partials/backend/forms/settings/_google_info.htm',
				'tab' => 'Google',
			],

			'providers[Google][enabled]' => [
				'label' => 'Enabled on frontend?',
				'type' => 'checkbox',
                'comment' => 'Can frontend users log in with Google?',
                'default' => 'true',
				'span' => 'left',
                'tab' => 'Google',
			],

            'providers[Google][enabledForBackend]' => [
                'label' => 'Enabled on backend?',
                'type' => 'checkbox',
                'comment' => 'Can administrators log into the backend with Google?',
                'default' => 'false',
                'span' => 'right',
                'tab' => 'Google',
            ],

			'providers[Google][app_name]' => [
				'label' => 'Application Name',
				'type' => 'text',
				'default' => 'Social Login',
				'comment' => 'This appears on the Google login screen. Usually your site name.',
				'tab' => 'Google',
			],

			'providers[Google][client_id]' => [
				'label' => 'Client ID',
				'type' => 'text',
				'tab' => 'Google',
			],

			'providers[Google][client_secret]' => [
				'label' => 'Client Secret',
				'type' => 'text',
				'tab' => 'Google',
			],
		], 'primary');
	}

    public function redirectToProvider()
    {
        if ($this->getAdapter()->isConnected() )
            return \Redirect::to($this->callback);

        $this->getAdapter()->authenticate();
    }

    /**
     * Handles redirecting off to the login provider
     *
     * @return array ['token' => array $token, 'profile' => \Hybridauth\User\Profile]
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