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
use contentmarketplace_linkedin\core_json\data_format\availability;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_json_data_format_availability_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validation(): void {
        $availability = new availability();

        self::assertTrue($availability->validate(constants::AVAILABILITY_AVAILABLE));
        self::assertTrue($availability->validate(constants::AVAILABILITY_RETIRED));

        self::assertFalse($availability->validate(strtolower(constants::AVAILABILITY_RETIRED)));
        self::assertFalse($availability->validate(strtolower(constants::AVAILABILITY_AVAILABLE)));

        self::assertFalse($availability->validate('cdwojis'));
        self::assertFalse($availability->validate('xsa82'));
        self::assertFalse($availability->validate(192.168));
        self::assertFalse($availability->validate(192));
    }
}