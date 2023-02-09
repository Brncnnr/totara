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
use core\collection;
use core\date_format;
use core\format;
use core\webapi\formatter\field\date_field_formatter;
use hierarchy_goal\entity\scale;
use hierarchy_goal\formatter\scale_value as scale_value_formatter;
use hierarchy_goal\entity\scale_value;
use hierarchy_goal\models\perform_status;
use mod_perform\entity\activity\participant_section;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use performelement_linked_review\content_type;
use performelement_linked_review\models\linked_review_content;
use performelement_linked_review\rb\helper\content_type_response_report;
use totara_core\advanced_feature;
use totara_core\hook\component_access_check;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship as relationship_model;

abstract class goal_assignment_content_type extends content_type {

    /**
     * The format type to use when formatting strings for output.
     */
    protected const TEXT_FORMAT = format::FORMAT_PLAIN;

    /**
     * @inheritDoc
     */
    public static function get_component(): string {
        return 'hierarchy_goal';
    }

    /**
     * @inheritDoc
     */
    public static function is_enabled(): bool {
        return advanced_feature::is_enabled('goals');
    }

    /**
     * @inheritDoc
     */
    public static function get_admin_settings_component(): ?string {
        return 'hierarchy_goal/components/performelement_linked_review/AdminEdit';
    }

    /**
     * @inheritDoc
     */
    public static function get_available_settings(): array {
        return [
            'enable_status_change' => false,
            'status_change_relationship' => null,
        ];
    }

    /**
     * @param array $settings
     * @return array
     */
    public static function get_display_settings(array $settings): array {
        $display_settings = [];

        $status_change_enabled = $settings['enable_status_change'] ?? false;
        $display_settings[get_string('enable_goal_status_change', 'hierarchy_goal')] = $status_change_enabled
            ? get_string('yes', 'core')
            : get_string('no', 'core');

        if ($status_change_enabled && !empty($settings['status_change_relationship'])) {
            $display_settings[get_string('enable_goal_status_change_participant', 'hierarchy_goal')] =
                relationship_model::load_by_id($settings['status_change_relationship'])->get_name();
        }

        return $display_settings;
    }

    /**
     * Append the actual human readable name of the status changing relationship if changing status is enabled.
     *
     * @param array $content_type_settings
     * @return array
     */
    public static function get_content_type_settings(array $content_type_settings): array {
        if (empty($content_type_settings['status_change_relationship']) || !$content_type_settings['enable_status_change']) {
            return $content_type_settings;
        }

        $relationship = relationship::load_by_id($content_type_settings['status_change_relationship']);
        $content_type_settings['status_change_relationship_name'] = $relationship->get_name();

        return $content_type_settings;
    }

    /**
     * Remove/clean any unwanted settings attributes before saving.
     *
     * @param array $content_type_settings
     * @return array
     */
    public static function clean_content_type_settings(array $content_type_settings): array {
        if ($content_type_settings['enable_status_change'] === false) {
            $content_type_settings['status_change_relationship'] = null;
        }
        unset($content_type_settings['status_change_relationship_name']);

        return $content_type_settings;
    }

    /**
     * @inheritDoc
     */
    public static function get_content_picker_component(): string {
        return 'hierarchy_goal/components/performelement_linked_review/ParticipantContentPicker';
    }

    /**
     * @inheritDoc
     */
    public static function get_participant_content_component(): string {
        return 'hierarchy_goal/components/performelement_linked_review/ParticipantContent';
    }

    /**
     * @inheritDoc
     */
    public static function get_admin_view_component(): string {
        return 'hierarchy_goal/components/performelement_linked_review/AdminView';
    }

    /**
     * @inheritDoc
     */
    public static function get_participant_content_footer_component(): string {
        return 'hierarchy_goal/components/ChangeStatusForm';
    }

    /**
     * @inheritDoc
     */
    public static function get_admin_content_footer_component(): string {
        return 'hierarchy_goal/components/ChangeStatusFormPreview';
    }

    /**
     * @inheritDoc
     */
    public static function get_response_report_helper(): content_type_response_report {
        return new response_report();
    }

    /**
     * Find out if a participant can view and change status for the given content items.
     *
     * @param collection $content_items
     * @param participant_section|null $participant_section
     * @param bool $can_view_other_responses
     * @return array
     */
    public static function get_goal_status_permissions(
        collection $content_items,
        ?participant_section $participant_section,
        bool $can_view_other_responses
    ): array {
        $can_change_status = false;
        $can_view_status = $can_view_other_responses;

        if ($participant_section) {
            // Element will be the same across all content items, so we can just get it from the first content item.
            /** @var linked_review_content $content_item */
            $content_item = $content_items->first();
            $participant_instance = participant_instance_model::load_by_entity($participant_section->participant_instance);

            $can_change_status = perform_status::can_change($participant_instance, $content_item->section_element);

            // If users are in a relationship that can change status or can view other responses they can view the rating
            $can_view_status = $can_change_status || $can_view_other_responses;
        }

        return [$can_view_status, $can_change_status];
    }

    /**
     * Format the scale_value, making sure the data runs through our formatters
     *
     * @param scale_value $scale_value
     * @return array|null
     */
    protected function format_scale_value(scale_value $scale_value): array {
        $scale_value_formatter = new scale_value_formatter($scale_value, $this->context);

        return [
            'id' => $scale_value->id,
            'name' => $scale_value_formatter->format('name', self::TEXT_FORMAT),
        ];
    }

    /**
     * Format the list of all scale values for a given scale.
     *
     * @param scale $scale
     * @return array
     */
    protected function format_scale_values(scale $scale): array {
        $formatted_scale_values = [];
        foreach ($scale->values as $scale_value) {
            $scale_value_formatter = new scale_value_formatter($scale_value, $this->context);
            $formatted_scale_values[] = [
                'id' => $scale_value->id,
                'name' => $scale_value_formatter->format('name', self::TEXT_FORMAT),
                'proficient' => (bool) $scale_value->proficient,
                'sort_order' => $scale_value->sortorder,
            ];
        }
        return $formatted_scale_values;
    }

    /**
     * Format the status change, making sure the data runs through our formatters
     *
     * @param perform_status $perform_status
     * @return array
     * @throws coding_exception
     */
    protected function format_status_change(perform_status $perform_status): array {
        $status_changer_user = null;
        if ($perform_status->status_changer_user) {
            $status_changer_user = [
                'fullname' => fullname($perform_status->status_changer_user->to_record())
            ];
        }

        $formatted_date = (new date_field_formatter(date_format::FORMAT_DATE, $this->context))
            ->format($perform_status->created_at);

        $scale_value = null;
        if ($perform_status->scale_value_id) {
            $scale_value = $this->format_scale_value($perform_status->scale_value);
        }

        return [
            'created_at' => $formatted_date,
            'status_changer_user' => $status_changer_user,
            'scale_value' => $scale_value,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function is_for_access_hook(component_access_check $hook): bool {
        $data = $hook->get_extra_data();
        if (!isset($data['content_type'])) {
            return false;
        }

        return $hook->get_component_name() === static::get_component() && $data['content_type'] === static::get_identifier();
    }

}
