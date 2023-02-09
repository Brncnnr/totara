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
 * Check https URL settings.
 */
abstract class url_common implements checkable {
    /**
     * Maximum allowed length.
     */
    const MAX_LENGTH = 2048;

    /**
     * @var string
     */
    protected $result = '';

    public function get_name(): string {
        $fieldname = $this->get_config_name();
        return get_string($fieldname, 'admin');
    }

    public function get_helplink(): ?moodle_url {
        return null;
    }

    /**
     * Helper function to validate url.
     *
     * @param string $id_empty language string id when the url is empty
     * @param string $id_toolong language string id when the url is too long
     * @param string $id_insecure language string id when the url is not https
     * @return integer PASS, SKIPPED or FAILED
     */
    protected function check_url(string $id_empty, string $id_toolong, string $id_insecure): int {
        global $CFG;
        $fieldname = $this->get_config_name();
        if (!isset($CFG->{$fieldname}) || (string)$CFG->{$fieldname} === '') {
            if (is_https() && strlen($CFG->wwwroot) <= static::MAX_LENGTH) {
                $this->result = get_string('check:pub_url_empty', 'totara_msteams');
                return status::SKIPPED;
            } else {
                $this->result = get_string($id_empty, 'totara_msteams');
                return status::FAILED;
            }
        }
        if (strpos($CFG->{$fieldname}, 'https://') !== 0) {
            $this->result = get_string($id_insecure, 'totara_msteams', static::MAX_LENGTH);
            return status::FAILED;
        }
        if (strlen($CFG->{$fieldname}) > static::MAX_LENGTH) {
            $this->result = get_string($id_toolong, 'totara_msteams', static::MAX_LENGTH);
            return status::FAILED;
        }
        return status::PASS;
    }

    public function get_report(): string {
        return $this->result;
    }
}
