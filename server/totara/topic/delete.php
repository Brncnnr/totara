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
use totara_topic\topic;
use core\output\notification;
use totara_topic\provider\topic_provider;

require_once(__DIR__ . "/../../config.php");
global $PAGE, $OUTPUT;

$back = optional_param('back', null, PARAM_URL);
$id = required_param('id', PARAM_INT);

// We are trying to fetch the topic first, just in case if it is not existing in the system.
$topic = topic::from_id($id);

if (null == $back) {
    $back = new moodle_url("/totara/topic/index.php");
} else {
    $back = new moodle_url($back);
}

require_login();
require_sesskey();

$context = context_system::instance();
$heading = get_string('deletetopic', 'totara_topic');

require_capability('totara/topic:delete', $context);

$PAGE->set_context($context);
$PAGE->set_url("/totara/topic/delete.php", ['id' => $id]);
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$value = $topic->get_raw_name();
$topic->delete();

$result = !topic_provider::topic_exists($id);

// We navigate back to managing topics
if (!$result) {
    $message = get_string('unsuccessdelete', 'totara_topic');
    $type = notification::NOTIFY_ERROR;
} else {
    $type = notification::NOTIFY_SUCCESS;
    $message = get_string('successdelete', 'totara_topic', $value);
}

redirect($back, $message, null, $type);