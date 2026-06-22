<?php

/**
 * Cloak+ (Envesko) - improved admin impersonation for UserSpice.
 *
 * The actual user switch is core-driven: User.php loads the user in $_SESSION['..._cloak_to'].
 * These helpers wrap that with options, a persistent banner, and uncloak-without-logout.
 */

if (!function_exists('CloakPlus_opt')) {
    /**
     * Read an option flag. Supports BOTH the modern associative form
     * (['allow_master_cloaking' => true]) and the legacy positional form (['allow_master_cloaking']).
     */
    function CloakPlus_opt($opts, $key)
    {
        if (!is_array($opts)) {
            return false;
        }
        if (array_key_exists($key, $opts)) {
            return (bool) $opts[$key];
        }
        return in_array($key, $opts, true);
    }
}

if (!function_exists('CloakPlus_Cloak')) {
    /**
     * Cloak the current (master) user into $cloakTo.
     * $opts (associative preferred; legacy positional still accepted):
     *   skip_master_check, allow_master_cloaking, do_not_store_original_dest,
     *   disable_logout_uncloak, no_redirect_on_success.
     * Returns ['state' => bool, 'error' => string|null].
     */
    function CloakPlus_Cloak($cloakTo, $opts = [])
    {
        global $user, $master_account, $us_url_root;

        $return = ['state' => false];

        if (!$user->isLoggedIn()) {
            $return['error'] = 'not_logged_in';
            return $return;
        }

        $cloakTo = (int) $cloakTo;
        if ($cloakTo === 0 || !userIdExists($cloakTo)) {
            $return['error'] = 'cloakee_invalid';
            return $return;
        }

        if ($user->data()->id == $cloakTo) {
            $return['error'] = 'cloakee_is_self';
            return $return;
        }

        // Permission: defer to the core gate so Cloak+ matches system-level cloaking exactly
        // (cloak_allowed == 1 OR a master account; never into a master unless you are one).
        // Falls back to the legacy master-only logic on pre-canCloak installs.
        if (function_exists('canCloak')) {
            if (!CloakPlus_opt($opts, 'skip_master_check')) {
                $reason = '';
                if (!canCloak($cloakTo, $reason)) {
                    $return['error'] = $reason ?: 'permission_denied';
                    return $return;
                }
            }
        } else {
            if (!CloakPlus_opt($opts, 'skip_master_check') && !in_array($user->data()->id, $master_account)) {
                $return['error'] = 'cloaker_not_in_master_array';
                return $return;
            }
            if (!CloakPlus_opt($opts, 'allow_master_cloaking') && in_array($cloakTo, $master_account)) {
                $return['error'] = 'cloakee_in_master_array';
                return $return;
            }
        }

        $fromId = (int) $user->data()->id;

        // Perform the cloak. Prefer the core helper (sets BOTH namespaced + legacy keys); fall back
        // to the legacy keys directly on very old installs.
        if (function_exists('setCloakSession')) {
            setCloakSession($fromId, $cloakTo);
        } else {
            Session::put('cloak_from', $fromId);
            Session::put('cloak_to', $cloakTo);
        }

        if (!CloakPlus_opt($opts, 'do_not_store_original_dest')) {
            $request_uri = Server::get('REQUEST_URI');
            $request_uri = explode($us_url_root, $request_uri);
            if (($request_uri[1] ?? null) != null) {
                Session::put('CloakPlus_dest', $request_uri[1]);
            }
        }

        if (CloakPlus_opt($opts, 'disable_logout_uncloak')) {
            Session::put('CloakPlus_disable_logout_uncloak', true);
        }

        logger($fromId, 'Cloaking', "Cloaked into {$cloakTo}");
        $return['state'] = true;

        if (CloakPlus_opt($opts, 'no_redirect_on_success')) {
            return $return;
        }
        Redirect::to($us_url_root);
    }
}

if (!function_exists('CloakPlus_Uncloak')) {
    /**
     * End the current cloak and restore the original (admin) user - without logging out.
     * $opts: no_redirect_on_success, ignore_dest.
     * Returns ['state' => bool, 'error' => string|null].
     */
    function CloakPlus_Uncloak($opts = [])
    {
        global $us_url_root;
        $return = ['state' => false];

        if (function_exists('isCloaked') && !isCloaked()) {
            $return['error'] = 'not_cloaked';
            return $return;
        }

        $dest = Session::get('CloakPlus_dest');

        if (function_exists('endCloak')) {
            endCloak(); // restores admin session, clears namespaced + legacy keys, logs + fires cloakEnd hook
        } else {
            // Legacy fallback
            $from = Session::get('cloak_from');
            if ($from != null) {
                Session::put(Config::get('session/session_name'), $from);
            }
            Session::delete('cloak_to');
            Session::delete('cloak_from');
        }
        Session::delete('CloakPlus_dest');
        Session::delete('CloakPlus_disable_logout_uncloak');

        $return['state'] = true;
        if (CloakPlus_opt($opts, 'no_redirect_on_success')) {
            return $return;
        }
        if ($dest && !CloakPlus_opt($opts, 'ignore_dest')) {
            Redirect::to($us_url_root . $dest);
        }
        Redirect::to($us_url_root);
    }
}

if (!function_exists('cloakPlusConfig')) {
    function cloakPlusConfig()
    {
        global $db;
        static $cfg = null;
        if ($cfg !== null) {
            return $cfg ?: null;
        }
        try {
            $q = $db->query('SELECT * FROM cloakplus_config WHERE id = 1');
            $cfg = $q->count() ? $q->first() : false;
        } catch (Exception $e) {
            $cfg = false;
        }
        return $cfg ?: null;
    }
}

if (!function_exists('cloakPlusBannerEnabled')) {
    function cloakPlusBannerEnabled()
    {
        $cfg = cloakPlusConfig();
        return $cfg ? ((int) $cfg->BannerEnabled === 1) : true; // default on
    }
}

if (!function_exists('cloakPlusCanUse')) {
    /**
     * Does the current user have system-level cloaking permission? Mirrors core canCloak()'s base
     * rule (cloak_allowed == 1 OR a master account). Used to gate access to the plugin config page so
     * only people who could cloak from core can reach the Cloak+ UI.
     */
    function cloakPlusCanUse()
    {
        global $user, $master_account;
        if (!isset($user) || !$user->isLoggedIn()) {
            return false;
        }
        if (in_array($user->data()->id, $master_account)) {
            return true;
        }
        return isset($user->data()->cloak_allowed) && (int) $user->data()->cloak_allowed === 1;
    }
}
