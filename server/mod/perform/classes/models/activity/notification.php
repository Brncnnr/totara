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
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\notification as notification_entity;
use mod_perform\entity\activity\notification_recipient as notification_recipient_entity;
use mod_perform\models\activity\helpers\relationship_helper;
use mod_perform\models\activity\notification as notification_model;
use mod_perform\notification\factory;

/**
 * Represents a single performance notification setting.
 *
 * @property-read integer $id ID
 * @property-read string $name
 * @property-read string $class_key
 * @property-read int $activity_id
 * @property-read boolean $active is active?
 * @property-read string|null $trigger_label
 * @property-read integer $trigger_type
 * @property-read integer $last_run_at
 *
 * @property-read activity $activity
 * @property-read collection|notification_recipient[] $recipients
 * @property-read integer[] $triggers
 */
class notification extends model {

    /**
     * @var notification_entity
     */
    protected $entity;

    protected $entity_attribute_whitelist = [
        'id',
        'activity_id',
        'class_key',
        'active',
    ];

    protected $model_accessor_whitelist = [
        'name',
        'activity',
        'last_run_at',
        'recipients',
        'trigger_label',
        'trigger_type',
        'triggers',
    ];

    /**
     * Create a new notification setting.
     *
     * @param activity|activity_entity|int $parent_activity
     * @param string $class_key
     * @param boolean $active If not specified, defaults to the default value.
     * @return self
     */
    public static function create($parent_activity, string $class_key, bool $active = null): self {
        if (is_object($parent_activity)) {
            $parent_activity = $parent_activity->id;
        }

        $broker = factory::create_broker($class_key);
        $entity = new notification_entity();
        $entity->activity_id = $parent_activity;
        $entity->class_key = $class_key;
        $entity->active = $active ?? factory::create_loader()->is_active_by_default($class_key);
        $entity->triggers = json_encode($broker->get_default_triggers(), JSON_UNESCAPED_SLASHES);
        $entity->save();
        return new self($entity);
    }

    /**
     * Create the default set of notifications for an activity.
     *
     * @param $parent_activity
     * @return collection|notification_model[]
     */
    public static function create_all_for_activity($parent_activity): collection {
        $relationships = relationship_helper::get_supported_perform_relationships();
        $class_keys = factory::create_loader()->get_class_keys();

        return collection::new($class_keys)->map(static function (string $class_key) use ($parent_activity, $relationships) {
            $notification = notification_model::create($parent_activity, $class_key);

            foreach ($relationships as $relationship) {
                notification_recipient::create($notification, $relationship);
            }

            return $notification;
        });
    }

    /**
     * Retrieves notifications by their parent activity.
     *
     * @param activity $parent parent activity
     * @return collection|static[] retrieved notifications
     */
    public static function load_all_by_activity(activity $parent): collection {
        $class_keys_order = array_flip(factory::create_loader()->get_class_keys());

        return notification_entity::repository()
            ->where('activity_id', $parent->get_id())
            ->get()
            ->map_to(static::class)
            ->sort(static function (self $a, self $b) use ($class_keys_order) {
                // Order the notifications by how they are defined in mod/perform/db/notifications.php to be consistent.
                return $class_keys_order[$a->class_key] <=> $class_keys_order[$b->class_key];
            });
    }

    /**
     * Retrieves notifications by their parent activity.
     *
     * @param activity $parent parent activity
     * @param string $class_key
     * @return static
     */
    public static function load_by_activity_and_class_key(activity $parent, string $class_key): self {
        $entity = notification_entity::repository()
            ->where('activity_id', $parent->id)
            ->where('class_key', $class_key)
            ->one(true);

        return new static($entity);
    }

    /**
     * Get all recipients.
     *
     * @param boolean $active_only get only active recipients
     * @return collection|notification_recipient[] Notification recipients, keyed by ID
     */
    public function get_recipients(bool $active_only = false): collection {
        return notification_recipient::load_by_notification($this, $active_only);
    }

    /**
     * Modify the builder to obtain associated recipients.
     * *Do not call this function directly!!*
     *
     * @param builder $builder a partially set up builder: see notification_recipient::load_by_notification()
     *                         * {perform_section} s
     *                         * {perform_section_relationship} sr
     *                         * {totara_core_relationship} r
     * @param boolean $active_only get only active recipients
     */
    public function recipients_builder(builder $builder, bool $active_only = false): void {
        $builder
            ->left_join([notification_recipient_entity::TABLE, 'nr'], function (builder $joining) {
                $joining->where_field('r.id', 'nr.core_relationship_id')
                    ->where('nr.notification_id', '=', $this->entity->id);
            })
            ->add_select(['nr.id', 'nr.active'])
            ->group_by(['nr.id', 'nr.active', 'nr.notification_id']);
        if ($active_only) {
            $builder->where('nr.active', '<>', 0);
        }
    }

