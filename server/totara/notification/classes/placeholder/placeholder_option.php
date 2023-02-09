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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\placeholder;

use Closure;
use coding_exception;
use lang_string;
use totara_notification\placeholder\abstraction\collection_placeholder;
use totara_notification\placeholder\abstraction\placeholder;

class placeholder_option {
    /**
     * @var string
     */
    private $placeholder_class;

    /**
     * @var lang_string
     */
    private $group_lang_string;

    /**
     * @var callable
     */
    private $instantiation_callback;

    /**
     * @var string
     */
    private $group_key;

    /**
     * placeholder_option constructor.
     * @param string      $group_key
     * @param string      $placeholder_class
     * @param lang_string $group_lang_string
     * @param callable    $instantiation_callback
     */
    private function __construct(
        string $group_key,
        string $placeholder_class,
        lang_string $group_lang_string,
        callable $instantiation_callback
    ) {
        $this->group_key = $group_key;
        $this->placeholder_class = $placeholder_class;
        $this->group_lang_string = $group_lang_string;
        $this->instantiation_callback = $instantiation_callback;
    }

    /**
     * @param string      $group_key
     * @param string      $placeholder_class
     * @param lang_string $group_lang_string
     * @param callable    $instantiation_callback
     *
     * @return placeholder_option
     */
    public static function create(
        string $group_key,
        string $placeholder_class,
        lang_string $group_lang_string,
        callable $instantiation_callback
    ): placeholder_option {
        if (!placeholder_helper::is_valid_placeholder_class($placeholder_class)) {
            throw new coding_exception(
                "Expecting the argument \$placeholder_class as a child of " . placeholder::class
            );
        }

        return new static($group_key, $placeholder_class, $group_lang_string, $instantiation_callback);
    }

    /**
     * Returning a list of placeholder options from the {@see placeholder} class.
     * @return option[]
     */
    public function get_provided_placeholder_options(): array {
        /** @see placeholder::get_options() */
        return call_user_func([$this->placeholder_class, 'get_options']);
    }

    /**
     * Returns a list of placeholder options whereas the key is the concatinate string
     * of the group_key and the key from placeholder options.
     *
     * @return option[]
     */
    public function get_map_group_options(): array {
        $options = $this->get_provided_placeholder_options();
        $group_options = [];

        foreach ($options as $option) {
            $new_key = key_helper::get_group_key($this->group_key, $option->get_key());
            $new_label = get_string(
                $this->group_lang_string->get_identifier(),
                $this->group_lang_string->get_component(),
                $option->get_label()
            );

            $group_options[] = option::create($new_key, $new_label);
        }

        return $group_options;
    }

    /**
     * @param string $pattern
     * @return array
     */
    public function find_map_group_options_match(string $pattern): array {
        // This is to keep the regex friendly with or without the square bracket.
        $pattern = key_helper::remove_bracket($pattern);

        $group_options = $this->get_map_group_options();
        if (empty($pattern)) {
            // Pattern is an empty string, skip the match process to speed up
            // the process and return everything.
            return $group_options;
        }

        return array_filter(
            $group_options,
            function (option $option) use ($pattern): bool {
                $option_label = $option->get_label();
                if (mb_stripos($option_label, $pattern) !== false) {
                    return true;
                } else {
                    return false;
                }
            }
        );
    }

    /**
     * Call to the instantiation callback to get the place holder object.
     *
     * @param array $event_data
     * @param int   $target_user_id
     * @return placeholder
     */
    public function get_placeholder_instance(array $event_data, int $target_user_id): placeholder {
        $closure = Closure::fromCallable($this->instantiation_callback);

        // Note that we will let the native php fail if the callback does not return the same type
        // as this function declared.
        return $closure->__invoke($event_data, $target_user_id);
    }

    /**
     * @return string
     */
    public function get_group_key(): string {
        return $this->group_key;
    }

    /**
     * @return bool
     */
    public function is_collection_placeholder(): bool {
        $interface_names = class_implements($this->placeholder_class);
        return is_array($interface_names) && in_array(collection_placeholder::class, $interface_names);
    }

    /**
     * A function to check if the legitimate of the simple placeholder key.
     * The bit that is used with the grouped key, which is provided by the
     * actual placeholder class {@see placeholder::get_options()}
     *
     * @param string $simple_key
     * @return bool
     */
    public function is_valid_provided_placeholder_key(string $simple_key): bool {
        $options = $this->get_provided_placeholder_options();
        foreach ($options as $option) {
            if ($option->get_key() === $simple_key) {
                return true;
            }
        }

        return false;
    }

    /**
     * We are pass thru to {@see placeholder::is_safe_html()
     *
     * @param string $simple_key
     * @return bool
     */
    public function is_safe_html(string $simple_key): bool {
        return call_user_func([$this->placeholder_class, 'is_safe_html'], $simple_key);
    }
}