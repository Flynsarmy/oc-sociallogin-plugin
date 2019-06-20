<?php namespace Flynsarmy\SocialLogin\SocialLoginProviders;

use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\SocialLoginProviders\SocialLoginProviderBase;
use URL;

class Facebook extends SocialLoginProviderBase
{
	use \October\Rain\Support\Traits\Singleton;

	protected $driver = 'facebook';

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

        $this->callback = URL::route('flynsarmy_sociallogin_provider_callback', ['Facebook'], true);
        $this->adapter = new \Hybridauth\Provider\Google([
            'callback' => $this->callback,

            'keys' => [
                'id'     => @$providers['Facebook']['client_id'],
                'secret' => @$providers['Facebook']['client_secret'],
            ],

            'debug_mode' => config('app.debug', false),
            'debug_file' => storage_path('logs/flynsarmy.sociallogin.'.basename(__FILE__).'.log'),
        ]);
	}

	public function isEnabled()
	{
		$providers = $this->settings->get('providers', []);

		return !empty($providers['Facebook']['enabled']);
	}

    public function isEnabledForBackend()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Facebook']['enabledForBackend']);
    }

	public function extendSettingsForm(Form $form)
	{
		$form->addFields([
			'noop' => [
				'type' => 'partial',
				'path' => '$/flynsarmy/sociallogin/partials/backend/forms/settings/_facebook_info.htm',
				'tab' => 'Facebook',
			],

			'providers[Facebook][enabled]' => [
                'label' => 'Enabled on frontend?',
				'type' => 'checkbox',
                'comment' => 'Can frontend users log in with Facebook?',
                'default' => 'true',
                'span' => 'left',
				'tab' => 'Facebook',
			],

            'providers[Facebook][enabledForBackend]' => [
                'label' => 'Enabled on backend?',
                'type' => 'checkbox',
                'comment' => 'Can administrators log into the backend with Facebook?',
                'default' => 'false',
                'span' => 'right',
                'tab' => 'Facebook',
            ],

			'providers[Facebook][client_id]' => [
				'label' => 'App ID',
				'type' => 'text',
				'tab' => 'Facebook',
			],

			'providers[Facebook][client_secret]' => [
				'label' => 'App Secret',
				'type' => 'text',
				'tab' => 'Facebook',
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
     * @return array ['token' => array $token, 'profile' => \Hybridauth\User\Profile]
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