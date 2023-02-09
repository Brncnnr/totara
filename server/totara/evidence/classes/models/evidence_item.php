<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_evidence
 */

namespace totara_evidence\models;

use coding_exception;
use core\entity\user;
use core\orm\collection;
use core\orm\query\builder;
use totara_evidence\hook\evidence_item_usage;
use totara_evidence\customfield_area;
use totara_evidence\entity;
use totara_evidence\event;
use totara_evidence\models\helpers\evidence_item_capability_helper;

/**
 * This class represents a piece of evidence uploaded by a user and
 * abstracts common functions associated with evidence items,
 * and prevents evidence items from having an invalid state.
 *
 * @package totara_evidence\models
 *
 * @property-read int $typeid Evidence type ID
 * @property-read evidence_type $type Evidence type model
 * @property-read int $user_id User ID for who the evidence is for
 * @property-read user $user User for who the evidence is for
 * @property-read int $status Status
 */
class evidence_item extends evidence {

    public const STATUS_ACTIVE = 1;

    protected $entity_attribute_whitelist = [
        'id',
        'typeid',
        'user_id',
        'user',
        'name',
        'status',
        'created_by',
        'created_by_user',
        'created_at',
        'modified_by',
        'modified_by_user',
        'modified_at',
        'imported',
    ];

    protected $model_accessor_whitelist = [
        'display_name',
        'display_created_at',
        'display_modified_at',
        'creator',
        'modifier',
        'type',
        'data',
    ];

    protected static function get_entity_class(): string {
        return entity\evidence_item::class;
    }

    /**
     * Is this evidence item in use elsewhere?
     *
     * @return bool
     */
    public function in_use(): bool {
        $used = $this->entity->plan_relations()->exists();
        if (!$used) {
            $hook = new evidence_item_usage('totara_evidence', $this->entity->id);
            $hook->execute();
            $used = $hook->count() > 0;
        }
        return $used;
    }

    /**
     * Check if the current user is allowed to modify this evidence item
     *
     * @param bool $require Require that this evidence can be modified
     * @return bool
     */
    public function can_modify(bool $require = false): bool {
        if (!$this->entity->exists()) {
            if ($require) {
                throw new coding_exception('Evidence item no longer exists');
            }
            return false;
        }

        $has_capability = evidence_item_capability_helper::for_item($this)->can_modify($require);
        if (!$has_capability) {
            return false;
        }

        if ($this->in_use()) {
            if ($require) {
                throw new coding_exception("Evidence item with ID {$this->id} is currently in use elsewhere");
            }
            return false;
        }

        return true;
    }

    /**
     * Create a new evidence item
     *
     * @param evidence_type $type
     * @param int|user $for_user
     * @param object|null $customfield_data
     * @param string|null $name
     *
     * @return evidence_item
     */
    public static function create(
        evidence_type $type,
        $for_user,
        object $customfield_data = null,
        string $name = null,
        int $imported = 0
    ): self {
        if (!is_numeric($for_user)) {
            $for_user = $for_user->id;
        }
        evidence_item_capability_helper::for_user($for_user)->can_create(true);

        $entity = new entity\evidence_item();
        $entity->name = $name;
        $entity->typeid = $type->get_id();
        $entity->user_id = $for_user;
        $entity->created_by = user::logged_in()->id;
        $entity->modified_by = user::logged_in()->id;
        $entity->status = self::STATUS_ACTIVE;
        $entity->imported = $imported;

        if ($name === null || empty(trim($name))) {
            $entity->name = self::get_default_name(new user($for_user), $type);
        }

        $entity->save();

        if ($customfield_data) {
            $customfield_data->id = $entity->id;
            customfield_area\field_helper::save_field_data($customfield_data);
        }

        $event = event\evidence_item_created::create_from_item($entity);
        $event->trigger();

        return new static($entity);
    }

    /**
     * Update an existing evidence item
     *
     * @param object|null $customfield_data
     * @param string|null $name
     *
     * @return evidence_item
     */
    public function update(object $customfield_data = null, string $name = null): self {
        $this->can_modify(true);
        if ($customfield_data === null && $name === null) {
            throw new coding_exception("Must specify an attribute to change for evidence item with ID {$this->id}");
        }

        if (is_string($name)) {
            $this->entity->name = $name;
        }
        $this->entity->modified_by = user::logged_in()->id;
        $this->entity->save();
        $this->entity->refresh();

        if ($customfield_data) {
            $customfield_data->id = $this->entity->id;
            customfield_area\field_helper::save_field_data($customfield_data);
        }

        $event = event\evidence_item_updated::create_from_item($this->entity);
        $event->trigger();

        return $this;
    }

    /**
     * Delete this evidence item and all it's associated field data
     */
    public function delete(): void {
        $this->can_modify(true);

        $event = builder::get_db()->transaction(function () {
            $event = event\evidence_item_deleted::create_from_item(new entity\evidence_item($this->entity->id));

            foreach ($this->entity->data as $data) {
                /** @var entity\evidence_field_data $data */
                customfield_area\field_helper::get_field_instance($data)->delete();
            }
            $this->entity->delete();

            return $event;
        });

        $event->trigger();
    }

    /**
     * Generate a name based upon the user's full name and the evidence type name.
     * Used for when no evidence name is specified.
     *
     * @param user $user
     * @param evidence_type $type
     * @return string
     */
    public static function get_default_name(user $user, evidence_type $type): string {
        return get_string('evidence_name_default', 'totara_evidence', [
            'user' => $user->fullname,
            'type' => $type->get_display_name()
        ]);
    }

    /**
     * The type of this evidence
     *
     * @return evidence_type
     */
    public function get_type(): evidence_type {
        return evidence_type::load_by_entity($this->entity->type);
    }

    /**
     * Get the custom field data for this evidence item
     *
     * @return collection
     */
    public function get_customfield_data(): collection {
        return $this->entity->data;
    }

    /**
     * Get entity data plus model data
     *
     * @return array
     */
    public function get_data(): array {
        return array_merge(parent::get_data(), [
            'data' => $this->get_customfield_data()->to_array(),
            'is_for_current_user' => user::logged_in() == $this->user_id,
            'type' => $this->type,
        ]);
    }

}
