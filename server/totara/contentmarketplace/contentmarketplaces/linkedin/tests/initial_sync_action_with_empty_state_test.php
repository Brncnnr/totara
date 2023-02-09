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
use contentmarketplace_linkedin\sync_action\sync_classifications;
use contentmarketplace_linkedin\sync_action\sync_learning_asset;
use contentmarketplace_linkedin\testing\generator;
use core_phpunit\testcase;
use totara_contentmarketplace\token\token;
use totara_core\http\clients\simple_mock_client;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_initial_sync_action_with_empty_state_testcase extends testcase {

    /**
     * @return void
     */
    public function test_initial_sync_without_proper_configuration(): void {
        $client = new simple_mock_client();
        $sync = new sync_learning_asset(true);
        $sync->set_api_client($client);
        $sync->set_asset_types(constants::ASSET_TYPE_COURSE);
        self::assertTrue($sync->is_skipped());
    }

    /**
     * @return void
     */
    public function test_initial_sync_classification_without_proper_configuration(): void {
        $client = new simple_mock_client();
        $action = new sync_classifications(true, new null_progress_trace(), $client);
        $action->set_classification_types(constants::CLASSIFICATION_TYPE_TOPIC);
        self::assertTrue($action->is_skipped());
    }

}