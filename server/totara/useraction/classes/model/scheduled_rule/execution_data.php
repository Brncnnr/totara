<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_useraction
 */

namespace totara_useraction\model\scheduled_rule;

/**
 * Holds data used to execute a scheduled rule.
 */
class execution_data {

    /**
     * @var int
     */
    private int $timestamp;

    /**
     * @param array $data
     */
    private function __construct(array $data = []) {
        $this->timestamp = $data['timestamp'] ?? time();
    }

    public static function instance(array $data = []): self {
        return new self($data);
    }

    /**
     * Get reference timestamp
     *
     * @return int
     */
    public function get_timestamp(): int {
        return $this->timestamp;
    }
}
