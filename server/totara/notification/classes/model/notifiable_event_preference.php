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
 * @author  Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\model;

use coding_exception;
use core\orm\entity\model;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_preference as notifiable_event_preference_entity;
use totara_notification\loader\delivery_channel_loader;
use totara_notification\delivery\channel\delivery_channel;

/**
 * Class notifiable_event_preference
 *
 * @property int $id
 * @property string $resolver_class_name
 * @property extended_context $extended_context
 * @property bool $enabled
 * @property delivery_channel[] $default_delivery_channels
 */
class notifiable_event_preference extends model {
    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'resolver_class_name',
    ];

    /**
     * @var string[]
     */
    protected $model_accessor_whitelist = [
        'enabled',
        'extended_context',
        'default_delivery_channels',
    ];

    /**
     * @param notifiable_event_preference_entity $entity
     * @return notifiable_event_preference
     */
    public static function from_entity(notifiable_event_preference_entity $entity): notifiable_event_preference {
        if (!$entity->exists()) {
            throw new coding_exception("Cannot instantiate a notification notifiable event from a non-existing entity");
        }

        return new notifiable_event_preference($entity);
    }

    /**
     * @param int $id
     * @return notifiable_event_preference
     */
    public static function from_id(int $id): notifiable_event_preference {
        $entity = new notifiable_event_preference_entity($id);
        return static::from_entity($entity);
    }

    /**
     * @param string $resolver_class_name
     * @param extended_context $extended_context
     * @param bool $enabled
     * @return notifiable_event_preference
     */
    public static function create(string $resolver_class_name, extended_context $extended_context, bool $enabled = true): notifiable_event_preference {
        $entity = new notifiable_event_preference_entity();
        $entity->resolver_class_name = $resolver_class_name;
        $entity->context_id = $extended_context->get_context_id();
        $entity->component = $extended_context->get_component();
        $entity->area = $extended_context->get_area();
        $entity->item_id = $extended_context->get_item_id();
        $entity->enabled = $enabled;

        $entity->save();

        return static::from_entity($entity);
    }

    /**
     * @param bool $enabled
     */
    public function set_enabled(bool $enabled) {
        $this->entity->set_attribute('enabled', $enabled);
    }

    /**
     * @return bool|null
     */
    public function get_enabled(): ?bool {
        if ($this->entity->enabled === null) {
            return null;
        }

        return (bool) $this->entity->enabled;
    }

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return notifiable_event_preference_entity::class;
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
     * @return void
     */
    public function set_extended_context(extended_context $extended_context): void {
        $this->entity->set_attribute('context_id', $extended_context->get_context_id());
        $this->entity->set_attribute('component', $extended_context->get_component());
        $this->entity->set_attribute('area', $extended_context->get_area());
        $this->entity->set_attribute('item_id', $extended_context->get_item_id());
    }

    /**
     * @return delivery_channel[]
     */
    public function get_default_delivery_channels(): array {
        $raw_list = $this->entity->get_attribute('default_delivery_channels');
        $resolver_class_name = $this->entity->get_attribute('resolver_class_name');

        if ($raw_list === null) {
            return delivery_channel_loader::get_for_event_resolver($resolver_class_name);
        }

        $list = explode(',', $raw_list);
        return delivery_channel_loader::get_from_list($resolver_class_name, $list);
    }

    /**
     * Sets the raw delivery channels value back, based on the provided collection.
     * If null, then the default delivery channel settings will be used instead.
     *
     * The collection will be transformed into the entity string ",key1,key2," etc...
     *
     * @param delivery_channel[]|null $delivery_channels
     */
    public function set_default_delivery_channels(?array $delivery_channels): void {
        if (null === $delivery_channels) {
            $this->entity->default_delivery_channels = null;
        } else {
            $concat = [];
            foreach ($delivery_channels as $delivery_channel) {
                if ($delivery_channel->is_enabled) {
                    $concat[] = $delivery_channel->component;
                }
            }

            // Delivery channels are saved as ',email,popup,' etc... with a , at either end
            // to make any filtering possible by going 'default_delivery_channels LIKE '%,key,%'
            // A blank entry is added to the start & end of the $concat list to generate the edge commas
            $concat[] = ''; // Add the , to the end
            array_unshift($concat, ''); // Add the , to the start

            $this->entity->default_delivery_channels = implode(',', $concat);
        }
    }

    /**
     * @return void
     */
    public function refresh(): void {
        $this->entity->refresh();
    }

    /**
     * @return void
     */
    public function delete(): void {
        $this->entity->delete();
    }

    /**
     * @return bool
     */
    public function exists(): bool {
        return $this->entity->exists();
    }

    /**
     * @return void
     */
    public function save(): void {
        $this->entity->save();
    }
}