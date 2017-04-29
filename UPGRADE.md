# Upgrade guide

- [Upgrading to 1.0.16 from 1.0.15](#upgrade-1.0.16)

<a name="upgrade-1.0.16"></a>
## Upgrading to 1.0.16
Version 1.0.16 of the Social Login plugin is a significant rewrite with a few breaking changes. We've moved from a much older login provider library to [Laravel Socialite](https://github.com/laravel/socialite). This should provide reliability improvements when logging in solving many of the issues users were experiencing.

### Before Updating
* In Admin go to Settings - Social Login and copy your login providers' settings. Some of these may need to be re-entered. You can just paste the same details back in again.

### After Updating
* Paste your copied login provider settings back in the Settings - Social Login area of admin if they've disappeared.
* If you have any third party plugins extending Social Login by adding extra login providers, they may need to require in new dependencies if they were relying on the old packages Social Login used. Your developer will need to handle this one. If you only see Google, Facebook and Twitter in Settings - Social Login area of admin then you probably don't need to worry about this.

### Other FAQ
* No changes are needed on the frontend of your site.
* Frontend users will not need to reassociate their social accounts with your site. Everything will keep working for them provided you follow the backend steps above.