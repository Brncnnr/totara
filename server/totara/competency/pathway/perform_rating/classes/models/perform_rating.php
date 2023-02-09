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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_perform_rating
 */

namespace pathway_perform_rating\models;

use coding_exception;
use core\entity\user;
use core\orm\entity\model;
use core\orm\entity\repository;
use mod_perform\constants;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section_relationship;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\subject_instance;
use moodle_exception;
use pathway_perform_rating\entity\perform_rating as perform_rating_entity;
use pathway_perform_rating\event\perform_rating_created;
use performelement_linked_review\entity\linked_review_content;
use performelement_linked_review\linked_review;
use totara_competency\entity\assignment;
use totara_core\relationship\relationship;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * This model represents a single rating given in a performance activity
 *
 * @property-read int $id
 * @property-read int $user_id
 * @property-read user $user
 * @property-read int $competency_id
 * @property-read competency $competency
 * @property-read int $scale_value_id
 * @property-read scale_value $scale_value
 * @property-read int $activity_id
 * @property-read activity $activity
 * @property-read int $subject_instance_id
 * @property-read subject_instance $subject_instance
 * @property-read int $rater_user_id
 * @property-read user $rater_user
 * @property-read int $rater_relationship_id
 * @property-read relationship $rater_relationship
 * @property-read int $created_at
 * @property-read participant_instance $participant_instance
 */
class perform_rating extends model {

    /**
     * @var perform_rating_entity
     */
    protected $entity;

    protected $entity_attribute_whitelist = [
        'id',
        'user_id',
        'user',
        'competency_id',
        'competency',
        'scale_value_id',
        'scale_value',
        'activity_id',
        'subject_instance_id',
        'rater_user_id',
        'rater_user',
        'rater_relationship_id',
        'created_at',
    ];

    protected $model_accessor_whitelist = [
        'rater_relationship',
        'rater_role',
        'activity',
        'subject_instance',
        'participant_instance',
    ];

    /**
     * Create a new rating for a competency
     *
     * @param int $competency_id
     * @param int|null $scale_value_id
     * @param int $participant_instance_id
     * @param int $section_element_id
     * @return self
     */
    public static function create(
        int $competency_id,
        ?int $scale_value_id,
        int $participant_instance_id,
        int $section_element_id
    ): self {
        $participant_instance = participant_instance::load_by_id($participant_instance_id);
        $subject_instance = $participant_instance->subject_instance;
        $section_element = section_element::load_by_id($section_element_id);
        $competency = competency::repository()->find_or_fail($competency_id);
        $rating_relationship = $participant_instance->core_relationship_id;

        if (!static::can_rate($participant_instance, $section_element)) {
            throw new moodle_exception('nopermissions');
        }

        if (!static::is_competency_valid($competency_id, $section_element_id, $subject_instance->id)) {
            throw new coding_exception(
                "The specified competency with ID {$competency_id} has not been linked to the performance activity"
            );
        }

        if (static::get_existing_rating($competency_id, $subject_instance->id, $rating_relationship) !== null) {
            throw new coding_exception(
                "A rating has already been made for subject instance {$subject_instance->id} and competency ID {$competency_id}"
            );
        }

        if (!static::is_scale_value_valid($scale_value_id, $competency_id)) {
            throw new coding_exception(
                "The specified scale valid with ID {$scale_value_id} is not valid for the competency with ID {$competency_id}"
            );
        }

        $rating = new perform_rating_entity();
        $rating->user_id = $subject_instance->subject_user_id;
        $rating->competency_id = $competency->id;
        $rating->scale_value_id = $scale_value_id;
        $rating->activity_id = $subject_instance->activity->id;
        $rating->subject_instance_id = $subject_instance->id;
        $rating->rater_user_id = $participant_instance->participant_id;
        $rating->rater_relationship_id = $rating_relationship;
        $rating->save();

        $rating = self::load_by_entity($rating);

        perform_rating_created::create_from_perform_rating($rating)->trigger();

        return $rating;
    }

    /**
     * Get a rating that already exists for the subject instance, rater relationship and competency.
     *
     * @param int $competency_id
     * @param int $subject_instance_id
     * @param int $rater_relationship_id Optional - if not specified gets the latest rating across all relationships.
     * @return static|null
     */
    public static function get_existing_rating(
        int $competency_id,
        int $subject_instance_id,
        int $rater_relationship_id = null
    ): ?self {
        $rating = perform_rating_entity::repository()
            ->where('competency_id', $competency_id)
            ->where('subject_instance_id', $subject_instance_id)
            ->when($rater_relationship_id !== null, static function (repository $repository) use ($rater_relationship_id) {
                $repository->where('rater_relationship_id', $rater_relationship_id);
            })
            ->order_by('created_at', 'DESC')
            ->order_by('id', 'DESC')
            ->first();

        return $rating ? static::load_by_entity($rating) : null;
    }

