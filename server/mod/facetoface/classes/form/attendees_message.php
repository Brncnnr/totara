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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @package modules
 * @subpackage facetoface
 */

namespace mod_facetoface\form;

use mod_facetoface\{attendees_helper, seminar_event};

defined('MOODLE_INTERNAL') || die();

class attendees_message extends \moodleform {

    public function definition() {

        $mform =& $this->_form;

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('header', 'recipientgroupsheader', get_string('messagerecipientgroups', 'facetoface'));

        // Display select recipient by status
        $statuses = \mod_facetoface\signup\state\attendance_state::get_all_attendance_code_with(
            [
                \mod_facetoface\signup\state\user_cancelled::class,
                \mod_facetoface\signup\state\event_cancelled::class,
                \mod_facetoface\signup\state\waitlisted::class,
                \mod_facetoface\signup\state\booked::class
            ]
        );

        $seminarevent = new seminar_event($this->_customdata['s']);
        $helper = new attendees_helper($seminarevent);

        $json_users = array();
        $attendees = array();

        // Show email and ID number if they are part of the user identity setting and the current user has the capability to see them.
        $extrafields = array();
        $context = $this->_customdata['context'];
        $userfields = get_extra_user_fields($context);
        if (in_array('email', $userfields)) {
            array_push($extrafields, 'email');
        }
        if (in_array('idnumber', $userfields)) {
            array_push($extrafields, 'idnumber');
        }
        foreach ($statuses as $status) {
            // Get count of users with this status. We cannot send any messages to deleted user anyway, so no point
            // to include them here.
            $count = $helper->count_attendees_with_codes([$status], false);

            if (!$count) {
                continue;
            }

            $users = $helper->get_attendees_with_codes([$status], false);
            // Get user minimal information and pass it to the json.
            // Do not pass the $users array to the json as it contains sensible data that can be exposed.
            $recipients = array();
            foreach ($users as $user) {
                $recipient = new \stdClass();
                $recipient->id = $user->id;
                $recipient->displayname = \mod_facetoface\attendees_list_helper::output_user_for_selection($user, $extrafields);
                $recipients[] = $recipient;
            }
            $json_users[$status] = $recipients;
            $attendees = array_merge($attendees, $users);

            /** @var string|\mod_facetoface\signup\state\state $state */
            $state = \mod_facetoface\signup\state\state::from_code($status);
            $mform->addElement('checkbox', 'recipient_group['.$status.']', $state::get_string() . ' - ' . get_string('xusers', 'facetoface', $count), null, array('id' => 'id_recipient_group_'.$status));
        }

        // Display individual recipient selectors
        $mform->addElement('header', 'recipientsheader', get_string('messagerecipients', 'facetoface'));

        $options = array();
        foreach ($attendees as $a) {
            $options[$a->id] = fullname($a);
        }
        $mform->addElement('select', 'recipients', get_string('individuals', 'facetoface'), $options,  array('size' => 5));
        $mform->addElement('hidden', 'recipients_selected');
        $mform->setType('recipients_selected', PARAM_SEQUENCE);
        $mform->addElement('button', 'recipient_custom', get_string('editmessagerecipientsindividually', 'facetoface'));
        $mform->addElement('checkbox', 'cc_managers', get_string('messagecc', 'facetoface'));

        $mform->addElement('header', 'messageheader', get_string('messageheader', 'facetoface'));

        $mform->addElement('text', 'subject', get_string('messagesubject', 'facetoface'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        $mform->addElement('editor', 'body', get_string('messagebody', 'facetoface'));
        $mform->setType('body', PARAM_CLEANHTML);
        $mform->addRule('body', get_string('required'), 'required', null, 'client');

        $json_users = json_encode($json_users);
        $mform->addElement('html', '<script type="text/javascript">var recipient_groups = '.$json_users.'</script>');

        // Add action buttons
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('sendmessage', 'facetoface'));
        $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('discardmessage', 'facetoface'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * If the form submitted then send messages to recipients.
     */
    public function send_message() {
        global $DB;

        $s = $this->_customdata['s'];
        $context = $this->_customdata['context'];
        $seminarevent = new seminar_event($s);
        $helper = new attendees_helper($seminarevent);
        $data = $this->get_submitted_data();

        // Get recipients list
        $recipients = array();
        if (!empty($data->recipient_group)) {
            foreach ($data->recipient_group as $key => $value) {
                if (!$value) {
                    continue;
                }

                // Do not include the deleted users here.
                $innerrecipients = $helper->get_attendees_with_codes([$key], false);
                $recipients = array_merge($recipients, $innerrecipients);
            }
        }

        // Get indivdual recipients
        if (empty($recipients) && !empty($data->recipients_selected)) {
            // Strip , prefix
            $data->recipients_selected = substr($data->recipients_selected, 1);
            $recipients = explode(',', $data->recipients_selected);
            list($insql, $params) = $DB->get_in_or_equal($recipients);
            $recipients = $DB->get_records_sql('SELECT * FROM {user} WHERE id ' . $insql, $params);
            if (!$recipients) {
                $recipients = array();
            }
        }

        // Send messages.
        $facetofaceuser = \mod_facetoface\facetoface_user::get_facetoface_user();

        $emailcount = 0;
        $emailerrors = 0;
        foreach ($recipients as $recipient) {
            $body = $data->body['text'];
            $format = $data->body['format'];
            $bodyplain = content_to_text($body, $format);
            $body = format_text($body, $format);

            if (email_to_user($recipient, $facetofaceuser, $data->subject, $bodyplain, $body) === true) {
                $emailcount += 1;

                // Are sending to managers
                if (empty($data->cc_managers)) {
                    continue;
                }

                // User have a manager assigned for the job assignment they signedup with (or all managers otherwise).
                $managers = array();
                if (!empty($recipient->jobassignmentid)) {
                    $ja = \totara_job\job_assignment::get_with_id($recipient->jobassignmentid);
                    if (!empty($ja->managerid)) {
                        $managers[] = $ja->managerid;
                    }
                } else {
                    $managers = \totara_job\job_assignment::get_all_manager_userids($recipient->id);
                }
                if (!empty($managers)) {
                    if ($format == FORMAT_JSON_EDITOR) {
                        $bodyplain = get_string('messagesenttostaffmember', 'facetoface', fullname($recipient))."\n\n".content_to_text($body, $format);
                        $body = get_string('messagesenttostaffmember', 'facetoface', fullname($recipient))."\n\n".format_text($body, $format);
                    } else {
                        // Append to message.
                        $body = get_string('messagesenttostaffmember', 'facetoface', fullname($recipient))."\n\n".$data->body['text'];
                        $bodyplain = html_to_text($body);
                    }

                    foreach ($managers as $managerid) {
                        $manager = \core_user::get_user($managerid, '*', MUST_EXIST);
                        if (email_to_user($manager, $facetofaceuser, $data->subject, $bodyplain, $body) === true) {
                            $emailcount += 1;
                        }
                    }
                }
            } else {
                $emailerrors += 1;
            }
        }

        if ($emailcount) {
            if (!empty($data->cc_managers)) {
                $message = get_string('xmessagessenttoattendeesandmanagers', 'facetoface', $emailcount);
            } else {
                $message = get_string('xmessagessenttoattendees', 'facetoface', $emailcount);
            }
            \mod_facetoface\event\message_sent::create_from_session($seminarevent, $context, 'messageusers')->trigger();
            $returnurl = new \moodle_url('/mod/facetoface/attendees/view.php', array('s' => $s));
            \core\notification::success($message);
            if (!(defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
                redirect($returnurl);
            }

        }

        if ($emailerrors) {
            $baseurl = new \moodle_url('/mod/facetoface/attendees/messageusers.php', array('s' => $s));
            \core\notification::error(get_string('xmessagesfailed', 'facetoface', $emailerrors));
            if (!(defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
                redirect($baseurl);
            }
        }
    }
}
