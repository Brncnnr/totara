<?php
/*
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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use context;
use core\entity\user;
use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\response\participant_section as participant_section_model;
use moodle_exception;
use totara_mvc\tui_view;

/*
 * This page shows a activity form for a given section or, if participant instance id is supplied,
 * for the first section of the given instance
 */
class print_user_activity extends perform_controller {

    /**
     * @var participant_instance_model
     */
    protected $participant_instance;
    protected $layout = 'webview';

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        // The reason this controller uses participant_section rather than participant_instance_id is
        // to keep the RelationshipSelector front-end component simple for both print and regular form use cases.
        $participant_section_id = $this->get_participant_section_id();

        try {
            $this->participant_instance = participant_section_model::load_by_id($participant_section_id)->get_participant_instance();
        } catch (\Exception $exception) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        return $this->participant_instance->get_context();
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $participant_section_id = $this->get_participant_section_id();

        // Block access if the subject user or the participant got deleted
        // or if the logged in user isn't the participant.
        if ($this->participant_instance->should_be_hidden()
            || $this->participant_instance->participant->id != user::logged_in()->id) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        $this->set_url(self::get_url(['participant_section_id' => $participant_section_id]));

        $props = [
            'current-user-id' => user::logged_in()->id,
            'participant-instance-id' => (int)$this->participant_instance->id,
            'participant-section-id' => $participant_section_id,
            'print' => true,
            'subject-instance-id' => (int)$this->participant_instance->subject_instance_id,
            'printed-on-date' => $this->get_printed_on_date(),
        ];

        $activity_name = (string)$this->participant_instance->subject_instance->activity->name;

        return self::create_tui_view('mod_perform/pages/UserActivity', $props)
            ->set_title(get_string('user_activities_page_print', 'mod_perform', format_string($activity_name)));
    }

    /**
     * @return string
     */
    public static function get_base_url(): string {
        return '/mod/perform/activity/print.php';
    }

    /**
     * @return int
     */
    protected function get_participant_section_id(): int {
        return $this->get_required_param('participant_section_id', PARAM_INT);
    }

    /**
     * Get the "Printed on ..." header string.
     * Yes because this comes from the back end controller
     * it can technically be out of date by the time the page is actually printed,
     * the date is the important part rather than the exact time.
     *
     * @return string
     */
    private function get_printed_on_date(): string {
        $formatted_date = userdate(
            time(),
            get_string('strftimedatetime', 'langconfig')
        );

        return get_string('printed_on_date', 'mod_perform', $formatted_date);
    }

}
