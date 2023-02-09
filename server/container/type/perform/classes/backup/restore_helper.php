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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package container_perform
 */

namespace container_perform\backup;

use core_container\backup\restore_helper as parent_restore_helper;
use restore_controller;
use restore_controller_exception;

/**
 * Performance activity specific functionality for restoring via the Moodle2 Backup API.
 *
 * @package core_container\backup
 */
class restore_helper extends parent_restore_helper {

    /**
     * Capability required in order to restore an activity.
     * Checked in the course context.
     *
     * @var string
     */
    public const CAPABILITY_CONTAINER = 'container/perform:restore';

    /**
     * Capability checks for restoring a performance activity.
     *
     * @param restore_controller $controller
     * @throws restore_controller_exception
     */
    public function check_security(restore_controller $controller): void {
        $this->require_capability($controller, self::CAPABILITY_CONTAINER);
    }

    /**
     * Get the role that the user restoring this performance activity should be assigned to in the restored activity.
     *
     * @return int|null
     */
    public function get_restorer_new_role_id(): ?int {
        // For now we manually assign the perform creator role after restoring, so no need to do it here.
        return null;
    }

}
