<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Ben Fesili <ben.fesili@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\placeholder;

use coding_exception;
use mod_perform\controllers\activity\user_activities;
use mod_perform\controllers\activity\user_activities_select_participants;
use mod_perform\controllers\activity\view_user_activity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\notification\factory;
use mod_perform\notification\placeholder;
use moodle_url;
use totara_job\job_assignment;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;
use html_writer;

class subject_instance extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    private ?subject_instance_model $subject_instance;

    /** @var int|null $recipient_id */
    private $recipient_id = null;

    /**
     * @param subject_instance_model|null $subject_instance
     */
    public function __construct(?subject_instance_model $subject_instance) {
        $this->subject_instance = $subject_instance;
    }

    /**
     * Set the recipient id.
     *
     * @param int $recipient_id
     * @return void
     */
    public function set_recipient_id(int $recipient_id): void {
        $this->recipient_id = $recipient_id;
    }

    /**
     * @return array|option[]
     * @throws \coding_exception
     */
    public static function get_options(): array {
        return [
            option::create('days_remaining', get_string('notification_placeholder_subject_days_remaining', 'mod_perform')),
            option::create('days_active', get_string('notification_placeholder_subject_days_active', 'mod_perform')),
            option::create('duedate', get_string('notification_placeholder_subject_duedate', 'mod_perform')),
            option::create('conditional_duedate', get_string('notification_placeholder_subject_conditional_duedate', 'mod_perform')),
            option::create('fullname', get_string('notification_placeholder_subject_fullname', 'mod_perform')),
            option::create('created_at', get_string('notification_placeholder_subject_created_at', 'mod_perform')),
            option::create('days_overdue', get_string('notification_placeholder_subject_days_overdue', 'mod_perform')),
            option::create('activity_url', get_string('notification_placeholder_subject_activity_url', 'mod_perform')),
            option::create('participant_selection_url', get_string('notification_placeholder_subject_participant_url', 'mod_perform')),
            option::create('activity_name_link', get_string('notification_placeholder_subject_activity_name_linked', 'mod_perform')),
            option::create('participant_selection_link', get_string('notification_placeholder_subject_participant_linked', 'mod_perform')),
            option::create('recipient_relationship', get_string('notification_placeholder_subject_recipient_relationship', 'mod_perform')),
        ];
    }

    /**
     * @param int $subject_instance_id
     * @return static
     */
    public static function from_id(int $subject_instance_id): self {
        $instance = self::get_cached_instance($subject_instance_id);
        if (!$instance) {
            $instance = new static(
                subject_instance_model::load_by_id($subject_instance_id)
            );
            self::add_instance_to_cache($subject_instance_id, $instance);
        }

        return $instance;
    }

    /**
     * @param string $key
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     * @throws \coding_exception
     */
    public function do_get(string $key): ?string {
        if ($this->subject_instance === null) {
            throw new \coding_exception("The performance subject instance is not available");
        }

        $subject_user = $this->subject_instance->get_subject_user();
        $subject_activity = $this->subject_instance->get_activity();

        switch ($key) {
            case "days_remaining":
                $duedate = $this->subject_instance->due_date;
                if (empty($duedate)) {
                    // No due date set
                    return NULL;
                }
                $time = factory::create_clock()->get_time();
                $due_delta = placeholder::format_duration($time, $duedate);
                if ($time >= $duedate) {
                    return 0;
                }
                return $due_delta;
            case "days_active":
                $time = factory::create_clock()->get_time();
                return placeholder::format_duration($this->subject_instance->created_at, $time);
            case "duedate":
                $duedate = $this->subject_instance->due_date;
                if (empty($duedate)) {
                    // No due date set
                    return NULL;
                }
                $strftimedate = get_string('strftimedate');
                return userdate($duedate, $strftimedate);
            case "conditional_duedate":
                $duedate = $this->subject_instance->due_date;
                if (empty($duedate)) {
                    // Hide the whole sentence from the result
                    return '';
                }
                $strftimedate = get_string('strftimedate');
                $duedate = userdate($duedate, $strftimedate);
                $a = new \stdClass();
                $a->duedate = $duedate;
                return get_string('conditional_duedate_subject_placeholder', 'mod_perform', $a);
            case "fullname":
                return $subject_user->fullname;
            case "created_at":
                $strftimedate = get_string('strftimedate');
                return userdate($this->subject_instance->created_at, $strftimedate);
            case "days_overdue":
                $duedate = $this->subject_instance->due_date;
                if (empty($duedate)) {
                    // No due date set
                    return NULL;
                }
                $time = factory::create_clock()->get_time();
                $due_delta = placeholder::format_duration($time, $duedate);
                if ($time >= $duedate) {
                    return $due_delta;
                }
                return 0;
            case "activity_url":
                return view_user_activity::get_url();
            case "activity_name_link":
                $recipient_id = $this->recipient_id;
                $recipient_participant_source = participant_source::INTERNAL;

                $recipient_participant_instance = participant_instance_entity::repository()
                    ->where('subject_instance_id', $this->subject_instance->get_id())
                    ->where('participant_id', $recipient_id)
                    ->where('participant_source', $recipient_participant_source)
                    ->order_by('id')
                    ->first();
                if ($recipient_participant_instance) {
                    $url = participant_instance_model::load_by_entity($recipient_participant_instance)->get_participation_url();
                } else {
                    $url = new moodle_url(user_activities::get_base_url());
                }
                $activity_name = format_string($subject_activity->name);
                return html_writer::link($url, $activity_name);
            case "participant_selection_link":
                $url = user_activities_select_participants::get_url();
                return html_writer::link($url, get_string('user_activities_select_participants_page_title', 'mod_perform'));
            case "participant_selection_url":
                return user_activities_select_participants::get_url();
            case "recipient_relationship":
                return self::subjects_relation_to_recipient($this->subject_instance->subject_user_id,$this->recipient_id);
        }

        throw new coding_exception("Invalid key '$key'");
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->subject_instance !== null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ($key === 'activity_name_link' ||
            $key === 'participant_selection_link' ||
            $key === 'participant_selection_url' ||
            $key === 'activity_url') {
            return true;
        }

        return parent::is_safe_html($key);
    }

    /**
     * @param $subject_user_id
     * @param $recipient_id
     * @return string
     * @throws coding_exception
     */
    protected function subjects_relation_to_recipient($subject_user_id, $recipient_id): string {
        // Is a Subject
        if ($subject_user_id == $recipient_id) {
            return get_string('notification_participant_role_subject', 'mod_perform');
        }

        // Is a Manager
        $managers = job_assignment::get_all_manager_userids($subject_user_id);
        if ($managers) {
            if (in_array($recipient_id, $managers)) {
                return get_string('notification_participant_role_manager', 'mod_perform');
            }
        }

        // Is a Appraiser
        $appraisers = \totara_job\entity\job_assignment::repository()
            ->where('userid', $subject_user_id)
            ->where_not_null('appraiserid')
            ->get()
            ->pluck('appraiserid');

        if ($appraisers) {
            if (in_array($recipient_id, $appraisers)) {
                return get_string('notification_participant_role_appraiser', 'mod_perform');
            }
        }

        // Is a Manager's manager
        foreach ($managers as $manager) {
            $managers_m = job_assignment::get_all_manager_userids($manager);
            if ($managers_m) {
                if (in_array($recipient_id, $managers_m)) {
                    return get_string('notification_participant_role_managers_manager', 'mod_perform');
                }
            }
        }

        // Is a Direct report
        $direct_report = job_assignment::get_direct_staff_userids($subject_user_id);
        if ($direct_report) {
            if (in_array($recipient_id, $direct_report)) {
                return get_string('notification_participant_role_direct_report', 'mod_perform');
            }
        }

        // We couldn't find a valid relation in the subject_instance.
        return get_string('relationship_not_found', 'mod_perform');
    }
}