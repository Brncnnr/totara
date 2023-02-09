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

namespace totara_notification\testing;

use coding_exception;
use context_system;
use core\json_editor\helper\document_helper;
use core\testing\component_generator;
use lang_string;
use ReflectionClass;
use totara_core\extended_context;
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notification_preference as entity;
use totara_notification\factory\built_in_notification_factory;
use totara_notification\factory\capability_factory;
use totara_notification\factory\notifiable_event_resolver_factory;
use totara_notification\local\helper;
use totara_notification\model\notification_preference;
use totara_notification\notification\built_in_notification;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\task\process_notification_queue_task;
use totara_notification_mock_built_in_notification;
use totara_notification_mock_lang_string;
use totara_notification_mock_notifiable_event_resolver;
use totara_notification_mock_recipient;
use totara_notification_test_progress_trace;

/**
 * @method static generator instance()
 */
final class generator extends component_generator {
    /**
     * @return string
     */
    private static function plugin_location(): string {
        global $CFG;
        return "{$CFG->dirroot}/totara/notification";
    }

    /**
     * @return string
     */
    private static function fixtures_location(): string {
        $location = self::plugin_location();
        return "{$location}/tests/fixtures";
    }

    /**
     * @param string      $component
     * @param string|null $notification_class_name
     *
     * @return notification_preference
     */
    public function add_mock_built_in_notification_for_component(
        ?string $notification_class_name = null,
        string $component = 'totara_notification'
    ): notification_preference {
        if (empty($notification_class_name)) {
            $this->include_mock_built_in_notification();
            $notification_class_name = totara_notification_mock_built_in_notification::class;
        }

        $reflection_class = new ReflectionClass(built_in_notification_factory::class);

        /** @see  built_in_notification_factory::get_map() */
        $method = $reflection_class->getMethod('get_map');
        $method->setAccessible(true);

        // We will have to get map from the current private method to make sure that our map is
        // initialized nicely.
        $map = $method->invoke(null);
        $method->setAccessible(false);

        if (!isset($map[$component])) {
            $map[$component] = [];
        }

        if (!helper::is_valid_built_in_notification($notification_class_name)) {
            throw new coding_exception(
                "Only able to add a child of " . built_in_notification::class
            );
        }

        $map[$component][] = $notification_class_name;

        /** @see built_in_notification_factory::$built_in_notification_classes */
        $property = $reflection_class->getProperty('built_in_notification_classes');
        $property->setAccessible(true);
        $property->setValue($map);

        $property->setAccessible(false);

        /**
         * @see built_in_notification::get_resolver_class_name()
         * @var string $resolver_class_name
         */
        $resolver_class_name = call_user_func([$notification_class_name, 'get_resolver_class_name']);

        return $this->create_notification_preference(
            $resolver_class_name,
            extended_context::make_with_context(context_system::instance()),
            ['notification_class_name' => $notification_class_name]
        );
    }

