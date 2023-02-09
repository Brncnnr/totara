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
namespace totara_contentmarketplace\interactor\abstraction;

use context_course;
use context_coursecat;

/**
 * An interactor interface that can help to resolve if user is able to create
 * a course within given course category.
 */
interface create_course_interactor {
    /**
     * @param context_coursecat $context_category
     * @return void
     */
    public function require_add_course_to_category(context_coursecat $context_category): void;

    /**
     * @param context_coursecat $context_category
     * @return void
     */
    public function can_add_course_to_category(context_coursecat $context_category): bool;

    /**
     * @return bool
     */
    public function can_add_activity_to_course(context_course $context_course): bool;

    /**
     * Returns the actor's id.
     * @return int
     */
    public function get_actor_id(): int;
}