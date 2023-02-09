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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment\helper;

use context_module;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\assignment;
use stdClass;

/**
 * Access checks at the module context level.
 */
class access_checks {
    /** @var stdClass|\cm_info */
    private $cm;

    /**
     * @param stdClass $cm
     */
    private function __construct(stdClass $cm) {
        $this->cm = $cm;
    }

    /**
     * @param stdClass $cm
     * @return self
     */
    public static function from_course_module(stdClass $cm): self {
        return new self($cm);
    }

    /**
     * @param assignment $assignment
     * @return self
     */
    public static function from_assignment(assignment $assignment): self {
        return self::from_course_module($assignment->get_course_module());
    }

    /**
     * @return stdClass
     */
    public function get_cm(): stdClass {
        return $this->cm;
    }

    /**
     * @return integer
     */
    public function get_course_id(): int {
        return $this->cm->course;
    }

    /**
     * Access check against the module context.
     *
     * @throws access_denied_exception
     */
    public function check(int $userid = null): void {
        global $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        $context = context_module::instance($this->cm->id);
        // Check user access on multi-tenancy.
        if ($context->is_user_access_prevented($userid)) {
            throw access_denied_exception::assignment('Cannot access assignment');
        }
        // Check course visibility.
        if (!totara_course_is_viewable($this->get_course_id(), $userid)) {
            throw access_denied_exception::workflow('Parent workflow is hidden');
        }
        // TODO: what about module visibility?
        // if (empty($this->cm->visible)) {
        //     throw access_denied_exception::assignment('Cannot access assignment?');
        // }
    }
}
