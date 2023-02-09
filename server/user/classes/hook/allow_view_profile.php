<?php
/**
 *
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @author Sam Hemelyrk <sam.hemelryk@totaralearning.com>
 * @package core_user
 *
 */

namespace core_user\hook;

use context_course;
use stdClass;
use totara_core\hook\base;

/**
 * Class allow_view_profile
 *
 * Hook to allow components and plugins to grant access to one user to
 * view another user's profile.
 *
 */
class allow_view_profile extends base {

    /**
     * @var int $target_user_id The user whose profile is being viewed.
     */
    public $target_user_id;

    /**
     * @var int $viewing_user_id The user who is trying to view the target user's profile.
     */
    public $viewing_user_id;

    /**
     * @var stdClass|null
     */
    protected $course;

    /**
     * @var context_course|null
     */
    protected $course_context;

    /**
     * @var bool $allow_view_profile Whether the viewing user should be allowed to view the target user's profile
     */
    private $allow_view_profile = false;

    /**
     * allow_view_profile constructor.
     *
     * @param int $target_user_id
     * @param int $viewing_user_id
     * @param stdClass|null $course the course object
     * @param context_course|null $course_context
     */
    public function __construct(
        int $target_user_id,
        int $viewing_user_id,
        ?stdClass $course = null,
        ?context_course $course_context = null
    ) {
        $this->target_user_id = $target_user_id;
        $this->viewing_user_id = $viewing_user_id;
        $this->course = $course;
        $this->course_context = $course_context;

        if ($this->course === null && $this->course_context !== null) {
            throw new \coding_exception('If a course context is passed a course object must be passed.');
        }
        if ($this->course_context !== null && $this->course_context->instanceid != $this->course->id) {
            throw new \coding_exception('The course object and course context do not match.');
        }
    }

    /**
     * @return stdClass|null
     */
    public function get_course(): ?stdClass {
        return $this->course;
    }

    /**
     * Returns the course context
     *
     * @return context_course|null
     */
    public function get_course_context(): ?context_course {
        if ($this->course !== null && $this->course_context === null) {
            $this->course_context = \context_course::instance($this->course->id);
        }
        return $this->course_context;
    }

    public function give_permission() {
        $this->allow_view_profile = true;
    }

    public function has_permission(): bool {
        return $this->allow_view_profile;
    }
}