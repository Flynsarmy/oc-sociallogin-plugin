<?php

// http://home.flynsarmy.com/flynsarmy/sociallogin/Google?s=/&f=/login
Route::get('flynsarmy/sociallogin/{provider}', array("as" => "flynsarmy_sociallogin_provider", 'middleware' => ['web'], function($provider_name, $action = "")
{
    $success_redirect = Input::get('s', '/');
	$error_redirect = Input::get('f', '/login');
    Session::flash('flynsarmy_sociallogin_successredirect', $success_redirect);
    Session::flash('flynsarmy_sociallogin_errorredirect', $error_redirect);

    $provider_class = Flynsarmy\SocialLogin\Classes\ProviderManager::instance()
        ->resolveProvider($provider_name);

	if ( !$provider_class )
		return Redirect::to($error_redirect)->withErrors("Unknown login provider: $provider_name.");

	$provider = $provider_class::instance();

	return $provider->redirectToProvider();
}))->where(['provider' => '[A-Z][a-zA-Z ]+']);

Route::get('flynsarmy/sociallogin/{provider}/callback', ['as' => 'flynsarmy_sociallogin_provider_callback', 'middleware' => ['web'], function($provider_name) {
    $success_redirect = Session::get('flynsarmy_sociallogin_successredirect', '/');
    $error_redirect = Session::get('flynsarmy_sociallogin_errorredirect', '/login');

    $provider_class = Flynsarmy\SocialLogin\Classes\ProviderManager::instance()
        ->resolveProvider($provider_name);

    if ( !$provider_class )
        return Redirect::to($error_redirect)->withErrors("Unknown login provider: $provider_name.");

    $provider = $provider_class::instance();

    try {
        // This will contain [token => ..., email => ..., ...]
        $provider_response = $provider->handleProviderCallback($provider_name);

        if ( !is_array($provider_response) )
            return Redirect::to($error_redirect);
    } catch (Exception $e) {
        // Log the error
        Log::error($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());

        return Redirect::to($error_redirect)->withErrors([$e->getMessage()]);
    }

    $provider_details = [
        'provider_id' => $provider_name,
        'provider_token' => $provider_response['token'],
    ];
    $user_details = array_except($provider_response, 'token');

    // Backend logins
    if ( $success_redirect == Backend::url() )
    {
        $user = Flynsarmy\SocialLogin\Classes\UserManager::instance()
            ->findBackendUserByEmail($user_details['email']);

        if ( !$user )
            throw new October\Rain\Auth\AuthException(sprintf(
                'Administrator with email address "%s" not found.', $user_details['email']
            ));

        // Support custom login handling
        $result = Event::fire('flynsarmy.sociallogin.handleBackendLogin', [
            $provider_details, $provider_response, $user
        ], true);
        if ( $result )
            return $result;

        BackendAuth::login($user, true);

        // Load version updates
        System\Classes\UpdateManager::instance()->update();

        // Log the sign in event
        Backend\Models\AccessLog::add($user);
    }
    // Frontend Logins
    else
    {
        // Grab the user associated with this provider. Creates or attach one if need be.
        $user = \Flynsarmy\SocialLogin\Classes\UserManager::instance()->find(
            $provider_details,
            $user_details
        );

        // Support custom login handling
        $result = Event::fire('flynsarmy.sociallogin.handleLogin', [
            $provider_details, $provider_response, $user
        ], true);
        if ( $result )
            return $result;

        Auth::login($user);
    }

    return Redirect::to($success_redirect);
}]);