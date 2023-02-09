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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use mod_facetoface\signup\state\{
    attendance_state,
    booked,
    waitlisted,
    fully_attended,
    partially_attended,
    no_show,
    user_cancelled,
    unable_to_attend,
    requested,
    requestedadmin,
    requestedrole
};

class mod_facetoface_notification_form extends moodleform {

    private $recipients = [
        'past_events' => '0',
        'events_in_progress' => '0',
        'upcoming_events' => '0',
        'fully_attended' => '0',
        'partially_attended' => '0',
        'unable_to_attend' => '0',
        'no_show' => '0',
        'waitlisted' => '0',
        'user_cancelled' => '0',
        'requested' => '0',
    ];

    function definition() {

        $mform =& $this->_form;

        /** @var facetoface_notification $notification */
        $notification = $this->_customdata['notification'];

        $mform->addElement('hidden', 'id', (int)$notification->id);
        $mform->setType('id', PARAM_INT);

        // Hide scheduling/recipient selectors for automatic notifications
        if ($notification->type == MDL_F2F_NOTIFICATION_AUTO) {

            $description = $notification->get_condition_description();
            $recipients = $notification->get_recipient_description();

            if (!empty($description)) {
                $mform->addElement('static', '', get_string('scheduling', 'facetoface'), $description);
            }
            if (!empty($recipients)) {
                $mform->addElement('static', '', get_string('recipients', 'facetoface'), $recipients);
            }
        } else {
            if ($notification->type == MDL_F2F_NOTIFICATION_SCHEDULED) {
                $sched_units = array(
                    MDL_F2F_SCHEDULE_UNIT_HOUR  => get_string('hours'),
                    MDL_F2F_SCHEDULE_UNIT_DAY   => get_string('days'),
                    MDL_F2F_SCHEDULE_UNIT_WEEK  => get_string('weeks'),
                    MDL_F2F_SCHEDULE_UNIT_MONTH => core_text::strtolower(get_string('months')),
                    MDL_F2F_SCHEDULE_UNIT_YEAR  => get_string('years'),
                );

                $sched_types = array(
                    MDL_F2F_CONDITION_BEFORE_SESSION => get_string('beforestartofsession', 'facetoface'),
                    MDL_F2F_CONDITION_AFTER_SESSION  => get_string('afterendofsession', 'facetoface'),
                    MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS => get_string('beforeregistrationends', 'facetoface')
                );

                $group = array();
                $group[] = $mform->createElement('select', 'scheduleamount', '', range(0, 24));
                $group[] = $mform->createElement('select', 'scheduleunit', '', $sched_units);
                $group[] = $mform->createElement('select', 'conditiontype', '', $sched_types);

                $mform->addGroup($group, 'schedule', get_string('scheduling', 'facetoface'), array(' '), false);

                $mform->addElement('html', html_writer::empty_tag('br'));
            }

            $group = [];
            $string = \html_writer::span(get_string('notification_booking_status', 'mod_facetoface'), '', ['class' => 'recipients_status']);
            $group[] = $mform->createElement('static', 'booking_status', '', $string);
            $string = get_string('booked_status', 'mod_facetoface', \core_text::strtolower(get_string('status_past_events', 'mod_facetoface')));
            $group[] = $mform->createElement('advcheckbox', 'past_events', $string);
            $string = get_string('booked_status', 'mod_facetoface', \core_text::strtolower(get_string('status_events_in_progress', 'mod_facetoface')));
            $group[] = $mform->createElement('advcheckbox', 'events_in_progress', $string);
            $string = get_string('booked_status', 'mod_facetoface', \core_text::strtolower(get_string('status_upcoming_events', 'mod_facetoface')));
            $group[] = $mform->createElement('advcheckbox', 'upcoming_events', $string);

            $string = \html_writer::span(get_string('notification_attendance_status', 'mod_facetoface'), '', ['class' => 'recipients_status']);
            $group[] = $mform->createElement('static', 'attendance_status', '', $string);
            $group[] = $mform->createElement('advcheckbox', 'fully_attended', get_string('status_fully_attended', 'mod_facetoface'));
            $group[] = $mform->createElement('advcheckbox', 'partially_attended', get_string('status_partially_attended', 'mod_facetoface'));
            $group[] = $mform->createElement('advcheckbox', 'unable_to_attend', get_string('status_unable_to_attend', 'mod_facetoface'));
            $group[] = $mform->createElement('advcheckbox', 'no_show', get_string('status_no_show', 'mod_facetoface'));
            $string = \html_writer::span(get_string('notification_other_status', 'mod_facetoface'), '', ['class' => 'recipients_status']);
            $group[] = $mform->createElement('static', 'other_status', '', $string);
            $group[] = $mform->createElement('advcheckbox', 'waitlisted', get_string('status_waitlisted', 'mod_facetoface'));
            $group[] = $mform->createElement('advcheckbox', 'user_cancelled', get_string('status_user_cancelled', 'mod_facetoface'));
            $group[] = $mform->createElement('advcheckbox', 'requested', get_string('status_pending_requests', 'mod_facetoface'));

            $mform->addGroup($group, 'recipients', get_string('recipients', 'mod_facetoface'), '', true);
            $mform->addHelpButton('recipients', 'recipients', 'facetoface');
            $mform->addRule('recipients', get_string('error:norecipientsselected', 'facetoface'), 'required');
            $mform->setType('recipients', PARAM_SEQUENCE);

            foreach ($this->recipients as $el) {
                $mform->setType("recipients[$el]", PARAM_BOOL);
            }
            // Prevent passing null as input parameter in PHP 8.1
            $recipients = is_null($notification->recipients) ? $this->recipients : json_decode($notification->recipients);
            foreach ($recipients as $element => $val) {
                // Old one, compatibility with < t13
                $val = $this->set_recipient_value($element, (int)$val);
                $mform->setDefault("recipients[$element]", (bool)$val);
            }

            $renderer =& $mform->defaultRenderer();
            $elementtemplate = '<div class="fitem">{element} <label>{label}</label></div>';
            $renderer->setGroupElementTemplate($elementtemplate, 'recipients');
        }

        $mform->addElement('html', html_writer::empty_tag('br'));

        // Display template picker
        if ($this->_customdata['templates']) {
            $tpls = array();
            $tpls[0] = '';
            foreach ($this->_customdata['templates'] as $tpl) {
                $tpls[$tpl->id] = $tpl->title;
            }

            $mform->addElement('select', 'templateid', get_string('template', 'facetoface'), $tpls);
        }

        // Display message content settings.
        $mform->addElement('text', 'title', get_string('title', 'facetoface'));
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('editor', 'body_editor', get_string('body', 'facetoface'));
        $mform->addHelpButton('body_editor', 'body', 'facetoface');
        $mform->setType('body', PARAM_RAW);

        $mform->addElement('html', html_writer::empty_tag('br'));

        $mform->addElement('checkbox', 'ccmanager', get_string('ccmanager', 'facetoface'), get_string('ccmanager_note', 'facetoface'));
        $mform->setType('ccmanager', PARAM_INT);

        $mform->addElement('editor', 'managerprefix_editor', get_string('managerprefix', 'facetoface'));
        $mform->setType('managerprefix_editor', PARAM_RAW);

        // Active/Inactive radio buttons.
        $group = [
            $mform->createElement('radio', 'status', null, get_string('inactive', 'mod_facetoface'), 0),
            $mform->createElement('radio', 'status', null, get_string('active', 'mod_facetoface'), 1),
        ];
        $mform->addGroup($group, null, get_string('status'), '', true);
        $mform->setType('status', PARAM_INT);

        $this->add_action_buttons(true, get_string('save', 'admin'));
    }


