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
use totara_competency\pathway;
use totara_hierarchy\entity\scale_value;

defined('MOODLE_INTERNAL') || die();

/**
 * To use this in tests, call \totara_competency\testing\generator::create_test_pathway() instead of instantiating it directly.
 */
class test_pathway extends pathway {

    /**
     * @var scale_value Scale value completion of the criteria in this group leads to
     */
    private $scale_value;

    /**
     * @param null|scale_value|\Closure $scale_value Can be a scale value or null which will be returned
     *   for anyone. Or could be a closure to vary the return value according to user id for example.
     * @return self
     */
    public function set_test_aggregate_current_value($scale_value): self {
        $this->scale_value = $scale_value;
        return $this;
    }

    /**
     * Calculates what value the user has achieved for this test pathway.
     *
     * @param int $user_id
     * @return base_achievement_detail
     */
    public function aggregate_current_value(int $user_id): base_achievement_detail {
        require_once(__DIR__ . '/test_achievement_detail.php');

        if ($this->scale_value instanceof \Closure) {
            // If a function was passed in, we'll execute that.
            $scale_value = ($this->scale_value)($user_id);
        } else {
            $scale_value = $this->scale_value;
        }

        if (is_null($scale_value)) {
            return new test_achievement_detail();
        } else {
            $achievement_detail = new test_achievement_detail();
            $achievement_detail->set_scale_value_id($scale_value->id);
            return $achievement_detail;
        }
    }

    /**
     * Not supported for test pathway.
     */
    protected function fetch_configuration(): void {
        // Do nothing.
    }

    /**
     * Not supported for test pathway.
     */
    protected function save_configuration() {
        // Do nothing.
    }

    /**
     * Not supported for test pathway.
     */
    protected function configuration_is_dirty(): bool {
        return false;
    }

    /**
     * Not supported for test pathway.
     */
    protected function delete_configuration(): void {
        // Do nothing.
    }

    /**
     * Not supported for test pathway.
     */
    public function get_short_description(): string {
        return '';
    }

}
