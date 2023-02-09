<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\testing\mock;

use context_coursecat;
use context_course;
use totara_contentmarketplace\interactor\abstraction\create_course_interactor as interface_create_course_interactor;

class create_course_interactor implements interface_create_course_interactor {
    /**
     * @var int
     */
    private $user_id;

    /**
     * The capability that we would want to run check for the user.
     * Default to 'totara/contentmarketplace:add'.
     *
     * @var string
     */
    private $capability;

    /**
     * create_course_interactor constructor.
     * @param int|null $user_id
     * @param string   $capability
     */
    public function __construct(?int $user_id = null, string $capability = 'totara/contentmarketplace:add') {
        global $USER;
        if (empty($user_id)) {
            $user_id = $USER->id;
        }

        $this->user_id = $user_id;
        $this->capability = $capability;
    }

    /**
     * @param context_coursecat $context_category
     * @return void
     */
    public function require_add_course_to_category(context_coursecat $context_category): void {
        require_capability($this->capability, $context_category, $this->user_id);
    }

    /**
     * @param context_coursecat $context_category
     * @return bool
     */
    public function can_add_course_to_category(context_coursecat $context_category): bool {
        return has_capability($this->capability, $context_category, $this->user_id);
    }

    /**
     * @return int
     */
    public function get_actor_id(): int {
        return $this->user_id;
    }

    /**
     * @return bool
     */
    public function can_add_activity_to_course(context_course $context_course): bool {
        return has_capability(
            'mod/contentmarketplace:addinstance',
            $context_course,
            $this->user_id
        );
    }
}