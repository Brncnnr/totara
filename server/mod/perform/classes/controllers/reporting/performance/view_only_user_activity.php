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

namespace mod_perform\controllers\reporting\performance;

use context;
use core\entity\user;
use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\subject_instance;
use mod_perform\util;
use moodle_exception;
use totara_mvc\tui_view;

/*
 * This page shows a activity form for a given section or, if participant instance id is supplied,
 * for the first section of the given instance
 */
class view_only_user_activity extends perform_controller {

    /**
     * @var subject_instance
     */
    protected $subject_instance;

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        $subject_instance_id = $this->get_subject_instance_id();

        try {
            $this->subject_instance = subject_instance::load_by_id($subject_instance_id);
        } catch (\Exception $exception) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        if ($this->subject_instance->should_be_hidden()) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        if (!util::can_report_on_user($this->subject_instance->subject_user_id, user::logged_in()->id)
            || $this->subject_instance->is_pending()) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        return $this->subject_instance->get_context();
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $this->set_url(self::get_url([
            'subject_instance_id' => $this->get_subject_instance_id(),
            'section_id' => $this->get_section_id(),
        ]));

        $props = [
            'current-user-id' => user::logged_in()->id,
            'subject-instance-id' => $this->get_subject_instance_id(),
            'section-id' => $this->get_section_id(),
        ];

        return self::create_tui_view('mod_perform/pages/ViewOnlyUserActivity', $props)
            ->set_title(get_string('user_activities_page_title', 'mod_perform'));
    }

    /**
     * @return string
     */
    public static function get_base_url(): string {
        return '/mod/perform/reporting/performance/view_user_activity.php';
    }

    /**
     * @return int
     */
    protected function get_subject_instance_id(): int {
        return $this->get_required_param('subject_instance_id', PARAM_INT);
    }

    /**
     * @return int|null
     */
    protected function get_section_id(): ?int {
        return $this->get_optional_param('section_id', null, PARAM_INT);
    }

}