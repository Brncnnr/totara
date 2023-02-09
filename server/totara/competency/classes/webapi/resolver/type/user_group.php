<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_competency\models\user_group as user_group_model;
use core\webapi\formatter\field\string_field_formatter;

/**
 * Note: It is the responsibility of the query to ensure the user is permitted to see an organisation.
 */
class user_group extends type_resolver {

    /**
     * @param string $field
     * @param user_group_model $user_group
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $user_group, array $args, execution_context $ec) {
        if (!$user_group instanceof user_group_model) {
            throw new \coding_exception('Accepting only entities.');
        }

        $format = $args['format'] ?? null;

        switch ($field) {
            case 'id':
                return $user_group->get_id();
            case 'name':
                $formatter = new string_field_formatter($format, \context_system::instance());
                return $formatter->format($user_group->get_name());
            case 'is_deleted':
                return $user_group->is_deleted();
            case 'type':
                return $user_group->get_type();
            default:
                throw new \coding_exception("Unknown field '{$field}' for user_group type");
        }
    }


}