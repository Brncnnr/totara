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
 * @author Qingyang liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\resource\input;

class name_length_validator implements input_validator {
    /**
     * @var int
     */
    protected $character_length;

    public function __construct(int $length) {
        $this->character_length = $length;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function is_valid($value): bool {
        if (!is_string($value)) {
            debugging("Expecting the parameter to be string", DEBUG_DEVELOPER);
            return false;
        }

        return \core_text::strlen(trim($value)) <= $this->character_length;
    }
}