    /**
     * Return the parent activity.
     *
     * @return activity
     */
    public function get_activity(): activity {
        return activity::load_by_id($this->entity->activity_id);
    }

    /**
     * Return the array of trigger values.
     *
     * @return integer[]
     */
    public function get_triggers(): array {
        $triggers = json_decode($this->entity->triggers);

        if (!is_array($triggers)) {
            return [];
        }

        return factory::create_trigger($this)->translate_outgoing($triggers);
    }

    /**
     * Return the last run time.
     *
     * @return integer
     */
    public function get_last_run_at(): int {
        return $this->entity->last_run_at ?? 0;
    }

    /**
     * Toggle the state for this notification recipient.
     *
     * @param boolean $active
     * @return static
     */
    public function toggle(bool $active): self {
        $this->entity->active = $active;
        $this->entity->save();
        return $this;
    }

    /**
     * Activate this notification.
     *
     * @param boolean $active Deprecated & Unused.
     * @return static
     */
    public function activate(bool $active = true): self {
        if (!empty(func_get_args())) {
            debugging(
                'The $active argument for the function \mod_perform\models\activity\notification::activate()' .
                ' is deprecated, please use toggle(), activate() or deactivate() instead.',
                DEBUG_DEVELOPER
            );
        }
        return $this->toggle($active ?? true);
    }

    /**
     * Deactivate this notification.
     *
     * @return static
     */
    public function deactivate(): self {
        return $this->toggle(false);
    }

    /**
     * Update event trigger values.
     *
     * @param array $triggers
     * @return static
     */
    public function set_triggers(array $triggers): self {
        $values = factory::create_trigger($this)->translate_incoming($triggers);
        $this->entity->triggers = json_encode($values, JSON_UNESCAPED_SLASHES);
        $this->entity->save();
        return $this;
    }

    /**
     * Update the last run time.
     *
     * @param integer $time
     * @return static
     */
    public function set_last_run_at(int $time): self {
        $this->entity->last_run_at = $time;
        $this->entity->save();
        return $this;
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
     * Return the localised name of this notification setting.
     *
     * @return string
     */
    public function get_name(): string {
        return factory::create_loader()->get_name_of($this->class_key);
    }

    /**
     * Return the trigger type.
     *
     * @return integer one of trigger_type constants
     */
    public function get_trigger_type(): int {
        return factory::create_loader()->get_trigger_type_of($this->class_key);
    }

    /**
     * Return the trigger label text.
     *
     * @return string|null localised label text or null if triggers are not supported
     */
    public function get_trigger_label(): ?string {
        return factory::create_loader()->get_trigger_label_of($this->class_key);
    }

    /**
     * Return true if the notification can provide trigger events.
     *
     * @return boolean
     */
    public function can_be_triggered(): bool {
        return factory::create_loader()->support_triggers($this->class_key);
    }

    /**
     * Get trigger values in seconds.
     *
     * @return array
     */
    public function get_triggers_in_seconds(): array {
        return array_map(function ($trigger) {
            return $trigger * DAYSECS;
        }, $this->get_triggers());
    }

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return notification_entity::class;
    }


    /*
     * Deprecated Methods
     */

    /**
     * @param notification_entity|object $entity
     */
    public function __construct($entity) {
        if (!$entity instanceof notification_entity) {
            debugging('A notification entity must be specified to notification::__construct()', DEBUG_DEVELOPER);
            $entity = new notification_entity($entity);
        }
        parent::__construct($entity);
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_class_key(): string {
        debugging(
            '\mod_perform\models\activity\notification::get_class_key() is deprecated and should no longer be used,' .
            ' please use $notification->class_key directly.',
            DEBUG_DEVELOPER
        );
        return $this->class_key;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_active(): string {
        debugging(
            '\mod_perform\models\activity\notification::get_active() is deprecated and should no longer be used,' .
            ' please use $notification->active directly.',
            DEBUG_DEVELOPER
        );
        return $this->active;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function delete(): self {
        debugging(
            '\mod_perform\models\activity\notification::delete() is deprecated and should no longer be used, '
            . 'do not delete notification records manually.',
            DEBUG_DEVELOPER
        );
        return $this;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function exists(): bool {
        debugging(
            '\mod_perform\models\activity\notification::get_active() is deprecated and should no longer be used,'
            . ' as the return value will always be true.',
            DEBUG_DEVELOPER
        );
        return $this->entity->exists();
    }

}
