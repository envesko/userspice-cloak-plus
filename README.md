# Cloak+ for UserSpice

This plugin improves the built in cloaking for UserSpice by adding a helper function to cloak with a number of options, and a logout hook to use this as a secondary method of returning to the original user.

## Features

- Callable function for easy cloaking with security checks
- Automatic redirection after cloaking
- Storage and redirection to original destination after cloaking session ended
- Logout button will double as an uncloak button
- Function option overrides including master account check skips, and modifications to the plugins core features

UserSpice can be downloaded from their [website](https://userspice.com/) or on [GitHub](https://github.com/mudmin/UserSpice5).

## Setting Up

### Recommended (via Spice Shaker)

1. Visit Spice Shaker in your Admin Dashboard
2. Search for "IPCheck CloudFlare"
3. Press "Download"
4. Press "Checkout/Install"
5. Enjoy :)

### Manually

1. Download the Release ZIP
2. Upload the Plugin Folder to `usersc/plugins`
3. Visit the Plugin Manager
4. Press "Install Plugin"
5. Enjoy :)

## Example Usage

The `CloakPlus_Cloak` function will return a `state` which can be accessed through the `array`, and in the event the `state` is `false`, it likely will return an `error` as well. If cloaking is successful, the plugin will redirect the user automatically to the homepage. If you disable this, it will return a `state` of `true`. The `state` will always be a `boolean`.

| Behavior                                                                                                                     | Override                     | Error                         |
| ---------------------------------------------------------------------------------------------------------------------------- | ---------------------------- | ----------------------------- |
| Check if there is a user logged in                                                                                           | _None_                       | `not_logged_in`               |
| Check if the provided `cloakTo` variable is an `int` (or can be converted) and is a valid User ID                            | _None_                       | `cloakee_invalid`             |
| Checks if the provided `cloakTo` variable is the same as the cloaker's ID                                                    | _None_                       | `cloakee_is_self`             |
| Check if the cloaker is in the [Master Account Array](https://userspice.com/master-account/)                                 | `skip_master_check`          | `cloaker_not_in_master_array` |
| Check if the cloakee (person being cloaked to) is _not_ in the [Master Account Array](https://userspice.com/master-account/) | `allow_master_cloaking`      | `cloakee_in_master_array`     |
| Store URI that the user cloaked from, to redirect them back to after                                                         | `do_not_store_original_dest` | _None_                        |
| Logging out triggers uncloaking                                                                                              | `disable_logout_uncloak`     | _None_                        |
| Auto redirect on cloaking                                                                                                    | `no_redirect_on_success`     | _None_                        |

### Basic Usage

```php
# Placeholder Variables
$userId = 100;
$errors = [];

$cloak = CloakPlus_Cloak($userId);
if (!$cloak['state']) {
    $errors[] = $cloak['error'] ?? 'There was an error processing your request';
}
```

### Advanced Usage

#### Disable Logout Uncloak Feature

```php
# Placeholder Variables
$userId = 100;
$errors = [];

$cloak = CloakPlus_Cloak($userId, ['disable_logout_uncloak']);
if (!$cloak['state']) {
    $errors[] = $cloak['error'] ?? 'There was an error processing your request';
}
```

#### Skip Master Check (Allows Other Users)

```php
# Placeholder Variables
$userId = 100;
$errors = [];

$cloak = CloakPlus_Cloak($userId, ['skip_master_check']);
if (!$cloak['state']) {
    $errors[] = $cloak['error'] ?? 'There was an error processing your request';
}
```

#### Preventing Redirect Loops During Uncloaking

In some instances, the URL you may have cloaked from could cause a redirect loop if you use URL parameters to trigger the cloaking. For example, if you cloak from https://example.com/user/bobsmith/cloak/, it will cause a redirect loop. If you still want to redirect them back to a URL other than the homepage however, you can do this by setting the `REQUEST_URI` to something else. In the above URL, we'll want to drop the `cloak/`, so we'll do that using the below code:

```php
# Placeholder Variables
$userId = 100;
$errors = [];

$request_uri_override = $_SERVER['REQUEST_URI'];
$request_uri_override = explode('cloak/', $request_uri_override);
$_SERVER['REQUEST_URI'] = $request_uri_override[0];

$cloak = CloakPlus_Cloak($userId, ['skip_master_check']);
if (!$cloak['state']) {
    $errors[] = $cloak['error'] ?? 'There was an error processing your request';
}
```

If you simply want to strip out regular URL parameters, eg. `?cloak=1`, we'll do this:

```php
# Placeholder Variables
$userId = 100;
$errors = [];

$request_uri_override = $_SERVER['REQUEST_URI'];
$request_uri_override = explode('?', $request_uri_override);
$_SERVER['REQUEST_URI'] = $request_uri_override[0];

$cloak = CloakPlus_Cloak($userId, ['skip_master_check']);
if (!$cloak['state']) {
    $errors[] = $cloak['error'] ?? 'There was an error processing your request';
}
```

Note, anything else that depends on `REQUEST_URI` before the user is redirected into the cloaked session will be using this new URI that you set. This is the only way to handle a modified redirection at the time of writing, however.

## Questions or Issues

If you have any issues please open an issue here on GitHub. This includes feature requests. If you wish to resolve an issue, you may complete a pull request. Please do not make a pull request for features without opening an issue first.

Pull Requests are expected to be validated with PHP CS Fixer using the following standards. A config file for this is included in the repo.

```
@PSR2, @Symfony, -phpdoc_annotation_without_dot, -phpdoc_no_alias_tag, -phpdoc_separation, -yoda_style
```

Any help with UserSpice can be asked in their [Discord](https://discord.gg/j25FeHu).

---

© 2026 **Envesko** - released under the MIT License (see `LICENSE`). Author, maintainer, and code owner: Envesko. Formerly maintained by Brandin Arsenault.

## v1.2.0 - UI, banner, uncloak
- Quick-cloak picker on the config page (search users, one-click Cloak).
- Persistent "you are cloaked" banner (toggle in settings) with one-click **Return to your account** - no logout needed (`CloakPlus_Uncloak()` / `files/uncloak.php`).
- Options accept an associative array (`["allow_master_cloaking" => true]`); legacy positional still works.
- Logout-uncloak now clears the namespaced cloak keys via core `endCloak()`.
