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
namespace container_workspace\tracker;

use container_workspace\interactor\workspace\interactor;
use container_workspace\workspace;
use core\entity\user_last_access;
use core\orm\query\builder;
use core\orm\query\order;
use core_container\factory;

/**
 * A class to keep track the last workspace that a user visited
 */
final class tracker {
    /**
     * @var int
     */
    private $user_id;

    /**
     * tracker constructor.
     * @param int|null $user_id
     */
    public function __construct(?int $user_id = null) {
        global $USER;

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $this->user_id = $user_id;
    }

    /**
     * @return void
     */
    public static function clear_all(): void {
        global $DB;
        $sql = '
            SELECT DISTINCT c.id FROM "ttr_user_lastaccess" ul
            INNER JOIN "ttr_course" c ON c.id = ul.courseid AND c.containertype = :workspace_type
        ';

        $ids = $DB->get_fieldset_sql($sql, ['workspace_type' => workspace::get_type()]);
        foreach ($ids as $id) {
            $DB->delete_records(user_last_access::TABLE, ['id' => $id]);
        }
    }

    /**
     * @param int $workspace_id
     * @return void
     */
    public function clear(int $workspace_id): void {
        global $DB;
        $DB->delete_records(
            user_last_access::TABLE,
            [
                'courseid' => $workspace_id,
                'userid' => $this->user_id
            ]
        );
    }

    /**
     * @param workspace $workspace
     * @param int|null  $time
     *
     * @return void
     */
    public function visit_workspace(workspace $workspace, ?int $time = null): void {
        if (empty($time)) {
            $time = time();
        }

        if ($workspace->is_to_be_deleted()) {
            // Workspace is about to deleted. No point to update the time tracking.
            debugging(
                "Workspace is deleted, cannot track workspace anymore",
                DEBUG_DEVELOPER
            );
            return;
        }

        $workspace_id = $workspace->get_id();
        $builder = builder::table(user_last_access::TABLE);

        $builder->where('userid', $this->user_id);
        $builder->where('courseid', $workspace_id);
        $builder->map_to(user_last_access::class);

        /** @var user_last_access $last_access */
        $last_access = $builder->one();

        if (null !== $last_access) {
            $last_access->timeaccess = $time;
            $last_access->save();

            return;
        }

        $last_access = new user_last_access();
        $last_access->userid = $this->user_id;
        $last_access->courseid = $workspace_id;
        $last_access->timeaccess = $time;

        $last_access->save();
    }

    /**
     * @return int|null
     */
    public function get_last_visit_workspace(): ?int {
        global $CFG;

        $builder = builder::table(user_last_access::TABLE, 'ul');
        $builder->select('ul.*');

        $builder->join(
            ['course', 'c'],
            function (builder $join): void {
                $join->where_field('c.id', 'ul.courseid');
                $join->where('c.containertype', workspace::get_type());
            }
        );

        // Excluding the workspaces that are flagged to be deleted.
        $builder->join(
            ['workspace', 'w'],
            function (builder $join): void {
                $join->where_field('c.id', 'w.course_id');
                $join->where('w.to_be_deleted', 0);
            }
        );

        $builder->where('ul.userid', $this->user_id);

        $builder->order_by('ul.timeaccess', order::DIRECTION_DESC);
        $builder->map_to(user_last_access::class);

        /** @var user_last_access $last_access */
        $last_access = $builder->first();
        if (null === $last_access) {
            return null;
        }

        // Confirm the user can access this workspace. This includes multitenancy checks and all other necessary checks.
        $workspace = factory::from_id($last_access->courseid);
        $workspace_interactor = new interactor($workspace, $this->user_id);
        if (!$workspace_interactor->can_view_workspace()) {
            return null;
        }

        return $last_access->courseid;
    }

    /**
     * Returning the last time visit of a given workspace.
     *
     * @param int $workspace_id
     * @return int|null
     */
    public function get_last_time_visit_workspace(int $workspace_id): ?int {
        $builder = builder::table(user_last_access::TABLE, 'ul');
        $builder->join(
            ['course', 'c'],
            function (builder $join): void {
                $join->where_field('c.id', 'ul.courseid');
                $join->where('c.containertype', workspace::get_type());
            }
        );

        $builder->select('ul.*');
        $builder->where('ul.courseid', $workspace_id);
        $builder->where('ul.userid', $this->user_id);
        $builder->map_to(user_last_access::class);

        /** @var user_last_access $last_access */
        $last_access = $builder->one();
        if (null === $last_access) {
            return null;
        }

        return $last_access->timeaccess;
    }

    /**
     * @param int $workspace_id
     * @return void
     */
    public static function clear_all_for_workspace(int $workspace_id): void {
        $workspace = factory::from_id($workspace_id);
        if (!$workspace->is_typeof(workspace::get_type())) {
            // Prevent from the usage of this function on different course.
            throw new \coding_exception("Cannot find the workspace with the given id");
        }

        $builder = builder::table(user_last_access::TABLE);
        $builder->where('courseid', $workspace_id);

        $builder->delete();
    }
}