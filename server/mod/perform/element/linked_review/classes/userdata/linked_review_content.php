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
 * @package performelement_linked_review
 */

namespace performelement_linked_review\userdata;

use Closure;
use context;
use coding_exception;

use core\entity\user;
use core\orm\query\builder;

use mod_perform\constants;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\section;
use mod_perform\entity\activity\section_element;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_section;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\participant_source;
use mod_perform\state\participant_section\complete;
use mod_perform\userdata\custom_userdata_exports;
use mod_perform\userdata\custom_userdata_item;

use performelement_linked_review\entity\linked_review_content as linked_review_content_entity;
use performelement_linked_review\entity\linked_review_content_response;
use performelement_linked_review\entity\linked_review_content_response_repository;

use totara_core\relationship\relationship;
use totara_userdata\userdata\target_user;

/**
 * Does GDPR userdata processing for the linked review element.
 */
class linked_review_content implements custom_userdata_item {
    /**
     * Cache to hold file item ids.
     */
    private $file_item_ids_cache = [];

    /**
     * Creates an instance of this class.
     *
     * @return linked_review_content the instance.
     */
    public static function create(): linked_review_content {
        return new linked_review_content();
    }

    /**
     * @inheritDoc
     */
    public function purge_subject(
        subject_instance $subject_instance,
        context $context
    ): void {
        // Unfortunately the ORM framework does not allow mass repository deletes
        // when there are tables joins present. So cannot do something like
        // repository->filter_XYZ()->delete(); have to get the ids of the records
        // to delete, then delete the records via their ids en mass.
        $response_ids = linked_review_content_response::repository()
            ->filter_by_subject_instance($subject_instance)
            ->get()
            ->pluck('id');

        if (!empty($response_ids)) {
            linked_review_content_response::repository()
                ->where('id', $response_ids)
                ->delete();
        }

        $content_ids = linked_review_content_entity::repository()
            ->filter_by_subject_instance($subject_instance)
            ->get()
            ->pluck('id');

        if (!empty($content_ids)) {
            linked_review_content_entity::repository()
                ->where('id', $content_ids)
                ->delete();
        }
    }

    /**
     * @inheritDoc
     */
    public function purge_participant(
        participant_instance $participant_instance,
        context $context
    ): void {
        $response_ids = linked_review_content_response::repository()
            ->filter_by_participant_instance($participant_instance)
            ->get()
            ->pluck('id');

        if (!empty($response_ids)) {
            linked_review_content_response::repository()
                ->where('id', $response_ids)
                ->delete();
        }
    }

    /**
     * @inheritDoc
     */
    public function count_participant_responses(
        target_user $participant,
        context $context
    ): array {
        $responses = $this
            ->get_participant_responses_repository($participant, $context)
            ->get();

        // The linked review main element has an empty response entry in the
        // element_response table; so it has to be deducted from that count in
        // the perform GDPR class.
        $parent_ids = array_unique($responses->pluck('linked_review_content_id'));

        return [$responses->count(), count($parent_ids)];
    }

    /**
     * @inheritDoc
     */
    public function export_participant_responses(
        target_user $participant,
        context $context
    ): custom_userdata_exports {
        return $this
            ->get_participant_responses_repository($participant, $context)
            ->get()
            ->reduce(
                Closure::fromCallable([$this, 'parse_response']),
                new custom_userdata_exports()
            );
    }

    /**
     * Returns the query to use to get participant responses.
     *
     * @param target_user $participant participant whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return linked_review_content_response_repository the repository set up
     *         to query for the linked review responses.
     */
    private function get_participant_responses_repository(
        target_user $participant,
        context $context
    ): linked_review_content_response_repository {
        return $this->get_all_responses_repository()
            ->filter_by_context($context)
            ->filter_by_participant_user_id($participant->id);
    }

    /**
     * @inheritDoc
     */
    public function count_other_visible_responses(
        target_user $subject,
        context $context
    ): array {
        $responses = $this
            ->get_other_visible_responses_repository($subject, $context)
            ->get();

        // The linked review main element has an empty response entry in the
        // element_response table; so it has to be deducted from that count in
        // the perform GDPR class.
        $parent_ids = array_unique($responses->pluck('linked_review_content_id'));

        return [$responses->count(), count($parent_ids)];
    }

