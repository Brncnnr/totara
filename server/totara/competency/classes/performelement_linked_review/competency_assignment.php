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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\performelement_linked_review;

use core\collection;
use core\date_format;
use core\format;
use core\webapi\formatter\field\date_field_formatter;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\subject_instance;
use pathway_perform_rating\models\perform_rating;
use performelement_linked_review\content_type;
use performelement_linked_review\models\linked_review_content;
use performelement_linked_review\rb\helper\content_type_response_report;
use totara_competency\data_providers\assignments;
use totara_competency\entity\assignment;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\formatter\assignment as assignment_formatter;
use totara_competency\formatter\competency as competency_formatter;
use totara_competency\formatter\profile\scale_value_progress;
use totara_competency\formatter\scale_value;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\profile\proficiency_value;
use totara_core\advanced_feature;
use totara_core\hook\component_access_check;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship as relationship_model;

class competency_assignment extends content_type {

    /**
     * The format type to use when formatting strings for output.
     */
    private const TEXT_FORMAT = format::FORMAT_PLAIN;

    /**
     * @inheritDoc
     */
    public static function get_component(): string {
        return 'totara_competency';
    }

    /**
     * @inheritDoc
     */
    public static function get_identifier(): string {
        return 'totara_competency';
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('pluginname', 'totara_competency');
    }

    /**
     * @inheritDoc
     */
    public static function get_table_name(): string {
        return assignment::TABLE;
    }

    /**
     * @inheritDoc
     */
    public static function is_enabled(): bool {
        return advanced_feature::is_enabled('competencies')
            && advanced_feature::is_enabled('competency_assignment');
    }

    /**
     * @inheritDoc
     */
    public static function get_admin_settings_component(): ?string {
        return 'totara_competency/components/performelement_linked_review/AdminEdit';
    }

    /**
     * @inheritDoc
     */
    public static function get_available_settings(): array {
        return [
            'enable_rating' => false,
            'rating_relationship' => null,
        ];
    }

    /**
     * @param array $settings
     * @return array
     */
    public static function get_display_settings(array $settings): array {
        $display_settings = [];

        $rating_enabled = $settings['enable_rating'] ?? false;
        $display_settings[get_string('enable_performance_rating', 'totara_competency')] = $rating_enabled
            ? get_string('yes', 'core')
            : get_string('no', 'core');

        if ($rating_enabled && !empty($settings['rating_relationship'])) {
            $display_settings[get_string('enable_performance_rating_participant', 'totara_competency')] =
                relationship_model::load_by_id($settings['rating_relationship'])->get_name();
        }

        return $display_settings;
    }

    /**
     * Append the actual human readable name of the rating relationship if rating is enabled.
     *
     * @param array $content_type_settings
     * @return array
     */
    public static function get_content_type_settings(array $content_type_settings): array {
        if (empty($content_type_settings['rating_relationship']) || !$content_type_settings['enable_rating']) {
            return $content_type_settings;
        }

        $relationship = relationship::load_by_id($content_type_settings['rating_relationship']);
        $content_type_settings['rating_relationship_name'] = $relationship->get_name();

        return $content_type_settings;
    }

    /**
     * Remove/clean any unwanted settings attributes before saving.
     *
     * @param array $content_type_settings
     * @return array
     */
    public static function clean_content_type_settings(array $content_type_settings): array {
        if ($content_type_settings['enable_rating'] === false) {
            $content_type_settings['rating_relationship'] = null;
        }
        unset($content_type_settings['rating_relationship_name']);

        return $content_type_settings;
    }

    /**
     * @inheritDoc
     */
    public static function get_content_picker_component(): string {
        return 'totara_competency/components/performelement_linked_review/ParticipantContentPicker';
    }

    /**
     * @inheritDoc
     */
    public static function get_participant_content_component(): string {
        return 'totara_competency/components/performelement_linked_review/ParticipantContent';
    }

    /**
     * @inheritDoc
    */
    public static function get_admin_view_component(): string {
        return 'totara_competency/components/performelement_linked_review/AdminView';
    }

    /**
     * @inheritDoc
     */
    public static function get_participant_content_footer_component(): string {
        return 'pathway_perform_rating/components/RatingForm';
    }

