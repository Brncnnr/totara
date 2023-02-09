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
 * @package weka_notification_placeholder
 */

use core\editor\variant_name;
use totara_webapi\phpunit\webapi_phpunit_helper;
use weka_notification_placeholder\extension;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use core_phpunit\testcase;

class weka_notification_placeholder_webapi_get_editor_with_extra_data_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_editor_with_extra_extension(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event();

        $this->setAdminUser();
        $result = $this->execute_graphql_operation(
            'core_editor',
            [
                'context_id' => context_system::instance()->id,
                'variant_name' => variant_name::DESCRIPTION,
                'format' => FORMAT_JSON_EDITOR,
                'extra_extensions' => json_encode([
                    [
                        'name' => extension::get_extension_name(),
                        'options' => ['resolver_class_name' => mock_resolver::class]
                    ]
                ]),
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertArrayHasKey('editor', $result->data);

        $editor_data = $result->data['editor'];
        self::assertIsArray($editor_data);
        self::assertArrayHasKey('variant', $editor_data);

        $variant_data = $editor_data['variant'];
        self::assertIsArray($variant_data);
        self::assertArrayHasKey('options', $variant_data);

        $options = $variant_data['options'];
        self::assertIsString($options);

        // Make sure that the extension name and the event_class_name
        // appears in the options.
        self::assertStringContainsString(
            extension::get_extension_name(),
            $options
        );

        self::assertStringContainsString(
            mock_resolver::class,
            $options
        );
    }
}