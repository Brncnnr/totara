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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\watcher;

use mod_perform\hook\participant_instances_created;
use mod_perform\task\service\participant_section_creation;
use Throwable;

/**
 * Class participant_sections
 * @package mod_perform\watcher
 */
class participant_sections
{
    /**
     * Task hook to generate participant sections for newly created participant instances.
     *
     * @param participant_instances_created $participant_instances_created_hook
     *
     * @return void
     * @throws Throwable
     */
    public static function create_participant_sections(participant_instances_created $participant_instances_created_hook): void {
        $participation_service = new participant_section_creation();
        $participation_service->generate_sections($participant_instances_created_hook->get_dtos(), $participant_instances_created_hook->get_activity_collection());
    }
}
