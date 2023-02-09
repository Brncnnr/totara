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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\query;

/**
 * A query for including the options to search for user when
 * multi tenancy is on.
 */
class user_tenant_query {
    /**
     * A flag to tell whether the loader should include the system user. This flag will only
     * work when the isolation mode is off.
     *
     * @var bool
     */
    private $include_system_user;

    /**
     * @var bool
     */
    private $include_participant;

    /**
     * user_tenant_query constructor.
     * @param bool $include_system_user
     * @param bool $include_participant
     */
    public function __construct(bool $include_system_user = true, bool $include_participant = true) {
        $this->include_system_user = $include_system_user;
        $this->include_participant = $include_participant;
    }

    /**
     * @return bool
     */
    public function is_including_participant(): bool {
        return $this->include_participant;
    }

    /**
     * @return bool
     */
    public function is_including_system_user(): bool {
        return $this->include_system_user;
    }
}