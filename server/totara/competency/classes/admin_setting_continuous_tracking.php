<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

use admin_setting_configselect;

global $CFG;
require_once("$CFG->dirroot/lib/adminlib.php");

/**
 * Setting to determine if continuous tracking of competencies is enabled or disabled
 */
class admin_setting_continuous_tracking extends admin_setting_configselect {

    public const DISABLED = 0;
    public const ENABLED = 1;

    /**
     * @inheritDoc
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, self::ENABLED, null);
    }

    /**
     * @inheritDoc
     */
    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }

        $this->choices = [
            self::DISABLED => get_string('settings_continuous_tracking_disabled', 'totara_competency'),
            self::ENABLED => get_string('settings_continuous_tracking_enabled', 'totara_competency')
        ];
        return true;
    }
}
