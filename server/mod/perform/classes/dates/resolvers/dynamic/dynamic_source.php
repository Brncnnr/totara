<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\dates\resolvers\dynamic;

use coding_exception;
use core\collection;
use JsonSerializable;

/**
 * The class represents a pointer to a specific dynamic date source,
 * a date source is comprised of a dynamic date resolver class and a option key.
 *
 * This class also holds the display name of the option itself for the
 * case when the resolver or option_key become unavailable and we still
 * want to display a selected but deleted option.
 *
 * @package mod_perform\dates\resolvers\dynamic
 * @see dynamic_date_resolver
 * @see dynamic_date_resolver::get_options()
 */
class dynamic_source implements JsonSerializable {

    /**
     * @var dynamic_date_resolver
     */
    protected $resolver;

    /**
     * @var string
     */
    protected $option_key;

    /**
     * @var string
     */
    protected $display_name;

    /**
     * dynamic_source constructor.
     *
     * @param dynamic_date_resolver|null $resolver
     * @param string $option_key
     * @param string|null $display_name
     */
    public function __construct(
        ?dynamic_date_resolver $resolver,
        string $option_key,
        ?string $display_name
    ) {
        $this->resolver = $resolver;
        $this->option_key = $option_key;
        $this->display_name = $display_name;
    }

    /**
     * Factory function to get one of every available resolver option.
     *
     * @return collection
     */
    public static function all_available(): collection {
        $date_resolver_classes = base_dynamic_date_resolver::get_all_classes();

        $all_options = static::combine_all_options($date_resolver_classes);

        return static::sort_by_display_name($all_options);
    }

    /**
     * Create a new resolver option from json (or assoc array).
     *
     * Note that the original resolver could have been deleted or the option could no longer be available
     * but an instance will still be returned. However the is_available method will reflect it's unavailability.
     *
     * If the option is no longer available the display name will be loaded from $data, otherwise it will be re-fetched
     * from the resolver.
     *
     * @param string|array $data A json encoded string or assoc array, with mandatory 'resolver_class_name' and 'option_key fields,
     *                           and optional 'display_name' field.s
     * @param bool $must_be_available The option must still exist, if not we will throw an exception.
     * @return static
     */
    public static function create_from_json($data, bool $must_be_available = false) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $resolver_class = $data['resolver_class_name'] ?? null;
        $option_key = $data['option_key'] ?? null;
        $display_name = $data['display_name'] ?? null;
        $custom_data = $data['custom_data'] ?? null;

        // Note: that we don't require display name because we will try load that from the resolver.
        if ($resolver_class === null || $option_key === null) {
            throw new coding_exception('resolver_class_name and option_key fields are mandatory');
        }

        $resolver = null;
        $source_option = null;
        if (is_subclass_of($resolver_class, dynamic_date_resolver::class)) {
            /** @var dynamic_date_resolver $resolver */
            $resolver = new $resolver_class();
            $resolver->set_custom_data($custom_data);

            $source_option = static::get_option_from_key($resolver->get_options(), $option_key);
        }

        if ($must_be_available && ($resolver === null || $source_option === null)) {
            throw new coding_exception('Source is not available');
        }

        if ($source_option !== null) {
            return $source_option;
        }

        return new static($resolver, $option_key, $display_name);
    }

    protected static function combine_all_options(collection $date_resolver_classes): collection {
        $combine_all_options = function (array $all_options, string $date_resolver_class) {
            /** @var dynamic_date_resolver $date_resolver */
            $date_resolver = new $date_resolver_class();

            return array_merge($all_options, $date_resolver->get_options()->all());
        };

        return collection::new(array_reduce($date_resolver_classes->all(), $combine_all_options, []));
    }

    protected static function sort_by_display_name(collection $dynamic_sources): collection {
        return $dynamic_sources->sort(function (dynamic_source $a, dynamic_source $b) {
            return strcmp($a->get_display_name(), $b->get_display_name());
        });
    }

    protected static function get_option_from_key(collection $options, string $option_key): ?dynamic_source {
        return $options->find(function (dynamic_source $dynamic_source) use ($option_key) {
            return $dynamic_source->get_option_key() === $option_key;
        });
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array {
        //  Used for saving to a single database column.
        return [
            'resolver_class_name' => $this->get_resolver_class_name(),
            'option_key' => $this->get_option_key(),
            'display_name' => $this->get_display_name(),
            'is_available' => $this->is_available(),
            'custom_setting_component' => $this->resolver->get_custom_setting_component(),
            'custom_data' => $this->resolver->get_custom_data(),
            'resolver_base' => $this->resolver->get_resolver_base(),
        ];
    }

    /**
     * Get the option key, the option key specifies which sub option for a date resolver will be used for determining dates.
     *
     * @return string
     */
    public function get_option_key(): string {
        return $this->option_key;
    }

    /**
     * @return string|null
     */
    public function get_resolver_class_name(): ?string {
        if ($this->resolver === null) {
            return null;
        }

        return get_class($this->resolver);
    }

    public function get_display_name(): string {
        return $this->display_name;
    }

    public function is_available(): bool {
        if ($this->resolver === null) {
            return false;
        }

        return $this->resolver->option_is_available($this->option_key);
    }

    /**
     * Get custom setting component
     *
     * @return string|null
     */
    public function get_custom_setting_component(): ?string {
        return $this->resolver->get_custom_setting_component();
    }

    /**
     *
     * @return string|null
     */
    public function get_custom_data(): ?string {
        return $this->resolver->get_custom_data();
    }

    public function get_resolver(): ?dynamic_date_resolver {
        return $this->resolver;
    }

    public function get_resolver_base(): ?string {
        if ($this->resolver === null) {
            return null;
        }

        return $this->resolver->get_resolver_base();
    }

}
