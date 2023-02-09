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
 * Class allow_view_profile_field
 *
 * Hook to allow components and plugins to grant access to one user to
 * view a specific field of another user's profile.
 *
 */
class allow_view_profile_field extends base {

    /**
     * @var string $field Name of the field to check
     */
    public $field;

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
     * @var bool $allow_view_profile_field Whether the viewing user is allowed to view the target user's profile field
     */
    private $allow_view_profile_field = false;

    /**
     * allow_view_profile_field constructor.
     * @param string $field
     * @param int $target_user_id
     * @param int $viewing_user_id
     * @param stdClass|null $course the course object
     * @param context_course|null $course_context
     */
    public function __construct(
        string $field,
        int $target_user_id,
        int $viewing_user_id,
        ?stdClass $course = null,
        ?context_course $course_context = null
    ) {
        $this->field = $field;
        $this->target_user_id = $target_user_id;
        $this->viewing_user_id = $viewing_user_id;
        $this->course = $course;
        $this->course_context = $course_context;
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
        return $this->course_context;
    }

    public function give_permission() {
        $this->allow_view_profile_field = true;
    }

    public function has_permission(): bool {
        return $this->allow_view_profile_field;
    }
}