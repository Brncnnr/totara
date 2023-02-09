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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\token;

class token {
    /**
     * The token's value
     * @var string
     */
    private $value;

    /**
     * NULL means that the token will never expired.
     * Otherwise it is the epoch time for when the token expired.
     *
     * @var int|null
     */
    private $time_expired;

    /**
     * token constructor.
     * @param string   $value
     * @param int|null $time_expired
     */
    public function __construct(string $value, ?int $time_expired) {
        $this->value = $value;
        $this->time_expired = $time_expired;
    }

    /**
     * @param int|null $time
     * @return bool
     */
    public function is_expired(?int $time = null): bool {
        if (null === $this->time_expired) {
            return false;
        }

        $time = $time ?? time();
        return $time >= $this->time_expired;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->get_value();
    }

    /**
     * @return string
     */
    public function get_value(): string {
        return $this->value;
    }

    /**
     * @return int|null
     */
    public function get_expiry(): ?int {
        return $this->time_expired;
    }
}