    /**
     * The array $data should contain the keys as follow:
     * + notification_class_name: String
     * + ancestor_id: Int
     * + body: String
     * + body_format: Int
     * + subject: String
     * + subject_format: Int
     * + recipient: String
     * + recipients: String[]
     * + schedule_offset: Int
     * + enabled: Boolean
     * + forced_delivery_channels: String[]
     *
     * @param array                 $data
     * @param extended_context|null $extended_context
     * @param string|notifiable_event_resolver $resolver_class_name
     *
     * @return notification_preference
     */
    public function create_notification_preference(
        string $resolver_class_name,
        ?extended_context $extended_context = null,
        array $data = []
    ): notification_preference {
        $extended_context = $extended_context ?? extended_context::make_with_context(context_system::instance());
        $builder = new notification_preference_builder($resolver_class_name, $extended_context);

        if (!empty($data['notification_name'])) {
            // Temporary to fix any tests that still preference to the old code.
            throw new coding_exception("This does not work like that");
        }

        $notification_class_name = $data['notification_class_name'] ?? null;
        if (!empty($notification_class_name)) {
            // Check that if the notification preference does exist.
            $repository = entity::repository();
            $entity = $repository->find_built_in(
                $notification_class_name,
                $extended_context
            );

            if (null !== $entity) {
                // The record is already existing in the system, we will just return the current record.
                // If developer want to tweak/update the values of certain fields, they can use the builder's API to do so.
                return notification_preference::from_entity($entity);
            }
        }

        if (empty($data['notification_class_name']) && empty($data['ancestor_id'])) {
            $this->include_mock_recipient();
            // We are only giving the default value if the notification_class_name or the ancestor is not
            // appearing in the $data parameter.
            $data['body_format'] = $data['body_format'] ?? FORMAT_JSON_EDITOR;
            $data['subject_format'] = $data['subject_format'] ?? FORMAT_JSON_EDITOR;

            $data['body'] = $data['body'] ?? 'This is a body';
            $data['title'] = $data['title'] ?? 'This is title';
            $data['subject'] = $data['subject'] ?? 'This is a subject';
            $data['schedule_offset'] = $data['schedule_offset'] ?? 0;
            $data['enabled'] = $data['enabled'] ?? true;
            $data['recipient'] = $data['recipient'] ?? totara_notification_mock_recipient::class;
            $data['recipients'] = $data['recipients'] ?? [totara_notification_mock_recipient::class];
            $data['forced_delivery_channels'] = $data['forced_delivery_channels'] ?? [];
        }

        if (isset($data['body']) && isset($data['body_format'])) {
            // Note: we can do a one statement if here with lots of "&&" condition. However it would leave us to
            // the point where the condition is a nightmare condition. Therefore i had made it simpler with nested
            // like this and debugging would just be easier - you are welcome :)
            $body_format = $data['body_format'];

            if (FORMAT_JSON_EDITOR == $body_format && !document_helper::looks_like_json($data['body'])) {
                $data['body'] = document_helper::create_json_string_document_from_text($data['body']);
            }
        }

        if (isset($data['subject']) && isset($data['subject_format'])) {
            // Note: we can do a one statement if here with lots of "&&" condition. However it would leave us to
            // the point where the condition is a nightmare condition. Therefore i had made it simpler with nested
            // like this and debugging would just be easier - you are welcome :)
            $subject_format = $data['subject_format'];

            if (FORMAT_JSON_EDITOR == $subject_format && !document_helper::looks_like_json($data['subject'])) {
                $data['subject'] = document_helper::create_json_string_document_from_text($data['subject']);
            }
        }

        $builder->set_notification_class_name($data['notification_class_name'] ?? null);
        $builder->set_ancestor_id($data['ancestor_id'] ?? null);
        $builder->set_body($data['body'] ?? null);
        $builder->set_body_format($data['body_format'] ?? null);
        $builder->set_subject($data['subject'] ?? null);
        $builder->set_subject_format($data['subject_format'] ?? null);
        $builder->set_title($data['title'] ?? null);
        $builder->set_additional_criteria($data['additional_criteria'] ?? null);
        $builder->set_schedule_offset($data['schedule_offset'] ?? null);
        $builder->set_recipients($data['recipients'] ?? null);
        $builder->set_enabled($data['enabled'] ?? null);
        $builder->set_forced_delivery_channels($data['forced_delivery_channels'] ?? null);

        return $builder->save();
    }

    /**
     * A helper function to create an overridden notification at lower context.
     * The parameter array $overridden_data should be similar to the one from
     * {@see generator::create_notification_preference()}.
     *
     * Note that we context's id of overridden must not but the same as the preference that
     * we are trying to override from.
     *
     * The attribute 'title' from $overridden_data will be ignored from this function.
     *
     * @param notification_preference $preference
     * @param extended_context        $extended_context
     * @param array                   $overridden_data
     * @return notification_preference
     */
    public function create_overridden_notification_preference(
        notification_preference $preference,
        extended_context $extended_context,
        array $overridden_data = []
    ): notification_preference {
        $current_extended_context = $preference->get_extended_context();
        if ($current_extended_context->is_same($extended_context)) {
            throw new coding_exception("Cannot create an overridden notification preference in the same context");
        }

        $record_data = [
            'notification_class_name' => $preference->get_notification_class_name(),
            'ancestor_id' => $preference->get_ancestor_id(),
            'body' => $overridden_data['body'] ?? null,
            'subject' => $overridden_data['subject'] ?? null,
            'body_format' => $overridden_data['body_format'] ?? null,
            'subject_format' => $overridden_data['subject_format'] ?? null,
            'recipient' => $overridden_data['recipient'] ?? null,
            'enabled' => $overridden_data['enabled'] ?? null,
            'forced_delivery_channels' => $overridden_data['forced_delivery_channels'] ?? null,
            'recipients' => $overridden_data['recipients'] ?? null
        ];

        if (!$preference->has_parent()) {
            // The preference that we are trying to override is sitting at top.
            $record_data['ancestor_id'] = $preference->get_id();
        }

        return $this->create_notification_preference(
            $preference->get_resolver_class_name(),
            $extended_context,
            $record_data
        );
    }

