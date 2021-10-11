<?php

namespace Flynsarmy\SocialLogin\Classes;

use Auth;
use BackendAuth;
use Event;
use Flash;
use Exception;
use Lang;
use Log;
use Flynsarmy\SocialLogin\Models\Provider;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use October\Rain\Auth\Models\User;
use Hybridauth\User\Profile;
use RainLab\User\Models\Settings as UserSettings;
use System\Models\File;

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
     * @param  array   $provider_details   ['id'=>..., 'token'=>...]
     * @param  Profile $user_details
     *
     * @throws Exception
     *
     * @return User
     */
    public function find(array $provider_details, Profile $user_details)
    {
        // Are we already attached?
        $provider = $this->findProvider($provider_details);

        if (!$provider) {
            // Does a user with this email exist?
            $user = Auth::findUserByLogin($user_details->email);
            // No user with this email exists - create one
            if (!$user) {
                if (UserSettings::get('allow_registration')) {
                    // Register the user
                    $user = $this->registerUser($provider_details, $user_details);
                } else {
                    Flash::warning(Lang::get('rainlab.user::lang.account.registration_disabled'));
                    return $user;
                }

            // User was found - attach provider
            } else {
                $user = $this->attachProvider($user, $provider_details);
            }

        // Provider was found, return the attached user
        } else {
            $user = $provider->user;

            // The user may have been deleted. Make sure this isn't the case
            if (!$user) {
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
        return Provider::where([
            'provider_id' => $provider_details['provider_id'],
            'provider_token' => json_encode($provider_details['provider_token'])
        ])->first();
    }

    /**
     * Register a new user with given details and attach a provider to them.
     *
     * @param  array   $provider_details   ['id'=>..., 'token'=>...]
     * @param  Profile $user_details
     *
     * @return User
     */
    public function registerUser(array $provider_details, Profile $user_details)
    {
        // Support custom login handling
        $user = Event::fire('flynsarmy.sociallogin.registerUser', [
            $provider_details, $user_details
        ], true);

        if ($user && $user instanceof User) {
            $this->attachAvatar($user, $user_details);
            return $user;
        }

        $new_password = str_random(16);

        $email = $user_details->email;
        // Some login providers don't return an email address. Use their
        // identifier with @dev.null instead.
        if (!$email) {
            $email = $user_details->identifier . '@dev.null';
        }

        $new_user = [
            'name' => $user_details->firstName,
            'surname' => $user_details->lastName,
            'email' => $email,
            'username' => $email,
            'password' => $new_password,
            'password_confirmation' => $new_password,
            'phone' => $user_details->phone,
        ];

        $user = Auth::register($new_user, true);
        $this->attachAvatar($user, $user_details);

        return $this->attachProvider($user, $provider_details);
    }


    /**
     * Attach avatar to a user
     *
     * @param  User    $user
     * @param  Profile $user_details
     *
     */

    public function attachAvatar(User $user, Profile $user_details)
    {
        if ($user_details->photoURL) {
            $thumbOptions = [
                    'mode'      => 'crop',
                    'extension' => 'auto'
            ];

            if (!empty($user_details->avatar_original)) {
                $file = new File();
                $saveto = tempnam($file->getTempPath(), 'user_id_' . $user->id . '_avatar');
                $saveToImage = $saveto . '.jpg';
                rename($saveto, $saveToImage);
                self::grabImage($user_details->photoURL, $saveToImage);

                $file->data = $saveToImage;

                if ($file->data && filesize($saveToImage) > 0) {
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
    public static function grabImage($url, $saveto)
    {
        $client = new Client();

        try {
            $profileResponse = $client->get($url);
            $profilePicture = $profileResponse->getBody();

            if (file_exists($saveto)) {
                unlink($saveto);
            }
            $fp = fopen($saveto, 'x');
            fwrite($fp, $profilePicture);
            fclose($fp);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                Log::error(Psr7\str($e->getResponse()));
            } else {
                Log::error($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        }
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
