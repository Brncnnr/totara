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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review;

use coding_exception;
use core\collection;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\helpers\child_element_config as base_child_element_config;
use mod_perform\models\activity\helpers\element_usage as base_element_usage;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\response\section_element_response;
use mod_perform\rb\helper\element_plugin_response_report_builder;
use performelement_linked_review\helper\content_element_response_builder;
use performelement_linked_review\helper\content_element_response_validator;
use performelement_linked_review\rb\helper\response_report_builder;
use totara_core\entity\relationship;
use totara_core\relationship\relationship as relationship_model;

class linked_review extends respondable_element_plugin {

    /**
     * Get the content type definition class for the element.
     *
     * @param element $element
     * @return string|content_type
     */
    public function get_content_type(element $element): string {
        $data = json_decode($element->data, true);
        return content_type_factory::get_class_name_from_identifier($data['content_type']);
    }

    /**
     * Get the settings for
     *
     * @param element $element
     * @return array
     */
    public function get_content_settings(element $element): array {
        $data = json_decode($element->data, true);
        return $data['content_type_settings'];
    }

    /**
     * @inheritDoc
     */
    public function validate_response(
        ?string $encoded_response_data,
        ?element $element,
        $is_draft_validation = false
    ): collection {
        return (new content_element_response_validator())->validate_responses(
            $encoded_response_data,
            $element,
            $is_draft_validation
        );
    }

