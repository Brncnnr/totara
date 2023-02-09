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
namespace totara_notification\placeholder\template_engine\mustache;

use coding_exception;
use Mustache_Autoloader;
use Mustache_Engine as core_mustache_engine;
use totara_notification\placeholder\abstraction\collection_placeholder;
use totara_notification\placeholder\abstraction\single_placeholder;
use totara_notification\placeholder\option;
use totara_notification\placeholder\placeholder_option;
use totara_notification\placeholder\template_engine\engine as engine_interface;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;

/**
 * We are using mustache engine to render the whole template with placeholder
 * into a proper meaningful content value.
 */
class engine implements engine_interface {
    /**
     * The notifiable event resolver class name.
     * @var string
     */
    private $resolver_class_name;

    /**
     * The generic notifiable event data.
     * @var array
     */
    private $event_data;

    /**
     * mustache_engine constructor.
     * @param string $resolver_class_name
     * @param array  $event_data
     */
    protected function __construct(string $resolver_class_name, array $event_data) {
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
            throw new coding_exception("The resolver class is not a valid notifiable event resolver");
        }

        return new static($resolver_class_name, $event_data);
    }

    /**
     * Given a hashmap of group keys and all the placeholder keys within the group key.
     * This variable is only to help optimizing loading whatever the values are for keys, as these keys
     * are the only keys that appear in the template string.
     *
     * The structure of this $provided_only_keys is quite simple:
     *
     * [
     *  'commenter' => ['fullname', 'email', 'lastname'],
     *  'item_article' => ['name', 'description']
     * ]
     *
     * @param int      $target_user_id
     * @param array    $provided_only_keys
     * @return array
     */
    protected function get_map_variables(int $target_user_id, array $provided_only_keys = []): array {
        /**
         * @see notifiable_event_resolver::get_notification_available_placeholder_options()
         * @var placeholder_option[] $placeholder_options
         */
        $placeholder_options = call_user_func([$this->resolver_class_name, 'get_notification_available_placeholder_options']);
        $map_variables = [];

        foreach ($placeholder_options as $placeholder_option) {
            $group_name = $placeholder_option->get_group_key();
            if (isset($map_variables[$group_name])) {
                // Ideally there should be no duplicated group name in the list of placeholder option.
                debugging("The group name '{$group_name}' had already been created", DEBUG_DEVELOPER);
                continue;
            }

            if (!empty($provided_only_keys) && !isset($provided_only_keys[$group_name])) {
                // Skip this process for this group's key if the array of provided only
                // keys does not have the group key appear in it.
                continue;
            }

            $map_variables[$group_name] = [];
            $placeholder_instance = $placeholder_option->get_placeholder_instance($this->event_data, $target_user_id);

            $load_only_keys = $provided_only_keys[$group_name] ?? [];

            if ($placeholder_instance instanceof collection_placeholder) {
                $map_variables[$group_name] = $placeholder_instance->get_collection_map($load_only_keys);
                continue;
            } else if ($placeholder_instance instanceof single_placeholder) {
                if (empty($load_only_keys)) {
                    // Load everything
                    $options = $placeholder_option->get_provided_placeholder_options();
                    $load_only_keys = array_map(
                        function (option $option): string {
                            return $option->get_key();
                        },
                        $options
                    );
                }

                foreach ($load_only_keys as $key) {
                    if (!$placeholder_option->is_valid_provided_placeholder_key($key)) {
                        debugging(
                            "The placeholder key '{$key}' of group '{$group_name}' is not a valid key. " .
                            "Default to invalid data",
                            DEBUG_DEVELOPER
                        );

                        $map_variables[$group_name][$key] = get_string('no_available_data_for_key', 'totara_notification', $key);
                        continue;
                    }

                    $value = $placeholder_instance->get($key);
                    $map_variables[$group_name][$key] = is_null($value)
                        ? get_string('no_available_data_for_key', 'totara_notification', $key)
                        : $value;
                }

                continue;
            }

            debugging("Invalid placeholder instance that is not either a collection or single getter", DEBUG_DEVELOPER);
        }

        return $map_variables;
    }

    /**
     * Rendering the template with the list of fetch only keys ($provided_only_keys).
     * If the array of keys are appearing to be not empty, then we are fetching everything
     * that provided by placeholder options.
     *
     * @param string $template
     * @param int    $target_user_id
     * @param array  $provided_only_keys
     *
     * @return string
     */
    public function render(string $template, int $target_user_id, array $provided_only_keys = []): string {
        global $CFG;
        if (!class_exists('Mustache_Engine')) {
            // This is just a safe fallback. The Mustache_Engine definitely included
            // at the start of the script when $PAGE is setup.
            require_once("{$CFG->dirroot}/lib/mustache/src/Mustache/Autoloader.php");
            Mustache_Autoloader::register();
        }

        $mustache = new core_mustache_engine([
            'entity_flags' => ENT_QUOTES,
            'escape' => 's',
        ]);

        $context_variables = $this->get_map_variables($target_user_id, $provided_only_keys);
        return $mustache->render($template, $context_variables);
    }

    /**
     * @param string $content
     * @param int    $target_user_id
     *
     * @return string
     */
    public function render_for_user(string $content, int $target_user_id): string {
        return $this->render($content, $target_user_id);
    }
}