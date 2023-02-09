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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package flavour_learn_professional
 */

namespace flavour_learn_professional;

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Learn professional flavour definition
 */
class definition extends \totara_flavour\definition {

    /**
     * @inheritDoc
     */
    public function get_component() {
        return 'flavour_learn_professional';
    }

    /**
     * @inheritDoc
     */
    protected function load_default_settings() {
        return [
            '' => [
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
                'enableprograms' => advanced_feature::ENABLED,
                'enableprogramcompletioneditor' => 1,
                'enablerecordoflearning' => advanced_feature::ENABLED,
                'enablelegacyprogramassignments' => 0,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function load_enforced_settings() {
        return [
            '' => [
                'audiencevisibility' => 0,
                'tenantsenabled' => 0,
                'enableevidence' => advanced_feature::DISABLED,
                'enablewebservices' => 0,
                'mnet_dispatcher_mode' => 'off',
                'enablelearningplans' => advanced_feature::DISABLED,
                'enablecertifications' => advanced_feature::DISABLED,
                'enablepositions' => advanced_feature::DISABLED,
                'enableorganisations' => advanced_feature::DISABLED,
                'enableuser_reports' => advanced_feature::DISABLED,
                // Disable Engage only features
                'enableengage_resources' => advanced_feature::DISABLED,
                'enablecontainer_workspace' => advanced_feature::DISABLED,
                'enabletotara_msteams' => advanced_feature::DISABLED,
                'enableml_recommender' => advanced_feature::DISABLED,
                // Disable Perform only features
                // Non-legacy features
                'enablecompetencies' => advanced_feature::DISABLED,
                'enablecompetency_assignment' => advanced_feature::DISABLED,
                'enableperformance_activities' => advanced_feature::DISABLED,
                'enableapi' => advanced_feature::DISABLED,
                // Legacy features
                'enableappraisals' => advanced_feature::DISABLED,
                'enablefeedback360' => advanced_feature::DISABLED,
                'enablegoals' => advanced_feature::DISABLED,
                'enablecompletionimport' => advanced_feature::DISABLED,
            ]
        ];
    }
}
