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

namespace totara_msteams\check\checks;

defined('MOODLE_INTERNAL') || die;

use moodle_url;
use totara_msteams\check\status;

/**
 * Check bot_app_secret.
 */
class bot_secret extends bot_common {
    public function get_name(): string {
        return get_string('check:bot_secret', 'totara_msteams');
    }

    public function get_config_name(): ?string {
        return 'bot_app_secret';
    }

    public function get_helplink(): ?moodle_url {
        return null;
    }

    public function check(): int {
        if (($status = parent::check_bot()) !== status::PASS) {
            return $status;
        }

        $appsecret = get_config('totara_msteams', 'bot_app_secret');
        if (empty($appsecret)) {
            $this->result = get_string('check:bot_secret_notset', 'totara_msteams');
            return status::FAILED;
        }

        return status::PASS;
    }
}
