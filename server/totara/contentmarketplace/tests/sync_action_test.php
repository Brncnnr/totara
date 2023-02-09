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
 * @package totara_contentmarketplace
 */
use core_phpunit\testcase;
use totara_contentmarketplace\sync;
use totara_core\http\clients\simple_mock_client;
use totara_contentmarketplace\plugininfo\contentmarketplace;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_sync_action_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_sync_action_class(): void {
        $sync = new sync(new simple_mock_client());
        $ref_class = new ReflectionClass($sync);

        $property = $ref_class->getProperty('sync_action_classes');
        $property->setAccessible(true);

        self::assertEmpty($property->getValue($sync));

        // Populate records.
        $method = $ref_class->getMethod('load_sync_action_classes');
        $method->setAccessible(true);

        // After the invoke, the property would still hold an empty array of classes. Because there are no plugins
        // enabled at the initial.
        $method->invoke($sync);
        self::assertEmpty($property->getValue($sync));

        // Enable one plugin.
        $plugin = contentmarketplace::plugin('linkedin');
        $plugin->enable();

        $method->invoke($sync);
        $value = $property->getValue($sync);

        self::assertNotEmpty($value);
        self::assertCount(2, $value);
    }
}