<?php
/*
 * This file is part of Totara TXP
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 */

use GraphQL\Utils\SchemaPrinter;
use totara_api\cli\metadata_helper;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;
use totara_webapi\graphql;

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'help'    => false,
        'quiet'   => false,
    ),
    array(
        'h' => 'help',
        'q' => 'quiet',
    )
);

// Do this even when run in quiet mode, to help with debugging.
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    $help =
        "Executes PHP tasks needed to prepare for API doc generation. This task must be run before the API docs build script.

php ./server/totara/api/cli/prep_api_docs.php

Options:
-h, --help            Print out this help
-q, --quiet           Echo only the file path to the generated docs

";

    echo $help;
    exit(0);
}

$api_data_directory = $CFG->dataroot . '/api';
if (!is_dir($api_data_directory)) {
    mkdir($api_data_directory);
}
if (!is_writable($api_data_directory)) {
    echo "Cannot write to directory '{$api_data_directory}'.";
    exit(0);
}

$types = endpoint_type_factory::get_all_types();
foreach ($types as $type_class) {
    $type = endpoint_type_factory::get_instance_by_class_name($type_class);

    $schema = build_schema($type);
    $schema_filename = $api_data_directory . '/schema.' . $type::get_name() . '.graphqls';
    file_put_contents($schema_filename, $schema);

    $metadata = build_metadata($type);
    // Save as single metadata.type.json file in dataroot/api/
    $metadata_filename = $api_data_directory . '/metadata.' . $type::get_name() . '.json';
    file_put_contents($metadata_filename, $metadata);
}

$nav_js_contents = build_docs_nav();
file_put_contents($api_data_directory . '/nav.js', $nav_js_contents);

if ($options['quiet']) {
    cli_write($api_data_directory);
} else {
    cli_writeln("Schema definitions and metadata saved in {$api_data_directory}");
}

function build_schema($type) {
    // Purge schema cache
    $cache = \cache::make('totara_webapi', 'schema');
    $cache_key = 'parsed_schema_' . $type::get_name();
    $cache->delete($cache_key);

    $schema = graphql::get_schema($type);
    $text_schema = SchemaPrinter::doPrint($schema);
    return $text_schema;
}

function build_metadata($type) {
    global $CFG;
    // Some types make use of all schema files so get a complete list in that case.
    $type_names = [$type::get_name()];
    if ($type->use_all_schemas()) {
        $type_names = array_map(
            function ($type_class_name) {
                /** @var endpoint_type $type_class_name */
                return $type_class_name::get_name();
            },
            endpoint_type_factory::get_all_types()
        );
    }

    $metadata = new metadata_helper();
    // TODO check everything below on Windows.

    // Add metadata.json file from core
    $metadata->add_file($CFG->dirroot . '/lib/webapi/metadata.json');

    // Add core metadata.json files for all relevant types.
    foreach ($type_names as $type_name) {
        $metadata->add_file($CFG->dirroot . "/lib/webapi/$type_name/metadata.json");
    }

    // Then read all plugin schema files.
    $plugin_types = \core_component::get_plugin_types();
    foreach ($plugin_types as $plugin_type => $typedir) {
        $plugins = \core_component::get_plugin_list($plugin_type);
        foreach ($plugins as $plugin => $plugindir) {
            // Add core metadata file for this plugin
            $metadata->add_file("{$plugindir}/webapi/metadata.json");

            // Add metadata files for all relevant types.
            foreach ($type_names as $type_name) {
                $metadata->add_file("{$plugindir}/webapi/{$type_name}/metadata.json");
            }
        }
    }
    // Return the combined files as a json string
    return $metadata->get_metadata_as_json();
}

function build_docs_nav() {
    // Always include 'Core' node.
    $nav = [
        [
            'id' => 'core',
            'name' => 'Core',
        ]
    ];
    // Loop through all subsystems.
    $subsystems = \core_component::get_core_subsystems();
    foreach ($subsystems as $subsystem => $unused) {
        $component = "core_{$subsystem}";
        if (get_string_manager()->string_exists($subsystem, $component)) {
            $nav[] = [
                'id' => $component,
                'name' => get_string($subsystem, $component),
            ];
        }
    }
    // Loop through all plugin types.
    $plugin_types = \core_component::get_plugin_types();
    foreach ($plugin_types as $plugin_type => $typedir) {
        $plugins = \core_component::get_plugin_list($plugin_type);
        foreach ($plugins as $plugin => $plugindir) {
            // If it has a 'pluginname' string, include in nav object.
            $component = "{$plugin_type}_{$plugin}";
            if (get_string_manager()->string_exists('pluginname', $component)) {
                $nav[] = [
                    'id' => $component,
                    'name' => get_string('pluginname', $component),
                ];
            }
        }
    }    // Convert to JSON
    $nav_json = json_encode($nav, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
    return "
    // nav structure for SpectaQL auto-generated by prep_api_docs.php
    exports.nav = {$nav_json};
    ";
}
