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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\enrol;

use container_workspace\exception\enrol_exception;
use container_workspace\member\member;
use container_workspace\workspace;
use core\entity\enrol;
use core\entity\user_enrolment;
use core\orm\collection;
use core\orm\query\builder;
use totara_core\visibility_controller;

/**
 * Enrolment manager.
 */
final class manager {
    /**
     * @var workspace
     */
    private $workspace;

    /**
     * enrollment_manager constructor.
     * @param workspace $workspace
     */
    private function __construct(workspace $workspace) {
        $this->workspace = $workspace;
    }

    /**
     * @param workspace $workspace
     * @return manager
     */
    public static function from_workspace(workspace $workspace): manager {
        $id = $workspace->id;
        if (null === $id || 0 === (int) $id) {
            throw new \coding_exception("Cannot instantiate an instance when workspace is invalid");
        }

        return new static($workspace);
    }

    /**
     * Enable a specific enrolment plugin
     *
     * @param string $enrol_type
     * @param array $fields
     * @return enrol
     */
    private function enable_enrolment(string $enrol_type, array $fields = []): enrol {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        if (!enrol_is_enabled($enrol_type)) {
            throw new \coding_exception("{$enrol_type} enrolment is not available");
        }

        $plugin = enrol_get_plugin($enrol_type);
        $record = $this->workspace->to_record();

        $enrol_fields = array_merge(['status' => ENROL_INSTANCE_ENABLED], $fields);
        $instance_id = $plugin->add_instance($record, $enrol_fields);
        return new enrol($instance_id);
    }

    /**
     * @return enrol
     */
    public function enable_self_enrolment(): enrol {
        return $this->enable_enrolment('self');
    }

    /**
     * @return enrol
     */
    public function enable_manual_enrolment(): enrol {
        return $this->enable_enrolment('manual');
    }

    /**
     * Enrol the given users in bulk
     *
     * @param array $user_ids
     * @param int $role_id
     * @param string $enrol
     */
    private function enrol_users_bulk(array $user_ids, int $role_id, string $enrol): void {
        $this->do_enrol_users($user_ids, $role_id, $enrol);
    }

    /**
     * Enrol a user in this workspace
     *
     * @param int $user_id
     * @param int $role_id
     * @param string $enrol
     *
     * @return void
     */
    private function enrol_user(int $user_id, int $role_id, string $enrol): void {
        $this->do_enrol_users([$user_id], $role_id, $enrol);
    }

    /**
     * First we need to check if this very user does have a record in enrolment or not. If there isn't
     * then we will create a new record and stop it from here. Otherwise, checking if it is the same
     * enrolment method or not.
     *
     * If it is the same enrolment method. Update the status, if it is not delete the record and create a new one.
     *
     * @param array $user_ids
     * @param int $role_id
     * @param string $enrol
     */
    private function do_enrol_users(array $user_ids, int $role_id, string $enrol): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $plugin = enrol_get_plugin($enrol);
        $workspace_id = $this->workspace->get_id();

        $enrol_instance = $DB->get_record(
            'enrol',
            [
                'enrol' => $plugin->get_name(),
                'courseid' => $workspace_id,
                'status' => ENROL_INSTANCE_ENABLED
            ],
            '*',
            IGNORE_MISSING
        );

        if (null === $enrol_instance || false === $enrol_instance) {
            throw enrol_exception::on_manual_enrol();
        }

        $context = $this->workspace->get_context();

        $user_ids_chunks = array_chunk($user_ids, builder::get_db()->get_max_in_params());
        $update_user_ids = [];
        $reactivated_userids = [];

