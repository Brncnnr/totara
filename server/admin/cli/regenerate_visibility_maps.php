<?php
/*
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(array('help' => false), array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    $help =
        "Regenerates all visibility adviser maps
        
These are used to optimise the resolution of learning item visibility.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/regenerate_visibility_maps.php
";

    echo $help;
    exit(0);
}

mtrace('Updating visibility maps at '.time() . ' ...');
$start = microtime(true);
foreach (\totara_core\visibility_controller::get_all_maps() as $type => $map) {
    $map_start = microtime(true);
    mtrace('    updating ' . $type, ' ... ');
    $map->recalculate_complete_map();
    mtrace(' done in ' . (microtime(true) - $map_start) . 's');
}
// Purge content cache.
$cache = cache::make('totara_core', 'visible_content');
$cache->purge();
$end = microtime(true);
mtrace('Complete at '.time() . ' in ' . ceil($end - $start) . 's');