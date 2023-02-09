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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\performelement_linked_review;

use context_user;
use core\collection;
use core\date_format;
use core\format;
use customfield_base;
use customfield_file;
use mod_perform\entity\activity\participant_section;
use mod_perform\models\activity\helpers\external_participant_token_validator;
use mod_perform\models\activity\subject_instance;
use performelement_linked_review\content_type;
use performelement_linked_review\rb\helper\content_type_response_report;
use totara_core\advanced_feature;
use totara_core\hook\component_access_check;
use totara_evidence\customfield_area\field_helper;
use totara_evidence\entity\evidence_item as evidence_item_entity;
use totara_evidence\models\evidence_item as evidence_item_model;
use totara_evidence\models\evidence_type as evidence_type_model;
use totara_evidence\data_providers\evidence as evidence_provider;
use totara_evidence\customfield_area\evidence as evidence_customfield_area;
use totara_evidence\formatter\evidence_item as evidence_item_formatter;
use totara_evidence\formatter\evidence_type as evidence_type_formatter;
use totara_evidence\formatter\evidence_item_field as evidence_item_field_formatter;

class evidence extends content_type {

    /**
     * @inheritDoc
     */
    public static function get_component(): string {
        return 'totara_evidence';
    }

    /**
     * @inheritDoc
     */
    public static function get_identifier(): string {
        return 'totara_evidence';
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('pluginname', 'totara_evidence');
    }

    /**
     * @inheritDoc
     */
    public static function is_enabled(): bool {
        return advanced_feature::is_enabled('evidence');
    }

    /**
     * @inheritDoc
     */
    public static function get_table_name(): string {
        return evidence_item_entity::TABLE;
    }

    /**
     * @inheritDoc
     */
    public static function get_admin_view_component(): string {
        return 'totara_evidence/components/performelement_linked_review/AdminView';
    }

    /**
     * @inheritDoc
     */
    public static function get_admin_settings_component(): ?string {
        return null;
    }

    /**
     * @inheritDoc
     */
    public static function get_content_picker_component(): string {
        return 'totara_evidence/components/performelement_linked_review/ParticipantContentPicker';
    }

    /**
     * @inheritDoc
     */
    public static function get_participant_content_component(): string {
        return 'totara_evidence/components/performelement_linked_review/ParticipantContent';
    }

    /**
     * @inheritDoc
     */
    public static function get_available_settings(): array {
        return [];
    }

    /**
     * @param array $settings
     * @return array
     */
    public static function get_display_settings(array $settings): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function load_content_items(
        subject_instance $subject_instance,
        collection $content_items,
        ?participant_section $participant_section,
        bool $can_view_other_responses,
        int $created_at
    ): array {
        if ($content_items->count() === 0) {
            return [];
        }

        return evidence_provider::create()
            ->set_filters([
                'user_id' => $subject_instance->subject_user_id,
                'ids' => $content_items->pluck('content_id'),
            ])
            ->set_page_size($args['input']['limit'] ?? null)
            ->fetch()
            ->key_by('id')
            ->map(
                function (evidence_item_entity $evidence_item) use (
                    $subject_instance
                ) {
                    return $this->create_result_item(
                        $evidence_item,
                        $subject_instance
                    );
                }
            )
            ->all(true);
    }

    /**
     * Create the data for one evidence content item.
     *
     * @param evidence_item_entity $evidence_item
     *
     * @return array
     */
    private function create_result_item(
        evidence_item_entity $evidence_item,
        subject_instance $subject_instance
    ): array {
        $fields = [];

        $evidence_item_formatter = new evidence_item_formatter(
            evidence_item_model::load_by_entity($evidence_item),
            context_user::instance($subject_instance->subject_user_id)
        );

        $evidence_type_formatter = new evidence_type_formatter(
            evidence_type_model::load_by_entity($evidence_item->type),
            context_user::instance($subject_instance->subject_user_id)
        );

        foreach ($evidence_item->data as $field_data) {
            $field = $field_data->field;

            // Create a customfield instance.
            $field_class = field_helper::get_field_class($field->datatype);

            /** @var customfield_base $field_instance */
            $field_instance = new $field_class(
                $field->id,
                $evidence_item,
                evidence_customfield_area::get_prefix(),
                'totara_evidence_type'
            );

            $extra_data = [
                'prefix'   => evidence_customfield_area::get_prefix(),
                'itemid'   => $field_data->id,
                'extended' => true
            ];

            if ($field_instance instanceof customfield_file) {
                $token = external_participant_token_validator::find_token_in_session();
                $extra_data['url_params'] = [$token];
            }

            $field_data = $field_instance->get_raw_field_data($field_data->data, $extra_data);
            $evidence_item_field_formatter = new evidence_item_field_formatter(
                $field_data,
                context_user::instance($subject_instance->subject_user_id)
            );

            $fields[] = [
                'label' => $evidence_item_field_formatter->format('label', FORMAT::FORMAT_PLAIN),
                'type' => $field_data->get_type(),
                'content' => $field_data->extra_to_json(),
            ];
        }

        return [
            'id' => $evidence_item->id,
            'display_name' => $evidence_item_formatter->format('name', FORMAT::FORMAT_PLAIN),
            'type' => $evidence_type_formatter->format('name', FORMAT::FORMAT_PLAIN),
            'content_type' => 'totara_evidence',
            'created_at' => $evidence_item_formatter->format('created_at', date_format::FORMAT_DATE),
            'fields' => $fields,
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
