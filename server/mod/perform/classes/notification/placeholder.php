<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\notification;

use coding_exception;
use core\entity\user;
use mod_perform\constants;
use mod_perform\controllers\activity\user_activities_select_participants;
use mod_perform\controllers\activity\view_user_activity;
use mod_perform\models\activity\activity_type;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\subject_instance;
use stdClass;
use totara_core\relationship\relationship as relationship_model;
use html_writer;

/**
 * The placeholder class, to provide a well-defined, public set of placeholders for notifications.
 */
class placeholder {

    const DUE_DATE_SUBJECT = 1;
    const DUE_DATE_PARTICIPANT = 2;

    /** @var string */
    private $recipient_fullname;

    /** @var string */
    private $activity_name;

    /** @var activity_type */
    private $activity_type;

    /** @var string */
    private $subject_fullname;

    /** @var string */
    private $participant_fullname;

    /** @var relationship_model */
    private $participant_relationship;

    /** @var string */
    private $instance_duedate;

    /** @var string */
    private $conditional_duedate;

    /** @var string */
    private $instance_created_at;

    /** @var int */
    private $instance_days_active;

    /** @var int */
    private $instance_days_remaining;

    /** @var int */
    private $instance_days_overdue;

    /** @var string */
    private $activity_url;

    /** @var string HTML link */
    private $activity_link;

    /** @var string */
    private $participant_selection_url;

    /** @var string HTML link */
    private $participant_selection_link;

    /**
     * @param string $name
     */
    public function __get(string $name) {
        $method_name = "get_{$name}";
        if (method_exists($this, $method_name)) {
            return $this->{$method_name}();
        } else if (property_exists($this, $name)) {
            return $this->{$name};
        }
        throw new coding_exception('Unknown property '.$name);
    }

    /**
     * Factory method to initialise placeholder values from an array of [key => value].
     * **Do not use this function in production code!!**
     *
     * @param string[] $values
     * @return placeholder
     * @throws coding_exception thrown when a placeholder name is invalid
     * @internal
     */
    public static function from_data(array $values): placeholder {
        $new = new placeholder();
        foreach ($values as $key => $value) {
            if (!property_exists($new, $key)) {
                throw new coding_exception("{$key} is not a valid placeholder name");
            }
            $new->{$key} = $value;
        }
        return $new;
    }

    /**
     * Returns human readable due date
     *
     * @return string|null
     */
    public function get_instance_duedate(): ?string {
        if (!empty($this->instance_duedate)) {
            // Format the due date
            $strftimedate = get_string('strftimedate');
            return userdate($this->instance_duedate, $strftimedate);
        }
        return $this->instance_duedate;
    }

    /**
     * If there's a due date return the string which should be inserted into the notification
     *
     * @return string
     */
    public function get_conditional_duedate(): string {
        $string = '';
        if (!empty($this->instance_duedate)) {
            $a = new stdClass();
            $a->duedate = $this->get_instance_duedate();
            switch ($this->conditional_duedate) {
                case self::DUE_DATE_PARTICIPANT:
                    $string = get_string('conditional_duedate_participant_placeholder', 'mod_perform', $a);
                    break;
                case self::DUE_DATE_SUBJECT:
                    $string = get_string('conditional_duedate_subject_placeholder', 'mod_perform', $a);
                    break;
                default:
                    $string = '';
                    break;
            }
        }
        return $string;
    }

    /**
     * Returns the html link for the participant selection
     *
     * @return string
     */
    public function get_participant_selection_link(): string {
        if (!empty($this->participant_selection_url)) {
            return html_writer::link(
                $this->participant_selection_url,
                get_string('user_activities_select_participants_page_title', 'mod_perform')
            );
        }

        return '';
    }

    /**
     * Returns the html link for the activity
     *
     * @return string
     */
    public function get_activity_link(): string {
        if (!empty($this->activity_url) && !empty($this->activity_name)) {
            return html_writer::link($this->activity_url, $this->get_activity_name());
        }
        return '';
    }

    /**
     * Returns the human readable string of the participant relationship
     *
     * @return string
     */
    public function get_participant_relationship(): string {
        if (!empty($this->participant_relationship)) {
            return $this->participant_relationship->get_name();
        }
        return '';
    }

    /**
     * Returns the human readable string of the activity type
     *
     * @return string
     */
    public function get_activity_type(): string {
        if (!empty($this->activity_type)) {
            return $this->activity_type->get_display_name();
        }
        return '';
    }

    /**
     * Returns the human readable string of the activity name
     */
    public function get_activity_name(): string {
        if (!empty($this->activity_name)) {
            return format_string($this->activity_name);
        }
        return '';
    }