    /**
     * @inheritDoc
     */
    public function export_other_visible_responses(
        target_user $subject,
        context $context
    ): custom_userdata_exports {
        return $this
            ->get_other_visible_responses_repository($subject, $context)
            ->get()
            ->reduce(
                Closure::fromCallable([$this, 'parse_response']),
                new custom_userdata_exports()
            );
    }

    /**
     * Returns the query to get a subject's visible responses.
     *
     * @param target_user $subject subject whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return linked_review_content_response_repository the repository set up
     *         to query for the linked review responses.
     */
    private function get_other_visible_responses_repository(
        target_user $subject,
        context $context
    ): linked_review_content_response_repository {
        return $this
            ->get_subject_responses_repository($subject, $context)
            ->where(
                function (builder $builder) use ($subject) {
                    $builder
                        ->where(
                            function (builder $builder) {
                                $section_relationship = section_relationship::TABLE;
                                $builder->where("${section_relationship}.can_view", true);
                            }
                        )
                        ->or_where(
                            function (builder $builder) use ($subject) {
                                $participant_instance = participant_instance::TABLE;
                                $builder->where("${participant_instance}.participant_id", $subject->id);
                            }
                        );
                }
            );
    }

    /**
     * @inheritDoc
     */
    public function count_other_hidden_responses(
        target_user $subject,
        context $context
    ): array {
        $responses = $this
            ->get_other_hidden_responses_repository($subject, $context)
            ->get();

        // The linked review main element has an empty response entry in the
        // element_response table; so it has to be deducted from that count in
        // the perform GDPR class.
        $parent_ids = array_unique($responses->pluck('linked_review_content_id'));

        return [$responses->count(), count($parent_ids)];
    }

    /**
     * @inheritDoc
     */
    public function export_other_hidden_responses(
        target_user $subject,
        context $context
    ): custom_userdata_exports {
        return $this
            ->get_other_hidden_responses_repository($subject, $context)
            ->get()
            ->reduce(
                Closure::fromCallable([$this, 'parse_response']),
                new custom_userdata_exports()
            );
    }

    /**
     * Returns the query to get a subject's hidden responses.
     *
     * @param target_user $subject subject whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return linked_review_content_response_repository the repository set up
     *         to query for the linked review responses.
     */
    private function get_other_hidden_responses_repository(
        target_user $subject,
        context $context
    ): linked_review_content_response_repository {
        $participant_instance = participant_instance::TABLE;
        $section_relationship = section_relationship::TABLE;
        $participant_section = participant_section::TABLE;

        return $this
            ->get_subject_responses_repository($subject, $context)
            ->where("${section_relationship}.can_view", false)
            ->where("{$participant_section}.progress", complete::get_code())
            ->where("${participant_instance}.participant_id", "!=", $subject->id);
    }

    /**
     * Formulates the base linked_review_content_response repository get all the
     * data need for exporting linked review element responses.
     *
     * @return linked_review_content_response_repository the repository set up
     *         to query for the linked review responses.
     */
    private function get_all_responses_repository(): linked_review_content_response_repository {
        $activity = activity::TABLE;
        $element = element::TABLE;
        $child_element = 'element_child';
        $participant_instance = participant_instance::TABLE;
        $linked_review = linked_review_content_entity::TABLE;
        $linked_review_response = linked_review_content_response::TABLE;
        $linked_review_response = linked_review_content_response::TABLE;

        // Note this returns a linked_review_content_response entity with extra
        // fields.
        return linked_review_content_response::repository()
            ->as($linked_review_response)
            ->add_activity_joins()
            ->add_element_joins()
            ->join(
                [$element, $child_element],
                "${linked_review_response}.child_element_id",
                'id'
            )
            ->select([
                "${linked_review_response}.*",
                "${activity}.id AS activity_id",
                "${activity}.name AS activity_name",
                "${activity}.anonymous_responses AS anonymous",
                "${element}.title AS parent_title",
                "${child_element}.plugin_name AS element_type",
                "${child_element}.title AS element_title",
                "${child_element}.data AS element_data",
                "${participant_instance}.participant_source",
                "${participant_instance}.participant_id",
                "${linked_review}.content_id AS content_id",
                "${linked_review}.content_type AS content_type"
            ]);
    }

