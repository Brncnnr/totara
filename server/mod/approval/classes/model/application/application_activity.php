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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application;

use core\entity\user as user_entity;
use core\orm\entity\model;
use mod_approval\entity\application\application_activity as application_activity_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\activity\activity;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;

/**
 * Approval workflow application activity model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read int $application_id Parent application ID
 * @property-read int $workflow_stage_id Related workflow_stage ID
 * @property-read int|null $workflow_stage_approval_level_id Related workflow_stage_approval_level ID
 * @property-read int|null $user_id Related user/actor ID
 * @property-read int $timestamp Activity timestamp
 * @property-read int $activity_type Application activity type (from activity::get_type)
 * @property-read string $activity_info JSON blob of information about the event
 * @property-read array $activity_info_parsed Decoded JSON blob
 *
 * Relationships:
 * @property-read application $application Parent application
 * @property-read workflow_stage $stage Related workflow_stage
 * @property-read workflow_stage_approval_level|null $approval_level Related workflow_stage_approval_level
 * @property-read user_entity|null $user Related user entity
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(application_activity_entity $entity)
 * @package mod_approval\models\application
 */
final class application_activity extends model {

    use model_trait;

    /** @var application_activity_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'application_id',
        'workflow_stage_id',
        'workflow_stage_approval_level_id',
        'user_id',
        'timestamp',
        'activity_type',
        'activity_info',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'activity_info_parsed',
        'application',
        'stage',
        'approval_level',
        'user'
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return application_activity_entity::class;
    }

    /**
     * Get the parent application
     *
     * @return application
     */
    public function get_application(): application {
        return application::load_by_entity($this->entity->application);
    }

    /**
     * Get the workflow stage where this activity occurred
     *
     * @return workflow_stage
     */
    public function get_stage(): workflow_stage {
        return workflow_stage::load_by_entity($this->entity->workflow_stage);
    }

    /**
     * Get the workflow stage approval level where this activity occurred
     *
     * @return workflow_stage_approval_level|null
     */
    public function get_approval_level(): ?workflow_stage_approval_level {
        if (is_null($this->entity->workflow_stage_approval_level)) {
            return null;
        } else {
            return workflow_stage_approval_level::load_by_entity($this->entity->workflow_stage_approval_level);
        }
    }

    /**
     * Get the applicator or approver.
     *
     * @return user_entity|null
     */
    public function get_user(): ?user_entity {
        return $this->entity->user;
    }

    /**
     * Get parsed JSON blob of activity_info.
     *
     * @return array
     */
    public function get_activity_info_parsed(): array {
        return json_decode($this->entity->activity_info, true, 32, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
    }

    /**
     * Create an application activity.
     *
     * @param application $application Parent application
     * @param int|null $actor_id ID of the user who caused the activity, or null if it was a system event (e.g. date reached)
     * @param string|activity $activity_class The application activity class name
     * @param array $activity_info Additional information
     * @return self
     */
    public static function create(
        application $application,
        ?int $actor_id,
        string $activity_class,
        array $activity_info = []
    ): self {
        $current_state = $application->get_current_state();

        if (!is_subclass_of($activity_class, activity::class)) {
            throw new model_exception('Invalid activity type');
        }

        // Validate activity_type.
        if (!$activity_class::is_valid_activity_info($activity_info)) {
            throw new model_exception('Invalid activity info');
        }
        $entity = new application_activity_entity();
        $entity->application_id = $application->id;
        $entity->workflow_stage_id = $current_state->get_stage_id();
        $entity->workflow_stage_approval_level_id = $current_state->get_approval_level_id();
        $entity->user_id = $actor_id;
        $entity->activity_type = $activity_class::get_type();
        $entity->activity_info = json_encode(
            $activity_info,
            JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );
        $entity->save();

        $activity_class::trigger_event($application, $actor_id, $activity_info);

        return self::load_by_entity($entity);
    }

    /**
     * Delete the record.
     *
     * @return self
     */
    public function delete(): self {
        $this->entity->delete();
        return $this;
    }
}
