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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use coding_exception;
use core\collection;
use core\orm\entity\repository;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\models\activity\participant_source;
use mod_perform\state\participant_section\not_submitted;

class participant_section_repository extends repository {

    /**
     * Get the first participant section for a given participant instance.
     *
     * @param int $participant_instance_id
     * @param int $participant_id
     * @param int $participant_source
     * @return participant_section
     * @throws coding_exception
     */
    public static function fetch_default(
        int $participant_instance_id,
        int $participant_id,
        int $participant_source = participant_source::INTERNAL
    ): participant_section_entity {
        /** @var participant_section_entity $first_participant_section */
        $first_participant_section = participant_section_entity::repository()
            ->as('ps')
            ->join([section::TABLE, 's'], 'ps.section_id', 's.id')
            ->where('participant_instance_id', $participant_instance_id)
            ->order_by('s.sort_order', 'asc')
            ->join([participant_instance_entity::TABLE, 'pi'], 'ps.participant_instance_id', 'pi.id')
            ->when(true, function (repository $repository) {
                participant_instance_repository::add_user_not_hidden_filter($repository, 'pi');
            })
            ->where('pi.participant_id', $participant_id)
            ->where('pi.participant_source', $participant_source)
            ->first();

        if ($first_participant_section === null) {
            throw new coding_exception('No participant section found for this subject instance and given participant');
        }

        return $first_participant_section;
    }

    /**
     * Gets the participant section for a user.
     *
     * @param int $participant_section_id
     * @param int $user_id
     * @param int $participant_source
     * @return participant_section|null
     */
    public static function get_participant_section_for_user(
        int $participant_section_id,
        int $user_id,
        int $participant_source = participant_source::INTERNAL
    ): ?participant_section_entity {
        return participant_section_entity::repository()->as('ps')
            // Bulk fetch all related entities that are required to build the domain models.
            ->with('section_elements.element') // Used in section element_response class (element is for validation).
            ->with('participant_instance') // For section element response class.
            ->with('section.core_relationships.resolvers') // To create other responder groups.
            ->with('participant_instance.core_relationship.resolvers') // For excluding main participant in other responder groups.
            // Ensure the user we are fetching responses for is a participant for the section they belong to.
            ->join([participant_instance_entity::TABLE, 'pi'], 'ps.participant_instance_id', 'pi.id')
            ->when(true, function (repository $repository) {
                participant_instance_repository::add_user_not_hidden_filter($repository, 'pi');
            })
            ->where('ps.id', $participant_section_id)
            ->where('pi.participant_id', $user_id)
            ->where('pi.participant_source', $participant_source)
            ->one(false);
    }

    /**
     * Gets all participant sections relating to particular participant instance.
     *
     * @param int $participant_instance_id
     * @param int $user_id Returned participant sections will be restricted to this user
     * @param int $participant_source
     * @return participant_section_entity[]|collection
     */
    public static function get_all_for_participant_instance(
        int $participant_instance_id,
        int $user_id,
        int $participant_source = participant_source::INTERNAL
    ): collection {
        return participant_section_entity::repository()->as('ps')
            ->with('participant_instance')
            // Ensure the user we are fetching responses for is a participant for the section they belong to.
            ->join([participant_instance_entity::TABLE, 'pi'], 'ps.participant_instance_id', 'pi.id')
            ->join([section::TABLE, 's'], 'ps.section_id', 's.id')
            ->when(true, function (repository $repository) {
                participant_instance_repository::add_user_not_hidden_filter($repository, 'pi');
            })
            ->where('pi.id', $participant_instance_id)
            ->where('pi.participant_id', $user_id)
            ->where('pi.participant_source', $participant_source)
            ->order_by('s.sort_order', 'asc')
            ->get();
    }

    /**
     * Observe the "Hide incomplete responses for closed instances" setting.
     *
     * Effectively this means excluding participant sections in progress state "not submitted",
     * unless they belong to the acting user.
     *
     * @param int $acting_participant_id
     * @param int $acting_participant_source
     * @param string $table_alias
     * @return $this
     */
    public function hide_incomplete_when_configured(int $acting_participant_id, int $acting_participant_source, string $table_alias = 'ps'): self {
        return $this
            ->when(
                get_config(null, 'perform_hide_incomplete_responses_closed_instances'),
                function (participant_section_repository $repository) use ($table_alias, $acting_participant_id, $acting_participant_source) {
                    $repository
                        ->join([participant_instance::TABLE, 'pi_hide'], "{$table_alias}.participant_instance_id", 'pi_hide.id')
                        ->where_raw(self::get_hide_incomplete_sections_condition_sql($acting_participant_id, $acting_participant_source, 'pi_hide'));
                }
            );
    }

    /**
     * Returns a common SQL snippet to use when the 'perform_hide_incomplete_responses_closed_instances' setting is turned on.
     *
     * @param int $acting_participant_id
     * @param int $acting_participant_source
     * @param string $participant_instance_alias
     * @param string $participant_section_alias
     * @return string
     */
    public static function get_hide_incomplete_sections_condition_sql(
        int $acting_participant_id,
        int $acting_participant_source,
        string $participant_instance_alias = 'pi',
        string $participant_section_alias = 'ps'
    ): string {
        return "
            (
                {$participant_section_alias}.progress <> " . not_submitted::get_code() . "
                OR (
                    {$participant_instance_alias}.participant_id = " . $acting_participant_id . "
                    AND {$participant_instance_alias}.participant_source = " . $acting_participant_source . "                            
                )
            )
        ";
    }
}