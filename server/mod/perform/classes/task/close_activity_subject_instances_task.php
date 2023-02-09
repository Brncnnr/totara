<?php
/*
 * This file is part of Totara Perform
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task;

use core\task\adhoc_task;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\subject_instance;

/**
 * Adhoc task to close all subject instances for a given activity.
 */
class close_activity_subject_instances_task extends adhoc_task {
    /**
     * Create the task.
     *
     * @param int $activity_id activity whose subject instances are to be closed.
     * @param int $user_id the user to use when running this task.
     *
     * @return self the created task.
     */
    public static function create(int $activity_id, int $user_id): self {
        $task = new self();
        $task->set_component('mod_perform');
        $task->set_custom_data(['activity_id' => $activity_id]);
        $task->set_userid($user_id);

        return $task;
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        $id = $this->get_custom_data()->activity_id ?? 0;

        if ($id) {
            subject_instance::close_subject_instances_in_activity(
                activity::load_by_id($id), $this->get_userid()
            );

            $is_test = defined('PHPUNIT_TEST') && PHPUNIT_TEST;
            if (!$is_test) {
                mtrace("successfully closed subject/participant instances in '$id'");
            }
        }
    }
}