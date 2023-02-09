<?php
/*
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

use admin_setting_configselect;

global $CFG;
require_once("$CFG->dirroot/lib/adminlib.php");

/**
 * Setting to determine the default aggregation method for competencies
 */
class admin_setting_legacy_aggregation_method extends admin_setting_configselect {

    public const LATEST_ACHIEVEMENT = 1;
    public const HIGHEST_ACHIEVEMENT = 2;

    public const NAME = 'totara_competency/legacy_aggregation_method';

    /**
     * @inheritDoc
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, self::HIGHEST_ACHIEVEMENT, null);
    }

    /**
     * @inheritDoc
     */
    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }

        $this->choices = [
            self::LATEST_ACHIEVEMENT => get_string('settings_legacy_aggregation_method_latest', 'totara_competency'),
            self::HIGHEST_ACHIEVEMENT => get_string('settings_legacy_aggregation_method_highest', 'totara_competency'),
        ];
        return true;
    }
}
