<?php
/**
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\watcher;

use mod_perform\hook\subject_instances_created;
use mod_perform\task\service\subject_static_instance_creation;
use Throwable;

/**
 * Class subject_static_instances
 * @package mod_perform\watcher
 */
class subject_static_instances {

    /**
     * Task hook to generate static subject instances for newly created subject instances.
     *
     * @param subject_instances_created $subject_instances_created_hook
     * @throws Throwable
     */
    public static function create_subject_static_instances(subject_instances_created $subject_instances_created_hook): void {
        $subject_static_service = new subject_static_instance_creation();
        $subject_static_service->generate_instances($subject_instances_created_hook->get_dtos());
    }

}
