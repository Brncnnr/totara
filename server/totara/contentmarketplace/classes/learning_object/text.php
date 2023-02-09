<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\learning_object;

/**
 * A wrapper object for normal text.
 */
class text {
    /**
     * One of the constant FORMAT_*
     * @var int
     */
    private $format;

    /**
     * The raw value.
     * @var string
     */
    private $raw_value;

    /**
     * text constructor.
     * @param string   $value
     * @param int|null $format
     */
    public function __construct(string $value, int $format) {
        $this->raw_value = $value;
        $this->format = $format;
    }

    /**
     * @return int
     */
    public function get_format(): int {
        return $this->format;
    }

    /**
     * @return string
     */
    public function get_raw_value(): string {
        return $this->raw_value;
    }
}