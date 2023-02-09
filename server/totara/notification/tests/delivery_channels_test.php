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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\entity\notifiable_event_preference as entity;
use totara_notification\loader\delivery_channel_loader;
use totara_notification\manager\notification_queue_manager;
use totara_notification\model\notifiable_event_preference as model;
use totara_notification\testing\generator;
use totara_notification_mock_delivery_channel as mock_channel_first;
use totara_notification_mock_delivery_channel_second as mock_channel_second;
use totara_notification_mock_delivery_channel_third as mock_channel_third;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;

/**
 * @group totara_notification
 */
class totara_notification_delivery_channels_testcase extends testcase {
    /**
     * @var model
     */
    protected $model;

    /**
     * @var entity
     */
    protected $entity;

    /**
     * Test delivery channel values stored in the entity are converted back to objects.
     */
    public function test_default_delivery_channels_convert_from_entity(): void {
        // Single entry
        $this->entity->default_delivery_channels = ',second,';
        $results = $this->model->default_delivery_channels;

        self::assertIsArray($results);
        self::assertCount(3, $results);

        // Check that all are disabled except 'second''
        foreach ($results as $component => $result) {
            if ($component === 'second') {
                self::assertTrue($result->is_enabled);
            } else {
                self::assertFalse($result->is_enabled, $component);
            }
        }

        // Multiple enabled
        $this->entity->default_delivery_channels = ',second,third,';
        $results = $this->model->default_delivery_channels;

        self::assertIsArray($results);
        self::assertCount(3, $results);

        // Check that all are disabled except 'second' & 'third'
        foreach ($results as $component => $result) {
            if ($component === 'second' || $component === 'third') {
                self::assertTrue($result->is_enabled);
            } else {
                self::assertFalse($result->is_enabled);
            }
        }

        // Default entry
        mock_resolver::set_notification_default_delivery_channels(['second']);
        $default_channels = delivery_channel_loader::get_for_event_resolver(mock_resolver::class);
        $this->entity->default_delivery_channels = null;
        $results = $this->model->default_delivery_channels;

        self::assertIsArray($results);
        self::assertCount(3, $results);

        foreach ($results as $component => $result) {
            self::assertArrayHasKey($component, $default_channels);
            $default = $default_channels[$component];
            self::assertEquals($default->is_enabled, $result->is_enabled);
        }

        // Check that an override is processed
        $this->entity->default_delivery_channels = ',first,';
        $results = $this->model->default_delivery_channels;

        // Check that all are disabled except 'first'
        foreach ($results as $component => $result) {
            if ($component === 'first') {
                self::assertTrue($result->is_enabled);
            } else {
                self::assertFalse($result->is_enabled);
            }
        }
    }

    /**
     * Test delivery channel values given to the model are converted to the string in the entity
     */
    public function test_default_delivery_channels_convert_from_model(): void {
        // Single entry
        $channels = delivery_channel_loader::get_defaults();
        foreach ($channels as $component => $channel) {
            $channel->set_enabled($component === 'third');
        }
        $this->model->set_default_delivery_channels($channels);
        self::assertSame(',third,', $this->entity->default_delivery_channels);

        // Multiple entries
        $channels = delivery_channel_loader::get_defaults();
        foreach ($channels as $component => $channel) {
            $channel->set_enabled($component === 'third' || $component === 'first');
        }
        $this->model->set_default_delivery_channels($channels);
        self::assertSame(',first,third,', $this->entity->default_delivery_channels);

        // Default entry
        $this->model->set_default_delivery_channels(null);
        self::assertNull($this->entity->default_delivery_channels);
    }

    /**
     * Asserts that if a message output is disabled, then the matching channel will not be included.
     */
    public function test_delivery_channel_list_excludes_disabled_outputs(): void {
        mock_resolver::set_notification_default_delivery_channels(['first', 'second', 'third']);
        // Check that all channels appear
        $this->set_enabled_message_outputs(['first', 'second', 'third']);
        $channels = delivery_channel_loader::get_for_event_resolver(mock_resolver::class);
        self::assertArrayHasKey('first', $channels);
        self::assertArrayHasKey('second', $channels);
        self::assertArrayHasKey('third', $channels);

        // Disable 'second' at the output level by excluding it
        $this->set_enabled_message_outputs(['first', 'third']);
        $channels = delivery_channel_loader::get_for_event_resolver(mock_resolver::class);
        self::assertArrayHasKey('first', $channels);
        self::assertArrayNotHasKey('second', $channels);
        self::assertArrayHasKey('third', $channels);

        // 'third' is now going to be a child of 'second', so with second disabled, third should also not appear.
        mock_channel_third::set_attribute('parent', 'second');
        $channels = delivery_channel_loader::get_for_event_resolver(mock_resolver::class);
        self::assertArrayHasKey('first', $channels);
        self::assertArrayNotHasKey('second', $channels);
        self::assertArrayNotHasKey('third', $channels);

        // But if second is enabled, third should reappear too
        $this->set_enabled_message_outputs(['first', 'second', 'third']);
        $channels = delivery_channel_loader::get_for_event_resolver(mock_resolver::class);
        self::assertArrayHasKey('first', $channels);
        self::assertArrayHasKey('second', $channels);
        self::assertArrayHasKey('third', $channels);

        // Third can be disabled independently of second
        $this->set_enabled_message_outputs(['first', 'second']);
        $channels = delivery_channel_loader::get_for_event_resolver(mock_resolver::class);
        self::assertArrayHasKey('first', $channels);
        self::assertArrayHasKey('second', $channels);
        self::assertArrayNotHasKey('third', $channels);
    }

