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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\webapi\formatter\field;

use core\date_format;
use core_date;
use DateTime;

/**
 * Formats a unix timestamp value into the given format, see \core\date_format for possible formats
 */
class date_field_formatter extends base {

    protected function validate_format(): bool {
        // All available date formats are valid
        return date_format::is_defined($this->format);
    }

    /**
     * @param int $value
     * @return string
     */
    protected function format_timestamp(int $value): string {
        return (string)$value;
    }

    /**
     * Formats unix timestamp to the iso8601 format
     *
     * @param int $value
     * @return string
     */
    protected function format_iso8601(int $value): string {
        $date = new DateTime('@' . $value);
        $date->setTimezone(core_date::get_user_timezone_object());

        return $date->format(DateTime::ISO8601);
    }

    /**
     * Formats the unix timestamp to the given format using the userdate function
     *
     * @param $value
     * @return string
     */
    protected function get_default_format($value) {
        return userdate($value, get_string(date_format::get_lang_string($this->format), 'langconfig'));
    }


}