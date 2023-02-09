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
use totara_msteams\manifest_helper;

/**
 * Check manifest_app_description.
 */
class mf_desc implements checkable {
    /**
     * Maximum allowed length.
     */
    const MAX_LENGTH = 80;

    /**
     * @var string
     */
    protected $result = '';

    public function get_name(): string {
        return get_string('check:mf_desc', 'totara_msteams');
    }

    public function get_config_name(): ?string {
        return 'manifest_app_description';
    }

    public function get_helplink(): ?moodle_url {
        return null;
    }

    public function check(): int {
        $desc = (string)get_config('totara_msteams', 'manifest_app_description');
        if ($desc === '') {
            $this->result = get_string('check:mf_desc_notset', 'totara_msteams');
            return status::FAILED;
        }
        if (manifest_helper::utf16_strlen($desc) > self::MAX_LENGTH) {
            $this->result = get_string('check:mf_desc_toolong', 'totara_msteams', self::MAX_LENGTH);
            return status::FAILED;
        }
        return status::PASS;
    }

    public function get_report(): string {
        return $this->result;
    }
}
