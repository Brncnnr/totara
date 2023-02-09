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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\testing;

use stdClass, coding_exception;

use totara_competency\models\assignment_actions;
use totara_competency\entity\assignment;
use totara_competency\user_groups;
use totara_job\job_assignment;

class assignment_generator {

    /**
     * @var generator
     */
    protected $generator;

    /**
     * totara_competency_assignment_generator constructor.
     *
     * @param generator $generator
     */
    public function __construct(generator $generator) {
        $this->generator = $generator;
    }

    /**
     * Create a competency assignment for an organisation
     *
     * @param int|null $competency_id ID of the competency to create an assignment for, if not supplied it will be created
     * @param int|null $position_id Position ID or null to whip up a new position with a framework
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_position_assignment(?int $competency_id = null, ?int $position_id = null, array $attributes = []) {
        if (is_null($position_id)) {
            $position_id = $this->create_position()->id;
        }

        if (is_null($competency_id)) {
            $competency_id = $this->generator->create_competency()->id;
        }

        $attributes['competency_id'] = $competency_id;
        $attributes['user_group_id'] = $position_id;
        $attributes['user_group_type'] = user_groups::POSITION;

        return $this->create_assignment($attributes);
    }

    /**
     * Create a competency assignment for an organisation
     *
     * @param int|null $competency_id ID of the competency to create an assignment for, if not supplied it will be created
     * @param int|null $organisation_id Organisation ID or null to whip up a new organisation with a framework
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_organisation_assignment(?int $competency_id = null, ?int $organisation_id = null, array $attributes = []) {
        if (is_null($organisation_id)) {
            $organisation_id = $this->create_organisation()->id;
        }

        if (is_null($competency_id)) {
            $competency_id = $this->generator->create_competency()->id;
        }

        $attributes['competency_id'] = $competency_id;
        $attributes['user_group_id'] = $organisation_id;
        $attributes['user_group_type'] = user_groups::ORGANISATION;

        return $this->create_assignment($attributes);
    }

    /**
     * Create a competency assignment for a cohort (audience alias)
     *
     * @param int|null $competency_id ID of the competency to create an assignment for, if not supplied it will be created
     * @param int|null $cohort_id Cohort ID or null to whip up a new audience
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_cohort_assignment(?int $competency_id = null, ?int $cohort_id = null, array $attributes = []) {
        if (is_null($cohort_id)) {
            $cohort_id = $this->create_cohort()->id;
        }

        if (is_null($competency_id)) {
            $competency_id = $this->generator->create_competency()->id;
        }

        $attributes['competency_id'] = $competency_id;
        $attributes['user_group_id'] = $cohort_id;
        $attributes['user_group_type'] = user_groups::COHORT;

        return $this->create_assignment($attributes);
    }

    /**
     * Create a competency assignment for an audience (cohort alias)
     *
     * @param int|null $competency_id ID of the competency to create an assignment for, if not supplied it will be created
     * @param int|null $cohort_id Cohort ID or null to whip up a new audience
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_audience_assignment(?int $competency_id = null, ?int $cohort_id = null, array $attributes = []) {
        return $this->create_cohort_assignment($competency_id, $cohort_id, $attributes);
    }

    /**
     * Create a competency assignment for a user
     *
     * @param int|null $competency_id ID of the competency to create an assignment for, if not supplied it will be created
     * @param int|null $user_id User ID or null to whip up a new user
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_user_assignment(?int $competency_id = null, ?int $user_id = null, array $attributes = []) {
        if (is_null($user_id)) {
            $user_id = $this->create_user()->id;
        }

        if (is_null($competency_id)) {
            $competency_id = $this->generator->create_competency()->id;
        }

        $attributes['competency_id'] = $competency_id;
        $attributes['user_group_id'] = $user_id;
        $attributes['user_group_type'] = user_groups::USER;

        return $this->create_assignment($attributes);
    }

    /***
     * Create self assignment for a given user and competency
     *
     * @param int|null $competency_id ID of the competency to create an assignment for, if not supplied it will be created
     * @param int|null $user_id User ID or null to whip up a new user
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_self_assignment(?int $competency_id = null, ?int $user_id = null, array $attributes = []) {
        $attributes['type'] = assignment::TYPE_SELF;

        return $this->create_user_assignment($competency_id, $user_id, $attributes);
    }

    /**
     * Create a new competency assignment
     *
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_assignment(array $attributes = []) {
        if (!in_array($attributes['user_group_type'] ?? null, user_groups::get_available_types())
            || !isset($attributes['user_group_id']) || !isset($attributes['competency_id'])
        ) {
            throw new coding_exception('You must supply a valid "user_group_type", any "user_group_id" and any "competency_id" to create an assignment');
        }

        // Applying default attributes, so we'd have a complete record to return
        $attributes = array_merge([
            'optional' => '0',
            'type' => assignment::TYPE_ADMIN,
            'status' => assignment::STATUS_ACTIVE,
            'created_by' => $this->logged_user(),
            'created_at' => time(),
            'updated_at' => time(),
            'archived_at' => null,
            'expand' => 1,
            'minproficiencyid' => null,
        ], $attributes);

        return (object) array_merge(['id' => $this->db()->insert_record('totara_competency_assignments', (object) $attributes)], $attributes);
    }

    /**
     * Archive assignment
     *
     * @param $assignment
     * @param bool $continue_tracking
     *
     * @return array Archived assignment ids
     */
    public function archive_assignment($assignment, $continue_tracking = true) {
        if (is_object($assignment)) {
            $assignment = $assignment->id;
        }

        return assignment_actions::create()->archive($assignment, $continue_tracking);
    }

