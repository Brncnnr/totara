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
namespace totara_notification\placeholder\template_engine\square_bracket;

use coding_exception;
use totara_notification\placeholder\key_helper;
use totara_notification\placeholder\placeholder_option;
use totara_notification\placeholder\template_engine\engine as engine_interface;
use totara_notification\placeholder\template_engine\mustache\engine as mustache_engine;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;

class engine implements engine_interface {
    /**
     * The generic notifiable event data.
     * @var array
     */
    private $event_data;

    /**
     * The notifiable event resolver class name.
     * @var string
     */
    private $resolver_class_name;

    /**
     * engine constructor.
     * @param string $resolver_class_name
     * @param array  $event_data
     */
    private function __construct(string $resolver_class_name, array $event_data) {
        $this->resolver_class_name = $resolver_class_name;
        $this->event_data = $event_data;
    }

    /**
     * @param string $resolver_class_name
     * @param array  $event_data
     *
     * @return engine
     */
    public static function create(string $resolver_class_name, array $event_data): engine {
        if (!resolver_helper::is_valid_event_resolver($resolver_class_name)) {
            throw new coding_exception("The resolver class name is not a valid notifiable event resolver");
        }

        return new static($resolver_class_name, $event_data);
    }

    /**
     * This function will help to return the list of
     * @param string $text
     * @return string[]
     */
    protected static function get_all_placeholders_from_text(string $text): array {
        $regex = key_helper::get_grouped_key_regex(true);
        $matches = [];

        preg_match_all($regex, $text, $matches);
        return array_unique($matches[0] ?? []);
    }

    /**
     * @return placeholder_option[]
     */
    private function get_map_placeholder_options(): array {
        /**
         * @see notifiable_event_resolver::get_notification_available_placeholder_options()
         * @var placeholder_option[] $placeholder_options
         */
        $placeholder_options = call_user_func([$this->resolver_class_name, 'get_notification_available_placeholder_options']);
        $map_placeholders = [];

        foreach ($placeholder_options as $placeholder_option) {
            $group_key = $placeholder_option->get_group_key();
            $map_placeholders[$group_key] = $placeholder_option;
        }

        return $map_placeholders;
    }

    /**
     * Return a hash-map of hash-map, 2 nested level array, which the first level key is about the
     * group name group all the placeholder keys and value is the hash-map of placeholder key and its
     * original match value.
     *
     * Example:
     * Given this string "Hello [commenter:fullname]" then the result of this function will be
     * [
     *      'commenter' => ['fullname' => '[commenter:fullname]']
     * ]
     *
     * @param string $text
     * @return array
     */
    private function get_map_matches(string $text): array {
        $matches = self::get_all_placeholders_from_text($text);
        $map_matches = [];

        // Construct the map for prefix.
        foreach ($matches as $match) {
            if (!key_helper::is_valid_grouped_key($match, true)) {
                // The regex above this if condition will eliminate all the miss matches.
                // However this is still in place because we just want another layer to filter
                // out and non-supported matches.
                debugging("Invalid grouped key '{$match}'", DEBUG_DEVELOPER);
                continue;
            }

            [$prefix, $placeholder_key] = key_helper::normalize_grouped_key($match);

            if (!isset($map_matches[$prefix])) {
                $map_matches[$prefix] = [];
            }

            // With this we can make sure that we are still able to key the placeholder and map it
            // with the bracket (opened and closed) match.
            $map_matches[$prefix][$placeholder_key] = $match;
        }

        return $map_matches;
    }

    /**
     * Rendering a content into a mustache readable content, and then call to mustache to
     * render the content into a human readable content.
     *
     * @param string $content
     * @param int    $target_user_id
     * @return string
     */
    public function render_for_user(string $content, int $target_user_id): string {
        $map_placeholders = $this->get_map_placeholder_options();
        $map_matches = $this->get_map_matches($content);

        $search = [];
        $replace = [];

        // We are caching the keys that existing in the template and group them.
        $only_keys = [];

        // Convert into mustache template
        foreach ($map_matches as $group_key => $placeholder_keys) {
            if (!isset($map_placeholders[$group_key])) {
                debugging(
                    "The key prefix '{$group_key}' does not exist in the list of available placeholder options",
                    DEBUG_DEVELOPER
                );

                continue;
            }

            $placeholder_option = $map_placeholders[$group_key];
            $collection_placeholder = $placeholder_option->is_collection_placeholder();

            if (!isset($only_keys[$group_key])) {
                $only_keys[$group_key] = [];
            }

            if ($collection_placeholder) {
                $search[] = key_helper::get_start_collection_key($group_key, true);
                $replace[] = "{{#{$group_key}}}";

                $search[] = key_helper::get_end_collection_key($group_key, true);
                $replace[] = "{{/{$group_key}}";
            }

            foreach ($placeholder_keys as $placeholder_key => $match) {
                if (!$placeholder_option->is_valid_provided_placeholder_key($placeholder_key)) {
                    // We are debugging it for now and does not render/replace the key, as we would want the
                    // system to move forward and ignore these keys.
                    debugging(
                        "The placeholder key '{$match}' is not a valid placeholder key provided by the options list",
                        DEBUG_DEVELOPER
                    );

                    continue;
                }

                $search[] = $match;
                $context_variable = $collection_placeholder ? $placeholder_key : "{$group_key}.{$placeholder_key}";

                if ($placeholder_option->is_safe_html($placeholder_key)) {
                    $replace[] = "{{{{$context_variable}}}}";
                } else {
                    $replace[] = "{{{$context_variable}}}";
                }

                $only_keys[$group_key][] = $placeholder_key;
            }
        }

        $content = str_replace($search, $replace, $content);

        $mustache_engine = mustache_engine::create($this->resolver_class_name, $this->event_data);
        return $mustache_engine->render($content, $target_user_id, $only_keys);
    }
}