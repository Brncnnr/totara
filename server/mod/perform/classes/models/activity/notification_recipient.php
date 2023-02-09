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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_perform\entity\activity\notification_recipient as notification_recipient_entity;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\entity\activity\section_relationship as section_relationship_entity;
use mod_perform\notification\factory;
use mod_perform\notification\recipient;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;

/**
 * Represents a notification setting recipient.
 *
 * @property-read integer $id
 * @property-read boolean $active is active?
 * @property-read integer $core_relationship_id
 * @property-read integer $notification_id
 * @property-read relationship $relationship
 * @property-read notification $notification
 */
class notification_recipient extends model {

    /**
     * @var notification_recipient_entity
     */
    protected $entity;

    protected $entity_attribute_whitelist = [
        'id',
        'active',
        'core_relationship_id',
        'notification_id',
    ];

    protected $model_accessor_whitelist = [
        'relationship',
        'notification',
        'relationship_id',
        'name',
    ];

    /**
     * @return relationship
     */
    public function get_relationship(): relationship {
        return relationship::load_by_entity($this->entity->relationship);
    }

    /**
     * @return notification
     */
    public function get_notification(): notification {
        return notification::load_by_entity($this->entity->notification);
    }

    /**
     * Return the localised string of this recipient.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->relationship->name;
    }

    /**
     * Get the ID of the relationship.
     *
     * @return string
     */
    public function get_relationship_id(): string {
        return $this->core_relationship_id;
    }

    /**
     * Create a new notification recipient.
     *
     * @param notification $parent
     * @param relationship $relationship
     * @param boolean $active If not specified, defaults to the default value.
     * @return self
     */
    public static function create(notification $parent, relationship $relationship, bool $active = null): self {
        $entity = new notification_recipient_entity();
        $entity->notification_id = $parent->id;
        $entity->core_relationship_id = $relationship->get_id();
        $entity->active = $active ?? self::is_active_for_relationship_by_default($parent->class_key, $relationship);
        $entity->save();

        return new self($entity);
    }

    /**
     * Is this notification recipient active for the given relationship by default?
     *
     * @param string $class_key
     * @param relationship $relationship
     * @return bool
     */
    private static function is_active_for_relationship_by_default(string $class_key, relationship $relationship): bool {
        $active_for_recipients = factory::create_loader()->get_default_active_recipients_of($class_key);

        if ($active_for_recipients === null) {
            return false;
        }

        return recipient::is_available($active_for_recipients, $relationship);
    }

    /**
     * Toggle the state for this notification recipient.
     *
     * @param boolean $active
     * @return self
     */
    public function toggle(bool $active): self {
        $this->entity->active = $active;
        $this->entity->save();
        return $this;
    }

    /**
     * Activate this notification recipient.
     *
     * @param boolean $active Deprecated & Unused.
     * @return static
     */
    public function activate(bool $active = true): self {
        if (!empty(func_get_args())) {
            debugging(
                'The $active argument for the function \mod_perform\models\activity\notification_recipient::activate()' .
                ' is deprecated, please use toggle(), activate() or deactivate() instead.',
                DEBUG_DEVELOPER
            );
        }
        return $this->toggle($active ?? true);
    }

    /**
     * Deactivate this notification recipient.
     *
     * @return static
     */
    public function deactivate(): self {
        return $this->toggle(false);
    }

    /**
     * Load all notification recipients.
     *
     * @param notification $parent
     * @param boolean $active_only get only active recipients
     * @return collection|notification_recipient[]
     */
    public static function load_by_notification(notification $parent, bool $active_only = false): collection {
        $builder = builder::table(relationship_entity::TABLE)
            ->as('r')
            ->select('r.id as core_relationship_id')
            ->group_by(['r.id', 'r.sort_order'])
            ->order_by('r.sort_order');

        if (!factory::create_loader()->are_all_possible_recipients($parent->class_key)) {
            $builder
                ->join([section_relationship_entity::TABLE, 'sr'], 'id', 'core_relationship_id')
                ->join([section_entity::TABLE, 's'], 'sr.section_id', 'id')
                ->where('s.activity_id', $parent->activity_id);
        }

        recipient::where_available(factory::create_loader()->get_possible_recipients_of($parent->class_key), $builder);
        $parent->recipients_builder($builder, $active_only);

        return $builder
            ->map_to(notification_recipient_entity::class)
            ->get()
            ->map_to(static::class);
    }

    /**
     * Reload the internal bookkeeping.
     *
     * @return static
     */
    public function refresh(): self {
        $this->entity->refresh();
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return notification_recipient_entity::class;
    }



    /*
     * Deprecated Methods
     */

    /**
     * @param notification_recipient_entity|object $entity
     */
    public function __construct($entity) {
        if (!$entity instanceof notification_recipient_entity) {
            debugging('A notification_recipient entity must be specified to notification_recipient::__construct', DEBUG_DEVELOPER);
            $entity = new notification_recipient_entity($entity);
        }
        parent::__construct($entity);
    }

    /**
     * @return integer
     * @deprecated since Totara 13.2
     */
    public function get_notification_id(): int {
        debugging(
            '\mod_perform\models\activity\notification_recipient::get_notification_id()' .
            ' is deprecated and should no longer be used, please use $notification_recipient->notification_id directly.',
            DEBUG_DEVELOPER
        );
        return $this->notification_id;
    }

    /**
     * @return boolean
     * @deprecated since Totara 13.2
     */
    public function get_active(): bool {
        debugging(
            '\mod_perform\models\activity\notification_recipient::get_active()' .
            ' is deprecated and should no longer be used, please use $notification_recipient->active directly.',
            DEBUG_DEVELOPER
        );
        return $this->active;
    }

}