    /**
     * Test the message queue is filtered by the resolver's set delivery channels
     */
    public function test_expected_message_outputs_filtered(): void {
        $user = $this->getDataGenerator()->create_user();
        
        $mock_message_providers = [
            'first' => [],
            'second' => [],
            'third' => [],
        ];

        mock_resolver::set_notification_default_delivery_channels(['first', 'second']);

        // Test by loading the get_active_message_processors, and check what's returned
        $resolver = new mock_resolver([]);

        $cache = new ReflectionProperty(delivery_channel_loader::class, 'resolver_channels');
        $cache->setAccessible(true);
        delivery_channel_loader::reset();

        // As this method is private, to test it we'll need to reflect our way inside. It also means
        // we can pass in an array of mock message_output data, as we only want to test our filter works.
        $method = new ReflectionMethod(
            notification_queue_manager::class,
            'filter_message_processors_by_delivery_channel'
        );
        $method->setAccessible(true);
        $results = $method->invoke(new notification_queue_manager(), $user->id, $resolver, $mock_message_providers);

        self::assertCount(2, $results);
        self::assertArrayHasKey('first', $results);
        self::assertArrayHasKey('second', $results);
        self::assertArrayNotHasKey('third', $results);

        $cache_value = $cache->getValue();
        self::assertNotEmpty($cache_value);

        // Run again (hitting from the cache this time)
        $results = $method->invoke(new notification_queue_manager(), $user->id, $resolver, $mock_message_providers);

        self::assertCount(2, $results);
        self::assertArrayHasKey('first', $results);
        self::assertArrayHasKey('second', $results);
        self::assertArrayNotHasKey('third', $results);

        // Disable all but 'first'
        mock_resolver::set_notification_default_delivery_channels(['first']);
        delivery_channel_loader::reset();

        $results = $method->invoke(new notification_queue_manager(), $user->id, $resolver, $mock_message_providers);

        self::assertCount(1, $results);
        self::assertArrayHasKey('first', $results);
        self::assertArrayNotHasKey('second', $results);

        $cache_value = $cache->getValue();
        self::assertNotEmpty($cache_value);

        // Run again, hitting the cache
        $results = $method->invoke(new notification_queue_manager(), $user->id, $resolver, $mock_message_providers);

        self::assertCount(1, $results);
        self::assertArrayHasKey('first', $results);
        self::assertArrayNotHasKey('second', $results);
    }

    /**
     * Test the static reading of properties works correctly.
     */
    public function test_delivery_channel_property_reads(): void {
        mock_channel_first::set_attribute('label', 'my test label');

        $channel = mock_channel_first::make(true);
        self::assertTrue($channel->is_enabled);
        self::assertEquals('my test label', $channel->label);
        self::assertNull($channel->not_a_property);

        $channel_data = $channel->to_array();
        self::assertIsArray($channel_data);
        self::assertEqualsCanonicalizing([
            'component' => 'first',
            'label' => 'my test label',
            'is_enabled' => true,
            'is_sub_delivery_channel' => false,
            'parent_component' => null,
            'display_order' => 10,
        ], $channel_data);
    }

    /**
     * Create a mock model to test against
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_delivery_channels();

        // Always reset the delivery channels back to nothing
        mock_resolver::set_notification_default_delivery_channels([]);

        $this->entity = new entity(null, false, true);
        $this->entity->resolver_class_name = mock_resolver::class;
        $this->model = model::from_entity($this->entity);

        // This lets us test the delivery channels without creating a dependency on message_* plugins
        $this->set_loader_definitions([
            mock_channel_first::class,
            mock_channel_second::class,
            mock_channel_third::class,
        ]);
        $this->set_enabled_message_outputs(['first', 'second', 'third']);
    }

    /**
     * Remove the hanging models
     */
    protected function tearDown(): void {
        $this->model = null;
        $this->entity = null;
        mock_resolver::set_notification_default_delivery_channels([]);
        mock_channel_first::clear();
        mock_channel_second::clear();
        mock_channel_third::clear();
        $this->set_loader_definitions(null);
        $this->set_enabled_message_outputs(null);
    }

    /**
     * Helper method to override the enabled message output filter - otherwise
     * our mock delivery channels will be filtered out as they're not real.
     *
     * @param array|null $enabled_outputs
     */
    private function set_enabled_message_outputs(?array $enabled_outputs): void {
        $cache_property = new ReflectionProperty(delivery_channel_loader::class, 'enabled_outputs');
        $cache_property->setAccessible(true);
        $cache_property->setValue($enabled_outputs);
    }

    /**
     * Helper method to override the channel loader when we're testing.
     * This allows us to override and inject our own mock delivery channels for testing.
     *
     * @param array|null $definitions
     */
    private function set_loader_definitions(?array $definitions): void {
        $cache_property = new ReflectionProperty(delivery_channel_loader::class, 'definitions');
        $cache_property->setAccessible(true);
        $cache_property->setValue($definitions);
    }
}