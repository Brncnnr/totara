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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\formatter;

use context_user;
use core\webapi\formatter\formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use hierarchy_goal\assignment_type_extended;
use hierarchy_goal\personal_goal_assignment_type;

/**
 * Maps the personal_goal entity class into a GraphQL totara_hierarchy_personal_goal
 * type.
 */
class personal_goal extends formatter {
    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'assignment_type' => null,
            'id' => null,
            'user_id' => null,
            'name' => string_field_formatter::class,
            'description' => function ($value, text_field_formatter $formatter) {
                $component = 'totara_hierarchy';
                $filearea = 'goal';
                $context = context_user::instance($this->object->userid);
                $itemid = $this->object->id;

                return $formatter
                    ->set_pluginfile_url_options($context, $component, $filearea, $itemid)
                    ->format($value);
            },
            'target_date' => date_field_formatter::class,
            'scale_id' => null,
            'scale_value_id' => null,
            'scale_value' => null,
            'deleted' => null,
            'type_id' => null,
            'type_name' => null,
            'visible' => null,
            'goal_scope' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'assignment_type':
                $type = (int)$this->object->assigntype;

                return assignment_type_extended::create_personal_goal_assignment_type(
                    personal_goal_assignment_type::by_value($type),
                    $this->object
                );

            case 'type_id':
                return $this->object->typeid;
            
            case 'type_name':
                $type = $this->object->type;
                return empty($type) ? null : $type->fullname;

            case 'target_date':
                // Unlike company goals which stores nulls when there are no target
                // dates, personal goals code stores 0s! So need to correct that
                // irritating problem here. BTW, it is safe to assume that 0 is
                // for unset values because the personal goals code also requires
                // future values to make the target date valid.
                $date = $this->object->targetdate;
                return empty($date) ? null : $date;

            case 'scale_id':
                return $this->object->scaleid;

            case 'scale_value_id':
                return $this->object->scalevalueid;

            case 'user_id':
                return $this->object->userid;

            default:
                return $this->object->$field;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        return array_key_exists($field, $this->get_map());
    }
}
