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
use totara_topic\form\bulk_topic_form;
use core\output\notification;
use core\notification as core_notification;
use totara_topic\local\helper;
use totara_topic\topic;

require_once(__DIR__ . "/../../config.php");
global $PAGE, $OUTPUT, $CFG;

$context = context_system::instance();
$url = new moodle_url("/totara/topic/bulk_add.php");

$PAGE->set_context($context);
$PAGE->set_url($url);

require_login();
require_capability('totara/topic:add', $context);

require_once("{$CFG->dirroot}/lib/adminlib.php");
admin_externalpage_setup('managetopics', '', null, $url, ['pagelayout' => 'report']);

$heading = get_string('bulkadd', 'totara_topic');

$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$form = new bulk_topic_form();
$data = $form->get_data();

$manageurl = new moodle_url("/totara/topic/index.php");

if ($form->is_cancelled()) {
    redirect($manageurl);
} else if (null != $data) {
    $duplicate_topics = topic::create_bulk(explode("\n", $data->topics));

    if (empty($duplicate_topics)) {
        redirect($manageurl, get_string('bulkaddsuccess', 'totara_topic'), null, notification::NOTIFY_SUCCESS);
    } else {
        core_notification::error(get_string("topicsduplicated", 'totara_topic', implode("; ", $duplicate_topics)));
    }
}

echo $OUTPUT->header();
echo $form->render();
echo $OUTPUT->footer();