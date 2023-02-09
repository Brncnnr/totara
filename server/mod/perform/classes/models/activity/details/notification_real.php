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

namespace mod_perform\models\activity\details;

use coding_exception;
use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_perform\entity\activity\notification as notification_entity;
use mod_perform\entity\activity\notification_recipient as notification_recipient_entity;
use mod_perform\models\activity\activity;
use mod_perform\notification\factory;

/**
 * @deprecated since Totara 13.2
 */
class notification_real extends model implements notification_interface {

    /**
     * @deprecated since Totara 13.2
     */
    public function get_activity(): activity {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.'
        );
        return activity::load_by_id($this->entity->activity_id);
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_class_key(): string {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        return $this->entity->class_key;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_active(): bool {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        return $this->entity->active;
    }

    /**
     * @deprecated since Totara 13.2
     * @param builder $builder
     * @param bool $active_only
     */
    public function recipients_builder(builder $builder, bool $active_only = false): void {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $builder
            ->left_join([notification_recipient_entity::TABLE, 'nr'], function (builder $joining) {
                $joining->where_field('r.id', 'nr.core_relationship_id')
                    ->where('nr.notification_id', '=', $this->entity->id);
            })
            ->add_select(['nr.id as recipient_id', 'nr.active as active'])
            ->group_by(['nr.id', 'nr.active', 'nr.notification_id']);
        if ($active_only) {
            $builder->where('nr.active', '<>', 0);
        }
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_triggers(): array {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $triggers = json_decode($this->entity->triggers);
        if (!is_array($triggers)) {
            $triggers = [];
        }
        return array_map(function ($value) {
            return (int)$value;
        }, $triggers);
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function get_last_run_at(): int {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        return $this->entity->last_run_at ?? 0;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function exists(): bool {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        return $this->entity->exists();
    }

    /**
     * @deprecated since Totara 13.2
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name) {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $methodname = 'get_'.$name;
        if (method_exists($this, $methodname)) {
            return $this->{$methodname}();
        }
        return parent::__get($name);
    }

    /**
     * @deprecated since Totara 13.2
     * @param string $name
     * @return bool
     */
    public function has_attribute(string $name): bool {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $methodname = 'get_'.$name;
        if (method_exists($this, $methodname)) {
            return true;
        }
        return parent::has_attribute($name);
    }

    /**
     * @deprecated since Totara 13.2
     * @param activity $parent
     * @return collection
     */
    public static function load_by_activity(activity $parent): collection {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        return notification_entity::repository()
            ->where('activity_id', $parent->get_id())
            ->get()
            ->map_to(function ($item) {
                return new self($item);
            });
    }

    /**
     * @deprecated since Totara 13.2
     * @param activity $parent
     * @param string $class_key
     * @param bool $strict
     * @return notification_real|null
     */
    public static function load_by_activity_and_class_key(activity $parent, string $class_key, bool $strict = false): ?self {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $entity = notification_entity::repository()
            ->where('activity_id', $parent->get_id())
            ->where('class_key', $class_key)
            ->one($strict);

        if (!$entity) {
            return null;
        }
        return new self($entity);
    }

    /**
     * @deprecated since Totara 13.2
     * @param int $notification_id
     * @return notification_real
     */
    public static function load_by_id(int $notification_id): self {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        /** @var notification_entity $entity */
        $entity = notification_entity::repository()->find_or_fail($notification_id);
        return new self($entity);
    }

    /**
     * @deprecated since Totara 13.2
     * @param activity $parent
     * @param string $class_key
     * @param bool $active
     * @return notification_real
     */
    public static function create(activity $parent, string $class_key, bool $active): self {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $broker = factory::create_broker($class_key);
        $entity = new notification_entity();
        $entity->activity_id = $parent->get_id();
        $entity->class_key = $class_key;
        $entity->active = $active;
        $entity->triggers = json_encode($broker->get_default_triggers(), JSON_UNESCAPED_SLASHES);
        $entity->save();
        $inst = new self($entity);
        return $inst;
    }

    /**
     * @deprecated since Totara 13.2
     * @param bool $active
     * @return notification_interface
     */
    public function activate(bool $active = true): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        if (!$this->entity->exists()) {
            throw new coding_exception('activate() is called after the entity is deleted');
        }
        $this->entity->active = $active;
        $this->entity->save();
        return $this;
    }

    /**
     * @deprecated since Totara 13.2
     * @param array $values
     * @return notification_interface
     */
    public function set_triggers(array $values): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $this->entity->triggers = json_encode($values, JSON_UNESCAPED_SLASHES);
        $this->entity->save();
        return $this;
    }

    /**
     * @deprecated since Totara 13.2
     * @param int $time
     * @return notification_interface
     */
    public function set_last_run_at(int $time): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $this->entity->last_run_at = $time;
        $this->entity->save();
        return $this;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function delete(): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $inst = new notification_sparse($this->get_activity(), $this->entity->class_key);
        $this->entity->delete();
        return $inst;
    }

    /**
     * @deprecated since Totara 13.2
     */
    public function refresh(): notification_interface {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        $this->entity->refresh();
        return $this;
    }

    /**
     * @deprecated since Totara 13.2
     */
    protected static function get_entity_class(): string {
        debugging(
            '\mod_perform\models\activity\details\notification_real is deprecated and should no longer be used.'
            . ' Please use \mod_perform\models\activity\notification instead.',
            DEBUG_DEVELOPER
        );
        return notification_entity::class;
    }
}
