# Cloak+ - AI instructions

Improved admin impersonation ("cloaking") for UserSpice: a helper function with options plus a logout
hook to return to the original user. Maintained by Envesko.

## Public functions (functions.php)
- `CloakPlus_Cloak($cloakTo, $opts = [])` - cloak the current master user into user `$cloakTo`.
  Validates: logged in, valid + non-self cloakee, cloaker in `$master_account`, cloakee not master
  (unless `allow_master_cloaking`). Stores the original destination + a logout-uncloak flag in the
  session. Opts include `skip_master_check`, `allow_master_cloaking`, `do_not_store_original_dest`,
  `disable_logout_uncloak`, `no_redirect_on_success`. Opts accept the **associative** form
  (`['allow_master_cloaking' => true]`) or the legacy positional form - see `CloakPlus_opt()`.
- `CloakPlus_Uncloak($opts = [])` - end the cloak + restore the admin WITHOUT logging out (used by the
  banner and `files/uncloak.php`). Prefers core `endCloak()`.
- `cloakPlusBannerEnabled()` - whether the persistent banner shows.
- The actual user switch is core-driven (User.php reads `$_SESSION['..._cloak_to']`); Cloak+ uses core
  `setCloakSession()` / `endCloak()` / `isCloaked()` / `cloakTo()` / `cloakFrom()` rather than rolling its own.

## Tables
`cloakplus_config` - single row (`BannerEnabled`). Dropped on delete.

## UI
`configure.php` = banner toggle + a searchable quick-cloak user picker. `footer.php` renders the
"you are cloaked" banner (fixed bottom bar, one-click return). `files/uncloak.php` = CSRF-gated uncloak.

## Lifecycle files
install (config table + logout hook)/activate/uninstall (retain)/delete (drop table)/migrate/configure
· `hooks/logout.php` (uncloak on logout via `endCloak()`) · `override.php`.

## Key conventions
All cloak actions are gated on `$master_account` membership and logged via `logger`. Bumped to
UserSpice 6.1.0. `update.php` is deprecated (stub); future changes go in `migrate.php`.
