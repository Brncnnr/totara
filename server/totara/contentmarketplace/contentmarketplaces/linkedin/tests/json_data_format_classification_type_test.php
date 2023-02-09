<?php
/**
 * This file is part of Totara Core
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
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\constants;
use core_phpunit\testcase;
use contentmarketplace_linkedin\core_json\data_format\classification_type;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_json_data_format_classification_type_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validation(): void {
        $format = new classification_type();
        $types = [
            constants::CLASSIFICATION_TYPE_LIBRARY,
            constants::CLASSIFICATION_TYPE_SUBJECT,
            constants::CLASSIFICATION_TYPE_SKILL,
            constants::CLASSIFICATION_TYPE_TOPIC
        ];

        foreach ($types as $type) {
            self::assertTrue($format->validate($type));
            self::assertFalse($format->validate(strtolower($type)));
        }

        self::assertFalse($format->validate(111));
        self::assertFalse($format->validate(false));
        self::assertFalse($format->validate(true));
        self::assertFalse($format->validate('true'));
        self::assertFalse($format->validate('coco'));
    }
}