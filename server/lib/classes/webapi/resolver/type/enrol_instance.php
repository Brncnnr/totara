<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;

class enrol_instance extends type_resolver {

    public static function resolve(string $field, $instance, array $args, execution_context $ec) {
        global $DB;

        if (!is_object($instance) || empty($instance->id)) {
            throw new \coding_exception('Invalid data handed to enrol_instance type resolver');
        }

        $context = $ec->get_relevant_context();
        switch ($field) {
            case 'id':
                return $instance->id;
            case 'type':
                return $instance->enrol;
            case 'role_name':
                if (empty($instance->roleid)) {
                    return null;
                } else {
                    $role = $DB->get_record('role', ['id' => $instance->roleid], '*', MUST_EXIST);
                    return role_get_name($role, $context);
                }
            case 'custom_name':
                if (empty($instance->name)) {
                    return null;
                } else {
                    return format_string($instance->name);
                }
            case 'sort_order':
                return $instance->sortorder;
            case 'password_required':
                return !empty($instance->password);
            default:
                throw new \coding_exception('Unrecognised field requested for enrol_instance type: ' . $field);
        }
    }
}
