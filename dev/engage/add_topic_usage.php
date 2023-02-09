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
 * @package totara_topic
 */
use totara_topic\provider\topic_provider;
use totara_topic\topic_helper;

define('CLI_SCRIPT', true);
require_once(__DIR__ . "/../../server/config.php");
global $CFG, $PAGE;

$PAGE->set_context(context_system::instance());
require_once("{$CFG->dirroot}/lib/clilib.php");

$help = "
A cli script to assign all the current topics within the system into the specific instance of a component.
Options:
    --instance-id=NUMBER        The id of particular instance that you want to assign the topics to
    --component=STRING          The component name of the particular instance
    --item-type=STRING          The itemtype which match with the tag area
    -h, --help                  Print out this help message
";

[$options, $unrecognized] = cli_get_params(
    [
        'instance-id' => null,
        'component' => null,
        'help' => false,
        'item-type' => null
    ],
    [
        'h' => 'help'
    ]
);

if ($options['help']) {
    echo $help;
    return 0;
} else if (empty($options['instance-id']) || empty($options['component'])) {
    echo "No parameter instance-id or component";
    return 1;
}

global $USER;
$USER = get_admin();

$topics = topic_provider::get_all();

if (empty($topics)) {
    echo "No topics to be assigned to";
    return 1;
}

$component = clean_param($options['component'], PARAM_COMPONENT);
if ('' == $component) {
    echo "Invalid component being passed into";
    return 1;
}

foreach ($topics as $topic) {
    topic_helper::add_topic_usage(
        $topic->get_id(),
        $component,
        $options['item-type'],
        $options['instance-id']
    );
}

echo "Done adding usage of the topics !!!";
return 0;