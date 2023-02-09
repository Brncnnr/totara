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
 * Check sso_scope.
 */
class sso_scope extends sso_id {
    public function get_name(): string {
        return get_string('check:sso_scope', 'totara_msteams');
    }

    public function get_config_name(): ?string {
        return 'sso_scope';
    }

    public function get_helplink(): ?moodle_url {
        return null;
    }

    public function check(): int {
        if (($status = parent::check()) !== status::PASS) {
            return $status;
        }

        // If SSO is enabled, both appid and scope are required.
        $appid = get_config('totara_msteams', 'sso_app_id');
        $scope = get_config('totara_msteams', 'sso_scope');
        if (empty($scope)) {
            $this->result = get_string('check:sso_scope_notset', 'totara_msteams');
            return status::FAILED;
        }
        if (!preg_match("/^api:\\/\\/[^\\/]+\\/{$appid}$/", $scope)) {
            $this->result = get_string('check:sso_scope_invalid', 'totara_msteams', $scope);
            return status::FAILED;
        }

        return status::PASS;
    }
}
