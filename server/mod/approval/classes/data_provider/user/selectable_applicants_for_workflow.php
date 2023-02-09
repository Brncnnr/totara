<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\user;

use coding_exception;
use core\entity\cohort_member;
use core\orm\entity\repository;
use core\orm\query\builder;
use hierarchy_organisation\entity\organisation;
use hierarchy_position\entity\position;
use mod_approval\entity\assignment\assignment;
use mod_approval\model\assignment\assignment_type\cohort;
use mod_approval\model\assignment\assignment_type\organisation as organisation_assignment_type;
use mod_approval\model\assignment\assignment_type\position as position_assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use totara_job\entity\job_assignment;

/**
 * Provider to search for selectable applicants within a workflow.
 *
 * @package mod_approval\data_provider\user
 */
class selectable_applicants_for_workflow extends selectable_applicants_base {

    /**
     * @var workflow
     */
    private $workflow;

    public function __construct(workflow $workflow, int $user_id) {
        $this->workflow = $workflow;
        parent::__construct($user_id);
    }

    /**
     * @inheritDoc
     */
    protected function build_query(): repository {
        $default_assignment = $this->workflow->default_assignment;
        $user_query = $this->get_user_query($default_assignment->get_context());

        return $this->limit_by_assignments($user_query);
    }

    /**
     * Limit users by workflow assignments.
     *
     * @param repository $repository
     * @return repository
     */
    private function limit_by_assignments(repository $repository): repository {
        $default_assignment = $this->workflow->default_assignment;

        // todo: move to assignment type classes in TL-32384
        switch ($default_assignment->assignment_type) {
            case cohort::get_code():
                $repository
                    ->join([cohort_member::TABLE, 'chm'], 'u.id', 'userid')
                    ->join([assignment::TABLE, 'assignment'], 'chm.cohortid', 'assignment_identifier')
                    ->where('assignment.course', $this->workflow->course_id)
                    ->where('assignment.assignment_type', cohort::get_code())
                    ->where('assignment.status', status::ACTIVE)
                    ->where('assignment.to_be_deleted', 0);
                break;
            case position_assignment_type::get_code():
                $position = new position($default_assignment->assignment_identifier);

                $repository
                    ->join([job_assignment::TABLE, 'ja'], 'u.id', 'userid')
                    ->join([position::TABLE, 'p'], 'ja.positionid', 'p.id')
                    ->where(function(builder $builder) use ($position) {
                        $builder->where('p.path', $position->path)
                            ->or_where_like_raw('p.path', "$position->path/%");
                    });
                break;
            case organisation_assignment_type::get_code():
                $organisation = new organisation($default_assignment->assignment_identifier);

                $repository
                    ->join([job_assignment::TABLE, 'ja'], 'u.id', 'userid')
                    ->join([organisation::TABLE, 'o'], 'ja.organisationid', 'o.id')
                    ->where(function(builder $builder) use ($organisation) {
                        $builder->where('o.path', $organisation->path)
                            ->or_where_like_raw('o.path', "$organisation->path/%");
                    });
                break;
            default:
                throw new coding_exception('Unknown assignment type');
        }
        return $repository;
    }
}
