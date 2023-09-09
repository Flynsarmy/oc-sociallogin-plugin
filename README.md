# October CMS Social Login Plugin
## Plugin Requirments
  1. RainLab User Plugin
  2. Hybridauth Laravel <a href="https://packagist.org/packages/hybridauth/hybridauth">Click to get</a>
### Social Logins
  1. Google
  2. Facebook
  3. Twitter

## How to set the project on your October CMS project
  1. Setup your October CMS Project
  2. Create a folder on your ``Plugin`` Folder name as ```flynsarmy```
  3. `cd` into that folder and clone the project using `HTTPS` or `SSH`
  4. Rename the Cloned projects folder name to ```sociallogin```
  5. Installing the RainLab User plugin to your project
  6. Open a new terminal on the project main directory you can do it using
        1. On Windows `Ctrl +`
        2. On Mac `Command + J`
  8. Enter the command to run the migration on the plugin ```php artisan october:migrate```
  9. Next use this```composer require hybridauth/hybridauth``` command and install the `Hybridauth Laravel` library.
  10. Now you can access the login by ```<YOUR_OCTOBER_CMS_URL>/flynsarmy/sociallogin/Google?s=/&f=/login```
        o Example: http://example.com/flynsarmy/sociallogin/Google?s=/&f=/login