    /**
     * @inheritDoc
     */
    public static function get_admin_content_footer_component(): string {
        return 'pathway_perform_rating/components/RatingFormPreview';
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

        $can_rate = false;
        $can_view_rating = $can_view_other_responses;

        if ($participant_section) {
            // Element will be the same across all content items, so we can just get it from the first content item.
            /** @var linked_review_content $content_item */
            $content_item = $content_items->first();
            $participant_instance = participant_instance_model::load_by_entity($participant_section->participant_instance);

            $can_rate = perform_rating::can_rate($participant_instance, $content_item->section_element);

            // If users are in a rater relationship or can view other responses they can view the rating
            $can_view_rating = $can_rate || $can_view_other_responses;
        }

        return assignments::for($subject_instance->subject_user_id)
            ->set_filters([
                'ids' => $content_items->pluck('content_id'),
            ])
            ->fetch()
            ->get()
            ->key_by('id')
            ->map(
                function (assignment_entity $assignment) use (
                    $subject_instance,
                    $created_at,
                    $can_rate,
                    $can_view_rating
                ) {
                    return $this->create_result_item(
                        $assignment,
                        $subject_instance,
                        $created_at,
                        $can_rate,
                        $can_view_rating
                    );
                }
            )
            ->all(true);
    }

    /**
     * Create the data for one competency content item
     *
     * @param assignment_entity $assignment
     * @param subject_instance $subject_instance
     * @param int $created_at
     * @param bool $can_rate
     * @param bool $can_view_rating
     * @return array
     */
    private function create_result_item(
        assignment_entity $assignment,
        subject_instance $subject_instance,
        int $created_at,
        bool $can_rate,
        bool $can_view_rating
    ): array {

        $proficiency_value = proficiency_value::value_at_timestamp($assignment, $subject_instance->subject_user_id, $created_at);
        $assignment_model = assignment_model::load_by_entity($assignment);

        $competency_formatter = new competency_formatter($assignment_model->get_competency(), $this->context);
        $assignment_formatter = new assignment_formatter($assignment_model, $this->context);

        $rating = $can_view_rating ? perform_rating::get_existing_rating($assignment->competency_id, $subject_instance->id) : null;

        return [
            'id' => $assignment_model->get_id(),
            'competency' => [
                'id' => $assignment_model->get_competency()->id,
                'display_name' => $competency_formatter->format('display_name', self::TEXT_FORMAT),
                'description' => $competency_formatter->format('description', format::FORMAT_HTML),
            ],
            'assignment' => [
                'reason_assigned' => $assignment_formatter->format('reason_assigned', self::TEXT_FORMAT),
            ],
            'achievement' => $this->format_proficiency_value($proficiency_value),
            'scale_values' => $this->format_scale_values($assignment_model),
            'can_rate' => $can_rate && !$rating,
            'can_view_rating' => $can_view_rating,
            'rating' => $rating ? $this->format_rating($rating) : null,
        ];
    }

    /**
     * Format the proficiency_value, making sure the data runs through our formatters
     *
     * @param proficiency_value $achievement
     * @return array|null
     */
    private function format_proficiency_value(proficiency_value $achievement): array {
        $scale_value_formatter = new scale_value_progress($achievement, $this->context);

        return [
            'id' => $achievement->id,
            'name' => $scale_value_formatter->format('name', self::TEXT_FORMAT),
            'proficient' => $scale_value_formatter->format('proficient', self::TEXT_FORMAT),
        ];
    }

    /**
     * Format the proficiency_value, making sure the data runs through our formatters
     *
     * @param assignment_model $assignment
     * @return array
     */
    private function format_scale_values(assignment_model $assignment): array {
        $scale = $assignment->get_assignment_specific_scale();

        $result = new collection();
        foreach ($scale->values as $value) {
            $formatter = new scale_value($value, $this->context);
            $result->append([
                'id' => $value->id,
                'name' => $formatter->format('name', self::TEXT_FORMAT),
                'proficient' => (bool) $value->proficient,
                'sort_order' => $value->sortorder,
                'description_html' => $formatter->format('description', format::FORMAT_HTML),
            ]);
        }
        return $result->sort('sort_order', 'asc', false)->to_array();
    }

    /**
     * Format the rating, making sure the data runs through our formatters
     *
     * @param perform_rating $rating
     * @return array
     */
    private function format_rating(perform_rating $rating): array {
        $rater_user = null;
        if ($rating->rater_user) {
            $rater_user = [
                'fullname' => fullname($rating->rater_user->to_record())
            ];
        }

        $formatted_date = (new date_field_formatter(date_format::FORMAT_DATE, $this->context))
            ->format($rating->created_at);

        $scale_value = null;
        if ($rating->scale_value_id) {
            $scale_value_formatter = new scale_value($rating->scale_value, $this->context);
            $scale_value = [
                'id' => $rating->scale_value_id,
                'name' => $scale_value_formatter->format('name', self::TEXT_FORMAT),
            ];
        }

        return [
            'created_at' => $formatted_date,
            'rater_user' => $rater_user,
            'scale_value' => $scale_value,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_response_report_helper(): content_type_response_report {
        return new response_report();
    }

    /**
     * @inheritDoc
     */
    public static function is_for_access_hook(component_access_check $hook): bool {
        return $hook->get_component_name() === static::get_component();
    }

}
