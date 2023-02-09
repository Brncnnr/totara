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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\my;

use core\session\manager;
use core_user;
use moodle_exception;
use totara_msteams\botfw\exception\unexpected_exception;
use totara_msteams\botfw\hook\hook;

class bot_hook implements hook {
    /** @var string */
    private $forcelang = '';

    /**
     * @inheritDoc
     */
    public function open(string $language): void {
        // Override the language preference.
        $this->forcelang = force_current_language($language);
    }

    /**
     * @inheritDoc
     */
    public function close(): void {
        // Revert to the previous language preference.
        force_current_language($this->forcelang);
        $this->forcelang = '';
        manager::write_close();
    }

    /**
     * @inheritDoc
     */
    public function set_user(int $userid): void {
        try {
            $user = core_user::get_user($userid, '*', MUST_EXIST);
            manager::write_close();
            manager::set_user($user);
            require_login(null, false, null, false, true);
            core_user::require_active_user($user, true);
        } catch (moodle_exception $ex) {
            throw new unexpected_exception('Invalid user', 0, $ex);
        }
    }
}
