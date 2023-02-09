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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\performelement_linked_review;

use coding_exception;
use context_system;
use core\collection;
use core\date_format;
use core\format;
use core\webapi\formatter\field\date_field_formatter;
use goal;
use hierarchy_goal\data_providers\assigned_company_goals;
use hierarchy_goal\entity\company_goal_assignment as company_goal_assignment_entity;
use hierarchy_goal\formatter\company_goal as company_goal_formatter;
use hierarchy_goal\helpers\goal_helper;
use hierarchy_goal\models\company_goal_perform_status;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\models\activity\subject_instance;
use performelement_linked_review\rb\helper\content_type_response_report;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

class company_goal_assignment extends goal_assignment_content_type {

    /**
     * @inheritDoc
     */
    public static function get_identifier(): string {
        return 'company_goal';
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('companygoal', 'totara_hierarchy');
    }

    /**
     * @inheritDoc
     */
    public static function get_table_name(): string {
        return company_goal_assignment_entity::TABLE;
    }

    /**
     * @inheritDoc
     */
    public function load_content_items(
        subject_instance $subject_instance,
        collection $content_items,
        ?participant_section_entity $participant_section,
        bool $can_view_other_responses,
        int $created_at
    ): array {
        if ($content_items->count() === 0) {
            return [];
        }

        [$can_view_status, $can_change_status] = self::get_goal_status_permissions(
            $content_items,
            $participant_section,
            $can_view_other_responses
        );

        return assigned_company_goals::create()
            ->set_filters([
                'user_id' => $subject_instance->subject_user_id,
                'ids' => $content_items->pluck('content_id'),
            ])
            ->fetch()
            ->key_by('id')
            ->map(
                function (company_goal_assignment_entity $company_goal_assignment) use (
                    $subject_instance,
                    $created_at,
                    $can_change_status,
                    $can_view_status
                ) {
                    return $this->create_result_item(
                        $company_goal_assignment,
                        $subject_instance,
                        $created_at,
                        $can_change_status,
                        $can_view_status
                    );
                }
            )
            ->all(true);
    }

    /**
     * Create the data for one company goal content item
     *
     * @param company_goal_assignment_entity $company_goal_assignment
     * @param subject_instance $subject_instance
     * @param int $created_at
     * @param bool $can_change_status
     * @param bool $can_view_status
     * @return array
     */
    private function create_result_item(
        company_goal_assignment_entity $company_goal_assignment,
        subject_instance $subject_instance,
        int $created_at,
        bool $can_change_status,
        bool $can_view_status
    ): array {
        $goal_status_scale_value = goal_helper::get_goal_scale_value_at_timestamp(
            goal::SCOPE_COMPANY,
            $company_goal_assignment->id,
            $created_at
        );
        $target_date = goal_helper::get_goal_target_date_at_timestamp(
            goal::SCOPE_COMPANY,
            $company_goal_assignment->goalid,
            $created_at
        );
        if (!$goal_status_scale_value) {
            throw new coding_exception("Scale could not be found for company goal assignment {$company_goal_assignment->id}");
        }
        $existing_status_change = $can_view_status
            ? company_goal_perform_status::get_existing_status($company_goal_assignment->goalid, $subject_instance->id)
            : null;

        $company_goal_formatter = new company_goal_formatter($company_goal_assignment->goal, context_system::instance());

        return [
            'id' => $company_goal_assignment->id,
            'goal' => [
                'id' => $company_goal_assignment->goalid,
                'display_name' => $company_goal_formatter->format('full_name', self::TEXT_FORMAT),
                'description' => $company_goal_formatter->format('description', format::FORMAT_HTML),
                'goal_scope' => goal::GOAL_SCOPE_COMPANY,
            ],
            'status' => $this->format_scale_value($goal_status_scale_value),
            'scale_values' => $this->format_scale_values($goal_status_scale_value->scale),
            'target_date' => ($target_date > 0)
                ? (new date_field_formatter(date_format::FORMAT_DATE, $this->context))->format($target_date)
                : null,
            'can_view_goal_details' => $this->can_view_goal_details(),
            'can_change_status' => $can_change_status,
            'can_view_status' => $can_view_status,
            'status_change' => $existing_status_change
                ? $this->format_status_change($existing_status_change)
                : null,
        ];
    }

    /**
     * Can the current user view the goals details
     *
     * @return bool
     */
    private function can_view_goal_details(): bool {
        return has_any_capability(['totara/hierarchy:viewallgoals', 'totara/hierarchy:viewgoal'], context_system::instance());
    }

    /**
     * @inheritDoc
     */
    public static function get_response_report_helper(): content_type_response_report {
        return new company_goal_response_report();
    }

}