    /**
     * @param string $resolver_class_name
     * @param string $component
     */
    public function add_notifiable_event_resolver(string $resolver_class_name, string $component = 'totara_notification'): void {
        notifiable_event_resolver_factory::load_map();
        $cache = notifiable_event_resolver_factory::get_cache_loader();

        $map = $cache->get(notifiable_event_resolver_factory::MAP_KEY, MUST_EXIST);
        if (!isset($map[$component])) {
            $map[$component] = [];
        }

        $map[$component][] = $resolver_class_name;
        $cache->set(
            notifiable_event_resolver_factory::MAP_KEY,
            $map
        );
    }

    /**
     * Purging the notifiable events within the system. If $component is provided,
     * then we are purging for specific component only. Otherwise all the resovlers.
     *
     * @param string|null $component
     * @return void
     */
    public function purge_notifiable_event_resolvers(?string $component = null): void {
        notifiable_event_resolver_factory::load_map();
        $cache = notifiable_event_resolver_factory::get_cache_loader();

        $map = $cache->get(notifiable_event_resolver_factory::MAP_KEY, MUST_EXIST);
        if (empty($component)) {
            $map = [];
        } else {
            // Purge for specific $component only
            $map[$component] = [];
        }

        $cache->set(
            notifiable_event_resolver_factory::MAP_KEY,
            $map
        );
    }

    /**
     * @param string $you_are_saying
     * @return lang_string
     */
    public function give_my_mock_lang_string(string $you_are_saying): lang_string {
        if (!class_exists('totara_notification_mock_lang_string')) {
            $fixture_director = self::fixtures_location();
            require_once("{$fixture_director}/totara_notification_mock_lang_string.php");
        }

        return new totara_notification_mock_lang_string($you_are_saying);
    }

    /**
     * @return void
     */
    public function include_mock_notifiable_event(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_notifiable_event.php");
    }

    /**
     * @return totara_notification_test_progress_trace
     */
    public function get_test_progress_trace(): totara_notification_test_progress_trace {
        if (!class_exists('totara_notification_test_progress_trace')) {
            $fixture_path = self::fixtures_location();
            require_once("{$fixture_path}/totara_notification_test_progress_trace.php");
        }

        return new totara_notification_test_progress_trace();
    }

    /**
     * @return void
     */
    public function include_mock_built_in_notification(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_built_in_notification.php");
    }

    /**
     * @return void
     */
    public function include_mock_additional_criteria_notification(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_additional_criteria_notification.php");
    }

    /**
     * @return void
     */
    public function include_real_mock_lang_string(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_real_mock_lang_string.php");
    }

    /**
     * @return void
     */
    public function include_mock_notifiable_event_resolver(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_notifiable_event_resolver.php");
    }

    /**
     * @return void
     */
    public function include_mock_additional_criteria_resolver(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_additional_criteria_resolver.php");
    }

    /**
     * @return void
     */
    public function include_mock_single_placeholder(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_single_placeholder.php");
    }

    /**
     * @return void
     */
    public function include_mock_invalid_placeholder(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_invalid_placeholder.php");
    }

    /**
     * @return void
     */
    public function include_mock_collection_placeholder(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_collection_placeholder.php");
    }

    /**
     * @return void
     */
    public function include_mock_recipient(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_recipient.php");
    }

    /**
     * @return void
     */
    public function include_mock_virtual_recipient(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_virtual_recipient.php");
    }

    /**
     * @return void
     */
    public function include_mock_owner(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_owner.php");
    }

    /**
     * @return void
     */
    public function include_mock_scheduled_aware_notifiable_event_resolver(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_scheduled_aware_event_resolver.php");
    }

    /**
     * @return void
     */
    public function include_mock_scheduled_event_with_on_event(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_scheduled_event_with_on_event.php");
    }

