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
 * @author Russell England <russell.england@totaralms.com>
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara
 * @subpackage plan
 */

use totara_evidence\models\evidence_item;
use totara_evidence\models\helpers\evidence_item_capability_helper;
use totara_evidence\output\view_item;

/**
 * Display attachments to an evidence
 *
 * @deprecated since Totara 12
 *
 * @global object $CFG
 * @global type $OUTPUT
 * @param int $userid
 * @param int $evidenceid
 * @return string
 */
function evidence_display_attachment($userid, $evidenceid) {
    global $CFG, $OUTPUT, $FILEPICKER_OPTIONS;

    if (!$filecontext = context_user::instance($userid)) {
        return '';
    }

    $out = '';

    $context = $FILEPICKER_OPTIONS['context'];

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'totara_plan', 'attachment', $evidenceid, null, FALSE);

    if (!empty($files)) {
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $path = '/'.$file->get_contextid().'/totara_plan/attachment'.$file->get_filepath().$file->get_itemid().'/'.$filename;
            $fileurl = moodle_url::make_file_url('/pluginfile.php', $path);

            $mimetype = $file->get_mimetype();
            $fileicon = file_mimetype_flex_icon($mimetype, $mimetype);

            $out .= html_writer::tag('a', $fileicon . s($filename), array('href' => $fileurl));
            $out .= html_writer::empty_tag('br');
        }
    }

    return $out;
}

/**
 * Deletes a selected evidence item.
 *
 * @deprecated since Totara 13
 *
 * @param int $evidenceid - totara_evidence_item->id
 */
function evidence_delete($evidenceid) {
    debugging('evidence_delete() has been deprecated and is no longer used, please use totara_evidence\models\evidence_item->delete() instead.', DEBUG_DEVELOPER);
    evidence_item::load_by_id($evidenceid)->delete();
}

/**
 * Get custom fields
 *
 * @deprecated since Totara 13
 *
 * @param int $itemid the evidence id
 * @return array
 */
function totara_plan_get_custom_fields($itemid) {
    debugging('totara_plan_get_custom_fields() has been deprecated and is no longer used, please use totara_evidence\entity\evidence_item->data instead.', DEBUG_DEVELOPER);
    return evidence_item::load_by_id($itemid)->get_customfield_data()->to_array();
}

/**
 * Returns markup to display an individual evidence relation
 *
 * @deprecated since Totara 13
 *
 * @global object $USER
 * @global object $DB
 * @global object $OUTPUT
 * @param int $evidenceid - totara_evidence_item->id
 * @param bool $delete - display a delete link
 * @return string html markup
 */
function display_evidence_detail($evidenceid, $delete = false, $return_url = null) {
    debugging('display_evidence_detail() has been deprecated and is no longer used, please use totara_evidence\output\view_item::create() instead.', DEBUG_DEVELOPER);
    return view_item::create(evidence_item::load_by_id($evidenceid));
}

/**
 * Lists all components that are linked to the evidence id
 *
 * @param int $evidenceid Evidence ID to list items for
 *
 * @return string html output
 */
function list_evidence_in_use($evidenceid) {
    global $DB, $OUTPUT;

    $out = '';

    $sql = "
        SELECT er.id, dp.name AS planname, er.component, comp.fullname AS itemname
        FROM {dp_plan_evidence_relation} AS er
        JOIN {dp_plan} AS dp ON dp.id = er.planid
        JOIN {dp_plan_competency_assign} AS c ON c.id = er.itemid
        JOIN {comp} AS comp ON comp.id = c.competencyid
        WHERE er.component = 'competency'
        AND er.evidenceid = ?
        UNION
        SELECT er.id, dp.name AS planname, er.component, course.fullname AS itemname
        FROM {dp_plan_evidence_relation} AS er
        JOIN {dp_plan} AS dp ON dp.id = er.planid
        JOIN {dp_plan_course_assign} AS c ON c.id = er.itemid
        JOIN {course} AS course ON course.id = c.courseid
        WHERE er.component = 'course'
        AND er.evidenceid = ?
        UNION
        SELECT er.id, dp.name AS planname, er.component, c.fullname AS itemname
        FROM {dp_plan_evidence_relation} AS er
        JOIN {dp_plan} AS dp ON dp.id = er.planid
        JOIN {dp_plan_objective} AS c ON c.id = er.itemid
        WHERE er.component = 'objective'
        AND er.evidenceid = ?
        UNION
        SELECT er.id, dp.name AS planname, er.component, prog.fullname AS itemname
        FROM {dp_plan_evidence_relation} AS er
        JOIN {dp_plan} AS dp ON dp.id = er.planid
        JOIN {dp_plan_program_assign} AS c ON c.id = er.itemid
        JOIN {prog} AS prog ON prog.id = c.programid
        WHERE er.component = 'program'
        AND er.evidenceid = ?
        ORDER BY planname, component, itemname";
    if ($items = $DB->get_records_sql($sql, array($evidenceid, $evidenceid, $evidenceid, $evidenceid))) {
        $out .= $OUTPUT->heading(get_string('evidenceinuseby', 'totara_plan'), 4);

        $tableheaders = array(
            get_string('planname', 'totara_plan'),
            get_string('component', 'totara_plan'),
            get_string('name', 'totara_plan'),
        );

        $tablecolumns = array(
            'planname',
            'component',
            'itemname'
        );

        // Start output buffering to bypass echo statements in $table->add_data()
        ob_start();
        $table = new flexible_table('linkedevidencelist');
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $url = new moodle_url('/totara/plan/record/evidence/index.php');
        $table->define_baseurl($url);
        $table->set_attribute('class', 'logtable generalbox dp-plan-evidence-items');
        $table->setup();

        foreach ($items as $item) {
            $row = array();
            $row[] = $item->planname;
            $row[] = get_string($item->component, 'totara_plan');
            $row[] = $item->itemname;
            $table->add_data($row);
        }

        // return instead of outputing table contents
        $table->finish_html();
        $out .= ob_get_contents();
        ob_end_clean();
    }
    return $out;

}

/**
 * Check whether the current user has permission to create, edit or delete evidence
 *
 * @deprecated since Totara 13
 *
 * @param int $userid The ID of the user the evidence is for
 * @param bool $is_editing obsolete, unused
 * @param bool $read_only obsolete, unused
 * @return bool
 */
function can_create_or_edit_evidence(int $userid, bool $is_editing = false, bool $read_only = false): bool {
    debugging('can_create_or_edit_evidence() has been deprecated and is no longer used, please use totara_evidence\models\helpers\evidence_item_capability_helper instead.', DEBUG_DEVELOPER);
    return evidence_item_capability_helper::for_user($userid)->can_create();
}
