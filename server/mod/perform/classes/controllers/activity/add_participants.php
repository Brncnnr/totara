<?php
/*
 * This file is part of Totara Perform
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use context;
use core\entity\user;
use mod_perform\controllers\perform_controller;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\subject_instance;
use mod_perform\util;
use moodle_exception;
use moodle_url;
use totara_mvc\tui_view;

class add_participants extends perform_controller {

    /**
     * @var subject_instance
     */
    private $subject_instance;

    public function setup_context(): context {
        $subject_instance_id = $this->get_required_param('subject_instance_id', PARAM_INT);

        /** @var subject_instance_entity $entity */
        $entity = subject_instance_entity::repository()->find($subject_instance_id);

        if ($entity === null) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        $this->subject_instance = subject_instance::load_by_entity($entity);

        return $this->subject_instance->activity->get_context();
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        // Cannot add participants to pending subject instances
        if (!$this->subject_instance->can_add_participants()) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        if ($this->subject_instance->should_be_hidden()) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        util::require_can_manage_participation(user::logged_in()->id, $this->subject_instance->subject_user_id);

        $this->set_url(self::get_url(['subject_instance_id' => $this->subject_instance->id]));

        $props = [
            'subject-instance-id' => $this->subject_instance->id,
            'go-back-link' => $this->get_go_back_link(),
        ];

        return self::create_tui_view('mod_perform/pages/AddParticipants', $props)
            ->set_title(get_string('add_participants_page_title', 'mod_perform'));
    }

    public static function get_base_url(): string {
        return '/mod/perform/manage/participation/add_participants.php';
    }

    private function get_go_back_link(): string {
        return (string) new moodle_url(
            manage_participation::get_base_url() . 'subject_instances.php',
            ['activity_id' => $this->subject_instance->activity->id]
        );
    }

}
