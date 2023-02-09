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
use totara_topic\form\topic_form;
use core\notification;
use totara_topic\topic;
use totara_topic\provider\topic_provider;

require_once(__DIR__ . "/../../config.php");
global $PAGE, $OUTPUT, $USER, $CFG;

$id = required_param('id', PARAM_INT);
$back = optional_param('back', null, PARAM_URL);

if (null != $back) {
    $back = new moodle_url($back);
} else {
    $back = new moodle_url("/totara/topic/index.php");
}

$context = context_system::instance();
$url = new moodle_url("/totara/topic/edit.php", ['id' => $id, 'back' => $back]);

require_login();

$PAGE->set_context($context);
$PAGE->set_url($url);

require_once("{$CFG->dirroot}/lib/adminlib.php");
admin_externalpage_setup('managetopics', '', null, $url, ['pagelayout' => 'report']);

$topic = topic::from_id($id);

$heading = get_string('edittopic', 'totara_topic');
require_capability('totara/topic:update', $context);

$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$PAGE->navbar->add($heading);
navigation_node::override_active_url($url);

$form = new topic_form([
    'id' => $topic->get_id(),
    'value' => $topic->get_raw_name()
]);

$data = $form->get_data();

if ($form->is_cancelled()) {
    redirect($back);
} else if (null != $data) {
    $existing = topic_provider::find_by_name($data->value);

    if (null === $existing || $existing->get_id() == $topic->get_id()) {
        // Tag does not exist, or it's the active tag
        $topic->update($data->value);

        notification::success(get_string('successupdate', 'totara_topic'));
        redirect($back);
    } else {
        notification::error(get_string("topicexists", 'totara_topic', $data->value));
    }
}

echo $OUTPUT->header();
echo $form->render();
echo $OUTPUT->footer();