<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\customfield_area;

use customfield_base;
use customfield_define_base;
use totara_evidence\entity\evidence_field_data;

global $CFG;
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

/**
 * Get a specific custom field type object
 *
 * @package totara_evidence\customfield_area
 */
class field_helper {

    /**
     * Get the custom field definition class
     *
     * @param string $datatype
     * @return customfield_define_base
     */
    public static function get_field_definition(string $datatype): customfield_define_base {
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/' . $datatype . '/define.class.php');
        $classname = 'customfield_define_' . $datatype;
        return new $classname();
    }

    /**
     * Get a static custom field class
     *
     * @param string $datatype
     * @return customfield_base|string Class name
     */
    public static function get_field_class(string $datatype): string {
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/' . $datatype . '/field.class.php');
        return 'customfield_' . $datatype;
    }

    /**
     * Get an instance of a custom field
     *
     * @param evidence_field_data $field_data
     * @return customfield_base
     */
    public static function get_field_instance(evidence_field_data $field_data): customfield_base {
        $field = $field_data->field;
        $class = static::get_field_class($field->datatype);
        return new $class($field->id, $field_data->item, evidence::get_prefix(), evidence::get_base_table());
    }

    /**
     * Load field form data for an item
     *
     * @param array $attributes Data such as id, typeid, user_id etc
     * @return object Form data object
     */
    public static function load_field_data(array $attributes = []): object {
        $data = (object) $attributes;
        customfield_load_data($data, evidence::get_prefix(), evidence::get_base_table());
        return $data;
    }

    /**
     * Save field form data for an item
     *
     * @param object $data
     */
    public static function save_field_data(object $data): void {
        customfield_save_data($data, evidence::get_prefix(), evidence::get_base_table());
    }

}
