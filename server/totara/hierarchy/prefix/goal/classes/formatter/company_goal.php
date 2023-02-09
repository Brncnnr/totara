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
use context_system;
use core\webapi\formatter\formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;

/**
 * Maps the company_goal entity class into a GraphQL totara_hierarchy_company_goal
 * type.
 */
class company_goal extends formatter {
    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'short_name' => string_field_formatter::class,
            'description' => function ($value, text_field_formatter $formatter) {
                $component = 'totara_hierarchy';
                $filearea = 'goal';
                $context = context_system::instance();
                $itemid = $this->object->id;

                return $formatter
                    ->set_pluginfile_url_options($context, $component, $filearea, $itemid)
                    ->format($value);
            },
            'id_number' => null,
            'framework_id' => null,
            'path' => null,
            'parent_id' => null,
            'visible' => null,
            'target_date' => date_field_formatter::class,
            'proficiency_expected' => null,
            'full_name' => string_field_formatter::class,
            'type_id' => null,
            'type_name' => null,
            'goal_scope' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'id_number':
                return $this->object->idnumber;

            case 'framework_id':
                return $this->object->frameworkid;

            case 'parent_id':
                return $this->object->parentid;

            case 'target_date':
                // Unfortunately it is possible to store a goal target date of 0
                // when there is no target date because the dates are stored as
                // integers. 0 is a valid timestamp but in this context, it must
                // be treated a logical marker because target dates will always
                // be positive, non zero values. So need to correct that irritating
                // '0-or-null-for-no-date' problem here.
                $date = $this->object->targetdate;
                return empty($date) ? null : $date;

            case 'proficiency_expected':
                return $this->object->proficiencyexpected;

            case 'type_id':
                return $this->object->typeid;
            
            case 'type_name':
                $type = $this->object->type;
                return empty($type) ? null : $type->fullname;

            case 'short_name':
                return $this->object->shortname;

            case 'full_name':
                return $this->object->fullname;

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
