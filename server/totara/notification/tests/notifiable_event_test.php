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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_notification
 */

use totara_notification\testing\generator as notification_generator;
use totara_notification_mock_notifiable_event_resolver as mock_event_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_phpunit\testcase;
use totara_notification\model\notifiable_event_preference as notifiable_event_preference_model;
use totara_notification\entity\notifiable_event_preference as notifiable_event_preference_entity;
use totara_core\extended_context;
use totara_notification\local\helper;

class totara_notification_notifiable_event_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_notifiable_event_with_empty_recipients(): void {
        $generator = notification_generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        // Set recipients to an empty array.
        mock_event_resolver::set_notification_available_recipients([]);

        // Run the mutation.
        try {
            $this->resolve_graphql_type(
                'totara_notification_event_resolver',
                'recipients',
                totara_notification_mock_notifiable_event_resolver::class
            );
            $this->fail('Exception is expected but not thrown');
        } catch (Exception $e) {
            $this->assertDebuggingCalled(['Do not use event_resolver::resolve any more, use event_resolver_v2::resolve instead']);
            $this->assertEquals(
                $e->getMessage(),
                'Coding error detected, it must be fixed by a programmer: ' .
                'Class totara_notification_mock_notifiable_event_resolver need to define recipient'
            );
        }
    }

    public function test_notifiable_event_resolver_enabled_status(): void {
        $generator = notification_generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        // Out of the box we should be able to get default status.
        $this->assertEquals(true, mock_event_resolver::get_default_enabled());

        // Now we can test with an instantiated object - create with 'enabled' null.
        $extended_context = extended_context::make_system();
        $entity = new notifiable_event_preference_entity();
        $entity->context_id = $extended_context->get_context_id();
        $entity->resolver_class_name = mock_event_resolver::class;
        $entity->component = $extended_context->get_component();
        $entity->area = $extended_context->get_area();
        $entity->item_id = $extended_context->get_item_id();
        $entity->default_delivery_channels = ",email,";
        $entity->enabled = null;
        $entity->save();

        // At entity level, we should get the truth.
        $model = new notifiable_event_preference_model($entity);
        $this->assertEquals(null, $model->get_enabled());

        // At render or queue processing time, we should get what the truth means:
        $enabled = !helper::is_resolver_disabled_by_any_context($entity->resolver_class_name, $extended_context);
        $this->assertEquals(true, $enabled);

        // Explicitly set false.
        $entity->set_attribute('enabled', false);
        $entity->save();
        $enabled = !helper::is_resolver_disabled_by_any_context($entity->resolver_class_name, $extended_context);
        $this->assertEquals(false, $enabled);

        // Explicitly set true.
        $entity->set_attribute('enabled', true);
        $entity->save();
        $enabled = !helper::is_resolver_disabled_by_any_context($entity->resolver_class_name, $extended_context);
        $this->assertEquals(true, $enabled);

        // Explicitly set null.
        $entity->set_attribute('enabled', null);
        $entity->save();
        $enabled = !helper::is_resolver_disabled_by_any_context($entity->resolver_class_name, $extended_context);
        $this->assertEquals(true, $enabled);
    }
}