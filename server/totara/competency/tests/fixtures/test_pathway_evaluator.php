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

use totara_competency\pathway;
use totara_competency\pathway_evaluator;
use totara_competency\pathway_evaluator_user_source;

global $CFG;
require_once($CFG->dirroot . '/totara/competency/tests/fixtures/test_pathway_evaluator_source.php');

class test_pathway_evaluator extends pathway_evaluator {
    /**
     * Constructor.
     *
     * @param pathway $pathway
     * @param pathway_evaluator_user_source $user_id_source
     */
    public function __construct(pathway $pathway, pathway_evaluator_user_source $user_id_source) {
        $test_user_source = $this->get_test_user_source($user_id_source);
        parent::__construct($pathway, $test_user_source);
    }

    /**
     * Instantiate and return a test_user_source using the same user_id_source as the pathway
     * @param pathway_evaluator_user_source $user_id_source
     * @return pathway_evaluator_user_source
     */
    private function get_test_user_source(pathway_evaluator_user_source $user_id_source): pathway_evaluator_user_source {
        return new \pathway_test_pathway\test_pathway_evaluator_user_source($user_id_source->get_source(), $user_id_source->is_full_user_set());
    }

}
