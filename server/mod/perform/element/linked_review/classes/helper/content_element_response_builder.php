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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace performelement_linked_review\helper;

use core\collection;
use mod_perform\models\activity\element;
use mod_perform\models\response\section_element_response;
use performelement_linked_review\entity\linked_review_content;
use performelement_linked_review\entity\linked_review_content_response;

/**
 * This class aggregates the submitted content child element responses(@see linked_review_content_response)
 * into a json structure.
 *
 * {
 *      "repeating_item_identifier" {
 *          "content_id": {
 *              "child_element_responses_identifier": {
 *                  "child_element_id": {
 *                      "response_data": //element_response
 *                      "child_element_id": Y // child element id
 *                  }
 *              }
 *              "content_id": X // content item id
 *          }
 *      }
 * }
 *
 * The child_element_responses_identifier & repeating_item_identifier are configurable strings in the child_element_config.
 * @see child_element_config
 *
 * @package performelement_linked_review\helpers
 */
class content_element_response_builder {

    /**
     * The parent section element response.
     *
     * @var section_element_response
     */
    private $section_element_response;

    /**
     * Constructor.
     *
     * @param section_element_response $section_element_response
     */
    public function __construct(section_element_response $section_element_response) {
        $this->section_element_response = $section_element_response;
    }

    /**
     * Builds the responses by grouping them content element responses.
     *
     * @return string|null
     */
    public function build_response_data(): ?string {
        $content_ids = $this->get_content_ids();
        $element_responses = $this->get_content_element_responses($content_ids);

        if ($element_responses->count() < 1) {
            return null;
        }
        $element_responses_per_content = $this->group_content_element_responses($element_responses);

        return $this->generate_result($content_ids, $element_responses_per_content);
    }

    /**
     * Builds the responses for response_data_formatted_lines.
     *
     * @return string|null
     */
    public function build_response_data_formatted_lines(): ?string {
        $content_ids = $this->get_content_ids();
        $element_responses = $this->get_content_element_responses($content_ids);

        if ($element_responses->count() < 1) {
            return null;
        }
        $element_responses_per_content = $this->group_content_element_response_formatted_lines($element_responses);

        return $this->generate_result($content_ids, $element_responses_per_content);
    }

    /**
     * Get the selected content ids of the subject user for the parent section_element_id.
     *
     * @return array
     */
    private function get_content_ids(): array {
        return linked_review_content::repository()
            ->where('section_element_id', $this->section_element_response->section_element_id)
            ->where('subject_instance_id', $this->section_element_response->participant_instance->subject_instance_id)
            ->get()
            ->pluck('id');
    }

    /**
     * Gets the saved content element responses of the participant for the specified content ids.
     *
     * @param array $review_content_ids
     * @return collection
     */
    private function get_content_element_responses(array $review_content_ids): collection {
        return linked_review_content_response::repository()
            ->where('linked_review_content_id', $review_content_ids)
            ->where('participant_instance_id', $this->section_element_response->participant_instance_id)
            ->get();
    }

    /**
     * Group the content child element responses.
     *
     * @param collection $content_element_responses
     * @return array
     */
    private function group_content_element_responses(collection $content_element_responses): array {
        $result = [];

        foreach ($content_element_responses as $content_element_response) {
            /** @var linked_review_content_response $content_element_response */
            $child_element_id = $content_element_response->child_element_id;
            $result[$content_element_response->linked_review_content_id][$child_element_id] = [
                'response_data' => $content_element_response->response_data,
                'child_element_id' => $child_element_id,
            ];
        }

        return $result;
    }

    /**
     * Group the content child element response formatted lines.
     *
     * @param collection $content_element_responses
     * @return array
     */
    private function group_content_element_response_formatted_lines(collection $content_element_responses): array {
        $result = [];

        foreach ($content_element_responses as $content_element_response) {
            /** @var linked_review_content_response $content_element_response*/
            $child_element_id = $content_element_response->child_element_id;
            /** @var element $element*/
            $element = $this->section_element_response->element->children->find('id', $child_element_id);

            $formatted_response_line = [];
            if ($element->element_plugin->get_is_respondable()) {
                $formatted_response_line = $element
                    ->element_plugin
                    ->format_response_lines(
                        $content_element_response->response_data,
                        $element->data
                    );
            }

            $result[$content_element_response->linked_review_content_id][$child_element_id] = [
                'response_data' => $formatted_response_line,
                'child_element_id' => $child_element_id,
            ];
        }

        return $result;
    }

    /**
     * Generates the json encoded response for the content ids and child element responses.
     *
     * @param array $content_ids
     * @param array $element_responses_per_content
     *
     * @return false|string
     */
    private function generate_result(array $content_ids, array $element_responses_per_content) {
        $content_responses = [];
        $child_element_config = $this->section_element_response
            ->element
            ->element_plugin
            ->get_child_element_config();
        $child_element_responses_identifier = $child_element_config->child_element_responses_identifier;

        foreach ($content_ids as $content_id) {
            $content_responses[$content_id] = [
                $child_element_responses_identifier => $element_responses_per_content[$content_id] ?? null,
                'content_id' => $content_id,
            ];
        }
        $repeating_item_identifier = $child_element_config->repeating_item_identifier;

        return json_encode([$repeating_item_identifier => $content_responses]);
    }
}