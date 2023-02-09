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

use hierarchy_competency\event\scale_min_proficient_value_updated;
use totara_competency\models\scale;
use totara_core\advanced_feature;

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
require_once('lib.php');

///
/// Setup / loading data
///

// Competency scale id
$id = required_param('id', PARAM_INT);
// Move up / down
$moveup = optional_param('moveup', 0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
// Set default value
$default = optional_param('default', 0, PARAM_INT);
$prefix = required_param ('prefix', PARAM_ALPHA);
$minproficiencyid = optional_param('minproficiencyid', 0, PARAM_INT);
$confirmupdate = optional_param('confirmupdate', false, PARAM_BOOL);

// Check if Competencies are enabled.
competency::check_feature_enabled();

$sitecontext = context_system::instance();
$hierarchy = hierarchy::load_hierarchy($prefix);

// Cache user capabilities.
extract($hierarchy->get_permissions());

$competencyscalestr = get_string('competencyscale', 'totara_hierarchy');
$pageurl = new moodle_url('/totara/hierarchy/prefix/competency/scale/view.php', array('id' => $id, 'prefix' => $prefix));

// Permissions.
if (!$canviewscales) {
    print_error('accessdenied', 'admin');
}

// Set up the page.
if ($canmanage) {
    // If the user can update the framework then show the admin link.
    admin_externalpage_setup($prefix.'manage');
} else {
    $PAGE->set_url($pageurl);
    $PAGE->set_context($sitecontext);
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title($competencyscalestr);
    $PAGE->set_heading($competencyscalestr);
}

if (!$scale = $DB->get_record('comp_scale', array('id' => $id))) {
    print_error('incorrectcompetencyscaleid', 'totara_hierarchy');
}

$scale_model = scale::load_by_id_with_values($scale->id);

$scale_assigned_to_framework = $scale_model->is_assigned();
$scale_used_by_users = $scale_model->is_in_use();

// Cache text
$str_edit = get_string('edit');
$str_delete = get_string('delete');
$str_moveup = get_string('moveup');
$str_movedown = get_string('movedown');


///
/// Process any actions
///

if ($canupdatescales || $candeletescales) {
    /// Move a value up or down
    if ((!empty($moveup) or !empty($movedown))) {

        // Can't reorder a scale that's in use
        if ($scale_used_by_users) {
            $returnurl = new moodle_url('/totara/hierarchy/prefix/competency/scale/view.php', array('id' => $scale->id, 'prefix' => 'competency'));
            print_error('error:noreorderscaleinuse', 'totara_hierarchy', $returnurl);
        }

        $move = NULL;
        $swap = NULL;

        // Get value to move, and value to replace
        if (!empty($moveup)) {
            $move = $DB->get_record('comp_scale_values', array('id' => $moveup));
            $resultset = $DB->get_records_sql("
                SELECT *
                FROM {comp_scale_values}
                WHERE
                    scaleid = ?
                    AND sortorder < ?
                ORDER BY sortorder DESC",
                array($scale->id, $move->sortorder), 0, 1
            );
            if ($resultset && count($resultset)) {
                $swap = reset($resultset);
                unset($resultset);
            }
        } else {
            $move = $DB->get_record('comp_scale_values', array('id' => $movedown));
            $resultset = $DB->get_records_sql("
                SELECT *
                FROM {comp_scale_values}
                WHERE
                    scaleid = ?
                    AND sortorder > ?
                ORDER BY sortorder ASC", array($scale->id, $move->sortorder), 0, 1
            );
            if ($resultset && count($resultset)) {
                $swap = reset($resultset);
                unset($resultset);
            }
        }

        if ($swap && $move) {
            // Swap sortorders
            $transaction = $DB->start_delegated_transaction();

            if ($DB->set_field('comp_scale_values', 'sortorder', $move->sortorder, array('id' => $swap->id))
              && $DB->set_field('comp_scale_values', 'sortorder', $swap->sortorder, array('id' => $move->id))
            ) {
                $transaction->allow_commit();
            }
        }
    }

    if ($default && $minproficiencyid) {
        // Confirmation is only required when users have values from this scale.
        if ($scale_used_by_users && !$confirmupdate) {
            // If the scale is used by users, present a dialog asking for confirmation.
            echo $OUTPUT->header();

            $title = get_string('confirmupdatescale_title', 'totara_hierarchy');
            $message = nl2br(get_string('confirmupdatescale_content', 'totara_hierarchy'));

            // This will be going into a single_button with method set to post, which means params will be post params.
            $continue_url = new moodle_url(
                $CFG->wwwroot . '/totara/hierarchy/prefix/competency/scale/view.php',
                array(
                    'id' => $scale->id,
                    'prefix' => 'competency',
                    'default' => $default,
                    'minproficiencyid' => $minproficiencyid,
                    'confirmupdate' => 1
                )
            );
            $continue = new single_button($continue_url, get_string('yes'), 'post', true);

            $cancel_url = new moodle_url(
                $CFG->wwwroot . '/totara/hierarchy/prefix/competency/scale/view.php',
                array(
                    'id' => $scale->id,
                    'prefix' => 'competency'
                )
            );
            $cancel = new single_button($cancel_url, get_string('no'), 'get');

            echo $OUTPUT->confirm($message, $continue, $cancel, $title);

            echo $OUTPUT->footer();
            exit;
        }

        require_sesskey();

        // Update
        $s = new stdClass();
        $s->id = $scale->id;

        // Check value exists
        if (!$DB->get_record('comp_scale_values', array('id' => $default))) {
            print_error('incorrectcompetencyscalevalueid', 'totara_hierarchy');
        }
        $s->defaultid = $default;

        // Check value exists
        if (!$DB->get_record('comp_scale_values', array('id' => $minproficiencyid))) {
            print_error('incorrectcompetencyscalevalueid', 'totara_hierarchy');
        }
        $s->minproficiencyid = $minproficiencyid;

        if (!$DB->update_record('comp_scale', $s)) {
            print_error('updatecompetencyscale', 'totara_hierarchy');
        } else {
            // Fetch the update scale record so it'll show up to the user.
            $scale = $DB->get_record('comp_scale', array('id' => $id));

            // We'll also make sure the scale value 'proficient' fields are up-to-date. These are legacy,
            // but we're keeping them aligned with minproficiencyid on the scale for backwards compatibility for now.
            $values = $DB->get_records('comp_scale_values', array('scaleid' => $scale->id), 'sortorder ASC');
            $proficient = true;
            foreach ($values as $value) {
                if ($proficient) {
                    if ($value->proficient != 1) {
                        $value->proficient = 1;
                        $DB->update_record('comp_scale_values', $value);
                    }
                } else {
                    if ($value->proficient != 0) {
                        $value->proficient = 0;
                        $DB->update_record('comp_scale_values', $value);
                    }
                }

                if ($value->id == $scale->minproficiencyid) {
                    $proficient = false;
                }
            }

            // Unset as we use another variable of the same name later.
            unset($proficient);

            scale_min_proficient_value_updated::create_from_instance($scale)->trigger();

            \core\notification::success(get_string('competencyscalechangeapplied', 'totara_hierarchy'));
        }
    }
}

///
/// Display page
///

// Load values
$values = $DB->get_records('comp_scale_values', array('scaleid' => $scale->id), 'sortorder');

$PAGE->navbar->add(get_string("competencyframeworks", 'totara_hierarchy'),
                    new moodle_url('/totara/hierarchy/framework/index.php', array('prefix' => 'competency')));
$PAGE->navbar->add(format_string($scale->name));

echo $OUTPUT->header();

echo $OUTPUT->action_link(new moodle_url('/totara/hierarchy/framework/index.php', array('prefix' => 'competency')), '&laquo; ' . get_string('allcompetencyscales', 'totara_hierarchy'));

// Display info about scale
echo $OUTPUT->heading(get_string('scalex', 'totara_hierarchy', format_string($scale->name)), 1);
$scale->description = file_rewrite_pluginfile_urls($scale->description, 'pluginfile.php', $sitecontext->id, 'totara_hierarchy', 'comp_scale', $scale->id);
echo html_writer::tag('p', format_text($scale->description));

// Print button for creating new scale value
$button_editscale = '';
if ($canupdatescales && !$scale_used_by_users) {
    echo $button_editscale = $OUTPUT->single_button(
        new moodle_url('/totara/hierarchy/prefix/competency/scale/editvalue.php', array('scaleid' => $scale->id, 'prefix' => 'competency')),
        get_string('addscalevalue', 'totara_hierarchy'),
        'get',
        array('class' => 'pull-right')
    );
}

// Display warning if scale is in use
if ($canupdatescales && $scale_used_by_users) {
    $warning = get_string('competencyscaleinusemayaffectlocked', 'totara_hierarchy') . html_writer::alist([
        get_string('competencyscaleinusemayaffectchange', 'totara_hierarchy'),
        get_string('competencyscaleinusemayaffectconfusion', 'totara_hierarchy'),
    ]);
    echo $OUTPUT->notification($warning, 'warning');
}

// Display scale values
if ($values) {
    if ($canupdatescales) {
        echo html_writer::start_tag('form', array('id' => 'compscaledefaultprofform', 'action' => new moodle_url('/totara/hierarchy/prefix/competency/scale/view.php'), 'method' => 'POST'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $id));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'prefix', 'value' => 'competency'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
        echo html_writer::empty_tag('br');
    }
    $table = new html_table();
    $table->data = array();
    // Headers
    $table->head = array(get_string('name'));
    $table->align = array('left');

    if ($canupdatescales || $candeletescales) {
        $table->head[] = get_string('plandefaultvalue', 'totara_hierarchy').' '.
            $OUTPUT->help_icon('plandefaultvalue', 'totara_hierarchy');
        $table->align[] = 'center';

        if (advanced_feature::is_enabled('competency_assignment')) {
            $table->head[] = get_string('competencyscaleminprofvaluewithassignments', 'totara_hierarchy') .
                ' ' . $OUTPUT->help_icon('competencyscaleminprofvaluewithassignments', 'totara_hierarchy');
        } else {
            $table->head[] = get_string('competencyscaleminprofvalue', 'totara_hierarchy') .
                ' ' . $OUTPUT->help_icon('competencyscaleminprofvalue', 'totara_hierarchy');
        }
        $table->align[] = 'center';

        $table->head[] = get_string('competencyscaleprofcolumn', 'totara_hierarchy').' '.
            $OUTPUT->help_icon('competencyscaleprofcolumn', 'totara_hierarchy');
        $table->align[] = 'center';

        $table->head[] = get_string('edit');
        $table->align[] = 'center';
    }

    $numvalues = count($values);

    // Add rows to table
    $count = 0;

    $proficient = true;

    foreach ($values as $value) {
        $count++;

        $row = array();
        $row[] = format_string($value->name);

        if ($canupdatescales || $candeletescales) {
            $buttons = array();
            $attributes = array('type' => 'radio', 'name' => 'default', 'value' => $value->id);
            // There is only one value or they can't update.
            if ($numvalues == 1 || !$canupdatescales) {
                $attributes['disabled'] = 'disabled';
            }
            // Is this the default value?
            if ($value->id == $scale->defaultid) {
                $attributes['checked'] = 'checked';
            }

            $row[] = html_writer::empty_tag('input', $attributes);

            $minprof_attributes = array('type' => 'radio', 'name' => 'minproficiencyid', 'value' => $value->id);
            if ($numvalues == 1 || !$canupdatescales) {
                $minprof_attributes['disabled'] = 'disabled';
            }
            if ($value->id == $scale->minproficiencyid) {
                $minprof_attributes['checked'] = 'checked';
            }
            $row[] = html_writer::empty_tag('input', $minprof_attributes);

            // Is this the proficient value?
            if ($proficient) {
                $row[] = get_string('yes');
                if ($value->id == $scale->minproficiencyid) {
                    // Values after this will not be proficient.
                    $proficient = false;
                }
            } else {
                $row[] = get_string('no');
            }

            if ($canupdatescales) {
                $buttons[] = $OUTPUT->action_icon(
                    new moodle_url('/totara/hierarchy/prefix/competency/scale/editvalue.php',
                    array('id' => $value->id, 'prefix' => 'competency')),
                    new pix_icon('t/edit', $str_edit),
                    null,
                    array('class' => 'action-icon', 'title' => $str_edit));
            }

            if (!$scale_used_by_users && $candeletescales) {
                /// prevent deleting default value
                if ($value->id == $scale->defaultid) {
                    $buttons[] = $OUTPUT->pix_icon('t/delete_grey', get_string('error:nodeletecompetencyscalevaluedefault', 'totara_hierarchy'), 'totara_core', array('class' => 'iconsmall action-icon'));
                } else {
                    $buttons[] = $OUTPUT->action_icon(
                        new moodle_url('/totara/hierarchy/prefix/competency/scale/deletevalue.php',
                        array('id' => $value->id, 'prefix' => 'competency')),
                        new pix_icon('t/delete', $str_delete),
                        null,
                        array('class' => 'action-icon', 'title' => $str_delete));
                }
            }

            // If value can be moved up
            if ($count > 1 && !$scale_used_by_users && $canupdatescales) {
                $buttons[] = $OUTPUT->action_icon(new moodle_url('/totara/hierarchy/prefix/competency/scale/view.php', array('id' => $scale->id, 'moveup' => $value->id, 'prefix' => 'competency')),
                        new pix_icon('t/up', $str_moveup), null, array('class' => 'action-icon', 'title' => $str_moveup));
            } else {
                $buttons[] = $OUTPUT->spacer(array('height' => 11, 'width' => 11));
            }

            // If value can be moved down
            if ($count < $numvalues && !$scale_used_by_users && $canupdatescales) {
                $buttons[] = $OUTPUT->action_icon(new moodle_url('/totara/hierarchy/prefix/competency/scale/view.php', array('id' => $scale->id, 'movedown' => $value->id, 'prefix' => 'competency')),
                        new pix_icon('t/down', $str_movedown), null, array('class' => 'action-icon', 'title' => $str_movedown));
            } else {
                $buttons[] = $OUTPUT->spacer(array('height' => 11, 'width' => 11));
            }

            $row[] = implode('', $buttons);
        }

        $table->data[] = $row;
    }

    echo html_writer::table($table);
    if ($canupdatescales) {
        if ($numvalues != 1) {
            if ($scale_used_by_users || $scale_assigned_to_framework) {
                $submit_string = get_string('competencyscalesaveapply', 'totara_hierarchy');
            } else {
                $submit_string = get_string('competencyscalesave', 'totara_hierarchy');
            }

            $save = new single_button(new moodle_url('/totara/hierarchy/prefix/competency/scale/view.php', array('id' => $scale->id, 'prefix' => 'competency')), $submit_string, 'post', true);
            $save->class = 'btn btn-xs single-button';
            $cancel = new single_button(new moodle_url('/totara/hierarchy/prefix/competency/scale/view.php', array('id' => $scale->id, 'prefix' => 'competency')), get_string('cancel'), 'get', false);
            $cancel->class = 'btn btn-xs single-button';

            echo html_writer::tag('div', $OUTPUT->render($save) . $OUTPUT->render($cancel), array('class' => 'btn-block'));
        }
        echo html_writer::end_tag('form');
    }
} else {
    echo html_writer::empty_tag('br');
    echo html_writer::tag('div', get_string('noscalevalues','totara_hierarchy'));
    echo html_writer::empty_tag('br');
}




/// and proper footer
echo $OUTPUT->footer();
