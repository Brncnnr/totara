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
 * @package elementlibrary
 */
use core\json_editor\schema;

require_once(__DIR__ . "/../config.php");
require_once("{$CFG->dirroot}/lib/adminlib.php");

global $OUTPUT;

admin_externalpage_setup('elementlibrary');

$schema = schema::instance();
$types = $schema->get_all_node_types();

echo $OUTPUT->header();
echo $OUTPUT->heading("List of all json node schema of for json editor");

echo $OUTPUT->box_start();

$table = new \html_table();
$table->head = ['type', 'JSON data samples'];

foreach ($types as $type => $classname) {
    $table->data[] = new html_table_row(
        [
            \html_writer::tag('p', $type),

            // Bit of hack on the styling.
            \html_writer::tag('code', get_sample_json($classname), ['style' => 'display:flex;'])
        ]
    );
}

// Rendering the table of content
echo $OUTPUT->render($table);

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

/**
 * Given the class name, this function should be able to find the sample json file for the node type.
 *
 * @param string $classname
 * @return string
 */
function get_sample_json(string $classname): string {
    global $CFG;
    $parts = explode("\\", $classname);

    $component = reset($parts);
    $node = end($parts);

    [$plugintype, $pluginname] = \core_component::normalize_component($component);

    if ('core' === $plugintype) {
        $basedirectory = $CFG->libdir;
    } else {
        $basedirectory = \core_component::get_plugin_directory($plugintype, $pluginname);
    }

    $file = "{$basedirectory}/tests/fixtures/json_editor/node/{$node}.php";
    if (!file_exists($file)) {
        return 'empty';
    }

    $json = require_once($file);
    $encoded = json_encode($json, JSON_PRETTY_PRINT);

    $encoded = str_replace("\n", "<br/>", $encoded);
    $encoded = str_replace(" ", "&nbsp;", $encoded);
    return $encoded;
}