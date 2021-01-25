<?php

namespace Flynsarmy\SocialLogin\Components;

use Session;
use URL;
use Cms\Classes\ComponentBase;
use Flynsarmy\SocialLogin\Models\Settings;
use Flynsarmy\SocialLogin\Classes\ProviderManager;
use Illuminate\Support\ViewErrorBag;

class SocialLogin extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Social Login',
            'description' => 'Adds social_login_link($provider, $success_url, $error_url) method.'
        ];
    }

    /**
     * Executed when this component is bound to a page or layout.
     */
    public function onRun()
    {
        $providers = ProviderManager::instance()->listProviders();

        // MarkupManager::instance()->registerFunctions([
        //  function($provider, $success_redirect='/', $error_redirect='/login') {
        //      $settings = Settings::instance()->getHauthProviderConfig();
        //      $is_enabled = !empty($settings[$provider]);

        //      if ( !$is_enabled )
        //          return '#';

        //      return ProviderManager::instance()->getBaseURL($provider) .
        //          '?s=' . URL::to($success_redirect) .
        //          '&f=' . URL::to($error_redirect);
        //  }
        // ]);

        $social_login_links = [];
        foreach ($providers as $provider_class => $provider_details) {
            if ($provider_class::instance()->isEnabled()) {
                $social_login_links[$provider_details['alias']] =
                    URL::route('flynsarmy_sociallogin_provider', [$provider_details['alias']]);
            }
        }

        $this->page['social_login_links'] = $social_login_links;

        $this->page['errors'] = Session::get('errors', new ViewErrorBag());
    }
}
