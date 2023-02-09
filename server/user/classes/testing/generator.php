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
 * @package core_user
 */

namespace core_user\testing;

use stdClass, coding_exception;

/**
 * Generator for core user component.
 */
final class generator extends \core\testing\component_generator {
    /**
     * Injecting default data to field data, depending on the different field type.
     *
     * @param string $field_data_type
     * @param stdClass $current_data
     *
     * @return stdClass
     */
    private function build_default_field_data(string $field_data_type, \stdClass $current_data): \stdClass {
        switch ($field_data_type) {
            case 'menu':
                $clone_data = fullclone($current_data);
                $clone_data->param1 = "xx\nyy\nzz";

                return $clone_data;

            case 'datetime':
                $clone_data = fullclone($current_data);
                $clone_data->param1 = 'xx';
                $clone_data->param2 = 'xc';
                $clone_data->startyear = 1996;
                $clone_data->endyear = 2020;

                return $clone_data;
            case 'text':
            default:
                return $current_data;
        }
    }

    /**
     * @param string        $data_type
     * @param string        $short_name
     * @param int           $category_id
     * @param string|null   $fullname
     *
     * @return \profile_field_base
     */
    public function create_custom_field(string $data_type, string $short_name,
                                        int $category_id = 0, ?string $fullname = null): \profile_field_base {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/user/profile/lib.php");
        require_once("{$CFG->dirroot}/user/profile/definelib.php");

        if (empty($data_type)) {
            throw new coding_exception("data type cannot be an empty string");
        }

        if ($DB->record_exists('user_info_field', ['shortname' => $short_name])) {
            throw new \coding_exception("The custom field with name '{$short_name}' has already existing");
        }

        $field_class_file = "{$CFG->dirroot}/user/profile/field/{$data_type}/field.class.php";
        $define_class_file = "{$CFG->dirroot}/user/profile/field/{$data_type}/define.class.php";

        if (!file_exists($field_class_file)) {
            throw new coding_exception("The field class file does not exist '{$field_class_file}'");
        } else if (!file_exists($define_class_file)) {
            throw new coding_exception("The field define class file does not exists '{$define_class_file}'");
        }

        require_once($field_class_file);
        require_once($define_class_file);

        $field_class = "profile_field_{$data_type}";
        $define_class = "profile_define_{$data_type}";

        if (!class_exists($field_class)) {
            throw new coding_exception("Class '{$field_class}' does not exist in the system");
        } else if (!class_exists($define_class)) {
            throw new coding_exception("Class '{$define_class}' does not exist in the system");
        }

        if (null === $fullname || '' === $fullname) {
            $fullname = uniqid();
        }

        $field_data = new stdClass();
        $field_data->datatype = $data_type;
        $field_data->description = '';
        $field_data->descriptionformat = FORMAT_HTML;
        $field_data->defaultdata = '';
        $field_data->defaultdataformat = FORMAT_HTML;
        $field_data->shortname = $short_name;
        $field_data->name = $fullname;
        $field_data->categoryid = $category_id;

        $field_data = $this->build_default_field_data($data_type, $field_data);

        /** @var \profile_define_base $define_field */
        $define_field = new $define_class();
        $define_field->define_save($field_data);

        if (!$field_data->id) {
            // This is pretty BAD, as we are relying the function to modify the object via references.
            // However, it is the only way for us to find out the custom field's new inserted id.
            throw new coding_exception("Cannot save the custom field data");
        }

        profile_reorder_fields();
        profile_reorder_categories();

        return new $field_class($field_data->id);
    }
}
