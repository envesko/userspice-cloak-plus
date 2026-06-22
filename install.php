<?php

require_once 'init.php';
if (in_array($user->data()->id, $master_account)) {
    $db = DB::getInstance();
    include 'plugin_info.php';

    $check = $db->query('SELECT * FROM us_plugins WHERE plugin = ?', [$plugin_name])->count();
    if ($check > 0) {
        err($plugin_name.' has already been installed!');
    } else {
        $fields = [
            'plugin' => $plugin_name,
            'status' => 'installed',
        ];
        $db->insert('us_plugins', $fields);
        if (!$db->error()) {
            err($plugin_name.' installed');
            logger($user->data()->id, 'USPlugins', $plugin_name.' installed');
        } else {
            err($plugin_name.' was not installed');
            logger($user->data()->id, 'USPlugins', 'Failed to to install plugin, Error: '.$db->errorString());
        }
    }

    // Config table: banner toggle + how the quick-cloak picker lists users
    $db->query('CREATE TABLE IF NOT EXISTS cloakplus_config (
        id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
        BannerEnabled TINYINT(1) NOT NULL DEFAULT 1,
        ListOrder VARCHAR(20) NOT NULL DEFAULT "active",
        ListLimit INT(11) UNSIGNED NOT NULL DEFAULT 25,
        HideInactive TINYINT(1) NOT NULL DEFAULT 0
    )');
    if ($db->query('SELECT id FROM cloakplus_config WHERE id = 1')->count() == 0) {
        $db->insert('cloakplus_config', ['id' => 1, 'BannerEnabled' => 1, 'ListOrder' => 'active', 'ListLimit' => 25, 'HideInactive' => 0]);
    }

    $hooks = [];
    $hooks['logout']['body'] = 'hooks/logout.php';
    registerHooks($hooks, $plugin_name);
}
