<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

use \core\notification;
use mod_facetoface\asset;
use mod_facetoface\asset_helper;
use mod_facetoface\form\asset_edit;

$id = optional_param('id', 0, PARAM_INT);
$backurl = optional_param('b', '', PARAM_LOCALURL);

$params = ['id' => $id];
$baseurl = new moodle_url('/mod/facetoface/asset/edit.php', $params);
// Check permissions.
if (is_siteadmin()) {
    admin_externalpage_setup('modfacetofaceassets', '', null, $baseurl);
} else {
    $context = context_system::instance();
    $PAGE->set_pagelayout('standard');
    $PAGE->set_context($context);
    $PAGE->set_url($baseurl);
    require_login(0, false);
    require_capability('mod/facetoface:managesitewideassets', $context);
}

$asset = new asset($id);
if (!empty($backurl)) {
    $returnurl = new moodle_url($backurl);
} else {
    $returnurl = new moodle_url('/mod/facetoface/asset/manage.php');
}

if ($asset->get_custom()) {
    redirect($returnurl, get_string('error:incorrectassetid', 'mod_facetoface'), null, notification::ERROR);
}

$mform = new asset_edit(null, ['asset' => $asset, 'backurl' => $returnurl], 'post', '', ['class' => 'dialog-nobind'], true, null, 'mform_modal');

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    $asset = asset_helper::save($data);
    $message = $id ? get_string('assetupdatesuccess', 'mod_facetoface') : get_string('assetcreatesuccess', 'mod_facetoface');
    redirect($returnurl, $message, null, notification::SUCCESS);
}

$pageheading = $id ? get_string('editasset', 'mod_facetoface') : get_string('addasset', 'mod_facetoface');
$PAGE->set_title($pageheading);
echo $OUTPUT->header();
echo $OUTPUT->heading($pageheading);

$mform->display();
echo $OUTPUT->footer();
