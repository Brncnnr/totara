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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use core\orm\query\builder;
use container_workspace\workspace as workspace_model;
use container_workspace\entity\workspace as entity;

/**
 * User data implementation for workspace.
 */
final class workspace extends item {
    /**
     * Note: all the workspaces can be purged from user(s), despite of their status
     * whether it is active or deleted.
     *
     * @param int $user_status
     * @return bool
     */
    public static function is_purgeable(int $user_status): bool {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_exportable(): bool {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function count(target_user $user, \context $context): int {
        $builder = builder::table('course', 'c');
        $builder->join([entity::TABLE, 'w'], 'c.id', 'w.course_id');
        $builder->where('w.user_id', $user->id);

        if (CONTEXT_COURSECAT == $context->contextlevel) {
            $category_id = $context->instanceid;
            $builder->where('c.category', $category_id);
        }

        return $builder->count();
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    protected static function export(target_user $user, \context $context): export {
        $builder = builder::table('course', 'c');
        $builder->select(['c.*', 'w.user_id',]);

        $builder->join([entity::TABLE, 'w'], 'c.id', 'w.course_id');
        $builder->where('w.user_id', $user->id);

        if (CONTEXT_COURSECAT == $context->contextlevel) {
            $category_id = $context->instanceid;
            $builder->where('c.category', $category_id);
        }

        $records = $builder->fetch();

        $export = new export();
        $fs = get_file_storage();

        foreach ($records as $record) {
            $workspace = workspace_model::from_record((object) $record);

            $map = [
                'full_name' => $workspace->fullname,
                'short_name' => $workspace->shortname,
                'summary' => content_to_text($workspace->summary, $workspace->summaryformat),
                'user_id' => $workspace->get_user_id(),
                'files' => []
            ];

            $files = $fs->get_area_files(
                $workspace->get_context()->id,
                workspace_model::get_type(),
                workspace_model::IMAGE_AREA,
                $workspace->get_id()
            );

            if (count($files) !== 0) {
                foreach ($files as $file) {
                    $map['files'][] = $export->add_file($file);
                }
            }

            $export->data[] = $map;
        }
        return $export;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function purge(target_user $user, \context $context): int {
        global $DB;

        $builder = builder::table('course', 'c');
        $builder->select(['c.id', 'w.user_id', 'w.id AS workspace_record_id']);

        $builder->join([entity::TABLE, 'w'], 'c.id', 'w.course_id');
        $builder->where('w.user_id', $user->id);

        if (CONTEXT_COURSECAT == $context->contextlevel) {
            $category_id = $context->instanceid;
            $builder->where('c.category', $category_id);
        }

        // Transfer ownership to the admin for workspace.
        $records = $builder->fetch();

        /** @var \stdClass $record */
        foreach ($records as $record) {
            $map = new \stdClass();

            $map->user_id = null;
            $map->id = $record->workspace_record_id;

            $DB->update_record(entity::TABLE, $map);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @return array
     */
    public static function get_fullname_string(): array {
        return ['user_data_item_workspace', 'container_workspace'];
    }

    /**
     * @return array
     */
    public static function get_compatible_context_levels(): array {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT];
    }
}
