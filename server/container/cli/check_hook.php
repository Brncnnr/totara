<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_container
 */
define('CLI_SCRIPT', true);
require_once(__DIR__ . "/../../config.php");

use core_container\hook_builder;
use core_container\hook\module_supported_in_container;
use core\command\executable;

global $CFG;
require_once("{$CFG->dirroot}/lib/clilib.php");

[$options, $params] = cli_get_params(
    [
        'help' => false,
        'container' => null,
    ],
    [
        'h' => 'help',
        'c' => 'container',
    ]
);

if (!empty($options['help'])) {
    echo "
This is a script to check whether you have added the watcher callback for most of the hooks 
in order to keep your container working as expected.

Note that this script is for developers only.
    
Usage:
    php container/cli/check_hook.php -c=\"your_container\"
    
Options:
    -h, --help              Print out this help
    -c, --container         The container that you are generating this hook for.
    ";

    return 0;
}

if (!isset($options['container'])) {
    echo "Please set the container to check the hook file\n";
    return 1;
}

$container_type = $options['container'];
[$plugintype, $pluginname] = core_component::normalize_component($container_type);
$plugins = core_component::get_plugin_list('container');

if (!isset($plugins[$pluginname])) {
    echo "Invalid container type that does not exist in the system: '{$container_type}'\n";
    return 1;
}

$hooknames = array_merge(
    hook_builder::get_redirect_hooks_from_plugins(),
    hook_builder::get_redirect_hooks_from_core_subsystems()
);

// All the special hooks that are not for redirecting or not coupled with the course record/id.
$hooknames[] = module_supported_in_container::class;

$location = $plugins[$pluginname];
$file = "{$location}/db/hooks.php";
$watchers = [];

if (file_exists($file)) {
    // Load hook file so that we can start checking.
    require($file);
}

$usages = [];
foreach ($watchers as $watcher) {
    if (array_key_exists('hookname', $watcher)) {
        $usages[] = $watcher['hookname'];
    }
}

$is_window = executable::is_windows();
// For the color of the text, please refer to this site https://misc.flogisoft.com/bash/tip_colors_and_formatting
foreach ($hooknames as $hookname) {
    $result = "\e[31mNo\e[0m";
    if ($is_window) {
        // Windows will not have this feature :)
        $result = 'No';
    }

    if (in_array($hookname, $usages)) {
        $result = "\e[32mYes\e[0m";
        if ($is_window) {
            // Windows will not have this feature :)
            $result = 'Yes';
        }
    }

    echo str_pad($hookname, 80) . " - {$result}\n";
}

return 0;