    /**
     * Pull the answer text string out of the encoded json data.
     *
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string|null
     */
    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data): ?string {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function validate_element(element_entity $element): void {
        parent::validate_element($element);

        $data = json_decode($element->data, true);

        if (empty($data)) {
            throw new coding_exception('No additional data was specified when saving the element with ID ' . $element->id);
        }

        $this->validate_content_type_and_settings($data);
    }

    /**
     * Validate the settings saved for the element.
     *
     * @param array $data Element data
     */
    private function validate_content_type_and_settings(array $data): void {
        $supported_keys = ['content_type', 'content_type_settings', 'selection_relationships'];
        $saved_keys = array_keys($data);
        if ($supported_keys != $saved_keys) {
            throw new coding_exception(
                'The saved data must contain and only contain these keys: ' . json_encode($supported_keys) .
                ', but the following keys were specified: ' . json_encode($saved_keys)
            );
        }

        $content_type = content_type_factory::get_class_name_from_identifier($data['content_type']);
        $available_settings = $content_type::get_available_settings();
        $saved_settings = $data['content_type_settings'] ?? [];

        // Check if the settings specified by the front end are actually supported.
        $invalid_saved_settings = array_diff(array_keys($saved_settings), array_keys($available_settings));
        if (!empty($invalid_saved_settings)) {
            throw new coding_exception(
                'Invalid setting(s) keys were saved: ' . json_encode($invalid_saved_settings) .
                '. Supported settings: ' . json_encode(array_keys($available_settings))
            );
        }

        $selection_relationship_ids = $data['selection_relationships'];
        if (empty($selection_relationship_ids)) {
            throw new coding_exception('No selection relationship IDs were specified.');
        }
        foreach ($selection_relationship_ids as $relationship_id) {
            if (!relationship::repository()->where('id', $relationship_id)->exists()) {
                throw new coding_exception('Invalid selection relationship ID specified: ' . $relationship_id);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function clean_element(element_entity $element): void {
        parent::clean_element($element);

        $data = json_decode($element->data, true);

        unset($data['components']);
        unset($data['selection_relationships_display']);
        unset($data['content_type_display']);
        unset($data['content_type_settings_display']);
        unset($data['compatible_child_element_plugins']);

        if (isset($data['content_type'], $data['content_type_settings']) && $element->exists()) {
            $content_type = content_type_factory::get_class_name_from_identifier($data['content_type']);
            $data['content_type_settings'] = $content_type::clean_content_type_settings($data['content_type_settings']);
        }

        $element->data = json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @inheritDoc
     */
    public function post_create(element $element): void {
        $data = json_decode($element->data, true, 512, JSON_THROW_ON_ERROR);

        $data = $this->set_content_type_and_settings($data);

        $element->set_data(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Set any missing settings with their respective defaults.
     *
     * @param array $data Element data
     * @return array Element data with the corrected settings.
     */
    private function set_content_type_and_settings(array $data): array {
        $content_type = content_type_factory::get_class_name_from_identifier($data['content_type']);

        $available_settings = $content_type::get_available_settings();
        $saved_settings = $data['content_type_settings'] ?? [];
        $settings = array_merge($available_settings, $saved_settings);
        $settings = $content_type::clean_content_type_settings($settings);

        return array_merge($data, ['content_type_settings' => $settings]);
    }

    /**
     * @inheritDoc
     */
    public function process_data(element_entity $element): ?string {
        $decoded_data = json_decode($element->data, true, 512, JSON_THROW_ON_ERROR);

        $content_type = content_type_factory::get_class_name_from_identifier($decoded_data['content_type']);

        $relationships = [];
        if (!empty($decoded_data['selection_relationships'])) {
            foreach ($decoded_data['selection_relationships'] as $relationship_id) {
                $relationships[] = [
                    'id' => $relationship_id,
                    'name' => relationship_model::load_by_id($relationship_id)->get_name()
                ];
            }
        }

        $human_readable_settings = [];
        foreach ($content_type::get_display_settings($decoded_data['content_type_settings']) as $name => $value) {
            $human_readable_settings[] = [
                'title' => $name,
                'value' => $value,
            ];
        }

        $additional_data = [
            'selection_relationships_display' => $relationships,
            'content_type_display' => $content_type::get_display_name(),
            'content_type_settings' => $content_type::get_content_type_settings($decoded_data['content_type_settings']),
            'content_type_settings_display' => $human_readable_settings,
            'components' => [
                'admin_content_footer' => $content_type::get_admin_content_footer_component(),
                'admin_settings' => $content_type::get_admin_settings_component(),
                'admin_view' => $content_type::get_admin_view_component(),
                'content_picker' => $content_type::get_content_picker_component(),
                'participant_content' => $content_type::get_participant_content_component(),
                'participant_content_footer' => $content_type::get_participant_content_footer_component(),
            ],
            'compatible_child_element_plugins' => $this->get_compatible_child_element_plugins($element->data),
        ];

        return json_encode(array_merge($decoded_data, $additional_data), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Gets child element plugins compatible with the element data.
     *
     * @param string|null $data
     * @return array
     */
    private function get_compatible_child_element_plugins(?string $data): array {
        $element_plugins = element_plugin::get_element_plugins();
        $compatible_plugins = [];

        foreach ($element_plugins as $element_plugin) {
            $element_usage = $element_plugin->get_element_usage();

            if ($element_usage->can_be_child_element &&
                $element_usage->is_compatible_child_element($this->get_plugin_name(), $data)
            ) {
                $compatible_plugins[] = $element_plugin->get_plugin_name();
            }
        }

        return $compatible_plugins;
    }

    /**
     * @inheritDoc
     */
    public function build_response_data(section_element_response $section_element_response): ?string {
        return (new content_element_response_builder($section_element_response))->build_response_data();
    }

    /**
     * @inheritDoc
     */
    public function build_response_data_formatted_lines(section_element_response $section_element_response): ?string {
        return (new content_element_response_builder($section_element_response))->build_response_data_formatted_lines();
    }

    /**
     * @inheritDoc
     */
    public function format_response_lines(?string $encoded_response_data, ?string $encoded_element_data): array {
        return [$encoded_response_data];
    }

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 90;
    }

    /**
     * @inheritDoc
     */
    public function get_child_element_config(): base_child_element_config {
        return new child_element_config();
    }

    /**
     * @inheritDoc
     */
    public function get_response_report_builder_helper(): ?element_plugin_response_report_builder {
        return new response_report_builder();
    }

    /**
     * @inheritDoc
     */
    public function get_element_usage(): base_element_usage {
        return new element_usage();
    }

    /**
     * @inheritDoc
     */
    public function is_enabled(): bool {
        $content_types = content_type_factory::get_all_enabled();
        return $content_types->count() > 0;
    }
}
