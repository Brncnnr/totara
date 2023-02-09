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
 * @package core_container
 */

namespace core_container\backup;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use core_container\container;
use restore_check;
use restore_controller;
use restore_controller_exception;

/**
 * Container specific functionality for restoring via the Moodle2 Backup API.
 * This is kept as a normal class because of some of the container implementation does not care
 * about backup/restore at all. Hence the container itself does not have to implement this class
 * in order to be a full functional container.
 *
 * @package core_container\backup
 */
class restore_helper {

    /**
     * @var container
     */
    protected $container;

    /**
     * restore_helper constructor.
     * @param container $container
     */
    final public function __construct(container $container) {
        $this->container = $container;
    }

    /**
     * Check the appropriate security conditions for restoring this course type.
     *
     * @param restore_controller $controller
     * @throws restore_controller_exception
     */
    public function check_security(restore_controller $controller): void {
        // Perform all initial security checks and apply (2nd param) them to settings automatically
        restore_check::check_security($controller, true);
    }

    /**
     * Get the role that the user restoring this course should be assigned to in the restored course.
     *
     * @return int|null
     */
    public function get_restorer_new_role_id(): ?int {
        global $CFG;
        return $CFG->restorernewroleid;
    }

    /**
     * Require the user has the specified capability.
     * Throws an appropriate exception if they do not.
     *
     * @param string $capability
     * @param restore_controller $controller
     * @throws restore_controller_exception
     */
    final protected function require_capability(restore_controller $controller, string $capability): void {
        if (!has_capability($capability, $this->container->get_context(), $controller->get_userid())) {
            $this->throw_missing_capability_exception($controller, $capability);
        }
    }

    /**
     * Throws an appropriate restore exception.
     *
     * @param string $capability
     * @param restore_controller $controller
     * @throws restore_controller_exception
     */
    final protected function throw_missing_capability_exception(restore_controller $controller, string $capability): void {
        throw new restore_controller_exception('restore_user_missing_capability', (object) [
            'user_id' => $controller->get_userid(),
            'courseid' => $controller->get_courseid(),
            'capability' => $capability,
        ]);
    }
}