<?php
// Ends the current cloak and returns to the original admin account. CSRF-protected.
require_once '../../../../users/init.php';

if (!Token::check(Input::get('csrf'))) {
    Redirect::to($us_url_root);
}

if (function_exists('CloakPlus_Uncloak')) {
    CloakPlus_Uncloak(); // restores the admin session + redirects to the original destination
}

Redirect::to($us_url_root);
