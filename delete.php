<?php

require_once 'init.php';
// Called only when the plugin is actively DELETED (not on deactivate).
if (in_array($user->data()->id, $master_account)) {
    $db = DB::getInstance();
    include 'plugin_info.php';
    $db->query('DROP TABLE IF EXISTS cloakplus_config');
    logger($user->data()->id, 'USPlugins', $plugin_name . ' deleted (cloakplus_config dropped)');
}
