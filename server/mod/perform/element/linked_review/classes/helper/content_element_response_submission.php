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

use coding_exception;
use core\orm\query\builder;
use mod_perform\hook\post_element_response_submission;
use mod_perform\models\activity\element;
use mod_perform\models\activity\participant_instance;
use performelement_linked_review\child_element_config;
use performelement_linked_review\entity\linked_review_content;
use performelement_linked_review\entity\linked_review_content_response;

/**
 * This class handles saving the child element responses for the selected contents.
 * It expects the response JSON structure as:
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
*/
class content_element_response_submission extends content_element_response_base {

    /**
     * Content child element responses.
     *
     * @var array
     */
    private $content_responses = [];

    /**
     * Child elements.
     *
     * @var array
     */
    private $child_elements;

    /**
     * Participant instance.
     *
     * @var participant_instance
     */
    private $participant_instance;

    /**
     * Subject instance content ids.
     *
     * @var array
     */
    private $subject_instance_content_ids;

    /**
     * Parent section element response id.
     *
     * @var int
     */
    private $section_element_response_id;

    /**
     * String identifier used to group child element responses.
     *
     * @var string
     */
    private $child_element_responses_identifier;

    /**
     * Decodes the content responses and stores the list of content child elements in memory.
     *
     * @param string $element_response
     * @param element $element
     * @param participant_instance $participant_instance
     * @param int $section_element_response_id
     */
    public function __construct(
        string $element_response,
        element $element,
        participant_instance $participant_instance,
        int $section_element_response_id
    ) {
        $this->participant_instance = $participant_instance;
        $this->set_respondable_content_ids($participant_instance->subject_instance_id);
        $child_element_config = $element->element_plugin->get_child_element_config();
        $this->child_element_responses_identifier = $child_element_config->child_element_responses_identifier;

        $repeating_item_identifier = $child_element_config->repeating_item_identifier;
        $content_responses = $this->decode_content_responses($element_response, $repeating_item_identifier);

        if (!empty($content_responses)) {
            $this->content_responses = $content_responses;
        }
        $this->child_elements = $element->children->key_by('id')->all(true);
        $this->section_element_response_id = $section_element_response_id;
    }

    /**
     * Set subject instance's content ids the participant can respond to.
     *
     * @param int $subject_instance_id
     */
    private function set_respondable_content_ids(int $subject_instance_id): void {
        $this->subject_instance_content_ids = linked_review_content::repository()->where('subject_instance_id', $subject_instance_id)
            ->get()->pluck('id');
    }

    /**
     * Save content responses.
     *
     * @return void
     */
    public function save_responses(): void {
        builder::get_db()->transaction(function() {
            foreach ($this->content_responses as $content_response) {
                if (!in_array($content_response['content_id'], $this->subject_instance_content_ids)) {
                    throw new coding_exception("You do not have permissions to respond to this content.");
                }
                $this->save_child_element_responses($content_response[$this->child_element_responses_identifier], $content_response['content_id']);
            }
        });
    }

    /**
     * Save content's child element responses for the specified content id.
     *
     * @param array $child_element_responses
     * @param int $linked_content_id
     * @return void
     */
    private function save_child_element_responses(array $child_element_responses, int $linked_content_id): void {
        foreach ($child_element_responses as $child_element_response) {
            if (empty($child_element_response['response_data']) || empty($child_element_response['child_element_id'])) {
                continue;
            }

            if(empty($this->child_elements[$child_element_response['child_element_id']])) {
                throw new coding_exception("Child element does not exist.");
            }
            $this->update_or_create_response(
                $child_element_response['response_data'],
                $linked_content_id,
                $this->child_elements[$child_element_response['child_element_id']],
            );
        }
    }

    /**
     * Update or create child element response for content response.
     *
     * @param string|null $response_data
     * @param int $content_id
     * @param element $child_element
     * @return void
     */
    private function update_or_create_response(
        ?string $response_data,
        int $content_id,
        element $child_element
    ): void {
        $hook = new post_element_response_submission(
            $this->section_element_response_id,
            $child_element,
            $this->participant_instance,
            $response_data,
        );
        $hook->execute();

        linked_review_content_response::update_or_create_response(
            $content_id,
            $child_element->id,
            $this->participant_instance->id,
            $hook->get_response_data()
        );
    }
}