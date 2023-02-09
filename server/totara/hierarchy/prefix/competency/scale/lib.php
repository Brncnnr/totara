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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara
 * @subpackage totara_hierarchy
 */
/**
 * competency/lib.php
 *
 * Library of functions related to competency scales.
 *
 * Note: Functions in this library should have names beginning with "competency_scale",
 * in order to avoid name collisions
  */

use totara_competency\models\scale;

/**
 * Determine whether an competency scale is assigned to any frameworks
 *
 * There is a less strict version of this function:
 * {@link competency_scale_is_used()} which tells you if the scale
 * values are actually assigned.
 *
 * @deprecated since Totara 13
 *
 * @param int $objectiveid
 * @return boolean
 */
function competency_scale_is_assigned($scaleid) {
    debugging('competency_scale_is_assigned() is deprecated. Use the is_assigned() method in '.scale::class, DEBUG_DEVELOPER);
    return scale::load_by_id_with_values($scaleid)->is_assigned();
}


/**
 * Determine whether a scale is in use or not.
 *
 * "in use" means that items are assigned any of the scale's values.
 * Therefore if we delete this scale or alter its values, it'll cause
 * the data in the database to become corrupt
 *
 * There is an even stricter version of this function:
 * {@link competency_scale_is_assigned()} which tells you if the scale
 * even is assigned to any frameworks
 *
 * @deprecated since Totara 13
 *
 * @param int $scaleid
 * @return boolean
 */
function competency_scale_is_used(int $scaleid) {
    debugging('competency_scale_is_used() is deprecated. Use the is_in_use() method in '.scale::class, DEBUG_DEVELOPER);
    return scale::load_by_id_with_values($scaleid)->is_in_use();
}

/**
 * Returns the ID of the scale value that is marked as proficient, if
 * there is only one. If there are none, or multiple it returns false
 *
 * @deprecated since Totara 13
 *
 * @param integer $scaleid ID of the scale to check
 * @return integer|false The ID of the sole proficient scale value or false
 */
function competency_scale_only_proficient_value($scaleid) {
    global $DB;

    debugging('totara_competency_scale_proficient_not_in_order has been deprecated. There should always be a minimum proficient id according to the scale.');

    $sql = "
        SELECT csv.id
        FROM {comp_scale_values} csv
        INNER JOIN (
            SELECT scaleid, SUM(proficient) AS sum
            FROM {comp_scale_values}
            GROUP BY scaleid
        ) count
        ON count.scaleid = csv.scaleid
        WHERE proficient = 1
            AND sum = 1
            AND csv.scaleid = ?";

    return $DB->get_field_sql($sql, array($scaleid));
}


/**
 * Get competency scales available for use by frameworks
 *
 * @return array
 */
function competency_scales_available() {
    global $DB;

    $sql = "
        SELECT
            id,
            name
        FROM {comp_scale} scale
        WHERE EXISTS
        (
            SELECT
                1
            FROM
                {comp_scale_values} scaleval
            WHERE
                scaleval.scaleid = scale.id
        )
        ORDER BY
            name ASC
    ";

    return $DB->get_records_sql($sql);
}


/**
 * A function to display a table list of competency scales
 * @param array $scales the scales to display in the table
 * @return html
 */
