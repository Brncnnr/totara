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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package performelement_redisplay
 */

namespace performelement_redisplay\data_provider;

use coding_exception;
use context_system;
use core\collection;
use core\date_format;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_section;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\models\response\responder_group;
use mod_perform\models\response\section_element_response;
use mod_perform\state\participant_section\complete;
use totara_core\entity\relationship;
use totara_core\relationship\relationship as relationship_model;

/**
 * This class builds previous participant response on a subject instance based on the redisplay element.
 *
 * It gets the most recent subject instance in relation to the specified current subject instance
 * & the activity the redisplay section_element_id belongs to.
 *
 * The selection of the most recent subject instance is based on the a job assignment is specified for the current subject instance.
 * @see source_data::select_subject_instance.
 *
 * Based on data found, it generates a title describing the situation of the previous subject instance.
 * It also then parses the responses for the specified section element id into your_response & the responder groups.
 *
 * The build method returns the result structure as @see previous_responses::return_response
 */
class previous_responses {

    /**
     * Section element id.
     *
     * @var int
     */
    private $section_element_id;

    /**
     * Current subject instance.
     *
     * @var subject_instance_model
     */
    private $current_subject_instance;

    /**
     * @var
     */
    private $source_data;

    /**
     * previous_responses constructor.
     *
     * @param int $section_element_id
     * @param subject_instance_model $subject_instance
     */
    public function __construct(int $section_element_id, subject_instance_model $subject_instance) {
        $this->section_element_id = $section_element_id;
        $this->current_subject_instance = $subject_instance;
    }

    /**
     * Build data object of previous responses with respect to current data.
     *
     * @param array $current_data
     * @return array
     */
    public function build(array $current_data): array {
        $this->validate_current_data($current_data);
        $this->set_source_data();

        if (empty($this->source_data['subject_instance'])) {
            return $this->return_response($this->get_title_without_subject_instance($current_data['activity']->id));
        }

        if ($this->no_participants()) {
            return $this->return_response($this->get_title_without_participation());
        }

        $responses = $this->get_responses_data();

        if (empty($responses['previous_responses'])) {
            return $this->return_response($this->get_title_without_participation());
        }

        if (!empty($current_data['participant_instance'])) {
            $responses['your_response'] = null;
            $previous_participant_instance = $this->find_previous_participant_instance($current_data['participant_instance']);

            if ($previous_participant_instance) {
                $responses = $this->separate_your_response($responses, $previous_participant_instance);
                $this->separate_your_participant_instance($previous_participant_instance->id);
            }
        }

        $responses_grouped_by_relationship = $this->group_previous_responses_by_relationship($responses['previous_responses']);

        $relationship_data = $this->source_data['activity']->anonymous_responses
            ? $this->get_anonymous_relationship_data($responses_grouped_by_relationship)
            : $this->get_relationships_data($responses_grouped_by_relationship);

        return $this->return_response(
            $this->get_title_with_responses($responses['latest_response']),
            $responses['your_response'] ?? null,
            $relationship_data['other_responder_groups'],
            $relationship_data['is_anonymous']
        );
    }

    /**
     * Validate current data has the needed values.
     *
     * @param array $current_data
     * @return void
     */
    private function validate_current_data(array $current_data): void {
        if (empty($current_data['activity']) || !$current_data['activity'] instanceof activity_model) {
            throw new coding_exception('current participant instance missing');
        }
    }

    /**
     * Set the source data with respect to the current subject instance.
     *
     * @return void
     */
    private function set_source_data(): void {
        $data = (new source_data($this->current_subject_instance))->get_data($this->section_element_id);
        $this->source_data = $data;
    }

    /**
     * Returns response object.
     *
     * @param string $title
     * @param section_element_response|null $your_response
     * @param array|collection|null $other_responder_groups
     * @param bool $is_anonymous
     *
     * @return array
     */
    private function return_response(
        string $title,
        ?section_element_response $your_response = null,
        ?array $other_responder_groups = null,
        bool $is_anonymous = false
    ): array {
        if (is_null($other_responder_groups)) {
            $other_responder_groups = new collection();
        }

        $result = [
            'title' => $title,
            'other_responder_groups' => $other_responder_groups,
            'is_anonymous' => $is_anonymous,
        ];

        if ($your_response !== null) {
            $result['your_response'] = $your_response;
        }

        return $result;
    }

    /**
     * Get responses and the latest response date.
     *
     * @return array
     */
    private function get_responses_data(): array {
        $previous_responses = $this->get_previously_submitted_responses();

        return [
            'previous_responses' => $previous_responses,
            'latest_response' => $this->get_most_recent_response_date($previous_responses),
        ];
    }

    /**
     * Get the previous responses submitted.
     *
     * @return array
     */
    private function get_previously_submitted_responses(): array {
        $section_id = $this->source_data['section_element']->section_id;

        return element_response::repository()
            ->as('er')
            ->join([participant_instance_entity::TABLE, 'pi'],'er.participant_instance_id', 'id')
            ->join([participant_section::TABLE, 'ps'],'pi.id', 'participant_instance_id')
            ->where('ps.section_id', $section_id)
            ->where('ps.progress', complete::get_code())
            ->find_for_participants_and_section_elements(
                $this->source_data['participant_instances']->pluck('id'),
                [$this->section_element_id]
            )
            ->filter(function ($response) {
                // todo: how to differentiate between a 'null' string and a null value in response_data.
                return $response->response_data != 'null';
            })
            ->key_by('participant_instance_id')
            ->all(true);
    }

