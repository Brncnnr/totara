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
 * @package totara_core
 */

namespace totara_core\formatter;

use coding_exception;
use core\webapi\formatter\formatter;
use totara_core\dates\date_time_setting;

/**
 * Format the date time settings for GraphQL.
 *
 * @package totara_core\formatter
 */
class date_time_setting_formatter extends formatter {

    /**
     * @var date_time_setting
     */
    protected $object;

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'iso' => null,
            'timezone' => null,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'iso':
                return $this->object->get_iso();
            case 'timezone':
                return $this->object->get_timezone();
        }

        throw new coding_exception('Unexpected field passed to formatter');
    }


    /**
     * @inheritDoc
     */
    protected function has_field(string $field): bool {
        return $field === 'iso' || $field === 'timezone';
    }

}
