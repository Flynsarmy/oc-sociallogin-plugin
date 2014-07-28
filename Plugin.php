<?php namespace Flynsarmy\SocialLogin;

use Event;
use System\Classes\PluginBase;
use RainLab\User\Models\User;
use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\Classes\ProviderManager;

/**
 * SocialLogin Plugin Information File
 *
 * http://www.mrcasual.com/on/coding/laravel4-package-management-with-composer/
 * https://cartalyst.com/manual/sentry-social
 *
 */
class Plugin extends PluginBase
{
	public $require = ['RainLab.User'];

	/**
	 * Returns information about this plugin.
	 *
	 * @return array
	 */
	public function pluginDetails()
	{
		return [
			'name'        => 'Social Login',
			'description' => 'Allows visitors to register/sign in with their social media accounts',
			'author'      => 'Flynsarmy',
			'icon'        => 'icon-users'
		];
	}

	public function registerSettings()
	{
		return [
			'settings' => [
				'label'       => 'Social Login',
				'description' => 'Manage Social Login providers.',
				'icon'        => 'icon-users',
				'class'       => 'Flynsarmy\SocialLogin\Models\Settings',
				'order'       => 600
			]
		];
	}

	public function registerComponents()
	{
		return [
			'Flynsarmy\SocialLogin\Components\SocialLogin'       => 'sociallogin',
		];
	}

	public function boot()
	{
		User::extend(function($model) {
			$model->hasMany['flynsarmy_sociallogin_providers'] = ['Flynsarmy\SocialLogin\Models\Provider'];
		});

		Event::listen('backend.form.extendFields', function(Form $form) {
			if (!$form->getController() instanceof \System\Controllers\Settings) return;
			if (!$form->model instanceof \Flynsarmy\SocialLogin\Models\Settings) return;

			foreach ( ProviderManager::instance()->listProviders() as $class => $details )
			{
				$classObj = $class::instance();
				$classObj->extendSettingsForm($form);
			}
		});

		Event::listen('backend.form.extendFields', function($widget) {
			if (!$widget->getController() instanceof \RainLab\User\Controllers\Users) return;
			if ($widget->getContext() != 'update') return;

			$widget->addFields([
				'flynsarmy_sociallogin_providers' => [
					'label'   => 'Social Providers',
					'type'    => 'Flynsarmy\SocialLogin\FormWidgets\LoginProviders',
				],
			], 'secondary');
		});
	}

	function register_flynsarmy_sociallogin_providers()
	{
		return [
			'\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Google' => [
				'label' => 'Google',
				'alias' => 'Google',
				'description' => 'Log in with Google'
			],
			'\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Twitter' => [
				'label' => 'Twitter',
				'alias' => 'Twitter',
				'description' => 'Log in with Twitter'
			],
			'\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Facebook' => [
				'label' => 'Facebook',
				'alias' => 'Facebook',
				'description' => 'Log in with Facebook'
			],
		];
	}
}
