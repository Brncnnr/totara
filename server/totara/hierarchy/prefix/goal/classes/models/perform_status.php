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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\models;

use coding_exception;
use core\entity\user;
use core\orm\entity\model;
use core\orm\entity\repository;
use core\orm\query\builder;
use dml_missing_record_exception;
use goal;
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section_relationship;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\subject_instance;
use moodle_exception;
use hierarchy_goal\entity\perform_status as perform_status_entity;
use performelement_linked_review\entity\linked_review_content;
use performelement_linked_review\linked_review;
use hierarchy_goal\entity\scale_value;
use stdClass;
use totara_core\relationship\relationship;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

/**
 * This model represents a single goal status change made in a performance activity
 *
 * @property-read int $id
 * @property-read int $user_id
 * @property-read user $user
 * @property-read int $goal_id
 * @property-read int $goal_personal_id
 * @property-read int $scale_value_id
 * @property-read scale_value $scale_value
 * @property-read int $activity_id
 * @property-read activity $activity
 * @property-read int $subject_instance_id
 * @property-read subject_instance $subject_instance
 * @property-read int $status_changer_user_id
 * @property-read user $status_changer_user
 * @property-read int $status_changer_relationship_id
 * @property-read relationship $status_changer_relationship
 * @property-read int $created_at
 * @property-read participant_instance $participant_instance
 */
abstract class perform_status extends model {

    /**
     * @var perform_status_entity
     */
    protected $entity;

    protected $entity_attribute_whitelist = [
        'id',
        'user_id',
        'user',
        'goal_id',
        'company_goal',
        'goal_personal_id',
        'personal_goal',
        'scale_value_id',
        'scale_value',
        'activity_id',
        'subject_instance_id',
        'status_changer_user_id',
        'status_changer_user',
        'status_changer_relationship_id',
        'created_at',
    ];

    protected $model_accessor_whitelist = [
        'status_changer_relationship',
        'status_changer_role',
        'activity',
        'subject_instance',
        'participant_instance',
    ];

    /**
     * Get the goal type.
     *
     * @return int
     */
    abstract public static function get_goal_type(): int;

    /**
     * Get the table name where the goal assignments are stored for this goal type.
     *
     * @return string
     */
    abstract public static function get_goal_assignment_table(): string;

    /**
     * Get the field name of the perform_status table where the goal_id is stored for this goal type.
     *
     * @return string
     */
    abstract public static function get_goal_id_field(): string;

    /**
     * Is the specified scale ID a valid scale value for the specified goal?
     *
     * @param int $scale_value_id
     * @param int $goal_id
     * @return bool
     */
    abstract protected static function is_scale_value_valid(int $scale_value_id, int $goal_id): bool;

    /**
     * Get the goal ID for the given goal assignment ID.
     *
     * @param int $goal_assignment_id
     * @return int
     * @throws coding_exception
     */
    abstract protected static function get_goal_id_from_assignment_id(int $goal_assignment_id): int;

    /**
     * Create a new change of status for a goal assignment
     *
     * @param int $goal_assignment_id
     * @param int $scale_value_id
     * @param int $participant_instance_id
     * @param int $section_element_id
     * @return static
     */
    public static function create(
        int $goal_assignment_id,
        int $scale_value_id,
        int $participant_instance_id,
        int $section_element_id
    ): self {
        $participant_instance = participant_instance::load_by_id($participant_instance_id);
        $subject_instance = $participant_instance->subject_instance;
        $section_element = section_element::load_by_id($section_element_id);
        $status_changer_relationship = $participant_instance->core_relationship_id;

        if (!static::can_change($participant_instance, $section_element)) {
            throw new moodle_exception('nopermissions', '' ,'', get_string('goal_status_update', 'hierarchy_goal'));
        }

        if (!static::is_goal_assignment_valid($goal_assignment_id, $section_element_id, $subject_instance->id)) {
            throw new coding_exception(
                "The specified goal assignment with ID {$goal_assignment_id} of type " . static::get_goal_type()
                    . " has not been linked to the performance activity"
            );
        }

        $goal_id = static::get_goal_id_from_assignment_id($goal_assignment_id);
        if (static::get_existing_status($goal_id, $subject_instance->id, $status_changer_relationship) !== null) {
            throw new coding_exception(
                "A status has already been saved for subject instance {$subject_instance->id},"
                    . " goal ID {$goal_id} and goal type " . static::get_goal_type()
            );
        }

        if (!static::is_scale_value_valid($scale_value_id, $goal_id)) {
            throw new coding_exception(
                "The specified scale value with ID {$scale_value_id} is not valid for the goal with ID {$goal_id}"
            );
        }

        // Prepare data for perform status update.
        $perform_status = new perform_status_entity();
        $perform_status->user_id = $subject_instance->subject_user_id;
        $perform_status->scale_value_id = $scale_value_id;
        $perform_status->activity_id = $subject_instance->activity->id;
        $perform_status->subject_instance_id = $subject_instance->id;
        $perform_status->status_changer_user_id = $participant_instance->participant_id;
        $perform_status->status_changer_relationship_id = $status_changer_relationship;
        $perform_status->goal_id = null;
        $perform_status->goal_personal_id = null;
        $goal_id_field = static::get_goal_id_field();
        $perform_status->{$goal_id_field} = $goal_id;

        // Prepare data for goal assignment update.
        $goal_assignment = new stdClass();
        $goal_assignment->id = $goal_assignment_id;
        $goal_assignment->scalevalueid = $scale_value_id;

        builder::get_db()->transaction(function () use ($perform_status, $goal_assignment) {
            $perform_status->save();
            $goal_type = static::get_goal_type();

            // update_goal_item() throws an exception when the goal assignment is deleted
            // and returns false when there is another problem.
            try {
                $result = goal::update_goal_item($goal_assignment, $goal_type);
            } catch (dml_missing_record_exception $e) {
                $result = false;
            }
            if (!$result) {
                throw new moodle_exception("Could not update goal status for assignment {$goal_assignment->id}, type {$goal_type}");
            }
        });

        return self::load_by_entity($perform_status);
    }

