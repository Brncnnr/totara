<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\event;

defined('MOODLE_INTERNAL') || die();

use \core\event\base;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\entity\competency_assignment_user;

abstract class assignment extends base {

    /**
     * Create instance of event.
     *
     * @param assignment_entity $assignment
     * @return self
     * @throws \coding_exception
     */
    public static function create_from_assignment(assignment_entity $assignment) {
        $data = [
            'objectid' => $assignment->id,
            'other' => [
                'type' => $assignment->type,
                'user_group_type' => $assignment->user_group_type,
                'user_group_id' => $assignment->user_group_id,
                'status' => $assignment->status,
            ],
            'context' => \context_system::instance()
        ];
        /** @var static $event */
        $event = static::create($data);
        $event->add_record_snapshot(assignment_entity::TABLE, (object)$assignment->get_attributes_raw());
        return $event;
    }

    /**
     * Create instance of event.
     *
     * @param competency_assignment_user $assignment_user
     * @return self
     * @throws \coding_exception
     */
    public static function create_from_assignment_user(competency_assignment_user $assignment_user) {
        $data = [
            'objectid' => $assignment_user->id,
            'relateduserid' => $assignment_user->user_id,
            'other' => [
                'assignment_id' => $assignment_user->assignment_id,
                'competency_id' => $assignment_user->competency_id,
            ],
            'context' => \context_system::instance()
        ];
        /** @var static $event */
        $event = static::create($data);
        $event->add_record_snapshot(competency_assignment_user::TABLE, (object)$assignment_user->get_attributes_raw());
        return $event;
    }

}
