<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

use totara_competency\linked_courses;
use totara_core\advanced_feature;

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');

require_login();
require_sesskey();

///
/// Setup / loading data
///

// Competency id
$id = required_param('competency', PARAM_INT);
// Evidence type
$type = required_param('type', PARAM_TEXT);
// Evidence instance id
$instance = required_param('instance', PARAM_INT);

// Indicates whether current related items, not in $relidlist, should be deleted
$deleteexisting = optional_param('deleteexisting', 0, PARAM_BOOL);

// Check if Competencies are enabled.
if (advanced_feature::is_disabled('competencies')) {
    echo html_writer::tag('div', get_string('competenciesdisabled', 'totara_hierarchy'), array('class' => 'notifyproblem'));
    die();
}

// Updated course lists
$idlist = optional_param('update', null, PARAM_SEQUENCE);
if ($idlist == null) {
    $idlist = array();
}
else {
    $idlist = explode(',', $idlist);
}

// Check perms
admin_externalpage_setup('competencymanage', '', array(), '/totara/hierarchy/item/edit.php');

$sitecontext = context_system::instance();
require_capability('totara/hierarchy:updatecompetency', $sitecontext);
$can_edit = has_capability('totara/hierarchy:updatecompetency', $sitecontext);

// Check type is available
$avail_types = array('coursecompletion', 'coursegrade', 'activitycompletion');

if (!in_array($type, $avail_types)) {
    die('type unavailable');
}

// Load competency
$competency = $DB->get_record('comp', array('id' => $id));

if ($type === 'coursecompletion') {
    // Check if Competencies are enabled.
    if (advanced_feature::is_enabled('competency_assignment')) {
        debugging('This page has been deprecated in Totara Perform. Please use totara/competency/competency_edit.php');
    }

    // The default linktype is Optional in Learn-only
    $idlist = array_map(function ($course_id) {
        return ['id' => $course_id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL];
    }, $idlist);

    if ($deleteexisting) {
        linked_courses::set_linked_courses($id, $idlist);
    } else {
        linked_courses::add_linked_courses($id, $idlist);
    }
} else {
    ///
    /// Delete removed courses (if specified)
    ///
    if ($deleteexisting && !empty($idlist)) {

        $assigned = $DB->get_records('comp_criteria', array('competencyid' => $id));
        $assigned = !empty($assigned) ? $assigned : array();

        foreach ($assigned as $ritem) {
            if (!in_array($ritem->iteminstance, $idlist)) {
                $data = new stdClass();
                $data->id = $ritem->id;
                $data->itemtype = $ritem->itemtype;
                $evidence = competency_evidence_type::factory((array)$data);
                $evidence->iteminstance = $ritem->iteminstance;
                $evidence->delete($competency);
            }
        }
    }

    // HTML to return for JS version
    foreach ($idlist as $instance) {
        $data = new stdClass();
        $data->itemtype = $type;
        $evidence = competency_evidence_type::factory((array)$data);
        $evidence->iteminstance = $instance;

        $newevidenceid = $evidence->add($competency);
    }
}

$editingon = 1;
$evidence = $DB->get_records('comp_criteria', array('competencyid' => $id));
$str_edit = get_string('edit');
$str_remove = get_string('remove');
$item = $competency;

$renderer = $PAGE->get_renderer('totara_hierarchy');
echo $renderer->competency_view_evidence($item, $evidence, $can_edit);
