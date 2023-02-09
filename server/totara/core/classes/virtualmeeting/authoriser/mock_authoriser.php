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
 * @author  Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\virtualmeeting\authoriser;

use coding_exception;
use totara_core\entity\virtual_meeting_auth;

/**
 * Provides a fake auth implementation for testing.
 */
class mock_authoriser implements authoriser {
    /** @var integer */
    private $userid;

    /** @var string */
    private $refresh_token;

    /** @var string */
    private $new_access_token;

    /** @var string */
    private $new_refresh_token;

    /** @var integer */
    private $new_expiry;

    /**
     * @param integer $userid
     * @param string $refresh_token
     * @param string $new_access_token
     * @param string $new_refresh_token
     * @param integer $new_expiry
     * @codeCoverageIgnore
     */
    public function __construct(int $userid, string $refresh_token, string $new_access_token, string $new_refresh_token, int $new_expiry) {
        $this->userid = $userid;
        $this->refresh_token = $refresh_token;
        $this->new_access_token = $new_access_token;
        $this->new_refresh_token = $new_refresh_token;
        $this->new_expiry = $new_expiry;
    }

    /**
     * @param virtual_meeting_auth $entity
     * @codeCoverageIgnore
     */
    public function refresh(virtual_meeting_auth $entity): void {
        if ($entity->userid === $this->userid && $entity->refresh_token === $this->refresh_token) {
            $entity->access_token = $this->new_access_token;
            $entity->refresh_token = $this->new_refresh_token;
            $entity->timeexpiry = $this->new_expiry;
            $entity->save();
        } else {
            throw new coding_exception("invalid refresh request: {$entity->user->username}, '{$entity->refresh_token}'");
        }
    }
}
