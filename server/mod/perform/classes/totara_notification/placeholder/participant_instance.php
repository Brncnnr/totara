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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\placeholder;

use html_writer;
use coding_exception;
use mod_perform\controllers\activity\user_activities_select_participants;
use mod_perform\models\activity\participant_source;
use mod_perform\notification\factory;
use mod_perform\notification\placeholder;
use moodle_url;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;
use mod_perform\controllers\activity\user_activities;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\participant_instance as participant_instance_model;

class participant_instance extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /** @var participant_instance_model|null $participant_instance */
    private $participant_instance = null;

    /** @var int|null $recipient_id */
    private $recipient_id = null;

    /**
     * Perform placeholder constructor.
     */
    public function __construct(?participant_instance_model $participant_instance) {
        $this->participant_instance = $participant_instance;
    }

    /**
     * @param int $participant_instance_id
     *
     * @return self
     */
    public static function from_id(int $participant_instance_id): self {

        $instance = self::get_cached_instance($participant_instance_id);
        if (!$instance) {
            $instance = new static(
                participant_instance_model::load_by_id($participant_instance_id)
            );
            self::add_instance_to_cache($participant_instance_id, $instance);
        }
        return $instance;
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
     * @return option[]
     */
    public static function get_options(): array {
        // Went for notification_placeholder_... to prevent overlap with existing strings
        return [
            option::create('relationship', get_string('notification_placeholder_participant_relationship', 'mod_perform')),
            option::create('participant_full_name', get_string('notification_placeholder_participant_fullname', 'mod_perform')),
            option::create('activity_name_link', get_string('notification_placeholder_activity_name_linked', 'mod_perform')),
            option::create('days_active', get_string('notification_placeholder_subject_days_active', 'mod_perform'))
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->participant_instance !== null;
    }

    /**
     * @param string $key
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     */
    public function do_get(string $key): ?string {
        if ($this->participant_instance === null) {
            throw new coding_exception("The performance participant instance is not available");
        }

        switch ($key) {
            case 'relationship':
                return $this->participant_instance->get_core_relationship()->get_name();
            case 'participant_full_name':
                return $this->participant_instance->get_participant()->fullname;
            case 'activity_name_link':
                // When the recipient_id is set, we can make the link to the activity specific to the recipient.
                $recipient_id = $this->recipient_id;
                if ($this->participant_instance->participant_source == participant_source::INTERNAL) {
                    $recipient_participant_source = participant_source::INTERNAL;
                } else {
                    $recipient_participant_source = participant_source::EXTERNAL;
                }
                $recipient_participant_instance = participant_instance_entity::repository()
                    ->where('subject_instance_id', $this->participant_instance->get_subject_instance()->id)
                    ->where('participant_id', $recipient_id)
                    ->where('participant_source', $recipient_participant_source)
                    ->order_by('id')
                    ->first();

                if ($recipient_participant_instance) {
                    $url = participant_instance_model::load_by_entity($recipient_participant_instance)->get_participation_url();
                } else {
                    $url = new moodle_url(user_activities::get_base_url());
                }
                $activity_name = $this->participant_instance->get_subject_instance()->activity->name;
                return html_writer::link($url, format_string($activity_name));
            case 'days_active':
                $time = factory::create_clock()->get_time();
                return placeholder::format_duration($this->participant_instance->created_at, $time);
        }

        throw new coding_exception("Invalid key '$key'");
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ('activity_name_link' === $key) {
            return true;
        }
        return parent::is_safe_html($key);
    }
}