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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace pathway_test_pathway;

use totara_competency\base_achievement_detail;

defined('MOODLE_INTERNAL') || die();

/**
 * Empty achievement detail class for testing.
 *
 * @package pathway_test_pathway
 */
class test_achievement_detail extends base_achievement_detail {

    /**
     * No strings for a test detail.
     *
     * @return array
     */
    public function get_achieved_via_strings(): array {
        return [];
    }

}