        foreach ($user_ids_chunks as $user_ids_chunk) {
            // Get all enrol instances of the same type which are non-active and make them active
            /** @var user_enrolment[]|collection $user_enrolments */
            $user_enrolments = user_enrolment::repository()
                ->join(['enrol', 'e'], 'enrolid', 'id')
                ->where('e.courseid', $workspace_id)
                ->where('e.enrol', $plugin->get_name())
                ->where('userid', $user_ids_chunk)
                ->get_lazy();

            foreach ($user_enrolments as $user_enrolment) {
                $update_user_ids[] = $user_enrolment->userid;
                if ($user_enrolment->status != ENROL_USER_ACTIVE) {
                    $reactivated_userids[] = $user_enrolment->userid;
                    $plugin->update_user_enrol($enrol_instance, $user_enrolment->userid, ENROL_USER_ACTIVE);
                }
            }

            // Delete all enrollment instances for the users which are not of the given type
            /** @var user_enrolment[]|collection $user_enrolments */
            $user_enrolment_ids = user_enrolment::repository()
                ->join(['enrol', 'e'], 'enrolid', 'id')
                ->where('e.courseid', $workspace_id)
                ->where('e.enrol', '<>', $plugin->get_name())
                ->where('userid', $user_ids_chunk)
                ->get()
                ->pluck('id');

            if (!empty($user_enrolments)) {
                user_enrolment::repository()->where('id', $user_enrolment_ids)->delete();
            }
        }

        // Make sure the reactivated users have the correct role in the context
        if (!empty($reactivated_userids)) {
            if (count($reactivated_userids) > 1) {
                role_assign_bulk($role_id, $reactivated_userids, $context->id, 'container_workspace');
            } else {
                $user_id = array_shift($reactivated_userids);
                role_assign($role_id, $user_id, $context->id, 'container_workspace');
            }
        }

        $user_ids_to_enrol = array_diff($user_ids, $update_user_ids);

