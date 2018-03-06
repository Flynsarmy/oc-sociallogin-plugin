<?php namespace Flynsarmy\SocialLogin\Classes;

use Auth;
use BackendAuth;
use Event;
use October\Rain\Auth\Models\User;
use Flynsarmy\SocialLogin\Models\Provider;

class UserManager
{
	use \October\Rain\Support\Traits\Singleton;

    /**
     * Finds a backend user by the email address.
     * @param string $email
     */
    public function findBackendUserByEmail($email)
    {
        $query = $this->createBackendUserModelQuery();
        $user = $query->where('email', $email)->first();
        return $user ?: null;
    }

    /**
     * Prepares a query derived from the user model.
     */
    protected function createBackendUserModelQuery()
    {
        $model = BackendAuth::createUserModel();
        $query = $model->newQuery();
        BackendAuth::extendUserQuery($query);
        return $query;
    }

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
			{
				// Register the user
				$user = $this->registerUser($provider_details, $user_details);
			}
			// User was found - attach provider
			else
				$user = $this->attachProvider($user, $provider_details);
		}
		// Provider was found, return the attached user
		else
		{
			$user = $provider->user;

			// The user may have been deleted. Make sure this isn't the case
			if ( !$user )
			{
				$provider->delete();
				return $this->find($provider_details, $user_details);
			}
		}

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
        // Support custom login handling
        $user = Event::fire('flynsarmy.sociallogin.registerUser', [
            $provider_details, $user_details
        ], true);
        if ( $user ){
			$this->attachAvatar($user, $user_details);
            return $user;
		}	

	    // Create a username if one doesn't exist
		if ( !isset($user_details['username']) )
		    $user_details['username'] = $user_details['email'];

        // Generate a random password for the new user
		$user_details['password'] = $user_details['password_confirmation'] = str_random(16);

		$user = Auth::register($user_details, true);
		$this->attachAvatar($user, $user_details);
		
		return $this->attachProvider($user, $provider_details);
	}
	
	
	/**
	 * Attach avatar to a user
	 *
	 * @param  User   $user
	 * @param  array $user_details       ['email'=>..., ...]
	 *
	 */
	 
	public function attachAvatar(User $user, array $user_details)
	{
			
			if (array_key_exists("avatar_original",$user_details)){
				$thumbOptions = [
						'mode'      => 'crop',
						'extension' => 'auto'
				];
				
				if (!empty($user_details['avatar_original'])){
					$file = new File;
					$saveto = tempnam($file->getTempPath(), 'user_id_'.$user->id.'_avatar');
					$saveToImage = $saveto.'.jpg';
					rename($saveto, $saveToImage); 
					self::grab_image($user_details['avatar_original'], $saveToImage);
					
					$file->data = $saveToImage; 
		
					if ($file->data && filesize($saveToImage)>0){
						$thumb = $file->getThumb('160', '160', $thumbOptions);	
						$file->save();
						$user->avatar()->add($file);
						$file->pathUrl = $file->getPath();
						$file->thumbUrl = $thumb;
					}	
				}	
			}	

	}
	
	
	/**
	 * grab image and store locally 
	 *
	 * @param  $url
	 * @param  $saveto
	 */
	public static function grab_image($url,$saveto){
		
		if (empty($url) || empty($saveto))
			return;

		$ch = curl_init ($url);
		
		if (strpos($url, 'facebook.com') !== false) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
		}

	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	    $raw=curl_exec($ch);
	    curl_close ($ch);
	    if(file_exists($saveto)){
	        unlink($saveto);
	    }
	    $fp = fopen($saveto,'x');
	    fwrite($fp, $raw);
	    fclose($fp);
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
		$user->flynsarmy_sociallogin_providers()
			->where('provider_id', $provider_details['provider_id'])
			->delete();

		$provider = new Provider($provider_details);
		$provider->user = $user;
		$provider->save();

		return $user;
	}
}