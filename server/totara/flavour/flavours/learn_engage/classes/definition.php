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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package flavour_learn_engage
 */

namespace flavour_learn_engage;

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Learn and Engage flavour definition
 */
class definition extends \totara_flavour\definition {

    /**
     * @inheritDoc
     */
    public function get_component() {
        return 'flavour_learn_engage';
    }

    /**
     * @inheritDoc
     */
    protected function load_default_settings() {
        return [
            '' => [
                // Enable non-legacy Learn features
                'enableoutcomes' => 1,
                'enableportfolios' => 1,
                'enablecompletion' => 1,
                'completiondefault' => 1,
                'enableavailability' => 1,
                'enablecourserpl' => 1,
                'enablemodulerpl' => $this->get_default_module_settings(),
                'enableplagiarism' => 1,
                'enablecontentmarketplaces' => 1,
                'enableprogramextensionrequests' => 1,
                'enablelearningplans' => advanced_feature::ENABLED,
                'enableprograms' => advanced_feature::ENABLED,
                'enablecertifications' => advanced_feature::ENABLED,
                'enablerecordoflearning' => advanced_feature::ENABLED,
                'enableprogramcompletioneditor' => 1,
                // Enable Engage only features
                'enableengage_resources' => advanced_feature::ENABLED,
                'enablecontainer_workspace' => advanced_feature::ENABLED,
                'enabletotara_msteams' => advanced_feature::ENABLED,
                'enableml_recommender' => advanced_feature::ENABLED,
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    protected function load_enforced_settings() {
        return [
            '' => [
                // Disable Perform only features
                // Non-legacy features
                'enableperformance_activities' => advanced_feature::DISABLED,
                'enablecompetency_assignment' => advanced_feature::DISABLED,
                'enablegoals' => advanced_feature::DISABLED,
                // Legacy features
                'enableappraisals' => advanced_feature::DISABLED,
                'enablefeedback360' => advanced_feature::DISABLED,
            ]
        ];
    }
}
