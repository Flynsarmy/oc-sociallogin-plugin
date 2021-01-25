<?php

namespace Flynsarmy\SocialLogin\Models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'flynsarmy_sociallogin_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

    protected $cache = [];
}
