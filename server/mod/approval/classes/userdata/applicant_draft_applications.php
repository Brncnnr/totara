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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\userdata;

use context;
use core\collection;
use totara_userdata\userdata\item;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;
use mod_approval\model\application\application;
use mod_approval\entity\application\application as application_entity;

class applicant_draft_applications extends item {

    /**
     * @inheritDoc
     */
    public static function is_purgeable(int $user_status) {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Is the given context level compatible with this item?
     * @return array
     */
    public static function get_compatible_context_levels(): array {
        return [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];
    }

    /**
     * Execute user data purging for this item.
     * @param target_user $user
     * @param context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context): int {

        $collection = self::get_applicant_records($user, $context);
        foreach ($collection as $item) {
            $application = application::load_by_id($item->id);
            $application->delete();
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Count user data for this item.
     * @param target_user $user
     * @param context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context): int {
        return self::get_applicant_records($user, $context)->count();
    }

    /**
     * Export user data from this item.
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        $export = new export();
        $export->data = self::get_applicant_records($user, $context)->all();
        return $export;
    }

    protected static function get_applicant_records(target_user $applicant, context $context): collection {
        $repo = application_entity::repository();
        $repo = self::get_activities_builder_join($repo, $context, 'approval', 'application.approval_id')
            ->as('application')
            ->select('*')
            ->join(['approval_workflow_version', 'workflow_version'], 'application.workflow_version_id', '=', 'id')
            ->join(['approval_workflow', 'workflow'], 'workflow_version.workflow_id', '=', 'id')
            ->join(['approval_workflow_type', 'workflow_type'], 'workflow.workflow_type_id', '=', 'id')
            ->join(['user', 'u'], 'application.user_id', 'id')
            ->join(['approval', 'assignment'], 'approval_id', 'id')
            ->join('course', 'assignment.course', 'id')
            ->where('user_id', $applicant->id)
            ->where_null('submitted');

        return $repo->get();
    }

    /**
     * @return array
     */
    public static function get_fullname_string(): array {
        return ['user_data_item_applicant_draft_applications', 'mod_approval'];
    }
}