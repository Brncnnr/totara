<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

use totara_tenant\local\util;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$id = required_param('id', PARAM_INT);
$cancel = optional_param('cancel', false, PARAM_BOOL);

$syscontext = context_system::instance();

require_login();
require_capability('totara/tenant:manageparticipants', $syscontext);
$PAGE->set_url('/totara/tenant/participants_other.php', ['id' => $id]);

if (empty($CFG->tenantsenabled)) {
    redirect(new moodle_url('/'));
}

$tenant = \core\record\tenant::fetch($id);
$categorycontext = context_coursecat::instance($tenant->categoryid);
$PAGE->set_context($categorycontext);
$PAGE->set_title(get_string('participantsother', 'totara_tenant'));

$returnurl = new moodle_url('/totara/tenant/participants.php', ['id' => $id]);

if ($cancel) {
    redirect($returnurl);
}

// Get the user_selector we will need.
$potentialuserselector = new totara_tenant\local\other_candidate_selector('addselect', array('cohortid'=>$tenant->cohortid, 'accesscontext'=>$categorycontext));
$existinguserselector = new totara_tenant\local\other_existing_selector('removeselect', array('cohortid'=>$tenant->cohortid, 'accesscontext'=>$categorycontext));

// Process incoming user assignments as extra participants.

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoassign = $potentialuserselector->get_selected_users();
    if (!empty($userstoassign)) {

        foreach ($userstoassign as $adduser) {
            util::add_other_participant($tenant->id, $adduser->id);
        }

        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();
    }
}

// Process removing user assignments to the cohort
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoremove = $existinguserselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $removeuser) {
            util::remove_other_participant($tenant->id, $removeuser->id);
        }

        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($tenant->name));

// Print the form.
?>
    <form id="assignform" method="post" action="<?php echo $PAGE->url ?>"><div>
            <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
            <?php // TL-7840: removed table ?>
            <div class="row-fluid user-multiselect">
                <div class="span5">
                    <label for="removeselect"><?php print_string('participantsother', 'totara_tenant'); ?></label>
                    <?php $existinguserselector->display() ?>
                </div>
                <div class="span2 controls">
                    <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.s(get_string('add')); ?>" title="<?php p(get_string('add')); ?>" />
                    <input name="remove" id="remove" type="submit" value="<?php echo s(get_string('remove')).'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php p(get_string('remove')); ?>" />
                </div>
                <div class="span5">
                    <label for="addselect"><?php print_string('potusers', 'cohort'); ?></label>
                    <?php $potentialuserselector->display() ?>
                </div>
            </div>
            <br /><br />
            <input type="submit" name="cancel" value="<?php p(get_string('back')); ?>" />
        </div></form>

<?php

echo $OUTPUT->footer();
