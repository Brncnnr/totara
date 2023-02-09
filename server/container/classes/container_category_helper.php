<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_container
 */
namespace core_container;

use core_container\facade\category_id_number_provider;
use core_container\facade\category_name_provider;
use coursecat;

/**
 * A helper to help on creating a default course category.
 */
final class container_category_helper {
    /**
     * Just a helper function to concat the container_type with the parent $category id,
     * as we would want this kind of concat to be the same across the system.
     *
     * @param string $container_type
     * @param int $category_id
     *
     * @return string
     */
    private static function build_id_number(string $container_type, int $category_id): string {
        /** @var container|string $container_class */
        $container_class = factory::get_container_class($container_type);
        if (is_subclass_of($container_class, category_id_number_provider::class)) {
            return $container_class::get_container_category_id_number();
        }

        return "{$container_type}-{$category_id}";
    }

    /**
     * Get the tenant category ID from the specified tenant ID or the specified user's tenant.
     *
     * @param int|null $tenant_id
     * @param int|null $user_id
     * @return int
     */
    private static function get_tenant_category_id(?int $tenant_id, ?int $user_id): int {
        global $DB, $USER;

        $top_level_id = 0;

        if (empty($user_id)) {
            $user_id = $USER->id;
        }

        if (empty($tenant_id)) {
            if ($user_id == $USER->id && property_exists($USER, 'tenantid')) {
                $tenant_id = $USER->tenantid;
            } else {
                $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $user_id]);
            }
        }

        $tenant_category_id = $DB->get_field('tenant', 'categoryid', ['id' => $tenant_id]);

        if (!$tenant_category_id) {
            // If no tenant category ID could be resolved, then fallback to this.
            return $top_level_id;
        }

        return $tenant_category_id;
    }

    /**
     * Finding the default category's id given by the container's type and the actor - which is $user_id in this case.
     *
     *
     * @param string        $container_type
     * @param bool          $create_on_missing
     *
     * @param string|null   $id_number          If id_number is not provided, it will be concatenate between the
     *                                          container_type and the parent's id, or if the container class
     *                                          is implementing the interface {@see category_id_number_provider}
     *                                          then the function itself will try to invoke
     *                                          {@see category_id_number_provider::get_container_category_id_number()}
     *
     * @param int|null      $user_id            If it is not provided, user in session will be used.
     *                                          The tenant_id param overrides the user's tenant ID.
     *
     * @param int|null      $tenant_id          If it is not provided, it will be ignored.
     *
     * @return int|null
     */
    public static function get_default_category_id(string $container_type, bool $create_on_missing = true,
                                                    ?string $id_number = null, ?int $user_id = null, ?int $tenant_id = null): ?int {
        global $CFG, $DB;

        // This is the very top level of course categories.
        $parent_id = 0;

        if (!empty($CFG->tenantsenabled)) {
            // Multi-tenancy compatible.
            $parent_id = static::get_tenant_category_id($tenant_id, $user_id);
        }

        if ($id_number === null || $id_number === '') {
            // Generate the default unique id number for category, based on the category's parent id.
            // And this category should be the one where container is belong to
            $id_number = static::build_id_number($container_type, $parent_id);
        }

        $category_id = $DB->get_field('course_categories', 'id', [
            'idnumber' => $id_number,
            'parent' => $parent_id
        ]);
        if ($category_id) {
            return $category_id;
        }

        if ($create_on_missing) {
            return static::create_container_category($container_type, $parent_id)->id;
        }

        return null;
    }

    /**
     * The function is for create a new record of table {course_categories} with provided parameters.
     *
     * If the parameter $name is not provided, it will try to look it up the container type whether
     * the container class is implement the interface {@see category_name_provider}. If it is, then it will
     * try to invoke {@see category_name_provider::get_container_category_name()}
     *
     * If the parameter $id_number is not provided, it will try to look it up the container type whether
     * the container class is implement the interface {@see category_id_number_provider}. If it is, then
     * it will try to invoke {@see category_id_number_provider::get_container_category_id_number()}
     *
     * @param string        $container_type
     * @param int           $parent_category_id
     * @param string|null   $id_number
     * @param string|null   $name
     *
     * @return coursecat
     */
    public static function create_container_category(string $container_type, int $parent_category_id,
                                                     string $id_number = null, ?string $name = null): coursecat {
        $container_class = factory::get_container_class($container_type);

        // Finding the name for the category.
        if (null === $name || '' === $name) {
            if (is_subclass_of($container_class, category_name_provider::class)) {
                $name = $container_class::get_container_category_name();
            } else {
                // Otheriwse just use the default language string for the name.
                $name = get_string('default_category', 'core_container');
            }
        }

        // Find the id number for the category.
        if (null === $id_number || '' === $id_number) {
            $id_number = static::build_id_number($container_type, $parent_category_id);
        }

        $record = new \stdClass();
        $record->name = $name;
        $record->idnumber = $id_number;
        $record->parent = $parent_category_id;
        $record->timemodified = time();
        $record->issystem = (int) $container_class::is_using_system_category();

        return coursecat::create($record);
    }

    /**
     * @param string $container_type
     */
    public static function create_container_categories(string $container_type): void {
        global $CFG, $DB;

        if (!empty($CFG->tenantsenabled)) {
            $tenant_ids = $DB->get_fieldset_select('tenant', 'id', "1 = 1");
            foreach ($tenant_ids as $tenant_id) {
                static::get_default_category_id($container_type, true, null, null, $tenant_id);
            }
        }

        static::get_default_category_id($container_type);
    }

    /**
     * Get the IDs of system created container categories.
     * This does not include the miscellaneous system course category, since that is not created via the containers API.
     *
     * @return int[]
     */
    public static function get_container_category_ids(): array {
        global $CFG, $DB;

        if (!empty($CFG->tenantsenabled)) {
            $tenant_ids = $DB->get_fieldset_select('tenant', 'id', "1 = 1");
        } else {
            $tenant_ids = [];
        }

        $category_ids = [];

        foreach (factory::get_container_classes() as $container_class) {
            $category_id = static::get_default_category_id($container_class::get_type(), false);
            if ($category_id !== null) {
                $category_ids[] = $category_id;
            }

            foreach ($tenant_ids as $tenant_id) {
                $category_ids[] = static::get_default_category_id($container_class::get_type(), false, null, null, $tenant_id);
            }
        }

        return $category_ids;
    }

}
