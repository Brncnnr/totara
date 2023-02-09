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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\interactor;

use context_user;

class notifiable_event_user_preference_interactor {
    /**
     * @var int
     */
    private $user_id;

    /** @var context_user|null  */
    private $context;

    /**
     * notifiable_event_user_preference_interactor constructor.
     * @param int $user_id
     */
    public function __construct(int $user_id) {
        $this->user_id = $user_id;
        $this->context = context_user::instance($this->user_id);
    }

    /**
     * Check if the user is capable of managing notification preference.
     *
     * @return bool
     */
    public function can_manage(): bool {
        global $USER;

        // If its your own preference.
        if ($this->user_id == $USER->id) {
            return has_capability('moodle/user:editownmessageprofile', $this->context);
        }

        // If its someone else's preference.
        return has_capability('moodle/user:editmessageprofile', $this->context);
    }

}