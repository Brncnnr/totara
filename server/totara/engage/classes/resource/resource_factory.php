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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\resource;

final class resource_factory {

    /**
     * resolver constructor.
     */
    private function __construct() {
        // Not allowed to instantiate this class.
    }

    /**
     * Create a resource_item from an ID. The parameter $resourceid should be pointing
     * to the field {id} in the table {engage_resource}
     *
     * @param int $resourceid
     * @return resource_item
     * @throws \coding_exception
     */
    public static function create_instance_from_id(int $resourceid): resource_item {
        global $DB;

        $resourcetype = $DB->get_field('engage_resource', 'resourcetype', ['id' => $resourceid], MUST_EXIST);
        $classes = \core_component::get_namespace_classes(
            'totara_engage\resource',
            resource_item::class,
            $resourcetype
        );

        if (empty($classes)) {
            throw new \coding_exception("No resource defined for '{$resourcetype}'");
        } elseif (1 != count($classes)) {
            debugging("More than one resource defined for '{$resourcetype}'", DEBUG_DEVELOPER);
        }

        /** @var resource_item $cls Class reference to resource_item and not actually an instance of it. */
        $cls = reset($classes);

        /** @see resource_item::from_resource_id */
        return $cls::from_resource_id($resourceid);
    }
}