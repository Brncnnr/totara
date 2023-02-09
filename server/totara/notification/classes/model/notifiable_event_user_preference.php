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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\model;

use coding_exception;
use core\orm\entity\model;
use core\webapi\param\boolean;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_user_preference as notifiable_event_user_preference_entity;
use totara_notification\loader\delivery_channel_loader;

/**
 * Class notifiable_event_user_preference
 *
 * @property int $id
 * @property int $user_id
 * @property string $resolver_class_name
 * @property-read int $context_id
 * @property-read string $component
 * @property-read string $area
 * @property-read int $item_id
 * @property extended_context $extended_context
 * @property boolean $enabled
 * @property array $delivery_channels
 */
class notifiable_event_user_preference extends model {
    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'user_id',
        'resolver_class_name',
        'enabled',
        'delivery_channels',
    ];

    /**
     * @var string[]
     */
    protected $model_accessor_whitelist = [
        'extended_context',
    ];

    /**
     * @param notifiable_event_user_preference_entity $entity
     * @return notifiable_event_user_preference
     */
    public static function from_entity(notifiable_event_user_preference_entity $entity): notifiable_event_user_preference {
        if (!$entity->exists()) {
            throw new coding_exception("Cannot create a notifiable event user preference from a non-existing entity");
        }

        return new notifiable_event_user_preference($entity);
    }

    /**
     * @param int $id
     * @return notifiable_event_user_preference
     */
    public static function from_id(int $id): notifiable_event_user_preference {
        $entity = new notifiable_event_user_preference_entity($id);
        return static::from_entity($entity);
    }

    /**
     * @param int $user_id
     * @param string $resolver_class_name
     * @param extended_context $extended_context
     * @param bool $enabled
     * @param array|null $delivery_channels
     * @return notifiable_event_user_preference
     */
    public static function create(
        int $user_id,
        string $resolver_class_name,
        extended_context $extended_context,
        bool $enabled = true,
        ?array $delivery_channels = null
    ): notifiable_event_user_preference {
        $entity = new notifiable_event_user_preference_entity();
        $entity->user_id = $user_id;
        $entity->resolver_class_name = $resolver_class_name;
        $entity->context_id = $extended_context->get_context_id();
        $entity->component = $extended_context->get_component();
        $entity->area = $extended_context->get_area();
        $entity->item_id = $extended_context->get_item_id();
        $entity->enabled = $enabled;
        $entity->delivery_channels = $delivery_channels;
        $entity->save();

        return static::from_entity($entity);
    }

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return notifiable_event_user_preference_entity::class;
    }

    /**
     * @param bool $enabled
     * @return notifiable_event_user_preference
     */
    public function set_enabled(bool $enabled): notifiable_event_user_preference {
        $this->entity->set_attribute('enabled', $enabled);
        return $this;
    }

    /**
     * @return bool
     */
    public function get_enabled(): bool {
        return (bool) $this->entity->enabled;
    }

    /**
     * @return array
     */
    public function get_delivery_channels(): array {
        return $this->entity->delivery_channels;
    }

    /**
     * @param string[]|null $delivery_channels
     * @return $this
     */
    public function set_delivery_channels(?array $delivery_channels): notifiable_event_user_preference {
        if (null === $delivery_channels) {
            $this->entity->delivery_channels = null;
        } else {
            $delivery_channels_list = delivery_channel_loader::get_for_event_resolver($this->resolver_class_name);
            // Filter down to only those that are enabled & parent is enabled
            $enabled_channels = [];
            foreach ($delivery_channels_list as $delivery_channel) {
                if ($delivery_channel->is_sub_delivery_channel && !in_array($delivery_channel->parent, $delivery_channels)) {
                    continue;
                }

                if (in_array($delivery_channel->component, $delivery_channels)) {
                    $enabled_channels[] = $delivery_channel->component;
                }
            }

            $this->entity->delivery_channels = $enabled_channels;
        }
        return $this;
    }

    /**
     * @return extended_context
     */
    public function get_extended_context(): extended_context {
        return extended_context::make_with_id(
            $this->entity->get_attribute('context_id'),
            $this->entity->get_attribute('component'),
            $this->entity->get_attribute('area'),
            $this->entity->get_attribute('item_id')
        );
    }

    /**
     * @param extended_context $extended_context
     * @return notifiable_event_user_preference
     */
    public function set_extended_context(extended_context $extended_context): notifiable_event_user_preference {
        $this->entity->set_attribute('context_id', $extended_context->get_context_id());
        $this->entity->set_attribute('component', $extended_context->get_component());
        $this->entity->set_attribute('area', $extended_context->get_area());
        $this->entity->set_attribute('item_id', $extended_context->get_item_id());
        return $this;
    }

    /**
     * @return $this
     */
    public function save(): notifiable_event_user_preference {
        $this->entity->save();
        return $this;
    }
}