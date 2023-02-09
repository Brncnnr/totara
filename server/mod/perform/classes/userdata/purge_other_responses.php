<?php
/*
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_perform
*/

namespace mod_perform\userdata;

use context;
use core_component;
use core\orm\query\builder;
use Exception;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\userdata\helpers\userdata_file_helper;
use mod_perform\userdata\traits\purge_trait;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

class purge_other_responses extends item {
    use purge_trait;

    /**
     * Execute user data purging for this item.
     * @param target_user $user
     * @param context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context): int {
        global $DB;

        try {
            $DB->transaction(function () use ($user, $context) {
                static::purge_files($user->id);

                subject_instance::repository()
                    ->filter_by_context($context)
                    ->filter_by_subject_user($user->id)
                    ->get()
                    ->map(function (subject_instance $subject_instance) use ($context) {
                        $track_user_assignment_id = $subject_instance->track_user_assignment_id;
                        $track_user_assignment = new track_user_assignment($track_user_assignment_id);
                        $track_user_assignment->deleted = 1;
                        $track_user_assignment->save();

                        // Assumption: it is safe to run the custom purges before the
                        // the main bulk of Perform's GDPR purges.
                        static::purge_custom_userdata($subject_instance, $context);

                        // Delete cascades to include participant instances and responses etc.
                        $subject_instance->delete();
                    });
            });
        } catch (Exception $e) {
            return self::RESULT_STATUS_ERROR;
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Purge the response files for the given subject user.
     *
     * @param int $user_id
     */
    protected static function purge_files(int $user_id): void {
        // Files to be purged are identified via their element_response id, then
        // subject instance id. Since the linked review element always has
        // one entry in the element_response table and that record's' subject
        // instance id is the same as its child element responses, the statement
        // below also removes the files belonging to linked review child responses.

        $fs = get_file_storage();
        builder::table('files')
            ->when(true, function (builder $builder) {
                userdata_file_helper::apply_respondable_element_file_restrictions($builder);
            })
            ->join([element_response::TABLE, 'er'], 'itemid', 'id')
            ->join([participant_instance::TABLE, 'pi'], 'er.participant_instance_id', 'id')
            ->join([subject_instance::TABLE, 'si'], 'pi.subject_instance_id', 'id')
            ->where('si.subject_user_id', $user_id)
            ->get()
            ->map(function (object $file) use ($fs) {
                $fs->get_file_instance($file)->delete();
            });
    }

    /**
     * Count user data for this item.
     * @param target_user $user
     * @param context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context): int {
        return subject_instance::repository()
            ->filter_by_context($context)
            ->filter_by_subject_user($user->id)
            ->count();
    }

    /**
     * Executes custom user data purges for the given subject instance.
     *
     * @param subject_instance $subject_instance the activity subject whose data
     *        is to be purged.
     * @param context $context restriction for purging e.g., system context for
     *        everything, course context for purging one course.
     */
    private static function purge_custom_userdata(
        subject_instance $subject_instance,
        context $context
    ): void {
        $factories = core_component::get_namespace_classes(
            'userdata', custom_userdata_item::class
        );

        foreach ($factories as $factory) {
            $factory::create()->purge_subject($subject_instance, $context);
        }
    }
}