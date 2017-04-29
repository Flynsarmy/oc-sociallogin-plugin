<?php namespace Flynsarmy\SocialLogin\Updates;

use Flynsarmy\SocialLogin\Models\Settings;
use October\Rain\Database\Updates\Migration;

/**
 * We've changed some settings names around so update from old to
 * new automatically for the admin.
 *
 * Class UpdateProviderSettingsLocations1016
 * @package Flynsarmy\SocialLogin\Updates
 */
class UpdateProviderSettingsLocations1016 extends Migration
{
    protected $mapping = [
        'Twitter.api_key' => 'Twitter.identifier',
        'Twitter.api_secret' => 'Twitter.secret',
        'Facebook.app_id' => 'Facebook.client_id',
        'Facebook.app_secret' => 'Facebook.client_secret',
    ];

    public function up()
    {
        $settings = Settings::instance();

        $providers = $settings->get('providers', []);

        foreach ( $this->mapping as $old => $new )
            if ( ($old_val=array_get($providers, $old)) )
                array_set($providers, $new, $old_val);


        $settings->set('providers', $providers);
    }

    public function down()
    {
        $settings = Settings::instance();

        $providers = $settings->get('providers', []);

        foreach ( $this->mapping as $new => $old )
            if ( ($old_val=array_get($providers, $old)) )
                array_set($providers, $new, $old_val);

        $settings->set('providers', $providers);
    }
}