function competency_scale_display_table($scales) {
    global $OUTPUT;

    $sitecontext = context_system::instance();

    // Cache permissions
    $can_edit = has_capability('totara/hierarchy:updatecompetencyscale', $sitecontext);
    $can_delete = has_capability('totara/hierarchy:deletecompetencyscale', $sitecontext);
    $can_add = has_capability('totara/hierarchy:createcompetencyscale', $sitecontext);
    $can_view = has_capability('totara/hierarchy:viewcompetencyscale', $sitecontext);

    // Make sure user has capability to view the table.
    if (!$can_view) {
        return;
    }

    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $stroptions = get_string('options', 'totara_core');

    ///
    /// Build page
    ///

    if ($scales) {
        $table = new html_table();
        $table->head  = array(get_string('scale'), get_string('used'));
        if ($can_edit || $can_delete) {
            $table->head[] = $stroptions;
        }

        $table->data = array();
        foreach ($scales as $scale) {
            $scale_model = scale::load_by_id_with_values($scale->id);

            $scale_used = $scale_model->is_in_use();
            $scale_assigned = $scale_model->is_assigned();
            $line = array();

            $line[] = $OUTPUT->action_link(new moodle_url('/totara/hierarchy/prefix/competency/scale/view.php', array('id' => $scale->id, 'prefix' => 'competency')), format_string($scale->name));

            if ($scale_used) {
                $line[] = get_string('yes');
            } else if ($scale_assigned) {
                $line[] = get_string('assignedonly', 'totara_hierarchy');
            } else {
                $line[] = get_string('no');
            }

            $buttons = array();
            if ($can_edit || $can_delete) {
                if ($can_edit) {
                    $buttons[] = $OUTPUT->action_icon(new moodle_url('/totara/hierarchy/prefix/competency/scale/edit.php', array('id' => $scale->id, 'prefix' => 'competency')),
                        new pix_icon('t/edit', $stredit), null, array('title' => $stredit));
                }

                if ($can_delete) {
                    if ($scale_used) {
                        $buttons[] = $OUTPUT->pix_icon('t/delete_grey', get_string('error:nodeletecompetencyscaleinuse', 'totara_hierarchy'), 'totara_core',
                            array('class' => 'iconsmall action-icon', 'title' => get_string('error:nodeletecompetencyscaleinuse', 'totara_hierarchy')));
                    } else if ($scale_assigned) {
                        $buttons[] = $OUTPUT->pix_icon('t/delete_grey', get_string('error:nodeletecompetencyscaleassigned', 'totara_hierarchy'), 'totara_core',
                            array('class' => 'iconsmall action-icon', 'title' => get_string('error:nodeletecompetencyscaleassigned', 'totara_hierarchy')));
                    } else {
                        $buttons[] = $OUTPUT->action_icon(new moodle_url('/totara/hierarchy/prefix/competency/scale/delete.php', array('id' => $scale->id, 'prefix' => 'competency')),
                            new pix_icon('t/delete', $strdelete), null, array('title' => $strdelete));
                    }
                }
                $line[] = implode('', $buttons);
            }

            $table->data[] = $line;
        }
    }

    $templatedata = new stdClass();
    $templatedata->heading = get_string('competencyscales', 'totara_hierarchy');

    if ($scales) {
        $templatedata->scales = $table->export_for_template($OUTPUT);
    } else {
        $templatedata->scales = false;
    }

    if ($can_add) {
        $templatedata->addbuttons = $OUTPUT->single_button(new moodle_url('/totara/hierarchy/prefix/competency/scale/edit.php',
            array('prefix' => 'competency')), get_string('scalescompcustomcreate', 'totara_hierarchy'), 'get');
        $templatedata->addbuttons .= $OUTPUT->help_icon('competencyscalesgeneral', 'totara_hierarchy');
    }

    echo $OUTPUT->render_from_template('totara_hierarchy/admin_scales', $templatedata);
}

/**
 * Tests whether or not a scale has values where those set as proficient are all above those that are not.
 *
 * @param int $scaleid
 * @return bool True if the proficient settings on values are not in order.
 * @deprecated since Totara 13
 */
function totara_competency_scale_proficient_not_in_order($scaleid) {
    global $DB;

    debugging('totara_competency_scale_proficient_not_in_order has been deprecated. Please use the minproficiencyid value on the scale to ensure proficiency cannot be out of order.');

    $maxprof = $DB->get_field('comp_scale_values', 'MAX(sortorder)', array('proficient' => 1, 'scaleid' => $scaleid));
    $minnoneprof = $DB->get_field('comp_scale_values', 'MIN(sortorder)', array('proficient' => 0, 'scaleid' => $scaleid));

    // It seems appropriate here to check whether the min proficient value calculated the old way
    // matches the value that we use for the new way.
    $minproficiencyid = $DB->get_field('comp_scale', 'minproficiencyid', array('id' => $scaleid));
    if ($DB->record_exists('comp_scale_values', array('id' => $minproficiencyid, 'sortorder' => $maxprof, 'scaleid' => $scaleid))) {
        // No match, so warn via debug message. But given we don't know exactly how this function might
        // be used, don't return false, just calculate that the old way further below.
        debugging('Min proficiency ID for scale does not align with proficient settings on scale values');
    }

    if (isset($maxprof) && isset($minnoneprof) && $maxprof > $minnoneprof) {
        return true;
    } else {
        return false;
    }
}
