<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\local\visibility\program;

defined('MOODLE_INTERNAL') || die();

use totara_core\local\visibility\resolver;

/**
 * Audience based visibility resolver for programs.
 */
final class audiencebased extends \totara_core\local\visibility\audiencebased implements resolver {

    use common;
    use \totara_core\local\visibility\program\program_common;

    /**
     * Returns the cohort association with this item type.
     *
     * @return int One of COHORT_ASSN_ITEMTYPE_*
     */
    protected function get_cohort_association(): int {
        return COHORT_ASSN_ITEMTYPE_PROGRAM;
    }

}