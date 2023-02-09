<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models;

use core\orm\entity\entity;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\configuration_change;

class activity_log_factory {

    /**
     * Instantiates an activity log model, choosing the appropriate type based on the entity passed in.
     *
     * @param entity $entity
     * @return activity_log
     */
    public static function create(entity $entity): activity_log {
        switch (get_class($entity)) {
            case configuration_change::class:
                return activity_log\configuration_change::load_by_entity($entity);
            case competency_achievement::class:
                return activity_log\competency_achievement::load_by_entity($entity);
            case competency_assignment_user_log::class:
                return activity_log\assignment::load_by_entity($entity);
        }

        throw new \coding_exception('Invalid entity', 'Entity not valid in activity_log_factory::create: ' . get_class($entity));
    }
}