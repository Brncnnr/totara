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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\workflow\helper;

use container_approval\approval as approval_container;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\workflow\workflow;

/**
 * Access checks at the module context level.
 */
class access_checks {
    /** @var approval_container */
    private $container;

    /**
     * @param approval_container $container
     */
    private function __construct(approval_container $container) {
        $this->container = $container;
    }

    /**
     * @param workflow $workflow
     * @return self
     */
    public static function from_workflow(workflow $workflow): self {
        return new self($workflow->get_container());
    }

    /**
     * @return approval_container
     */
    public function get_container(): approval_container {
        return $this->container;
    }

    /**
     * @return integer
     */
    public function get_course_id(): int {
        return $this->container->id;
    }

    /**
     * Access check against the course context.
     *
     * @throws access_denied_exception
     */
    public function check(int $userid = null): void {
        global $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        $context = $this->container->get_context();
        // Check user access on multi-tenancy.
        if ($context->is_user_access_prevented($userid)) {
            throw access_denied_exception::workflow('Cannot access workflow');
        }
        // Check course visibility.
        if (!totara_course_is_viewable($this->get_course_id(), $userid)) {
            throw access_denied_exception::workflow('Workflow is hidden');
        }
    }
}
