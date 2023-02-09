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
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

define('MAX_USERS_PER_PAGE', 1000);

$s      = required_param('s', PARAM_INT); // facetoface session ID
$add    = optional_param('add', false, PARAM_BOOL);
$remove = optional_param('remove', false, PARAM_BOOL);

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);
$course = $DB->get_record('course', array('id' => $seminar->get_course()));

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($seminar->get_name());
$PAGE->set_heading($course->fullname);

// Check essential permissions.
require_login($course, false, $cm);
require_capability('mod/facetoface:viewattendees', $context);

// Recipients.
$recipient_helper = new \mod_facetoface\recipients_list_helper();
$recipient_helper->set_recipients();

// Handle the POST actions sent to the page.
if ($data = data_submitted()) {
    // Add.
    if ($add and has_capability('mod/facetoface:addrecipients', $context)) {
        $recipient_helper->add_recipients($data);
    }
    // Remove.
    if ($remove and has_capability('mod/facetoface:removerecipients', $context)) {
        $recipient_helper->remove_recipients($data);
    }
}

// Set/Prepare the list of currently selected recipients for template.
$recipient_helper->set_existing_recipients();
// Set/Prepare all available attendees for template.
$recipient_helper->set_potential_recipients($seminarevent);
// Set recipients value for the form.
$recipients = implode(',', $recipient_helper->get_recipients());
// Set form url.
$url = new moodle_url('/mod/facetoface/attendees/editrecipients.php');
// Prints a form to add/remove users from the recipients list.
?>

<form id="assignform" method="post" action="<?php echo $url->out(); ?>">
    <div>
        <input type="hidden" name="s" value="<?php echo($s) ?>" />
        <input type="hidden" name="sesskey" value="<?php p(sesskey()) ?>" />
        <input type="hidden" name="add" value="" />
        <input type="hidden" name="remove" value="" />
        <input type="hidden" name="recipients" value="<?php echo($recipients) ?>" />
        <table summary="" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
            <tr>
                <td valign="top" width="40%">
                    <label for="removeselect"><?php print_string('existingrecipients', 'facetoface'); ?></label>
                    <br />
                    <select name="removeselect[]" size="22" style="width: 100%;" id="removeselect" multiple="multiple"
                            onfocus="getElementById('assignform').add.disabled=true;
                           getElementById('assignform').remove.disabled=false;
                           getElementById('assignform').addselect.selectedIndex=-1;">

                        <?php
                        $extrafields = array();
                        $userfields = get_extra_user_fields($PAGE->context);
                        if (in_array('email', $userfields)) {
                            array_push($extrafields, 'email');
                        }
                        if (in_array('idnumber', $userfields)) {
                            array_push($extrafields, 'idnumber');
                        }
                        $existingusers = $recipient_helper->get_existing_recipients();
                        if ($existingusers) {
                            foreach ($existingusers as $existinguser) {
                                $fullname = \mod_facetoface\attendees_list_helper::output_user_for_selection($existinguser, $extrafields);
                                echo "<option value=\"{$existinguser->id}\">{$fullname}</option>\n";
                            }
                        } else {
                            echo '<option/>'; // empty select breaks xhtml strict
                        }
                        ?>

                    </select>
                </td>
                <td valign="middle" style="width: 20%; text-align: center;">
                    <p class="arrow_button">
                        <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('add'); ?>" title="<?php print_string('add'); ?>" style="width: 75%; text-align: center; margin: auto; " onClick="$('form#assignform input[name=add]').val(1);" />
                        <br />
                        <input name="remove" id="remove" type="submit" value="<?php echo $OUTPUT->rarrow().'&nbsp;'.get_string('remove'); ?>" title="<?php print_string('remove'); ?>" style="width: 75%; text-align: center;" onCLick="$('form#assignform input[name=remove]').val(1);" />
                    </p>
                </td>
                <td valign="top" width="40%">
                    <label for="addselect"><?php print_string('potentialrecipients', 'facetoface'); ?></label>
                    <br />
                    <select name="addselect[]" size="22" style="width: 100%;" id="addselect" multiple="multiple"
                            onfocus="getElementById('assignform').add.disabled=false;
                           getElementById('assignform').remove.disabled=true;
                           getElementById('assignform').removeselect.selectedIndex=-1;">
                        <?php
                        $availableusers = $recipient_helper->get_potential_recipients();
                        if ($availableusers) {
                            foreach ($availableusers as $user) {
                                $fullname = \mod_facetoface\attendees_list_helper::output_user_for_selection($user, $extrafields);
                                echo "<option value=\"{$user->id}\">{$fullname}</option>\n";
                            }
                        } else {
                            echo '<option/>'; // empty select breaks xhtml strict
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>

    </div>
</form>
