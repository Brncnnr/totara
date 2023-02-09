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

namespace mod_perform\formatter\activity;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use mod_perform\models\activity\subject_instance as subject_instance_model;

/**
 * Class subject_instance
 *
 * @package mod_perform\formatter\activity
 */
class subject_instance extends entity_model_formatter {

    /**
     * @var subject_instance_model
     */
    protected $object;

    protected function get_map(): array {
        return [
            'id' => null,
            'activity' => null,
            'created_at' => date_field_formatter::class,
            'due_on' => null,
            'instance_count' => null,
            'subject_user' => null,
            'progress_status' => null,
            'availability_status' => null,
            'participant_instances' => null,
            'job_assignment' => null,
            'static_instances' => null,

            // These are deprecated since T15. Use the 'due_on' attribute instead.
            'due_date' => date_field_formatter::class,
            'is_overdue' => null,
        ];
    }

}