<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_job
 */

namespace totara_job\reference;

use core\exception\unresolved_record_reference;
use core\reference\user_record_reference;
use core\webapi\reference\base_record_reference;
use stdClass;
use totara_job\entity\job_assignment;
use core\entity\cohort_member;
use core\entity\tenant;

/**
 * Job assignment record reference. Used to find one record by provided parameters
 */
class job_assignment_record_reference extends base_record_reference {

    /**
     * @var bool - specify for a user query whether to filter for a user as a matching tenant participant in the cohort_members
     * table, rather than as a matching tenant member.
     */
    private bool $allow_tenant_participants = false;

    /**
     * @param bool $allow
     * @return void
     */
    public function set_allow_tenant_participants(bool $allow): void {
        $this->allow_tenant_participants = $allow;
    }

    /**
     * @inheritDoc
     */
    protected array $refine_columns = ['id', 'idnumber', 'userid'];

    /**
     * @inheritDoc
     */
    public function __construct(?string $entity_name = null) {
        parent::__construct($entity_name);
        $this->filter(function (stdClass $record): stdClass {
            // Let's see if we can get the user reference to check for correct tenant and other flags
            $user_reference = new user_record_reference($this->entity_name);
            $user_reference->set_allow_tenant_participants($this->allow_tenant_participants);
            try {
                $user_reference->get_record(['id' => $record->userid]);
            } catch (\Throwable $exception) {
                throw new unresolved_record_reference($this->entity_name . ' user reference not found');
            }
            return $record;
        });
    }

    /**
     * @inheritDoc
     */
    protected function get_table_name(): string {
        return job_assignment::TABLE;
    }

    /**
     * @inheritDoc
     */
    protected function get_entity_name(): string {
        return 'Job assignment';
    }

    /**
     * Checks that a target user is a tenant participant (for the same tenant as the api_client user).
     * This filter is for calling when: (a) you know it's for a tenant target user, (b) you know the api client user & their
     * tenant, and (c) you have already done a query for a matching tenant member and it's failed.
     * @param int $user_logged_in_tenant_id
     * @return $this
     */
    public function is_a_tenant_participant(int $user_logged_in_tenant_id): self {
        $this->filter(function (stdClass $record) use ($user_logged_in_tenant_id) {
            global $DB, $CFG;

            // Check that the user is in a cohort (i.e is a participant) for the api_client user's tenant.
            $result = tenant::repository()->as('t')
                ->join([cohort_member::TABLE, 'cm'], 'cm.cohortid', 't.cohortid')
                ->where('cm.userid', $record->userid)
                ->where('t.id', $user_logged_in_tenant_id)
                ->get();

            // The user for the job_assignment is not a tenant participant either, don't proceed with updating.
            if (empty($result->to_array())) {
                throw new unresolved_record_reference('There was a problem finding a single job assignment record match or you do not have permission to manage it');
            }

            return $record;
        });
        return $this;
    }
}
