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
 * @package contentmarketplace_linkedin
 */
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use contentmarketplace_linkedin\oauth\oauth_2;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_oauth_2_testcase extends testcase {
    /**
     * @return void
     */
    public function test_instantiate_oauth_with_no_client_id(): void {
        $generator = generator::instance();
        $generator->set_config_client_secret('clientsecret');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Either the client's secret or client's id was not set up correctly");

        oauth_2::create_from_config();
    }

    /**
     * @return void
     */
    public function test_instantiate_oauth_with_no_client_secret(): void {
        $generator = generator::instance();
        $generator->set_config_client_id('clientid');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Either the client's secret or client's id was not set up correctly");

        oauth_2::create_from_config();
    }
}