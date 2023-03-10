<?php
/**
 * This file is part of Totara Learn
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package container_approval
 */

namespace container_approval\backup;

use backup_controller;
use backup_controller_exception;
use core_container\backup\backup_helper as parent_backup_helper;

/**
 * Workflow-specific functionality for backing up via the Moodle2 Backup API.
 *
 * @package core_container\backup
 */
class backup_helper extends parent_backup_helper {

    /**
     * Capability required in order to backup a workflow.
     * Checked in the course context.
     *
     * @var string
     */
    public const CAPABILITY_CONTAINER = 'container/approval:backup';

    /**
     * Capability checks for backing up an approval workflow.
     *
     * @param backup_controller $controller
     * @throws backup_controller_exception
     */
    public function check_security(backup_controller $controller): void {
        $this->require_capability($controller, self::CAPABILITY_CONTAINER);
    }

}
