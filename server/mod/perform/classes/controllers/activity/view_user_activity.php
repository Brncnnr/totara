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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use context;
use core\entity\user;
use invalid_parameter_exception;
use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\section;
use mod_perform\models\response\participant_section as participant_section_model;
use mod_perform\totara\menu\my_activities;
use moodle_exception;
use totara_mvc\tui_view;

/*
 * This page shows a activity form for a given section or, if participant instance id is supplied,
 * for the first section of the given instance
 */
class view_user_activity extends perform_controller {

    /**
     * @var participant_instance_model
     */
    protected $participant_instance;

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        $participant_instance_id = $this->get_participant_instance_id();
        $participant_section_id = $this->get_participant_section_id();

        $this->check_required_params($participant_instance_id, $participant_section_id);

        try {
            $this->participant_instance = $this->get_participant_instance($participant_instance_id, $participant_section_id);
        } catch (\Exception $exception) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        return $this->participant_instance->get_context();
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $participant_instance_id = $this->get_participant_instance_id();
        $participant_section_id = $this->get_participant_section_id();

        if ($this->participant_instance->should_be_hidden()) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        $props = [
            'current-user-id' => user::logged_in()->id,
            'user-activities-url' => (string) user_activities::get_base_url(),
        ];
        $url_args = [];

        if ($this->participant_instance->participant->id == user::logged_in()->id) {
            $props['subject-instance-id'] = (int)$this->participant_instance->subject_instance_id;
            $props['participant-instance-id'] = (int)$this->participant_instance->id;

            if ($participant_section_id > 0) {
                $props['participant-section-id'] = (int)$participant_section_id;
                $url_args['participant_section_id'] = $participant_section_id;
            } else if ($participant_instance_id > 0) {
                $url_args['participant_instance_id'] = $participant_instance_id;
            }
        }

        $url = self::get_url($url_args);
        $this->set_url($url);
        $this->get_page()->set_totara_menu_selected(my_activities::class);

        $section = $this->get_section();
        $activity = $section->get_activity();
        $name = format_string($activity->name);
        $title = $activity->get_multisection_setting()
            ? $name .' - '. format_string($section->get_display_title())
            : $name;

        return self::create_tui_view('mod_perform/pages/UserActivity', $props)
            ->set_title($title);
    }

    /**
     * @return string
     */
    public static function get_base_url(): string {
        return '/mod/perform/activity/view.php';
    }

    /**
     * @return int
     */
    protected function get_participant_instance_id(): int {
        return $this->get_optional_param('participant_instance_id', 0, PARAM_INT);
    }

    /**
     * @return int
     */
    protected function get_participant_section_id(): int {
        return $this->get_optional_param('participant_section_id', 0, PARAM_INT);
    }

    /**
     * Returns the participant instance for this controller
     *
     * @param int $participant_instance_id
     * @param int $participant_section_id
     * @return participant_instance_model
     */
    private function get_participant_instance(
        int $participant_instance_id,
        int $participant_section_id
    ): participant_instance_model {
        return $participant_section_id
            ? participant_section_model::load_by_id($participant_section_id)->get_participant_instance()
            : participant_instance_model::load_by_id($participant_instance_id);
    }

    /**
     * check if participant_instance_id and participant_section_id are both provided or no one is provided
     *
     * @param int $participant_instance_id
     * @param int $participant_section_id
     * @throws invalid_parameter_exception
     */
    private function check_required_params(int $participant_instance_id, int $participant_section_id): void {
        if (!$participant_instance_id && !$participant_section_id) {
            throw new invalid_parameter_exception(
                'At least one parameter is required, either participant_instance_id or participant_section_id'
            );
        }
    }

    /**
     * @return section
     */
    private function get_section(): section {
        $participant_instance = self::get_participant_instance(
            $this->get_participant_instance_id(),
            $this->get_participant_section_id()
        );

        $section_entity = $participant_instance->get_participant_sections()->current();

        return section::load_by_id($section_entity->section_id);
    }
}
