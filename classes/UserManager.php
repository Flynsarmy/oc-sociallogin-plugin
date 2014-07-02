<?php namespace Flynsarmy\SocialLogin\Classes;

use Auth;
use October\Rain\Auth\Models\User;
use Flynsarmy\SocialLogin\Models\Provider;

class UserManager
{
	use \October\Rain\Support\Traits\Singleton;

	/**
	 * Finds and returns the user attached to the given provider. If one doesn't
	 * exist, it's created. If one exists but isn't attached, it's attached.
	 *
	 * @param  array $provider_details   ['id'=>..., 'token'=>...]
	 * @param  array $user_details       ['email'=>..., ...]
	 *
	 * @return User
	 */
	public function find(array $provider_details, array $user_details)
	{
		// Are we already attached?
		$provider = $this->findProvider($provider_details);

		if ( !$provider )
		{
			// Does a user with this email exist?
			$user = Auth::findUserByLogin( $user_details['email'] );
			// No user with this email exists - create one
			if ( !$user )
				$user = $this->registerUser($provider_details, $user_details);
			// User was found - attach provider
			else
				$user = $this->attachProvider($user, $provider_details);
		}
		// Provider was found, return the attached user
		else
			$user = $provider->user;

		return $user;
	}

	/**
	 * Looks for a provider with given name and token
	 *
	 * @param  array $provider_details  ['id'=>..., 'token'=>...]
	 *
	 * @return Provider on sucess, null on fail
	 */
	public function findProvider(array $provider_details)
	{
		return Provider::where('provider_id', '=', $provider_details['provider_id'])
			->where('provider_token', '=', $provider_details['provider_token'])
			->first();
	}

	/**
	 * Register a new user with given details and attach a provider to them.
	 *
	 * @param  array $provider_details   ['id'=>..., 'token'=>...]
	 * @param  array $user_details       ['email'=>..., ...]
	 *
	 * @return User
	 */
	public function registerUser(array $provider_details, array $user_details)
	{
		// Generate a random password for the new user
		$user_details['password'] = $user_details['password_confirmation'] = str_random(16);

		$user = Auth::register($user_details, true);
		return $this->attachProvider($user, $provider_details);
	}

	/**
	 * Attach a provider to a user
	 *
	 * @param  User   $user
	 * @param  array  $provider_details ['id'=>..., 'token'=>...]
	 *
	 * @return User
	 */
	public function attachProvider(User $user, array $provider_details)
	{
		$user->flynsarmy_sociallogin_providers()->add(new Provider($provider_details));
		$user->save();

		return $user;
	}
}