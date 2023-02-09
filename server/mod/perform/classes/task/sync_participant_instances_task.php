<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task;

use core\task\scheduled_task;
use mod_perform\task\service\participant_instance_sync;

/**
 * Synchronise participant instances
 *
 * This will go through all subject instances that have been flagged as needing synchronisation. For these, participant
 * instances are created or closed according to current relationships and configuration settings.
 */
class sync_participant_instances_task extends scheduled_task {

    public function get_name() {
        return get_string('sync_participant_instances_task', 'mod_perform');
    }

    public function execute() {
        (new participant_instance_sync())->sync_instances();
    }

}
