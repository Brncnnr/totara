<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\orm\query\builder;
use mod_approval\entity\form\form;
use mod_approval\entity\form\form_version;
use mod_approval\form_schema\form_schema;
use mod_approval\model\form\approvalform_base;
use mod_approval\plugininfo\approvalform;

define('CLI_SCRIPT', 'yes!');

require(__DIR__ . '/../../server/config.php');
global $CFG;
/** @var core_config $CFG */

require_once($CFG->libdir.'/clilib.php');

[$options, $filters] = cli_get_params(
    ['help' => false, 'agree' => false],
    ['h' => 'help', 'a' => 'agree', 'f' => 'agree']
);

if (!empty($options['help'])) {
    $basename = basename(__FILE__);
    cli_writeln("Update database records to the latest form schema by reloading a schema file of each plugin.

Usage:
    php {$basename} {-a|-f|--agree} [PLUGIN[=FILE]] ...

Required parameters:
    -f, -a, --agree       Agree to the destructive operation

Options:
    -h, --help            Print out this help
    PLUGIN=FILE           List of plugins to be processed
                          If no plugins are specified, all plugins will be processed
                          If FILE is not specified, the plugin's default schema is used

Example:
  $ php {$basename} --agree
  $ php {$basename} --agree simple
  $ php {$basename} --agree sf182 simple=\"\$PWD/server/mod/approval/tests/fixtures/schema/test1.json\"
");
    exit(2);
}
if (empty($options['agree'])) {
    cli_error(
        cli_logo(5, true) . PHP_EOL .
        str_repeat(' ', 9) . 'A required parameter is missing.' . PHP_EOL .
        str_repeat(' ', 6) . 'Please pass `--help` to see the help.' . PHP_EOL .
        'Also this script is for development purposes only.',
        1
    );
}

$plugins = [];
$pluginnames = array_keys(approvalform::get_enabled_plugins());
if (!empty($filters)) {
    foreach ($filters as $filter) {
        if (strpos($filter, '=') !== false) {
            [$plugin, $path] = explode('=', $filter, 2);
            $plugins[$plugin] = $path;
        } else {
            $plugins[$filter] = null;
        }
    }
    $pluginnames = $pluginnames = array_intersect(array_keys($plugins), $pluginnames);
}

foreach ($pluginnames as $pluginname) {
    $ids = builder::table(form_version::TABLE, 'fv')
        ->join([form::TABLE, 'f'], 'f.id', 'fv.form_id')
        ->where('f.plugin_name', $pluginname)
        ->select('fv.id')
        ->get()
        ->keys();
    $plugin = approvalform_base::from_plugin_name($pluginname);
    $what = 'default schema';
    $record = ['json_schema' => $plugin->get_form_schema_json()];
    if (isset($plugins[$pluginname])) {
        $json_schema = file_get_contents($plugins[$pluginname]);
        if (!$json_schema) {
            cli_error("Failed to read '{$plugins[$pluginname]}'", 3);
        }
        $what = "file '{$plugins[$pluginname]}'";
        $record['json_schema'] = $json_schema;
    }
    try {
        $record['json_schema'] = form_schema::from_json($record['json_schema'])->to_json();
    } catch (JsonException $ex) {
        cli_error("Failed to parse {$what}, error: {$ex->getMessage()}", 3);
    }
    $version = $plugin->get_form_version();
    if ($version !== null) {
        $record['version'] = $version;
    }
    builder::table(form_version::TABLE)->where_in('id', $ids)->update($record);
    echo "Updated {$pluginname} with {$what}.\n";
}