    /**
     * @return void
     */
    public function include_mock_scheduled_event_with_on_event_resolver(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_scheduled_event_with_on_event_resolver.php");
    }

    /**
     * @return void
     */
    public function include_mock_scheduled_built_in_notification(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_scheduled_built_in_notification.php");
    }

    /**
     * @return void
     */
    public function include_invalid_notifiable_event_resolver(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_invalid_notifiable_event_resolver.php");
    }

    /**
     * @return void
     */
    public function include_mock_delivery_channels(): void {
        $fixture_directory = self::fixtures_location();
        require_once("{$fixture_directory}/totara_notification_mock_delivery_channel.php");
        require_once("{$fixture_directory}/totara_notification_mock_delivery_channel_second.php");
        require_once("{$fixture_directory}/totara_notification_mock_delivery_channel_third.php");
    }

    /**
     * @param lang_string $body
     * @return void
     */
    public function add_body_to_mock_built_in_notification(lang_string $body): void {
        $this->include_mock_built_in_notification();
        totara_notification_mock_built_in_notification::set_default_body($body);
    }

    /**
     * @param string $body
     * @return void
     */
    public function add_string_body_to_mock_built_in_notification(string $body): void {
        $lang_string = $this->give_my_mock_lang_string($body);
        $this->add_body_to_mock_built_in_notification($lang_string);
    }

    /**
     * @param lang_string $subject
     * @return void
     */
    public function add_subject_to_mock_built_in_notification(lang_string $subject): void {
        $this->include_mock_built_in_notification();
        totara_notification_mock_built_in_notification::set_default_subject($subject);
    }

    /**
     * @param string $subject
     * @return void
     */
    public function add_string_subject_to_mock_built_in_notification(string $subject): void {
        $lang_string = $this->give_my_mock_lang_string($subject);
        $this->add_subject_to_mock_built_in_notification($lang_string);
    }

    /**
     * Adding the list of recipient ids to the mock notifiable event resolver.
     *
     * @param array $recipient_ids
     * @return void
     */
    public function add_mock_recipient_ids_to_resolver(array $recipient_ids): void {
        $callback = function () use ($recipient_ids) {
            return $recipient_ids;
        };

        $this->include_mock_notifiable_event_resolver();
        totara_notification_mock_notifiable_event_resolver::set_recipient_ids_resolver($callback);
    }

    /**
     * @param process_notification_queue_task $task
     * @param int                             $due_time
     *
     * @return void
     */
    public function set_due_time_of_process_notification_task(process_notification_queue_task $task,
                                                              int $due_time): void {
        $reflection_class = new ReflectionClass($task);

        /** @see process_notification_queue_task::$due_time */
        $property = $reflection_class->getProperty('due_time');
        $property->setAccessible(true);

        $property->setValue($task, $due_time);
        $property->setAccessible(false);
    }

    /**
     * Remove the built_in notification classes from the factory, and
     * remove all the preference records from database.
     *
     * Note - please do not call to this function outside of the unit tests.
     *
     * @return void
     */
    public function purge_built_in_notifications(): void {
        global $DB;
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new coding_exception('Cannot execute the function out of unit test environment');
        }

        $reflection_class = new ReflectionClass(built_in_notification_factory::class);
        $method = $reflection_class->getMethod('get_map');

        // Reset the maps to empty values.
        $method->setAccessible(true);
        $map = $method->invoke(null);
        foreach ($map as $component => $items) {
            $map[$component] = [];
        }

        $static_map = $reflection_class->getProperty('built_in_notification_classes');
        $static_map->setAccessible(true);
        $static_map->setValue($map);

        // Reset the variable and function to not be accessible.
        $static_map->setAccessible(false);
        $method->setAccessible(false);

        // Delete everything from the database.
        $DB->execute('TRUNCATE TABLE "ttr_notification_preference"');
    }

    /**
     * @param string $capability
     * @param int[]  $context_levels
     *
     * @return void
     */
    public function add_extra_capability(string $capability, array $context_levels): void {
        capability_factory::load_map();

        $cache = capability_factory::get_cache_loader();
        $map = $cache->get(capability_factory::MAP_KEY);

        foreach ($context_levels as $context_level) {
            if (!isset($map[$context_level])) {
                $map[$context_level] = [];
            }

            $map[$context_level][] = $capability;
        }

        $cache->set(capability_factory::MAP_KEY, $map);
    }
}