    /**
     * Get date of most recent response.
     *
     * @param array $responses
     * @return string
     */
    private function get_most_recent_response_date(array $responses): string {
        $last_update_dates = array_map(function ($element_response) {
            return $element_response->updated_at;
        }, $responses);

        return $last_update_dates
            ? $this->format_date(max($last_update_dates))
            : '';
    }

    /**
     * Format date.
     *
     * @param string $timestamp
     * @return string
     */
    private function format_date(string $timestamp): string {
        return userdate($timestamp, get_string(date_format::get_lang_string(date_format::FORMAT_DATE), 'langconfig'));
    }

    /**
     * Find the previous participant instance.
     *
     * @param participant_instance_model $current_participant_instance
     * @return participant_instance_model|null
     */
    private function find_previous_participant_instance(
        participant_instance_model $current_participant_instance
    ): ?participant_instance_model {
        $participant_instances = $this->source_data['participant_instances'];

        return $participant_instances->find(function ($participant_instance) use ($current_participant_instance) {
            return $participant_instance->participant_id === $current_participant_instance->participant_id
                && $participant_instance->participant_source === $current_participant_instance->participant_source;
        });
    }

    /**
     * Separate your response from other responses.
     *
     * @param array $response_data
     * @param participant_instance_model $participant_instance
     * @return array
     */
    private function separate_your_response(array $response_data, participant_instance $participant_instance): array {
        $your_response = $response_data['previous_responses'][$participant_instance->id] ?? null;

        if ($your_response) {
            $response_data['your_response'] = new section_element_response(
                $participant_instance,
                $this->source_data['section_element'],
                $your_response,
                new collection()
            );
            unset($response_data['previous_responses'][$participant_instance->id]);
        }

        return $response_data;
    }

    /**
     * Separate your participant instance from all the participant instances.
     *
     * @param int $participant_instance_id
     * @return void
     */
    private function separate_your_participant_instance(int $participant_instance_id): void {
        $this->source_data['participant_instances'] = $this->source_data['participant_instances']->filter(
            function ($participant_instance) use ($participant_instance_id) {
                return $participant_instance->id !== $participant_instance_id;
            }
        );
    }

    /**
     * Group previous responses by relationship.
     *
     * @param array $previous_responses
     * @return array
     */
    private function group_previous_responses_by_relationship(array $previous_responses): array {
        $responses_by_relationship = [];

        /** @var array $participant_instances*/
        $participant_instances = $this->source_data['participant_instances']->key_by('id')->all(true);

        foreach ($previous_responses as $previous_response) {
            $participant_instance = $participant_instances[$previous_response->participant_instance_id];
            $responses_by_relationship[$participant_instance->core_relationship_id][] = new section_element_response(
                $participant_instance,
                $this->source_data['section_element'],
                $previous_response,
                new collection()
            );
        }

        return $responses_by_relationship;
    }

    /**
     * @param array $responses_grouped_by_relationship
     *
     * @return array
     */
    private function get_anonymous_relationship_data(array $responses_grouped_by_relationship): array {
        $responder_groups = [];
        $responder_groups[] = responder_group::create_anonymous_group()
            ->append_responses(array_merge(...$responses_grouped_by_relationship));

        return [
            'is_anonymous' => true,
            'other_responder_groups' => $responder_groups,
        ];
    }

    /**
     * Get the relationship data.
     *
     * @param array $responses_grouped_by_relationship
     * @return array
     */
    private function get_relationships_data(array $responses_grouped_by_relationship): array {
        $responder_groups = [];
        $relationships = relationship::repository()
            ->where_in('id', array_keys($responses_grouped_by_relationship))
            ->get()
            ->key_by('id')
            ->map_to(relationship_model::class)->all(true);

        foreach ($responses_grouped_by_relationship as $relationship_id => $participant_responses) {
            $responder_groups[] = new responder_group(
                $relationships[$relationship_id]->get_name(),
                new collection($participant_responses)
            );
        }

        return [
            'is_anonymous' => false,
            'other_responder_groups' => $responder_groups,
        ];
    }

    /**
     * Get title with responses.
     *
     * @param string $last_response_date
     * @return string
     */
    private function get_title_with_responses(string $last_response_date): string {
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());
        return get_string(
            'redisplayed_summary',
            'performelement_redisplay',
            (object) [
                'activity_name' => $formatter->format($this->source_data['activity']->name),
                'date_created' => $this->format_date($this->source_data['subject_instance']->created_at),
                'date_updated' => $last_response_date,
            ]
        );
    }

    /**
     * Get title without a subject instance.
     *
     * @param int $current_activity_id
     * @return string
     */
    private function get_title_without_subject_instance(int $current_activity_id): string {
        $string_identifier = $current_activity_id === $this->source_data['activity']->id
            ? 'redisplay_no_subject_instance_for_same_activity'
            : 'redisplay_no_subject_instance_for_another_activity';
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());

        return get_string($string_identifier, 'performelement_redisplay', $formatter->format($this->source_data['activity']->name));
    }

    /**
     * Checks if there are participants.
     *
     * @return bool
     */
    private function no_participants(): bool {
        return $this->source_data['subject_instance']->is_pending()
            || empty($this->source_data['participant_instances'])
            || $this->source_data['participant_instances']->count() === 0;
    }

    /**
     * Get title string when there's no participation.
     *
     * @return string
     */
    private function get_title_without_participation(): string {
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());
        $data = (object) [
            'activity_name' => $formatter->format($this->source_data['activity']->name),
            'subject_instance_date' => $this->format_date($this->source_data['subject_instance']->created_at),
        ];

        return get_string('redisplay_no_participants', 'performelement_redisplay', $data);
    }
}