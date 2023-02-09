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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\userdata;

use context;

use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\participant_instance;

use totara_userdata\userdata\target_user;

/**
 * Hook for additional/custom processing in Perform's GDPR process.
 *
 * NB: implementations must provide a static method with this signature so that
 * the main Perform GDPR classes can create instances:
 *     public static create(): custom_userdata_item
 */
interface custom_userdata_item {
    /**
     * Purges data belonging to the specified subject.
     *
     * @param subject_instance $subject_instance subject instance whose details
     *        are to be purged.
     * @param context $context restriction for this operation.
     *
     * @throws Exception if the processing failed.
     */
    public function purge_subject(
        subject_instance $subject_instance,
        context $context
    ): void;

    /**
     * Purges data belonging to the specified participant.
     *
     * @param participant_instance $participant_instance participant instance
     *        whose details are to be purged.
     * @param context $context restriction for this operation.
     *
     * @throws Exception if the processing failed.
     */
    public function purge_participant(
        participant_instance $participant_instance,
        context $context
    ): void;

    /**
     * Counts all responses belonging to the specified participant.
     *
     * @param target_user $participant participant whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return int[] a tuple containing *2* elements: the actual response count
     *         and the offset that needs to be subtracted from the main
     *         element_response count if any.
     */
    public function count_participant_responses(
        target_user $participant,
        context $context
    ): array;

    /**
     * Exports all responses belonging to the specified participant.
     *
     * @param target_user $participant participant whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return custom_userdata_exports the exported data.
     *
     * @throws Exception if the processing failed.
     */
    public function export_participant_responses(
        target_user $participant,
        context $context
    ): custom_userdata_exports;

    /**
     * Counts responses belonging to everyone in all activities in which the
     * target user is a subject.
     *
     * @param target_user $subject subject whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return int[] a tuple containing *2* elements: the actual response count
     *         and the offset that needs to be subtracted from the main
     *         element_response count if any.
     */
    public function count_other_visible_responses(
        target_user $subject,
        context $context
    ): array;

    /**
     * Exports responses belonging to everyone in all activities in which the
     * target user is a subject.
     *
     * @param target_user $subject subject whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return custom_userdata_exports the exported data.
     */
    public function export_other_visible_responses(
        target_user $subject,
        context $context
    ): custom_userdata_exports;

    /**
     * Counts responses belonging to other participants in all activities in which
     * the target user is a subject.
     *
     * @param target_user $subject subject whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return int[] a tuple containing *2* elements: the actual response count
     *         and the offset that needs to be subtracted from the main
     *         element_response count if any.
     */
    public function count_other_hidden_responses(
        target_user $subject,
        context $context
    ): array;

    /**
     * Exports responses belonging to other participants in all activities in which
     * the target user is a subject.
     *
     * @param target_user $subject subject whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return custom_userdata_exports the exported data.
     */
    public function export_other_hidden_responses(
        target_user $subject,
        context $context
    ): custom_userdata_exports;
}
