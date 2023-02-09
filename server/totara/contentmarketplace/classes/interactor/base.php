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
namespace totara_contentmarketplace\interactor;

use context;
use required_capability_exception;

class base {
    /**
     * The user id of who we are checking for.
     * @var int
     */
    protected $actor_id;

    /**
     * base constructor.
     * @param int|null $user_id     Pass NULL to get the current user in session.
     */
    public function __construct(?int $user_id = null) {
        global $USER;
        $this->actor_id = $user_id ?? $USER->id;
    }

    /**
     * Returns the current's actor id.
     * @return int
     */
    public function get_actor_id(): int {
        return $this->actor_id;
    }

    /**
     * @param string  $capability
     * @param context $context
     * @param string  $error_msg
     * @param string  $component
     *
     * @return required_capability_exception
     */
    protected static function create_required_capability_exception(
        string $capability,
        context $context,
        string $error_msg = 'nopermissions',
        string $component = 'error'
    ): required_capability_exception {
        return new required_capability_exception(
            $context,
            $capability,
            $error_msg,
            $component
        );
    }
}