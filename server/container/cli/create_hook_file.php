<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

global $CFG;
require_once("{$CFG->dirroot}/lib/clilib.php");

[$options, $params] = cli_get_params(
    [
        'help' => false,
        'classname' => null,
        'method' => null,
        'container' => null,
        'force' => false
    ],
    [
        'h' => 'help',
        'cls' => 'classname',
        'm' => 'method',
        'c' => 'container',
        'f' => 'force'
    ]
);

if (!empty($options['help'])) {
    echo "
This is a script to generate the hook file for your specific container component. Use this script
to generate the simple hook file for your container, which it will generate a list of redirection hooks and map it
with the provided classname::method callback for you.

Usage:
    php container/cli/create_hook_file.php -cls=\"your\\watcher\\classname\" -m=\"redirect_to_page\" -c=\"your_container\"
    
Options:
    -h, --help              Print out this help
    -cls, --classname       The hook's watcher class name
    -m, --method            The method for redirecting to from the hook.
    -c, --container         The container that you are generate this hook for.
    -f, --force             If the hook file is already existing, this parameter will rewrite the whole file.            
    ";

    return 0;
}

if (!isset($options['classname'])) {
    echo "Missing the callback class name\n";
    return 1;
}

if (!isset($options['method'])) {
    echo "Missing the method for callback\n";
    return 1;
}

if (!isset($options['container'])) {
    echo "Missing the container type\n";
    return 1;
}

$classname = $options['classname'];
$method = $options['method'];

$containertype = $options['container'];

if (!class_exists($classname) || !method_exists($classname, $method)) {
    echo "Classname and method are not existing in the system\n";
    return 1;
}

[$plugintype, $pluginname] = core_component::normalize_component($containertype);
$plugins = core_component::get_plugin_list('container');

if (!isset($plugins[$pluginname])) {
    echo "Invalid container type that is not existing in the system: '{$containertype}'\n";
    return 1;
}

$location = $plugins[$pluginname];
$file = "{$location}/db/hooks.php";

if (file_exists($file) && !$options['force']) {
    echo "The file '{$file}' had already existing in the system, please use '--force' to rewrite the file";
    return 1;
}

$hooknames = array_merge(
    hook_builder::get_redirect_hooks_from_plugins(),
    hook_builder::get_redirect_hooks_from_core_subsystems()
);

// Start building up the content file.

$year = date('y');
$content = "<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) {$year} onwards Totara Learning Solutions LTD
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
 * @author 
 * @package {$containertype}
 */
 defined('MOODLE_INTERNAL') || die();
\n\$watchers = [\n";
foreach ($hooknames as $hookname) {
    $content .= "    [\n";
    $content .= "        'hookname' => '{$hookname}',\n";
    $content .= "        'callback' => ['{$classname}', '{$method}']\n";
    $content .= "    ],\n";
}

$content .= "];";
$dir = dirname($file);

if (!file_exists($dir)) {
    mkdir($dir, 0755, true);
}

file_put_contents($file, $content);
echo "Generated a hook file '{$file}'\n";
return 0;