    /**
     * Formulates the linked_review_content_response repository get all the
     * data need for exporting linked review element responses.
     *
     * @param target_user $subject subject whose responses are to be exported.
     * @param context $context restriction for this operation.
     *
     * @return linked_review_content_response_repository the repository set up
     *         to query for the linked review responses.
     */
    private function get_subject_responses_repository(
        target_user $subject,
        context $context
    ): linked_review_content_response_repository {
        $section_relationship = section_relationship::TABLE;
        $section = section::TABLE;
        $section_element = section_element::TABLE;
        $participant_section = participant_section::TABLE;
        $participant_instance = participant_instance::TABLE;

        return $this
            ->get_all_responses_repository()
            ->add_participant_instance_join()
            ->join($section, "${section_element}.section_id", 'id')
            ->join(
                $section_relationship,
                function (builder $builder) use ($section, $section_relationship): builder {
                    $id = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT)->id;

                    return $builder
                        ->where_field("${section}.id", "${section_relationship}.section_id")
                        ->where("${section_relationship}.core_relationship_id", '=', $id);
                }
            )
            ->join(
                $participant_section,
                function (builder $builder) use ($participant_section, $participant_instance, $section): builder {
                    return $builder
                        ->where_field("${participant_section}.section_id", "${section}.id")
                        ->where_field("${participant_section}.participant_instance_id", "{$participant_instance}.id");
                }
            )
            ->filter_by_context($context)
            ->filter_by_subject_user_id($subject->id)
            ->add_select("${section_relationship}.can_view as subject_can_view");
    }

    /**
     * Create an export record from the incoming raw data. Note each export is a
     * keyed array structured in this manner:
     * - activity_id: activity id
     * - activity_name: activity name
     * - linked_review_content_id: review content record id eg competency id
     * - linked_review_content_type: review content type eg totara_competency
     * - linked_review_title: main linked review question text
     * - element_title: review sub question text
     * - element_response: review sub question answer
     * - element_response_id: review sub question response id
     * - participant_id: respondent user id; not present if the response
     *   is anonymized.
     * - created_at: timestamp when response was created
     * - updated_at: timestamp when response was last updated.
     *
     * @param custom_userdata_exports $exports export instance to which to add
     *        current export data.
     * @param linked_review_content_response $raw raw data as formulated by the
     *        query from get_XYZ_responses_repository(). In other words, this has
     *        extra fields in addition to the normal linked_review_content_response
     *        attributes.
     *
     * @return custom_userdata_exports the updated export instance.
     */
    private function parse_response(
        custom_userdata_exports $exports,
        linked_review_content_response $raw
    ): custom_userdata_exports {
        try {
            $element_plugin = element_plugin::load_by_plugin($raw->element_type);
        } catch (coding_exception $e) {
            // This means the plugin was removed or does not exist. So no response
            // can be returned.
            return $exports;
        }

        if (!$element_plugin->get_is_respondable()) {
            return $exports;
        }

        $response = $element_plugin
            ->decode_response($raw->response_data, $raw->element_data);

        $export = [
            'activity_id' => $raw->activity_id,
            'activity_name' => $raw->activity_name,
            'linked_review_content_id' => $raw->content_id,
            'linked_review_content_type' => $raw->content_type,
            'linked_review_title' => $raw->parent_title,
            'element_title' => $raw->element_title,
            'element_response' => $response,
            'element_response_id' => $raw->id,
            'participant_id' => $raw->participant_id,
            'created_at' => $raw->created_at,
            'updated_at' => $raw->updated_at
        ];

        if ($raw->anonymous
            && (
                $raw->participant_source === participant_source::EXTERNAL
                || $raw->participant_id != user::logged_in()->id
            )
        ) {
            unset($export['participant_id']);
        }

        $section_element_id = $raw->linked_review_content->section_element_id;
        $participant_instance_id = $raw->participant_instance_id;
        $section_participant = "$section_element_id/$participant_instance_id";

        $file_item_ids = [];
        if (!in_array($section_participant, $this->file_item_ids_cache)) {
            $file_item_ids = element_response::repository()
                ->where('section_element_id', $section_element_id)
                ->where('participant_instance_id', $participant_instance_id)
                ->get()
                ->pluck('id');

            $this->file_item_ids_cache[] = $section_participant;
        }

        return $exports
            ->add_exports($export)
            ->add_file_item_ids(...$file_item_ids);
    }
}
