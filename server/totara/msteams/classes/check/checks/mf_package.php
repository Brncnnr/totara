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
use totara_msteams\check\checkable;
use totara_msteams\check\status;

/**
 * Check manifest_app_package_name.
 */
class mf_package implements checkable {
    /**
     * @var string
     */
    protected $result = '';

    public function get_name(): string {
        return get_string('check:mf_package', 'totara_msteams');
    }

    public function get_config_name(): ?string {
        return 'manifest_app_package_name';
    }

    public function get_helplink(): ?moodle_url {
        return null;
    }

    public function check(): int {
        $packname = get_config('totara_msteams', 'manifest_app_package_name');
        if (empty($packname)) {
            $this->result = get_string('check:mf_package_notset', 'totara_msteams');
            return status::FAILED;
        }
        if ($packname === 'com.totaralearning.microsoft.msteams') {
            $this->result = get_string('check:mf_package_nodefault', 'totara_msteams');
            return status::FAILED;
        }
        return status::PASS;
    }

    public function get_report(): string {
        return $this->result;
    }
}
