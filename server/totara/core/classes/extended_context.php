<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_core
 */
namespace totara_core;

use coding_exception;
use context;
use context_system;
use totara_core\identifier\component_area;
use totara_core\identifier\instance_identifier;

/**
 * Convenience class to hold a Totara component, item and context combination.
 */
final class extended_context {
    /**
     * Empty component for natural context.
     * @var string
     */
    public const NATURAL_CONTEXT_COMPONENT = '';

    /**
     * Empty area for natural context.
     * @var string
     */
    public const NATURAL_CONTEXT_AREA = '';

    /**
     * Empty item's id for natural context.
     * @var int
     */
    public const NATURAL_CONTEXT_ITEM_ID = 0;

    /**
     * @var instance_identifier
     */
    private $identifier;

    /**
     * Factory method to create an instance.
     *
     * @param context $context
     * @param string  $component
     * @param string  $area
     * @param int     $item_id
     *
     * @return extended_context the object instance.
     */
    public static function make_with_context(
        context $context,
        string $component = self::NATURAL_CONTEXT_COMPONENT,
        string $area = self::NATURAL_CONTEXT_AREA,
        int $item_id = self::NATURAL_CONTEXT_ITEM_ID
    ): extended_context {
        return self::make_with_id(
            $context->id,
            $component,
            $area,
            $item_id
        );
    }

    /**
     * Factory method to create an instance.
     *
     * @param int    $context_id
     * @param string $component
     * @param string $area
     * @param int    $item_id
     *
     * @return extended_context the object instance.
     */
    public static function make_with_id(
        int $context_id,
        string $component = self::NATURAL_CONTEXT_COMPONENT,
        string $area = self::NATURAL_CONTEXT_AREA,
        int $item_id = self::NATURAL_CONTEXT_ITEM_ID
    ): extended_context {
        if ($component !== self::NATURAL_CONTEXT_COMPONENT ||
            $area !== self::NATURAL_CONTEXT_AREA ||
            $item_id !== self::NATURAL_CONTEXT_ITEM_ID
        ) {
            if ($component === self::NATURAL_CONTEXT_COMPONENT ||
                $area === self::NATURAL_CONTEXT_AREA ||
                $item_id === self::NATURAL_CONTEXT_ITEM_ID
            ) {
                throw new coding_exception('Extended contexts must either provide component, area AND item ID, or none of these');
            }
        }

        return new extended_context(
            new instance_identifier(
                new component_area($component, $area),
                $item_id,
                $context_id
            )
        );
    }

    /**
     * @return extended_context
     */
    public static function make_system(): extended_context {
        $context = context_system::instance();
        return self::make_with_context($context);
    }

    /**
     * extended_context constructor.
     * @param instance_identifier $identifier
     */
    private function __construct(instance_identifier $identifier) {
        $this->identifier = $identifier;
    }

    /**
     * Returns the object state for var_dump().
     *
     * @return array [string=>mixed] a list of attributes to show.
     */
    public function __debugInfo(): array {
        return [
            'context id' => $this->get_context_id(),
            'component' => $this->get_component(),
            'area' => $this->get_area(),
            'item id' => $this->get_item_id(),
        ];
    }

    /**
     * Returns a string version of the object state.
     *
     * @return string the stringified object state.
     */
    public function __toString(): string {
        $values = '';
        foreach ($this->__debugInfo() as $key => $value) {
            $str = "\t$key=$value";
            $values = empty($values) ? $str : "$values,\n$str";
        }

        return sprintf("%s[\n%s\n]", self::class, $values);
    }

    /**
     * Returns the associated natural context.
     *
     * @return context
     */
    public function get_context(): context {
        $context_id = $this->identifier->get_context_id();
        return context::instance_by_id($context_id);
    }

    /**
     * Returns the associated context id.
     *
     * @return int
     */
    public function get_context_id(): int {
        return $this->identifier->get_context_id();
    }

    /**
     * @return int
     */
    public function get_context_level(): int {
        $context = $this->get_context();
        return $context->contextlevel;
    }

    /**
     * Returns the associated component.
     *
     * @return string
     */
    public function get_component(): string {
        return $this->identifier->get_component();
    }

    /**
     * Returns the associated area.
     *
     * @return string
     */
    public function get_area(): string {
        return $this->identifier->get_area();
    }

    /**
     * Returns the associated item id.
     *
     * @return int
     */
    public function get_item_id(): int {
        return $this->identifier->get_instance_id();
    }

    /**
     * Determine if two extended contexts are identical.
     *
     * @param extended_context $other_context
     * @return bool
     */
    public function is_same(extended_context $other_context): bool {
        return $this->get_context_id() == $other_context->get_context_id() &&
            $this->get_component() == $other_context->get_component() &&
            $this->get_area() == $other_context->get_area() &&
            $this->get_item_id() == $other_context->get_item_id();
    }

    /**
     * Determine if the extended context matches to a natural context
     *
     * @return bool
     */
    public function is_natural_context(): bool {
        return $this->get_area() === self::NATURAL_CONTEXT_AREA;
    }

    /**
     * Get the parent extended context.
     *
     * The parent of a non-natural context is simply the natural context component of the extended context.
     * The parent of the system context is null - it has no parent.
     *
     * @return extended_context|null
     */
    public function get_parent(): ?extended_context {
        if (!$this->is_natural_context()) {
            if (!empty($this->context)) {
                return self::make_with_context($this->context);
            } else {
                return self::make_with_id($this->get_context_id());
            }
        }

        $parent_context = $this->get_context()->get_parent_context();

        if ($parent_context === false) {
            return null;
        }

        return self::make_with_context($parent_context);
    }

    /**
     * Get a list of all the ancestor context ids.
     *
     * Note that this includes the "natural" context when the current context is "extended".
     *
     * @return array
     */
    public function get_parent_context_ids(): array {
        if ($this->is_natural_context()) {
            return $this->get_context()->get_parent_context_ids(false);
        } else {
            return $this->get_context()->get_parent_context_ids(true);
        }
    }
}
