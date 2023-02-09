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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\testing;

use coding_exception;
use context_coursecat;
use core\orm\query\builder;

/**
 * A helper functionalities that are used in php unit mainly.
 */
class helper {
    /**
     * helper constructor.
     */
    private function __construct() {
        // Prevent this class from construction.
    }

    /**
     * Get the course creator role's id.
     *
     * @return int
     */
    public static function get_course_creator_role(): int {
        $db = builder::get_db();
        $records = $db->get_records(
            'role',
            ['archetype' => 'coursecreator'],
            'id',
            'id',
            0,
            1
        );

        $record = reset($records);
        return $record->id;
    }

    /**
     * Get the site manager role's id.
     *
     * @return int
     */
    public static function get_site_manager_role(): int {
        $db = builder::get_db();
        $records = $db->get_records(
            'role',
            ['archetype' => 'manager'],
            'id',
            'id',
            0,
            1
        );

        $record = reset($records);
        return $record->id;
    }

    /**
     * Get the authenticated user role's id.
     *
     * @return int
     */
    public static function get_authenticated_user_role(): int {
        return builder::get_db()->get_field('role', 'id', ['shortname' => 'user']);
    }

    /**
     * Returns student role's id.
     *
     * @return int
     */
    public static function get_student_role(): int {
        $db = builder::get_db();
        $records = $db->get_records(
            'role',
            ['archetype' => 'student'],
            'id',
            'id',
            0,
            1
        );

        $record = reset($records);
        return $record->id;
    }

    /**
     * Returns the default course category id within the system.
     *
     * @return int
     */
    public static function get_default_course_category_id(): int {
        $db = builder::get_db();
        return $db->get_field('course_categories', 'id', ['issystem' => 0]);
    }

    /**
     * Returns the default course category context within the system.
     *
     * @return context_coursecat
     */
    public static function get_default_course_category_context(): context_coursecat {
        return context_coursecat::instance(self::get_default_course_category_id());
    }

    /**
     * @return int
     */
    public static function get_tenant_domain_manager_id(): int {
        $db = builder::get_db();
        $records = $db->get_records(
            'role',
            ['archetype' => 'tenantdomainmanager'],
            'id',
            'id',
            0,
            1
        );

        if (empty($records)) {
            throw new coding_exception(
                "The tenant domain manager role does not exist"
            );
        }

        $record = reset($records);
        return $record->id;
    }
}