    /**
     * Alias to create a cohort (audience)
     *
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_cohort(array $attributes = []) {
        return $this->data_generator()->create_cohort($attributes);
    }

    /**
     * Alias to create a cohort (audience)
     *
     * @param stdClass|array|int $members Cohort members ids or user objects
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_cohort_and_add_members($members, array $attributes = []) {
        $cohort = $this->data_generator()->create_cohort($attributes);

        if (!is_array($members)) {
            $members = [$members];
        }

        foreach ($members as $member) {
            cohort_add_member($cohort->id, is_object($member) ? $member->id : $member);
        }

        return $cohort;
    }

    /**
     * Alias to create a user
     *
     * @param array $attributes Record attributes
     * @return stdClass
     */
    public function create_user(array $attributes = []) {
        return $this->data_generator()->create_user($attributes);
    }

    /**
     * Create an organisation and a corresponding framework if needed
     *
     * @param array $attributes Record attributes
     * @param int|null $framework_id Framework ID, creates new if not supplied
     * @return stdClass
     */
    public function create_organisation(array $attributes = [], ?int $framework_id = null) {
        if (!empty($framework_id)) {
            $attributes['frameworkid'] = $framework_id;
        }

        if (empty($attributes['frameworkid'])) {
            $attributes['frameworkid'] = $this->generator->hierarchy_generator()->create_org_frame([])->id;
        }

        return $this->generator->hierarchy_generator()->create_org($attributes);
    }

    /**
     * Create an organisation + corresponding framework if needed and enrol users
     *
     * @param stdClass|array|int User id(s) or object(s) to add to the organisation
     * @param array $attributes Record attributes
     * @param int|null $framework_id Framework ID, creates new if not supplied
     * @return stdClass
     */
    public function create_organisation_and_add_members($members, array $attributes = [], ?int $framework_id = null) {
        $organisation = $this->create_organisation($attributes, $framework_id);

        if (!is_array($members)) {
            $members = [$members];
        }

        foreach ($members as $member) {
            $id = is_object($member) ? $member->id : $member;

            job_assignment::create([
                'userid' => $id ,
                'idnumber' => "ja_for_org_{$id}_user_{$organisation->id}",
                'fullname' => 'Job assignment numero ' . $id,
                'organisationid' => $organisation->id
            ]);
        }

        return $organisation;
    }

    /**
     * Create an organisation + corresponding framework if needed and enrol users
     *
     * @param stdClass|array|int User id(s) or object(s) to add to the position
     * @param array $attributes Record attributes
     * @param int|null $framework_id Framework ID, creates new if not supplied
     * @return stdClass
     */
    public function create_position_and_add_members($members, array $attributes = [], ?int $framework_id = null) {
        $position = $this->create_position($attributes, $framework_id);

        if (!is_array($members)) {
            $members = [$members];
        }

        foreach ($members as $member) {
            $id = is_object($member) ? $member->id : $member;

            job_assignment::create([
                'userid' => $id ,
                'idnumber' => "ja_for_pos_{$id}_user_{$position->id}",
                'fullname' => 'Job assignment numero ' . $id,
                'positionid' => $position->id
            ]);
        }

        return $position;
    }

    /**
     * Create a position and a corresponding framework if needed
     *
     * @param array $attributes Record attributes
     * @param int|null $framework_id Framework ID, creates new if not supplied
     * @return stdClass
     */
    public function create_position(array $attributes = [], ?int $framework_id = null) {
        if (!empty($framework_id)) {
            $attributes['frameworkid'] = $framework_id;
        }

        if (empty($attributes['frameworkid'])) {
            $attributes['frameworkid'] = $this->generator->hierarchy_generator()->create_pos_frame([])->id;
        }

        return $this->generator->hierarchy_generator()->create_pos($attributes);
    }

    /**
     * DB alias
     *
     * @return \moodle_database
     */
    protected function db() {
        global $DB;
        return $DB;
    }

    /**
     * Get logged user id
     *
     * @param int $fallback Login as specified user ID if no one is logged in
     * @return int
     */
    protected function logged_user(int $fallback = 2) {
        global $USER;

        return $USER->id ?: $fallback;
    }

    /**
     * Get basic testing data generator
     *
     * @return \core\testing\generator
     */
    protected function data_generator() {
        return \core\testing\generator::instance();
    }

}