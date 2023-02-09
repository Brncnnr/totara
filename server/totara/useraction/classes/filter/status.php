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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\filter;

use core\entity\user_repository;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * Filter against the active user status.
 */
class status implements filter_contract {

    const STATUS_SUSPENDED = 0;
    const STATUS_DELETED = 1;

    const ENUM_SUSPENDED = 'SUSPENDED';
    const ENUM_DELETED = 'DELETED';

    /**
     * Helper method to map the enums with their values.
     *
     * @var array|string[]
     */
    private static array $map = [
        self::STATUS_SUSPENDED => self::ENUM_SUSPENDED,
        self::STATUS_DELETED => self::ENUM_DELETED,
    ];

    private int $status;

    /**
     * @param int|null $status
     */
    public function __construct(int $status) {
        $this->status = $status;
    }

    /**
     * @param $input
     * @return filter_contract
     */
    public static function create_from_input($input): filter_contract {
        if (!is_string($input) || in_array($input, self::$map) === false) {
            throw new \coding_exception('Invalid status filter input.');
        }
        $flipped = array_flip(self::$map);
        return new self($flipped[$input]);
    }

    /**
     * @param $stored
     * @return filter_contract
     */
    public static function create_from_stored($stored): filter_contract {
        return new self((int) $stored);
    }

    /**
     * @return int
     */
    public function get_status(): int {
        return $this->status;
    }

    /**
     * Return the status in enum form.
     *
     * @return string
     */
    public function to_graphql(): string {
        return self::$map[$this->status];
    }

    /**
     * @inheritDoc
     */
    public function apply(user_repository $user_repository, execution_data $execution_data): user_repository {
        if ($this->get_status() === self::STATUS_SUSPENDED) {
            $user_repository->where('suspended', 1);
        }

        return $user_repository;
    }
}