    /**
     * Get an already existing status for the subject instance, status_changer relationship and goal assignment.
     *
     * @param int $goal_id
     * @param int $subject_instance_id
     * @param int|null $status_changer_relationship_id Optional - if not specified gets the latest status across all relationships.
     * @return static|null
     */
    public static function get_existing_status(
        int $goal_id,
        int $subject_instance_id,
        int $status_changer_relationship_id = null
    ): ?self {
        $perform_status = perform_status_entity::repository()
            ->where(static::get_goal_id_field(), $goal_id)
            ->where('subject_instance_id', $subject_instance_id)
            ->when(
                $status_changer_relationship_id !== null,
                static function (repository $repository) use ($status_changer_relationship_id) {
                    $repository->where('status_changer_relationship_id', $status_changer_relationship_id);
                }
            )
            ->order_by('created_at', 'DESC')
            ->order_by('id', 'DESC')
            ->first();

        return $perform_status ? static::load_by_entity($perform_status) : null;
    }

    /**
     * Check if changing status is enabled and the participant is permitted to make a status change.
     *
     * @param participant_instance $participant_instance
     * @param section_element $section_element
     * @return bool
     */
    public static function can_change(participant_instance $participant_instance, section_element $section_element): bool {
        $user = user::logged_in();

        /** @var linked_review $linked_review_plugin */
        $linked_review_plugin = $section_element->element->element_plugin;
        if (!$linked_review_plugin instanceof linked_review) {
            throw new coding_exception('The section element with ID ' . $section_element->id . ' is not a linked_review element');
        }
        $content_settings = $linked_review_plugin->get_content_settings($section_element->element);

        if (!$content_settings['enable_status_change'] || empty($content_settings['status_change_relationship'])) {
            return false;
        }

        if ((int)$content_settings['status_change_relationship'] !== (int)$participant_instance->core_relationship_id) {
            return false;
        }

        return participant_section::repository()
            ->join([section_relationship::TABLE, 'sr'], 'section_id', 'section_id')
            ->join([participant_instance_entity::TABLE, 'pi'], 'participant_instance_id', 'id')
            ->where('sr.core_relationship_id', $content_settings['status_change_relationship'])
            ->where('participant_instance_id', $participant_instance->id)
            ->where('section_id', $section_element->section_id)
            ->where('pi.participant_source', participant_source::INTERNAL)
            ->where('pi.participant_id', $user->id)
            ->exists();
    }

    /**
     * Has the specified goal assignment been selected as a content item in the activity?
     *
     * @param int $goal_assignment_id
     * @param int $section_element_id
     * @param int $subject_instance_id
     * @return bool
     */
    private static function is_goal_assignment_valid(
        int $goal_assignment_id,
        int $section_element_id,
        int $subject_instance_id
    ): bool {
        return linked_review_content::repository()
            ->join([static::get_goal_assignment_table(), 'ga'], 'content_id', 'id')
            ->where('ga.id', $goal_assignment_id)
            ->where('section_element_id', $section_element_id)
            ->where('subject_instance_id', $subject_instance_id)
            ->exists();
    }

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return perform_status_entity::class;
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
     * Returns the relationship of the status changer.
     *
     * In case the related participant instance got deleted it returns null.
     *
     * @return relationship|null
     */
    public function get_status_changer_relationship(): ?relationship {
        return $this->entity->status_changer_relationship
            ? relationship::load_by_entity($this->entity->status_changer_relationship)
            : null;
    }

    /**
     * Get the status_changer's role with respect to the user viewing the status and the status_changer relationship.
     *
     * @return string
     */
    public function get_status_changer_role(): string {
        $user = user::logged_in();

        if (!is_null($user)
            && (int)$user->id === (int)$this->status_changer_user_id
            && $this->status_changer_relationship->idnumber === constants::RELATIONSHIP_SUBJECT
        ) {
            return get_string('perform_review_goal_status_changer_you', 'hierarchy_goal');
        }

        return $this->status_changer_relationship->name;
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