    /**
     * Check if rating is enabled and the participant is permitted to make a rating.
     *
     * @param participant_instance $participant_instance
     * @param section_element $section_element
     * @return bool
     */
    public static function can_rate(participant_instance $participant_instance, section_element $section_element): bool {
        /** @var linked_review $linked_review_plugin */
        $linked_review_plugin = $section_element->element->element_plugin;
        if (!$linked_review_plugin instanceof linked_review) {
            throw new coding_exception('The section element with ID ' . $section_element->id . ' is not a linked_review element');
        }
        $content_settings = $linked_review_plugin->get_content_settings($section_element->element);

        if (!$content_settings['enable_rating'] || empty($content_settings['rating_relationship'])) {
            return false;
        }

        if ($content_settings['rating_relationship'] != $participant_instance->core_relationship_id) {
            return false;
        }

        return participant_section::repository()
            ->join([section_relationship::TABLE, 'sr'], 'section_id', 'section_id')
            ->where('sr.core_relationship_id', $content_settings['rating_relationship'])
            ->where('participant_instance_id', $participant_instance->id)
            ->where('section_id', $section_element->section_id)
            ->exists();
    }

    /**
     * Has the specified competency been selected as a content item in the activity?
     *
     * @param int $competency_id
     * @param int $section_element_id
     * @param int $subject_instance_id
     * @return bool
     */
    private static function is_competency_valid(int $competency_id, int $section_element_id, int $subject_instance_id): bool {
        return linked_review_content::repository()
            ->join([assignment::TABLE, 'ass'], 'content_id', 'id')
            ->where('ass.competency_id', $competency_id)
            ->where('section_element_id', $section_element_id)
            ->where('subject_instance_id', $subject_instance_id)
            ->exists();
    }

    /**
     * Is the specified scale valid ID a valid scale value for the specified competency?
     *
     * @param int|null $scale_value_id
     * @param int $competency_id
     * @return bool
     */
    private static function is_scale_value_valid(?int $scale_value_id, int $competency_id): bool {
        if ($scale_value_id === null) {
            // Can always set the rating to 'No rating' - aka null
            return true;
        }

        return scale_value::repository()
            ->join('comp_scale_assignments', 'scaleid', 'scaleid')
            ->join('comp', 'comp_scale_assignments.frameworkid', 'frameworkid')
            ->where('comp.id', $competency_id)
            ->where('id', $scale_value_id)
            ->exists();
    }

    /**
     * Returns the latest rating given in a performance activity for the competency and user
     *
     * @param int $competency_id
     * @param int $user_id
     * @return static|null
     */
    public static function get_latest(int $competency_id, int $user_id): ?self {
        $rating = perform_rating_entity::repository()
            ->where('user_id', $user_id)
            ->where('competency_id', $competency_id)
            ->order_by('created_at', 'DESC')
            ->order_by('id', 'DESC')
            ->first();

        return $rating ? self::load_by_entity($rating) : null;
    }

    /**
     * Delete this rating.
     */
    public function delete(): void {
        $this->entity->delete();
    }

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return perform_rating_entity::class;
    }

    /**
     * Returns the activity.
     *
     * @return activity|null
     */
    public function get_activity(): ?activity {
        return $this->entity->activity ?
            activity::load_by_entity($this->entity->activity)
            : null;
    }

    /**
     * Returns the relationship of the rater.
     *
     * In case the related participant instance got deleted it returns null.
     *
     * @return relationship|null
     */
    public function get_rater_relationship(): ?relationship {
        return $this->entity->rater_relationship
            ? relationship::load_by_entity($this->entity->rater_relationship)
            : null;
    }

    /**
     * Get the rater's role with respect to the user viewing the rating and the rater relationship.
     *
     * @return string
     */
    public function get_rater_role(): string {
        $user = user::logged_in();

        if (!is_null($user)
            && (int)$user->id === (int)$this->rater_user_id
            && $this->rater_relationship->idnumber === constants::RELATIONSHIP_SUBJECT
        ) {
            return get_string('your_rating', 'pathway_perform_rating');
        }

        return $this->rater_relationship->name;
    }

    /**
     * Returns the subject instance this rating was given.
     *
     * In case the subject instance got deleted it returns null
     *
     * @return subject_instance|null
     */
    public function get_subject_instance(): ?subject_instance {
        return $this->entity->subject_instance
            ? subject_instance::load_by_entity($this->entity->subject_instance)
            : null;
    }

    /**
     * Returns the participant instance this rating was given.
     *
     * In case the participant instance got deleted it returns null
     *
     * @return participant_instance|null
     */
    public function get_participant_instance(): ?participant_instance {
        return $this->entity->participant_instance
            ? participant_instance::load_by_entity($this->entity->participant_instance)
            : null;
    }

}
