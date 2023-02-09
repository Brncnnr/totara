<?php
/*
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\task;

use core\collection;
use core\task\adhoc_task;
use totara_competency\entity\scale_aggregation;
use totara_competency\legacy_aggregation;
use totara_hierarchy\entity\competency;
use totara_core\advanced_feature;

class default_criteria_on_install extends adhoc_task {

    /**
     * Add default criteria to any competencies when upgrading to a version with totara_competency.
     *
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        // This task should only run for non-perform
        if (advanced_feature::is_enabled('competency_assignment')) {
            return;
        }

        /**
         * Load any competencies that don't already have an associated overall aggregation type record.
         *
         * This should therefore include any competencies from prior to totara_competency.
         *
         * If this task is running again after previously failing, it should at least exclude competencies that had
         * already had criteria added.
         *
         * @var competency[]|collection $competencies
         */
        $competencies = competency::repository()
            ->with('scale')
            ->left_join([scale_aggregation::TABLE, 'sa'], 'id', 'competency_id')
            ->where('sa.id', null)
            ->order_by('depthlevel', 'desc')
            ->order_by('id', 'asc')
            ->get();

        foreach ($competencies as $competency) {
            $aggregation = new legacy_aggregation($competency);
            $aggregation->create_default_pathways($competency->scale, false);
        }

        // Linked courses are synced through observers - no need for additional steps here

        // Run aggregation task right away
        if (!empty($competencies)) {
            (new competency_aggregation_queue())->execute();
        }
    }


}
