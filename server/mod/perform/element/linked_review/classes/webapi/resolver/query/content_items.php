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

namespace performelement_linked_review\webapi\resolver\query;

use coding_exception;
use context;
use core\orm\entity\repository;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_instance_repository;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\helpers\external_participant_token_validator;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\section_element as section_element_model;
use mod_perform\models\activity\section_relationship;
use mod_perform\models\activity\settings\visibility_conditions\visibility_manager;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\util;
use performelement_linked_review\content_type;
use performelement_linked_review\content_type_factory;
use performelement_linked_review\models\linked_review_content;
use performelement_linked_review\models\linked_review_content as linked_review_content_model;

class content_items extends query_resolver {

    /**
     * {@inheritdoc}
     */
    final public static function resolve(array $args, execution_context $ec) {
        global $USER;

        $validator = null;
        $token = $args['token'] ?? $args['input']['token'] ?? null;
        $subject_instance_id = $args['subject_instance_id'] ?? $args['input']['subject_instance_id'] ?? null;
        $participant_section_id = $args['participant_section_id'] ?? $args['input']['participant_section_id'] ?? null;
        $section_element_id = $args['section_element_id'] ?? $args['input']['section_element_id'] ?? null;

        if (!empty($token)) {
            $validator = new external_participant_token_validator($token);
            if (!$validator->is_valid() || $validator->is_subject_instance_closed()) {
                throw new coding_exception('Token validation for external participant failed');
            }
            if ((int) $validator->get_participant_instance()->subject_instance_id !== (int) $subject_instance_id) {
                throw new coding_exception('Invalid subject instance for given token');
            }
        } else {
            \require_login(null, false, null, false, true);
        }

        $section_element = section_element_model::load_by_id($section_element_id);
        if ($section_element->element->get_element_plugin()->get_plugin_name() !== 'linked_review') {
            throw new coding_exception('Invalid section element ID: ' . $section_element_id);
        }

        $subject_instance = subject_instance_model::load_by_id($subject_instance_id);
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($subject_instance->get_context());
        }

        $participant_section = null;
        if ($participant_section_id) {
            if ($validator) {
                $participant_section = participant_section_entity::repository()
                    ->with('section.section_relationships')
                    ->where('id', $participant_section_id)
                    ->where('section_id', $section_element->section_id)
                    ->where('participant_instance_id', $validator->get_participant_instance()->id)
                    ->one();
            } else {
                $participant_section = participant_section_entity::repository()
                    ->with('section.section_relationships')
                    ->join([participant_instance::TABLE, 'pi'], 'participant_instance_id', 'id')
                    ->where('id', $participant_section_id)
                    ->where('section_id', $section_element->section_id)
                    ->where('pi.participant_id', $USER->id)
                    ->where('pi.participant_source', participant_source::INTERNAL)
                    ->where('pi.subject_instance_id', $subject_instance_id)
                    ->one();
            }
        }

        if ($participant_section === null
            && !util::can_report_on_user($subject_instance->subject_user_id, $USER->id)
        ) {
            throw new coding_exception('User does not participate in the section with ID ' . $section_element->section_id);
        }

        $content_items = linked_review_content_model::get_existing_selected_content($section_element_id, $subject_instance_id);
        if ($content_items->count() === 0) {
            return ['items' => []];
        }

        $content_type = self::get_content_type_instance($section_element, $subject_instance->get_context());

        $can_view_other_responses = $participant_section ? self::can_view_other_responses($participant_section) : true;

        $created_at = $content_items->first()->created_at;
        $loaded_content_items = $content_type->load_content_items(
            $subject_instance,
            $content_items,
            $participant_section,
            $can_view_other_responses,
            $created_at
        );
        foreach ($loaded_content_items as $content_id => $loaded_content_data) {
            $linked_item_model = $content_items->find(function (linked_review_content $item) use ($content_id) {
                [$content_id, $content_type] =
                    strpos($content_id, '-') !== false
                        ? explode('-', $content_id)
                        : [$content_id, null];

                $result = $content_id == $item->content_id;
                if ($content_type) {
                    $result = $result && $content_type == $item->content_type;
                }

                return $result;
            });

            if ($linked_item_model) {
                $linked_item_model->set_content($loaded_content_data);
            } else {
                throw new coding_exception("Couldn't find a linked content item with content ID {$content_id}");
            }
        }

        return ['items' => $content_items->all() ?? []];
    }

    /**
     * Get the instance of the content_type responsible for the content of the review element
     *
     * @param section_element_model $section_element
     * @param context $context
     * @return string|content_type
     */
    private static function get_content_type_instance(section_element_model $section_element, context $context): content_type {
        $element_data = $section_element->element->data;
        $data = json_decode($element_data, true);

        return content_type_factory::get_from_identifier($data['content_type'], $context);
    }

    /**
     * Check it the participant can view the other responses on the given section
     *
     * @param participant_section_entity $participant_section
     * @return bool
     */
    private static function can_view_other_responses(participant_section_entity $participant_section): bool {
        /** @var section_relationship $section_relationship */
        $section_relationship = $participant_section
            ->section
            ->section_relationships
            ->find('core_relationship_id', $participant_section->participant_instance->core_relationship_id);

        $other_responses_visible = self::are_other_responses_visible($participant_section);

        return $section_relationship->can_view && $other_responses_visible;
    }

    /**
     * Checks if visibility conditions on activity allow viewing other responses.
     *
     * @param participant_section_entity $participant_section
     * @return bool
     */
    private static function are_other_responses_visible(participant_section_entity $participant_section): bool {
        $participant_instance = $participant_section->participant_instance;

        $other_participant_instances = participant_instance_entity::repository()
            ->as('pi')
            ->join([participant_section_entity::TABLE, 'ps'], 'id', 'participant_instance_id')
            ->when(true, function (repository $repository) {
                participant_instance_repository::add_user_not_hidden_filter($repository, 'pi');
            })
            ->where('subject_instance_id', $participant_instance->subject_instance_id)
            // Guard on the actual participant_instance_id (rather than participant_user_id)
            // because we need to handle the case where the same user is both a manager and appraiser to the subject.
            ->where('pi.id', '!=', $participant_section->participant_instance_id)
            ->where('ps.section_id', $participant_section->section_id)
            ->get();

        $participant_instance = participant_instance_model::load_by_entity($participant_section->participant_instance);

        $activity_settings = $participant_instance->subject_instance->activity->get_settings();
        $visibility_value = $activity_settings->lookup(activity_setting::VISIBILITY_CONDITION) ?? 0;

        $visibility_option = (new visibility_manager())->get_option_with_value($visibility_value);

        return $visibility_option->show_responses($participant_instance, $other_participant_instances);
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
        ];
    }

}
