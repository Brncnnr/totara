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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter\activity;

use core\webapi\formatter\formatter;
use mod_perform\dates\resolvers\dynamic\dynamic_source;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps the dynamic_source class into the GraphQL mod_perform_dynamic_date_source type.
 */
class dynamic_date_source extends formatter {

    /** @var dynamic_source */
    protected $object;

    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'resolver_class_name' => null,
            'option_key' => null,
            'display_name' => null,
            'is_available' => null,
            'custom_setting_component' => null,
            'custom_data' => null,
            'resolver_base' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        switch ($field) {
            case 'resolver_class_name':
            case 'option_key':
            case 'display_name':
            case 'is_available':
            case 'custom_setting_component':
            case 'custom_data':
            case 'resolver_base':
                return true;
            default:
                return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'resolver_class_name':
                return $this->object->get_resolver_class_name();
            case 'option_key':
                return $this->object->get_option_key();
            case 'display_name':
                return $this->object->get_display_name();
            case 'is_available':
                return $this->object->is_available();
            case 'custom_setting_component':
                return $this->object->get_custom_setting_component();
            case 'custom_data':
                return $this->object->get_custom_data();
            case 'resolver_base':
                return $this->object->get_resolver_base();
            default:
                return null;
        }
    }

}