    /**
     * Validate form data
     *
     * @access  public
     * @param   array   $data
     * @param   array   $files
     * @return  array
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $mform =& $this->_form;

        if ($mform->elementExists('recipients')) {
            $recipients = $mform->getElement('recipients');
            $elements = $recipients->getElements();

            $has_val = false;
            foreach ($elements as $element) {
                if (in_array($element->getName(), array_keys($this->recipients))) {
                    if ($element->getValue()) {
                        $has_val = true;
                        break;
                    }
                }
            }

            if (!$has_val) {
                $errors['recipients'] = get_string('error:norecipientsselected', 'facetoface');
            }
        }

        return $errors;
    }

    /**
     * Compatibility with < t13
     *
     * @param string $element
     * @param int $value
     * @return int
     */
    private function set_recipient_value(string $element, int $value): int {
        /** @var facetoface_notification $notification */
        $notification = $this->_customdata['notification'];
        switch ($element) {
            case 'past_events':
                $value = ((int)$notification->booked == MDL_F2F_RECIPIENTS_ALLBOOKED) ? 1 : $value;
                break;
            case 'events_in_progress':
                $value = ((int)$notification->booked == MDL_F2F_RECIPIENTS_ALLBOOKED) ? 1 : $value;
                break;
            case 'upcoming_events':
                $value = ((int)$notification->booked == MDL_F2F_RECIPIENTS_ALLBOOKED) ? 1 : $value;
                break;
            case 'fully_attended':
                $value = ((int)$notification->booked == MDL_F2F_RECIPIENTS_ATTENDED) ? 1 : $value;
                break;
            case 'no_show':
                $value = ((int)$notification->booked == MDL_F2F_RECIPIENTS_NOSHOWS) ? 1 : $value;
                break;
            case 'waitlisted':
                $value = ((int)$notification->waitlisted == 1) ? 1 : $value;
                break;
            case 'user_cancelled':
                $value = ((int)$notification->cancelled == 1) ? 1 : $value;
                break;
            case 'requested':
                $value = ((int)$notification->requested == 1) ? 1 : $value;
                break;
        }
        return $value;
    }
}