    /**
     * Factory method to initialise placeholder values from a participant instance.
     *
     * @param participant_instance $participant_instance
     * @return placeholder
     */
    public static function from_participant_instance(participant_instance $participant_instance): placeholder {
        $time = factory::create_clock()->get_time();
        $participant = $participant_instance->get_participant();
        $subject_instance = $participant_instance->get_subject_instance();
        $activity = $subject_instance->get_activity();

        $new = new placeholder();
        $new->recipient_fullname = $participant->fullname;
        $new->activity_name = $activity->name;
        $new->activity_type = $activity->get_type();
        $new->subject_fullname = $subject_instance->subject_user->fullname;
        $new->participant_fullname = $participant->fullname;
        $new->participant_relationship = $participant_instance->get_core_relationship();
        $new->instance_duedate = $subject_instance->due_date ?? 0;
        $new->instance_created_at = $subject_instance->created_at;
        $new->instance_days_active = $new::format_duration($new->instance_created_at, $time);
        if ($new->instance_duedate) {
            $due_delta = $new::format_duration($time, $new->instance_duedate);
            if ($time >= $new->instance_duedate) {
                // Due date is here or has passed.
                $new->instance_days_remaining = 0;
                $new->instance_days_overdue = $due_delta;
            } else {
                // Not yet overdue
                $new->instance_days_remaining = $due_delta;
                $new->instance_days_overdue = 0;
            }

            $new->conditional_duedate = self::DUE_DATE_PARTICIPANT;
        }

        $new->activity_url = $participant_instance->get_participation_url();
        $new->participant_selection_url = user_activities_select_participants::get_url();

        return $new;
    }

    /**
     * Factory method to initialise placeholder values from a participant instance.
     *
     * @param subject_instance $subject_instance
     * @return placeholder
     */
    public static function from_subject_instance(subject_instance $subject_instance): placeholder {
        $time = factory::create_clock()->get_time();
        $subject = $subject_instance->get_subject_user();
        $activity = $subject_instance->get_activity();

        $new = new placeholder();
        $new->recipient_fullname = $subject->fullname;
        $new->activity_name = $activity->name;
        $new->activity_type = $activity->get_type();
        $new->subject_fullname = $subject->fullname;
        $new->participant_fullname = $subject->fullname;
        $new->participant_relationship = relationship_model::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $new->instance_duedate = $subject_instance->due_date ?? 0;
        $new->instance_created_at = $subject_instance->created_at;
        $new->instance_days_active = $new::format_duration($new->instance_created_at, $time);
        if ($new->instance_duedate) {
            $due_delta = $new::format_duration($time, $new->instance_duedate);
            if ($time >= $new->instance_duedate) {
                // Due date is here or has passed.
                $new->instance_days_remaining = 0;
                $new->instance_days_overdue = $due_delta;
            } else {
                // Not yet overdue
                $new->instance_days_remaining = $due_delta;
                $new->instance_days_overdue = 0;
            }

            $new->conditional_duedate = self::DUE_DATE_SUBJECT;
        }

        $new->activity_url = view_user_activity::get_url();
        $new->participant_selection_url = user_activities_select_participants::get_url();

        return $new;
    }

    /**
     * Format the duration time in a friendly way rather than just subtraction.
     * If the duration is longer than $cutoff i.e. working time, then the duration is round up to the nearest days.
     *
     * @param integer       $timestart      Start time in Unix timestamp
     * @param integer       $timefinish     Finish time in Unix timestamp
     * @param integer       $cutoff         Cut off time in seconds, 8 hours by default
     * @return integer      Duration in days.
     */
    public static function format_duration(int $timestart, int $timefinish, int $cutoff = HOURSECS * 8): int {
        $duration = (int)abs($timefinish - $timestart);    // Call abs() like format_time
        // Subtract full days from duration, we'll add them back later.
        $days = floor($duration / DAYSECS);
        $duration = $duration - ($days * DAYSECS);
        // If duration is greater that cutoff, round up to a full day.
        if ($duration >= $cutoff) {
            $duration = DAYSECS;
        }
        // Add full days back in to duration.
        $duration = $duration + ($days * DAYSECS);
        return (int) ($duration / DAYSECS);
    }

    /**
     * Sets the participant/recipient properties appropriately.
     *
     * @param user $participant
     * @param relationship_model $relationship
     */
    public function set_participant(user $participant, relationship_model $relationship) {
        $this->recipient_fullname = $participant->fullname;
        $this->participant_fullname = $participant->fullname;
        $this->participant_relationship = $relationship;
    }

    /**
     * Convert this to an oject we can use in a lang string
     *
     * @return stdClass
     */
    public function to_record(): stdClass {
        $record = new stdClass();
        foreach ($this as $name => $value) {
            $method_name = "get_{$name}";
            if (method_exists($this, $method_name)) {
                $value = $this->{$method_name}();
            }
            $record->{$name} = $value;
        }

        return $record;
    }

}