        if (!empty($user_ids_to_enrol)) {
            if (count($user_ids_to_enrol) > 1) {
                // For some unknown reason the bulk enrol function needs the ids encapsulated in objects
                $user_ids_mapped = array_map(
                    function ($user_id) {
                        return (object) ['userid' => $user_id];
                    },
                    $user_ids_to_enrol
                );

                $plugin->enrol_user_bulk($enrol_instance, $user_ids_mapped);
                role_assign_bulk($role_id, $user_ids_to_enrol, $context->id, 'container_workspace');
            } else {
                $user_id = array_shift($user_ids_to_enrol);
                $plugin->enrol_user($enrol_instance, $user_id);
                role_assign($role_id, $user_id, $context->id, 'container_workspace');
            }
        }
    }

    /**
     * @param int $user_id
     * @param int $role_id
     *
     * @return void
     */
    public function self_enrol_user(int $user_id, int $role_id): void {
        $this->enrol_user($user_id, $role_id, 'self');
    }

    /**
     * @param int $user_id
     * @param int $role_id
     * @param int|null $actor_id
     * @return void
     *
     * @throws enrol_exception
     */
    public function manual_enrol_user(int $user_id, int $role_id, ?int $actor_id = null): void {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        // Check capability.
        $context = $this->workspace->get_context();
        if (!has_capability('container/workspace:addmember', $context, $actor_id)) {
            throw enrol_exception::on_manual_enrol();
        }

        $this->enrol_user($user_id, $role_id, 'manual');
    }

    /**
     * Enrol users in bulk
     *
     * @param array $user_ids
     * @param int $role_id
     * @param int|null $actor_id
     * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
     */
    public function manual_enrol_user_bulk(array $user_ids, int $role_id, ?int $actor_id = null): void {
        debugging("manager::manual_enrol_user_bulk has been deprecated.", DEBUG_DEVELOPER);
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $owner_id = $this->workspace->get_user_id();
        if ($actor_id != $owner_id) {
            // Not an actor, time to check for capability.
            $context = $this->workspace->get_context();

            if (!has_capability('container/workspace:addmember', $context, $actor_id)) {
                throw enrol_exception::on_manual_enrol();
            }
        }

        $this->enrol_users_bulk($user_ids, $role_id, 'manual');
    }

    /**
     * Add a specific audience enrolment to the workspace for the specific role.
     * If no role is specified, then the workspace member role will be chosen.
     *
     * Returns the audience ids that were saved (any new ones), excluding any existing ids.
     *
     * @param array $audience_ids
     * @param int|null $role_id
     * @return array
     */
    public function enrol_audiences(array $audience_ids, ?int $role_id = null): array {
        global $DB;

        // If we have a role provided, use that. Otherwise fall back to the student
        if ($role_id === null) {
            $role = member::get_role_for_members();
            $role_id = $role->id;
        }

        // We need to filter out audience_ids that are already enrolled
        $existing = $DB->get_records(
            'enrol',
            [
                'courseid' => $this->workspace->id,
                'roleid' => $role_id,
                'status' => ENROL_INSTANCE_ENABLED,
            ]
        );
        $existing_ids = [];
        foreach ($existing as $audience) {
            $existing_ids[] = (int) $audience->customint1;
        }
        unset($existing_audiences);
        $audience_ids = array_diff($audience_ids, $existing_ids);

        foreach ($audience_ids as $audience_id) {
            $this->enable_enrolment(
                'cohort',
                [
                    'customint1' => $audience_id,
                    'roleid' => $role_id
                ]
            );
        }

        return $audience_ids;
    }

    /**
     * Enrollment will be suspended and any roles assigned in the workspace will be removed.
     * There are no capability checks performed, those must be performed beforehand.
     *
     * If the primary owner is removed, then they'll be removed from the workspace record as well.
     *
     * @param user_enrolment $user_enrolment
     * @return void
     */
    public function suspend_enrol(user_enrolment $user_enrolment): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $enrol = new enrol($user_enrolment->enrolid);
        $plugin = enrol_get_plugin($enrol->enrol);

        $instance = (object) $enrol->to_array();
        $user_id = $user_enrolment->userid;

        // Remove their assigned roles. It does not matter what roles are assigned
        $context = \context_course::instance($enrol->courseid);
        $roles = get_user_roles($context, $user_id, false);

        foreach ($roles as $role) {
            role_unassign($role->roleid, $user_id, $context->id, 'container_workspace');
        }

        // If we've removed the primary owner, update the workspace
        if ($this->workspace->get_user_id() == $user_id) {
            $this->workspace->remove_user();
        }

        $plugin->update_user_enrol($instance, $user_id, ENROL_USER_SUSPENDED);
    }

    /**
     * @param int|null $actor_id
     * @return  void
     */
    public function delete_enrol_instances(?int $actor_id = null): void {
        global $CFG, $USER, $DB;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $owner_id = $this->workspace->get_user_id();
        if ($actor_id != $owner_id && !is_siteadmin($actor_id)) {
            throw new \coding_exception("Cannot delete enrol instances");
        }

        $workspace_id = $this->workspace->get_id();
        $instances = $DB->get_records('enrol', ['courseid' => $workspace_id]);

        foreach ($instances as $instance) {
            $plugin = enrol_get_plugin($instance->enrol);
            $plugin->delete_instance($instance);
        }
    }

    /**
     * Delete the specific enrolments for the audience. This will delete all instances
     * for the specific audience.
     * @param int $audience_id
     * @return void
     */
    public function unenrol_audience(int $audience_id): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $instances = $DB->get_records(
            'enrol',
            [
                'courseid' => $this->workspace->id,
                'customint1' => $audience_id,
                'enrol' => 'cohort',
            ]
        );

        $plugin = enrol_get_plugin('cohort');
        foreach ($instances as $instance) {
            $plugin->delete_instance($instance);
        }
    }

    /**
     * Toggle the moodle/course:viewhiddencourses capability
     *
     * @param bool $is_hidden
     * @return void
     */
    public function toggle_viewhidden_capability(bool $is_hidden): void {
        if ($is_hidden && !$this->workspace->is_hidden()) {
            throw new \coding_exception('Cannot enable the moodle/course:viewhiddencourses capability for a non-hidden workspace');
        }

        $context = $this->workspace->get_context();
        $roles = [
            member::get_role_for_owners(),
            member::get_role_for_members(),
        ];

        foreach ($roles as $role) {
            if ($is_hidden) {
                assign_capability(
                    'moodle/course:viewhiddencourses',
                    CAP_ALLOW,
                    $role->id,
                    $context->id
                );
            } else {
                unassign_capability(
                    'moodle/course:viewhiddencourses',
                    $role->id,
                    $context->id
                );
            }
        }

        // We need to rebuild the visibility
        $map = visibility_controller::course()->map();
        $map->recalculate_map_for_instance($this->workspace->get_id());
    }
}