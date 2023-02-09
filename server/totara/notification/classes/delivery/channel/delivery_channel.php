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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\delivery\channel;

/**
 * Delivery channel represents one of the available options. It holds
 * static information only.
 *
 * @property-read bool $is_enabled
 * @property-read string $label
 * @property-read string $parent
 * @property-read bool $is_sub_delivery_channel
 * @property-read int $display_order
 * @property-read string $component
 *
 * @package totara_notification\delivery\channel
 */
abstract class delivery_channel {

    /**
     * Indicates if this specific instance of the delivery channel
     * is considered enabled or not.
     *
     * @var bool|null
     */
    private $enabled;

    /**
     * delivery_channel constructor.
     *
     * @param bool|null $enabled
     */
    private function __construct(?bool $enabled) {
        $this->enabled = $enabled;
    }

    /**
     * Return the human-readable name of the delivery channel.
     *
     * @return string
     */
    abstract public static function get_label(): string;

    /**
     * Define the order the delivery channels should be sorted in.
     * Note: There's no validation here, it's not a very smart attribute.
     *
     * The purpose is to force some semblance of order in the list.
     *
     * @return int
     */
    abstract public static function get_display_order(): int;

    /**
     * @return string|null
     */
    public static function get_parent(): ?string {
        return null;
    }

    /**
     * @return bool
     */
    public static function get_is_sub_delivery_channel(): bool {
        return (bool) static::get_parent();
    }

    /**
     * Returns the messaging component for this class.
     *
     * @return string
     */
    public static function get_component(): string {
        $class = explode('\\', static::class);
        return str_replace('message_', '', array_shift($class));
    }

    /**
     * Return an instance of this delivery channel.
     * This is used to transfer basic data between the model & graphql layer.
     * There is no persistence involved at all.
     *
     * @param bool|null $enabled
     * @return delivery_channel
     */
    public static function make(?bool $enabled = null): delivery_channel {
        return new static($enabled);
    }

    /**
     * Helper to access static properties.
     * We don't validate that much as this class doesn't hold secret information.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        $get_method = 'get_' . $name;

        // We only have one local get method, the rest are static
        if ($get_method === 'get_is_enabled') {
            return call_user_func([$this, $get_method]);
        }

        if (method_exists(static::class, $get_method)) {
            return call_user_func([static::class, $get_method]);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function get_is_enabled(): bool {
        return $this->enabled ?? false;
    }

    /**
     * @param bool|null $enabled
     */
    public function set_enabled(?bool $enabled): void {
        $this->enabled = $enabled;
    }

    /**
     * @return array
     */
    public function to_array(): array {
        return [
            'component' => $this->component,
            'label' => $this->label,
            'is_enabled' => (bool) $this->is_enabled,
            'is_sub_delivery_channel' => $this->is_sub_delivery_channel,
            'parent_component' => $this->parent,
            'display_order' => $this->display_order,
        ];
    }
}