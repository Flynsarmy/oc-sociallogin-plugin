<?php

// http://home.flynsarmy.com/flynsarmy/sociallogin/Google?s=/&f=/login
Route::get('flynsarmy/sociallogin/{provider}/{action?}', array("as" => "flynsarmy_sociallogin_provider", function($provider_name, $action = "")
{
	$success_redirect = Input::get('s', '/');
	$error_redirect = Input::get('f', '/login');

	$manager = Flynsarmy\SocialLogin\Classes\ProviderManager::instance();
	$provider_class = $manager->resolveProvider($provider_name);

	if ( !$provider_class )
		return Redirect::to($error_redirect)->withErrors("Unknown login provider: $provider_name.");

	$provider = $provider_class::instance();

	try {
		// This will contain [token => ..., email => ..., ...]
		$provider_response = $provider->login($provider_name, $action);
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

	// Grab the user associated with this provider. Creates or attach one if need be.
	$user = \Flynsarmy\SocialLogin\Classes\UserManager::instance()->find(
		$provider_details,
		$user_details
	);

	Auth::login($user);

	return Redirect::to($success_redirect);
}))->where(['provider' => '[A-Z][a-zA-Z ]+']);