<?php namespace Flynsarmy\SocialLogin\Models;

use Model;
use URL;
use Flynsarmy\SocialLogin\Classes\DropDownHelper;
use Flynsarmy\SocialLogin\Classes\ProviderManager;

class Settings extends Model
{
	public $implement = ['System.Behaviors.SettingsModel'];

	// A unique code
	public $settingsCode = 'flynsarmy_sociallogin_settings';

	// Reference to field configuration
	public $settingsFields = 'fields.yaml';

	protected $cache = [];

	public function getHauthConfig($provider)
	{
		$config = [
			"base_url" => ProviderManager::instance()->getBaseURL("$provider/auth"),
			"providers" => $this->getHauthProviderConfig(),
		];

		return $config;
	}

	public function getHauthProviderConfig()
	{
		if ( empty($this->cache['hauth_providers_config']) )
		{
			$config = [];

			$providers = $this->get('providers');
			if ( !empty($providers['Google']['enabled']) )
				$config["Google"] = array(
					"enabled" => true,
					"keys"    => array ( "id" => @$providers['Google']['client_id'], "secret" => @$providers['Google']['client_secret'] ),
					"scope"   =>
						"https://www.googleapis.com/auth/userinfo.profile ". // optional
						"https://www.googleapis.com/auth/userinfo.email"   , // optional
				);

			$this->cache['hauth_providers_config'] = $config;
		}

		return $this->cache['hauth_providers_config'];
	}

	public function getErrorRedirectOptions()
	{
		return DropDownHelper::instance()->pages();
	}
}