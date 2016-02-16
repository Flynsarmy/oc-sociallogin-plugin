<?php namespace Flynsarmy\SocialLogin\SocialLoginProviders;

use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\SocialLoginProviders\SocialLoginProviderBase;
use Flynsarmy\SocialLogin\Models\Settings;
use Hybrid_Endpoint;
use Hybrid_Auth;
use URL;

class Twitter extends SocialLoginProviderBase
{
	public function isEnabled()
	{
		$providers = $this->settings->get('providers', []);

		return !empty($providers['Twitter']['enabled']);
	}

	public function extendSettingsForm(Form $form)
	{
		$form->addFields([
			'noop' => [
				'type' => 'partial',
				'path' => '@/plugins/flynsarmy/sociallogin/partials/backend/forms/settings/_twitter_info.htm',
				'tab' => 'Twitter',
			],

			'providers[Twitter][enabled]' => [
				'label' => 'Enabled?',
				'type' => 'checkbox',
				'default' => 'true',
				'tab' => 'Twitter',
			],

			'providers[Twitter][api_key]' => [
				'label' => 'API Key',
				'type' => 'text',
				'tab' => 'Twitter',
			],

			'providers[Twitter][api_secret]' => [
				'label' => 'API Secret',
				'type' => 'text',
				'tab' => 'Twitter',
			],
		], 'primary');
	}

	public function login($provider_name, $action)
	{
		// check URL segment
		if ($action == "auth") {
			Hybrid_Endpoint::process();

			return;
		}

		$providers = $this->settings->get('providers', []);

		// create a HybridAuth object
		$socialAuth = new Hybrid_Auth([
			"base_url" => URL::route('flynsarmy_sociallogin_provider', [$provider_name, 'auth']),
			"providers" => [
				'Twitter' => [
					"enabled" => true,
					"keys"    => array ( "key" => @$providers['Twitter']['api_key'], "secret" => @$providers['Twitter']['api_secret'] ),
					// "scope"   => "email, user_about_me",
				]
			],
		]);

		// authenticate with Twitter
		$provider = $socialAuth->authenticate($provider_name);

		// fetch user profile
		$userProfile = $provider->getUserProfile();

		$provider->logout();

		return [
			'token' => $userProfile->identifier,
			'username' => $userProfile->displayName,
			'email' => substr($userProfile->profileURL, 19).'@dev.null',
			'name' => trim($userProfile->firstName.' '.$userProfile->lastName),
		];
	}
}