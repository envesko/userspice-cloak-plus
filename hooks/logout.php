<?php

global $user, $us_url_root;

// On logout, return to the original admin instead of logging out - unless explicitly disabled.
$disableLogout = Session::get('CloakPlus_disable_logout_uncloak');
$cloaked = function_exists('isCloaked') ? isCloaked() : (Session::get('cloak_to') != null);

if ($disableLogout == null && $cloaked) {
    $cloak_dest = Session::get('CloakPlus_dest');
    // endCloak() restores the admin session and clears BOTH namespaced + legacy keys (important:
    // the old code only deleted the legacy keys, leaving a namespaced cloak alive after logout).
    if (function_exists('endCloak')) {
        $res = endCloak();
        $ok = !empty($res['ok']);
    } else {
        $cloakFrom = Session::get('cloak_from');
        $ok = ($cloakFrom != null);
        if ($ok) {
            Session::delete('cloak_to');
            Session::delete('cloak_from');
            Session::put(Config::get('session/session_name'), $cloakFrom);
        }
    }

    if ($ok) {
        Session::delete('CloakPlus_dest');
        Session::delete('CloakPlus_disable_logout_uncloak');
        if ($cloak_dest != null) {
            Redirect::to("{$us_url_root}{$cloak_dest}");
        } else {
            Redirect::to($us_url_root);
        }
        exit();
    } else {
        logger($user->data()->id, 'Cloaking', 'Uncloak Failed', ['ERROR' => 'no_cloak_from']);
    }
}
