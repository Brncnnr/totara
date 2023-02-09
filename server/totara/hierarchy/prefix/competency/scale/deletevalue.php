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

use pathway_criteria_group\criteria_group;
use totara_competency\entity\assignment;
use totara_competency\min_proficiency_override_for_assignments;
use totara_competency\models\scale;

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
require_once('lib.php');


///
/// Setup / loading data
///

// Get params
$id = required_param('id', PARAM_INT);
$prefix = required_param('prefix', PARAM_ALPHA);
// Delete confirmation hash
$delete = optional_param('delete', '', PARAM_ALPHANUM);

// Cache user capabilities.
$sitecontext = context_system::instance();

// Check if Competencies are enabled.
competency::check_feature_enabled();

// Permissions.
require_capability('totara/hierarchy:deletecompetencyscale', $sitecontext);

// Set up the page.
admin_externalpage_setup($prefix.'manage');

if (!$value = $DB->get_record('comp_scale_values', array('id' => $id))) {
    print_error('error:invalidscalevalueid', 'totara_hierarchy');
}

$scale = $DB->get_record('comp_scale', array('id' => $value->scaleid));

///
/// Display page
///

$returnparams = array('id' => $value->scaleid, 'prefix' => 'competency');
$returnurl = new moodle_url('/totara/hierarchy/prefix/competency/scale/view.php', $returnparams);
$deleteparams = array('id' => $value->id, 'delete' => md5($value->timemodified), 'sesskey' => $USER->sesskey, 'prefix' => 'competency');
$deleteurl = new moodle_url('/totara/hierarchy/prefix/competency/scale/deletevalue.php', $deleteparams);

// Can't delete if the scale is in use
if (scale::load_by_id_with_values($value->scaleid)->is_in_use()) {
    \core\notification::error(get_string('error:nodeletescalevalueinuse', 'totara_hierarchy'));
    redirect($returnurl);
}

if ($value->id == $scale->defaultid) {
    \core\notification::error(get_string('error:nodeletecompetencyscalevaluedefault', 'totara_hierarchy'));
    redirect($returnurl);
}

if (!$delete) {
    echo $OUTPUT->header();

    $scale_value_delete_message = html_writer::tag('p', get_string('deletecheckscalevalue', 'totara_hierarchy'));

    $scale_value_pathway_count = criteria_group::get_pathway_count_by_scale_value_id($value->id);
    $override_count = assignment::repository()
        ->where('minproficiencyid', $value->id)
        ->count();

    if ($scale_value_pathway_count > 0 || $override_count > 0) {
        $warnings = [];

        if ($scale_value_pathway_count > 0) {
            $warnings[] = get_string(
                'delete_check_scale_value_pathways2',
                'totara_hierarchy',
                $scale_value_pathway_count
            );
        }

        if ($override_count > 0) {
            $warnings[] = get_string(
                'delete_check_scale_value_overrides',
                'totara_hierarchy',
                $override_count
            );
        }

        $warning_message = get_string('delete_check_scale_value_start', 'totara_hierarchy', $value->name) .
            html_writer::alist($warnings);

        $scale_value_delete_message = $warning_message . $scale_value_delete_message;
    }

    echo $OUTPUT->confirm(
        $scale_value_delete_message,
        $deleteurl,
        $returnurl,
        get_string('delete_check_scale_value_confirmation', 'totara_hierarchy')
    );

    echo $OUTPUT->footer();
    exit;
}


///
/// Delete competency scale
///

if ($delete != md5($value->timemodified)) {
    \core\notification::error(get_string('error:checkvariable', 'totara_hierarchy'));
    redirect($returnurl);
}

if (!confirm_sesskey()) {
    \core\notification::error(get_string('confirmsesskeybad', 'error'));
    redirect($returnurl);
}

$DB->transaction(function () use ($value, $scale) {
    global $DB;

    if ($value->id == $scale->minproficiencyid) {
        // Deal with this being the minimum proficiency value.

        $values = $DB->get_records('comp_scale_values', array('scaleid' => $scale->id), 'sortorder ASC');
        $choose_next = false;
        $new_minimum = null;
        foreach ($values as $this_value) {
            if ($choose_next) {
                $new_minimum = $this_value;
                break;
            }

            if ($this_value->id == $value->id) {
                if (is_null($new_minimum)) {
                    // We haven't got a previous value to use, meaning this was the highest.
                    // Choose the next value.
                    $choose_next = true;
                    continue;
                } else {
                    // The next highest record, which is what $new_minimum will have been set to, is going
                    // to be our new minimum.
                    break;
                }
            }

            // Set this latest value as candidate for the new minimum.
            $new_minimum = $this_value;
        }

        $DB->set_field('comp_scale', 'minproficiencyid', $new_minimum->id, array('id' => $scale->id));
        if ($new_minimum->proficient != 1) {
            $DB->set_field('comp_scale_values', 'proficient', 1, array('id' => $new_minimum->id));
        }
    }

    // Delete all minimum proficiency overrides which use the deleted scale value.
    min_proficiency_override_for_assignments::reset_with_scale_value($value->id);

    $DB->delete_records('comp_scale_values', array('id' => $value->id));

    \hierarchy_competency\event\scale_value_deleted::create_from_instance($value)->trigger();
});


\core\notification::success(get_string('deletedcompetencyscalevalue', 'totara_hierarchy', format_string($value->name)));
redirect($returnurl);
