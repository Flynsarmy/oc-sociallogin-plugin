<?php namespace Flynsarmy\SocialLogin;

use App;
use Backend;
use Event;
use URL;
use Illuminate\Foundation\AliasLoader;
use System\Classes\PluginBase;
use RainLab\User\Models\User;
use RainLab\User\Controllers\Users as UsersController;
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
    // Make this plugin run on updates page
    public $elevated = true;

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
				'order'       => 600,
                'permissions' => ['rainlab.users.access_settings'],
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
	    // Load socialite
        App::register('\Laravel\Socialite\SocialiteServiceProvider');
        AliasLoader::getInstance()->alias('Socialite', 'Laravel\Socialite\Facades\Socialite');

		User::extend(function($model) {
			$model->hasMany['flynsarmy_sociallogin_providers'] = ['Flynsarmy\SocialLogin\Models\Provider'];
		});

		// Add 'Social Logins' column to users list
        UsersController::extendListColumns(function($widget, $model) {
            if (!$model instanceof \RainLab\User\Models\User)
                return;

            $widget->addColumns([
                'flynsarmy_sociallogin_providers' => [
                    'label'      => 'Social Logins',
                    'type'       => 'partial',
                    'path'       => '~/plugins/flynsarmy/sociallogin/models/provider/_provider_column.htm',
                    'searchable' => false
                ]
            ]);
        });

        // Generate Social Login settings form
		Event::listen('backend.form.extendFields', function(Form $form) {
			if (!$form->getController() instanceof \System\Controllers\Settings) return;
			if (!$form->model instanceof \Flynsarmy\SocialLogin\Models\Settings) return;

			foreach ( ProviderManager::instance()->listProviders() as $class => $details )
			{
				$classObj = $class::instance();
				$classObj->extendSettingsForm($form);
			}
		});

		// Add 'Social Providers' field to edit users form
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

		// Add backend login provider integration
		Event::listen('backend.auth.extendSigninView', function() {
            $providers = ProviderManager::instance()->listProviders();

            $social_login_links = [];
            foreach ( $providers as $provider_class => $provider_details )
                if ( $provider_class::instance()->isEnabledForBackend() )
                    $social_login_links[$provider_details['alias']] = URL::route('flynsarmy_sociallogin_provider', [$provider_details['alias']]).'?s='.Backend::url().'&f='.Backend::url('backend/auth/signin');

            if ( !count($social_login_links) )
                return;

		    require __DIR__.'/partials/backend/_login.htm';
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
