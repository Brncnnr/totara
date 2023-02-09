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

namespace totara_msteams;

use admin_setting_configselect;
use auth_oauth2\api;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/adminlib.php');

/**
 * The select menu for OAuth2 services.
 */
final class admin_setting_oauth2_issuer_select extends admin_setting_configselect {
    /**
     * Constructor.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param int $defaultsetting
     */
    public function __construct(string $name, string $visiblename, string $description, int $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, null);
    }

    /**
     * @inheritDoc
     */
    public function load_choices() {
        global $DB;
        if (is_array($this->choices)) {
            return true;
        }
        if (is_enabled_auth('oauth2')) {
            $options = $DB->get_records('oauth2_issuer', ['enabled' => 1], 'id', 'id,name');
            foreach ($options as $id => $rec) {
                $options[$id] = $rec->name;
            }
            $this->choices = [0 => get_string('settings:oauth2_issuer_none', 'totara_msteams')] + $options;
        } else {
            $this->choices = [0 => get_string('settings:oauth2_issuer_none', 'totara_msteams')];
        }
        return true;
    }
}
