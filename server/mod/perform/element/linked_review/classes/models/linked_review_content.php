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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\models;

use coding_exception;
use core\entity\user;
use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\subject_instance;
use moodle_exception;
use performelement_linked_review\content_type_factory;
use performelement_linked_review\entity\linked_review_content as linked_review_content_entity;
use performelement_linked_review\entity\linked_review_content_response;
use performelement_linked_review\linked_review;

/**
 * Class element_subject_instance_review_content
 *
 * @property-read int $id
 * @property-read int $section_element_id
 * @property-read int $subject_instance_id
 * @property-read int $selector_id
 * @property-read string $content
 * @property-read int $content_id
 * @property-read int $created_at
 * @property-read user $selector
 * @property-read subject_instance $subject_instance
 * @property-read section_element $section_element
 * @property-read linked_review_content_response[]|collection $responses
 *
 * @package performelement_linked_review\models
 */
class linked_review_content extends model {

    /**
     * @var linked_review_content_entity
     */
    protected $entity;

    /**
     * Actual content which can be json encoded
     *
     * @var mixed
     */
    protected $content;

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return linked_review_content_entity::class;
    }

    protected $entity_attribute_whitelist = [
        'id',
        'content_id',
        'content_type',
        'section_element_id',
        'subject_instance_id',
        'selector',
        'selector_id',
        'created_at',
        'responses',
        'meta_data',
    ];

    protected $model_accessor_whitelist = [
        'content',
        'section_element',
        'subject_instance',
    ];

    /**
     * Update the content IDs that are linked to the subject user's section element.
     *
     * @param array $content_ids
     * @param int $section_element_id
     * @param int $participant_instance_id
     * @return static[]|collection
     */
    public static function update_content(array $content_ids, int $section_element_id, int $participant_instance_id): collection {
        self::validate_input($content_ids, $section_element_id, $participant_instance_id);

        return builder::get_db()->transaction(function () use ($content_ids, $section_element_id, $participant_instance_id) {
            $participant_instance = participant_instance_model::load_by_id($participant_instance_id);

            $current_linked_content_ids = self::get_existing_selected_content(
                $section_element_id,
                $participant_instance->subject_instance_id
            )->pluck('content_id');

            $content_ids_to_delete = array_diff($current_linked_content_ids, $content_ids);
            self::delete_multiple($content_ids_to_delete, $section_element_id, $participant_instance_id, false);

            $new_content_ids_to_create = array_diff($content_ids, $current_linked_content_ids);
            return self::create_multiple($new_content_ids_to_create, $section_element_id, $participant_instance_id, false);
        });
    }

    /**
     * Create multiple new content links to a section element.
     *
     * @param array $content array of content ids or content data containing the id
     * @param int $section_element_id
     * @param int $participant_instance_id
     * @param bool $validate Whether to validate the inputted IDs.
     * @return collection
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function create_multiple(
        array $content,
        int $section_element_id,
        int $participant_instance_id,
        bool $validate = true
    ): collection {
        if ($validate) {
            self::validate_input($content, $section_element_id, $participant_instance_id);
        }

        return collection::new($content)
            ->map(static function ($content) use ($section_element_id, $participant_instance_id) {
                if (is_int($content)) {
                    $content = ['id' => $content];
                }
                return self::create_from_content($content, $section_element_id, $participant_instance_id, false);
            });
    }

    /**
     * Create a new content link to a section element.
     *
     * @param int $content_id
     * @param int $section_element_id
     * @param int $participant_instance_id
     * @param bool $validate Whether to validate the inputted IDs.
     * @param string|null $item_type
     * @return static
     */
    public static function create(
        int $content_id,
        int $section_element_id,
        int $participant_instance_id,
        bool $validate = true,
        ?string $item_type = ''
    ): self {
        $content = [
            'id' => $content_id,
            'itemtype' => $item_type,
        ];

        return self::create_from_content($content, $section_element_id, $participant_instance_id, $validate);
    }

    /**
     * Create a new content link to a section element from a content object.
     *
     * @param array $content
     * @param int $section_element_id
     * @param int $participant_instance_id
     * @param bool $validate Whether to validate the inputted IDs.
     * @return static
     */
    public static function create_from_content(
        array $content,
        int $section_element_id,
        int $participant_instance_id,
        bool $validate = true
    ): self {
        if ($validate) {
            self::validate_input([$content], $section_element_id, $participant_instance_id);
        }

        $participant_instance = participant_instance_model::load_by_id($participant_instance_id);

        $element = section_element::load_by_id($section_element_id)->element;
        $element_data = json_decode($element->data, true);
        $element_plugin = $element->element_plugin;

        $content_type = $element_data['content_type'] ?? null;
        if (!$element_plugin instanceof linked_review || !$content_type) {
            throw new coding_exception('element plugin is not a linked_review type');
        }

        $content_type_instance = content_type_factory::get_from_identifier($content_type, $participant_instance->get_context());
        $content_type = $content_type_instance->get_content_type_name($content);

        $content_id = $content['id'] ?? null;
        if (empty($content_id)) {
            throw new coding_exception('Missing content id');
        }

        $meta_data = $content_type_instance->get_metadata($participant_instance->subject_instance->subject_user_id, $content);

        $entity = new linked_review_content_entity();
        $entity->content_id = $content_id;
        $entity->content_type = $content_type;
        $entity->meta_data = json_encode($meta_data);
        $entity->section_element_id = $section_element_id;
        $entity->subject_instance_id = $participant_instance->subject_instance_id;
        $entity->selector_id = $participant_instance->participant_id;
        $entity->save();

        return static::load_by_entity($entity);
    }

    /**
     * Unlink the specified content IDs from the section element.
     *
     * @param int[] $content_ids
     * @param int $section_element_id
     * @param int $participant_instance_id
     * @param bool $validate Whether to validate the inputted IDs.
     */
    public static function delete_multiple(
        array $content_ids,
        int $section_element_id,
        int $participant_instance_id,
        bool $validate = true
    ): void {
        if ($validate) {
            self::validate_input($content_ids, $section_element_id, $participant_instance_id);
        }

        $participant_instance = participant_instance_model::load_by_id($participant_instance_id);

        linked_review_content_entity::repository()
            ->where('section_element_id', $section_element_id)
            ->where('subject_instance_id', $participant_instance->subject_instance_id)
            ->where_in('content_id', $content_ids)
            ->delete();
    }

    /**
     * Delete this and all corresponding responses.
     */
    public function delete(): void {
        builder::get_db()->transaction(function () {
            linked_review_content_response::repository()
                ->where('linked_review_content_id', $this->id)
                ->delete();

            $this->entity->delete();
        });
    }

    /**
     * Get the content IDs that have already been selected for the subject's section element.
     *
     * @param int $section_element_id
     * @param int $subject_instance_id
     * @return static[]|collection
     */
    public static function get_existing_selected_content(
        int $section_element_id,
        int $subject_instance_id
    ): collection {
        return linked_review_content_entity::repository()
            ->where('section_element_id', $section_element_id)
            ->where('subject_instance_id', $subject_instance_id)
            ->order_by('id')
            ->get()
            ->map_to(static::class);
    }

    /**
     * Set the content. As the types are pluggable the code loading the content item needs to make sure the
     * type code loads the correct item and set it here.
     *
     * @param array $content
     */
    public function set_content(array $content): void {
        // Try to validate it
        if (!isset($content['id']) || $content['id'] != $this->content_id) {
            throw new coding_exception('Content item does not have an id.');
        }

        $this->content = $content;
    }

    /**
     * Returns the content for this item
     *
     * @return mixed
     */
    public function get_content() {
        return $this->content;
    }

    /**
     * Load linked review content by section element id and participant instance id
     *
     * @param int $section_element_id
     * @param int $participant_instance_id
     * @return collection
     * @throws coding_exception
     */
    public static function load_by_section_element_and_participant_instance(int $section_element_id, int $participant_instance_id): collection {
        $subject_instance_id = participant_instance_model::load_by_id($participant_instance_id)->subject_instance_id;
        return linked_review_content_entity::repository()
            ->where('section_element_id', $section_element_id)
            ->where('subject_instance_id', $subject_instance_id)
            ->get();
    }

    /**
     * Validate the values inputted when saving and throw errors if they are invalid.
     *
     * @param array $content array of content ids or content data containing the id
     * @param int $section_element_id
     * @param int $participant_instance_id
     */
    private static function validate_input(
        array $content,
        int $section_element_id,
        int $participant_instance_id
    ): void {
        // Make sure the section element is a linked review element, and that the participant section has the element in it.
        $is_valid_element = section_element_entity::repository()
            ->where('id', $section_element_id)
            ->join([element::TABLE, 'el'], 'element_id', 'id')
            ->where('el.plugin_name', 'linked_review')
            ->join([participant_section_entity::TABLE, 'ps'], 'section_id', 'section_id')
            ->where('ps.participant_instance_id', $participant_instance_id)
            ->exists();
        if (!$is_valid_element) {
            throw new coding_exception(
                "The specified section element with ID {$section_element_id} is not a linked review element " .
                "or the specified participant instance with ID {$participant_instance_id} does not share the same section."
            );
        }

        $section_element = section_element::load_by_id($section_element_id);
        $element = $section_element->get_element();
        if (!self::can_participant_select_content($participant_instance_id, $section_element)) {
            throw new moodle_exception('nopermissions', 'error');
        }

        // Make sure the content IDs actually point to content.
        /** @var linked_review $element_plugin */
        $element_plugin = $element->get_element_plugin();
        $element_plugin->get_content_type($element)::validate_content($content);
    }

    /**
     * Checks if the participant can select content.
     *
     * @param int $participant_instance_id
     * @param section_element $linked_review_section_element
     * @return bool
     */
    private static function can_participant_select_content(
        int $participant_instance_id,
        section_element $linked_review_section_element
    ): bool {
        $element = $linked_review_section_element->element;
        $participant_instance = participant_instance_model::load_by_id($participant_instance_id);

        $can_select_content = self::can_participate_on_section($linked_review_section_element, $participant_instance);

        if (!$can_select_content) {
            return false;
        }

        $element_data = json_decode($element->get_data(), 'true');
        $selection_relationships =  $element_data['selection_relationships'] ?? null;

        if (empty($selection_relationships)) {
            return false;
        }

        return self::participant_instance_belongs_to_logged_in_user($participant_instance)
            && in_array((int)$participant_instance->core_relationship_id, $selection_relationships);
    }

    /**
     * Checks if participant instance belongs to logged in user.
     *
     * @param participant_instance_model $participant_instance
     * @return bool
     */
    private static function participant_instance_belongs_to_logged_in_user(participant_instance_model $participant_instance): bool {
        return (int)$participant_instance->participant_id === user::logged_in()->id
        && (int)$participant_instance->participant_source === participant_source::INTERNAL;
    }

    /**
     * Checks if participant instance can select content
     *
     * @param section_element $linked_review_section_element
     * @param participant_instance_model $participant_instance
     * @return bool
     */
    private static function can_participate_on_section(
        section_element $linked_review_section_element,
        participant_instance_model $participant_instance
    ): bool {
        $section_relationship = $linked_review_section_element
            ->section
            ->get_section_relationships()
            ->find('core_relationship_id', $participant_instance->core_relationship_id);

        return $section_relationship !== null;
    }

    /**
     * Get the section element model.
     *
     * @return section_element
     */
    public function get_section_element(): section_element {
        return section_element::load_by_entity($this->entity->section_element);
    }

    /**
     * Get the subject instance model.
     *
     * @return subject_instance
     */
    public function get_subject_instance(): subject_instance {
        return subject_instance::load_by_entity($this->entity->subject_instance);
    }

}
