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
use core\collection;
use mod_perform\entity\activity\element_response;
use mod_perform\userdata\traits\export_trait;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

class export_other_hidden_responses extends item {
    use export_trait;

    /**
     * Count user data for this item.
     * @param target_user $user
     * @param context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context): int {
        $element_response_count = (element_response::repository())
            ->filter_for_export()
            ->filter_by_context($context)
            ->filter_by_subject_for_export($user->id)
            ->filter_by_subject_cannot_view($user->id)
            ->count();

        return self::count_custom_userdata($element_response_count, $user, $context);
    }

    /**
     * Export user data from this item.
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        $responses = (element_response::repository())
            ->filter_for_export()
            ->filter_by_context($context)
            ->filter_by_subject_for_export($user->id)
            ->filter_by_subject_cannot_view($user->id)
            ->get(true)
            ->map(function ($response) use ($user) {
                return self::process_response_record($response, $user->id);
            })
            ->filter(
                // Because some responses can be processed explicitly via custom
                // handlers, they may return an empty data set in process_response_record().
                function (array $export): bool {
                    return !empty($export);
                }
            );

        $export = new export();
        $export->data = $responses->to_array();
        $export->files = static::get_response_files($responses->pluck('id'));

        return self::export_custom_userdata($export, $user, $context);
    }

    /**
     * Counts custom user data exports for the given subject.
     *
     * @param $initial_count existing count to which to add custom data counts.
     * @param target_user $user the activity subject whose data is to be
     *        exported.
     * @param context $context restriction for purging e.g., system context for
     *        everything, course context for purging one course.
     *
     * @return int[] a tuple containing *2* elements: the actual response count
     *         and the offset that needs to be subtracted from the main
     *         element_response count if any.
     */
    private static function count_custom_userdata(
        int $initial_count,
        target_user $user,
        context $context
    ): int {
        return self::custom_userdata_items()
            ->reduce(
                function (int $running_count, custom_userdata_item $item) use ($user, $context): int {
                    [$count, $offset] = $item->count_other_hidden_responses($user, $context);
                    $new_count = $running_count + $count - $offset;

                    return $new_count > 0 ? $new_count : 0;
                },
                $initial_count
            );
    }

    /**
     * Executes custom user data exports for the given participant.
     *
     * @param export $exports instance to which to add custom exports and files.
     * @param target_user $user the activity participant whose data is to be
     *        exported.
     * @param context $context restriction for purging e.g., system context for
     *        everything, course context for purging one course.
     *
     * @return export the updated set of data to be exported.
     */
    private static function export_custom_userdata(
        export $exports,
        target_user $user,
        context $context
    ): export {
        return self::custom_userdata_items()
            ->reduce(
                function (export $exports, custom_userdata_item $item) use ($user, $context): export {
                    return $item
                        ->export_other_hidden_responses($user, $context)
                        ->add_to_exports($exports);
                },
                $exports
            );
    }

    /**
     * Returns the custom userdata items to execute for the export.
     *
     * @param target_user $user the activity participant whose data is to be
     *        exported.
     * @param context $context restriction for purging e.g., system context for
     *        everything, course context for purging one course.
     *
     * @return collection|custom_userdata_item[] the custom user data items.
     */
    private static function custom_userdata_items(): collection {
        $factories = core_component::get_namespace_classes(
            'userdata', custom_userdata_item::class
        );

        return collection::new($factories)
            ->map(
                function (string $class): custom_userdata_item {
                    return $class::create();
                }
            );
    }
}