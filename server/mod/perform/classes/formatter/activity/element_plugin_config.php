<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */
namespace mod_perform\formatter\activity;

use coding_exception;
use core\webapi\formatter\formatter;
use mod_perform\models\activity\helpers\displays_responses;
use mod_perform\models\activity\respondable_element_plugin;

/**
 * Class element_plugin
 *
 * @package mod_perform\formatter\activity
 * @property-read element_plugin $object
 */
class element_plugin_config extends formatter {

    protected function get_map(): array {
        return [
            'is_respondable' => null, // not formatted, because this is element type
            'displays_responses' => null,
            'has_title' => null, // not formatted, because this is element title field
            'has_reporting_id' => null, // not formatted, because this is element reporting id field
            'title_text' => null, // not formatted, because this is lang string
            'title_help_text' => null, // not formatted, because this is lang string
            'is_title_required' => null, // not formatted, because this is to check title is required
            'is_response_required_enabled' => null, // not formatted, because this is to check response is required
            'extra_config_data' => null, // not formatted, because this is to check response is required
        ];
    }

    /**
     * @param string $field
     * @return bool|string|null
     */
    protected function get_field(string $field) {
        if (!$this->object instanceof displays_responses &&
            in_array($field, ['has_reporting_id', 'is_response_required_enabled'], true)
        ) {
            return false;
        }

        switch ($field) {
            case 'is_respondable':
                return $this->object instanceof respondable_element_plugin; // false for derived_response_element_plugin.
           case 'displays_responses':
                return $this->object instanceof displays_responses;
            case 'has_title':
                return $this->object->has_title();
            case 'has_reporting_id':
                return $this->object->has_reporting_id();
            case 'title_text':
                return $this->object->get_title_text();
            case 'title_help_text':
                return $this->object->get_title_help_text();
            case 'is_title_required':
                return $this->object->is_title_required();
            case 'is_response_required_enabled':
                return $this->object->is_response_required_enabled();
            case 'extra_config_data':
                return json_encode($this->object->get_extra_config_data(), JSON_THROW_ON_ERROR);
            default:
                throw new coding_exception('Unexpected field passed to formatter');
        }
    }

    protected function has_field(string $field): bool {
        return array_key_exists($field, $this->get_map());
    }
}