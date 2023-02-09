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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\model;

use coding_exception;
use contentmarketplace_linkedin\entity\learning_object as learning_object_entity;
use contentmarketplace_linkedin\entity\user_progress as user_progress_entity;
use contentmarketplace_linkedin\event\user_progress_updated;
use core\orm\entity\model;
use core\orm\query\builder;

/**
 * @property-read int $id
 * @property int $user_id
 * @property string $learning_object_urn
 * @property int $progress
 * @property int $time_created
 * @property int $time_updated
 * @property int $time_completed
 *
 * @property-read bool $started
 * @property-read bool $completed
 * @property-read learning_object|null $learning_object
 *
 * @package contentmarketplace_linkedin\model
 */
class user_progress extends model {

    /**
     * @var user_progress_entity
     */
    protected $entity;

    /**
     * Progress is recorded as a percentage, so 100 is the maximum value and means the course is complete.
     */
    public const PROGRESS_COMPLETE = 100;

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return user_progress_entity::class;
    }

    protected $entity_attribute_whitelist = [
        'id',
        'user_id',
        'learning_object_urn',
        'progress',
        'time_created',
        'time_updated',
        'time_completed',
    ];

    protected $model_accessor_whitelist = [
        'started',
        'completed',
        'learning_object',
    ];

    /**
     * Loads an existing user completion for a given user & LinkedIn Learning course URN.
     *
     * @param int $user_id
     * @param string $learning_object_urn
     * @return static|null
     */
    public static function load_for_user_and_learning_object_urn(int $user_id, string $learning_object_urn): ?self {
        $entity = user_progress_entity::repository()
            ->where('user_id', $user_id)
            ->where('learning_object_urn', $learning_object_urn)
            ->one();

        return $entity ? static::load_by_entity($entity) : null;
    }

    /**
     * Loads an existing user completion for a given user & LinkedIn Learning course ID.
     *
     * @param int $user_id
     * @param int $learning_object_id
     * @return static|null
     */
    public static function load_for_user_and_learning_object_id(int $user_id, int $learning_object_id): ?self {
        $entity = user_progress_entity::repository()
            ->join([learning_object_entity::TABLE, 'learning_object'], 'learning_object_urn', 'urn')
            ->where('user_id', $user_id)
            ->where('learning_object.id', $learning_object_id)
            ->one();

        return $entity ? static::load_by_entity($entity) : null;
    }

    /**
     * Set the progress for the specified user and LinkedIn Learning object URN
     *
     * @param int $user_id
     * @param string $learning_object_urn
     * @param int $progress
     * @param int $timestamp
     * @return static
     */
    public static function set_progress(int $user_id, string $learning_object_urn, int $progress, int $timestamp): self {
        return builder::get_db()->transaction(function () use ($user_id, $learning_object_urn, $progress, $timestamp) {
            $model = static::load_for_user_and_learning_object_urn($user_id, $learning_object_urn);

            if ($model === null) {
                $entity = new user_progress_entity();
                $entity->user_id = $user_id;
                $entity->learning_object_urn = $learning_object_urn;
                $entity->time_created = $timestamp;

                // We first set the following to be empty,
                // as they will be updated properly when the update_progress() method is called.
                $entity->progress = -1;
                $entity->time_updated = -1;
                $entity->time_completed = null;

                $entity->save();

                $model = static::load_by_entity($entity);
            }

            return $model->update_progress($progress, $timestamp);
        });
    }

    /**
     * Set the specified user and LinkedIn Learning object URN as completed
     *
     * @param int $user_id
     * @param string $learning_object_urn
     * @param int $timestamp
     * @return static
     */
    public static function set_completed(int $user_id, string $learning_object_urn, int $timestamp): self {
        return self::set_progress($user_id, $learning_object_urn, self::PROGRESS_COMPLETE, $timestamp);
    }

    /**
     * Update the completion progress.
     *
     * @param int $progress
     * @param int $timestamp
     * @return $this
     */
    public function update_progress(int $progress, int $timestamp): self {
        if ($this->time_updated > $timestamp) {
            return $this;
        }

        if ($progress < 0 || $progress > self::PROGRESS_COMPLETE) {
            throw new coding_exception("Invalid progress percentage of $progress was specified - must be in a range of 0-100");
        }

        $this->entity->progress = $progress;
        $this->entity->time_updated = $timestamp;
        if ($progress >= self::PROGRESS_COMPLETE) {
            $this->entity->time_completed = $timestamp;
        }

        return builder::get_db()->transaction(function () {
            $this->entity->save();

            $event = user_progress_updated::create_from_user_progress($this);
            $event->trigger();

            return $this;
        });
    }

    /**
     * Has the user started this learning item?
     *
     * @return bool
     */
    public function get_started(): bool {
        return $this->progress > 0;
    }

    /**
     * Has the user completed this learning item?
     *
     * @return bool
     */
    public function get_completed(): bool {
        return $this->progress >= self::PROGRESS_COMPLETE;
    }

    /**
     * Get the associated learning object model.
     *
     * Note: There is an edge case when xAPI progress statements have been received, but the learning object sync hasn't happened yet,
     * so it is possible for the associated learning object record to be null.
     *
     * @return learning_object|null
     */
    public function get_learning_object(): ?learning_object {
        return $this->entity->learning_object ? learning_object::load_by_entity($this->entity->learning_object) : null;
    }

}
