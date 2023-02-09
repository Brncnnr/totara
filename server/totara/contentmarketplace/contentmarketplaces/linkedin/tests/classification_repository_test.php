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
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use contentmarketplace_linkedin\entity\classification;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_classification_repository_testcase extends testcase {
    /**
     * @return void
     */
    public function test_find_by_urn(): void {
        $generator = generator::instance();
        $classification = $generator->create_classification('urn:li:organization:469');

        $repository = classification::repository();
        $found_entity = $repository->find_by_urn('urn:li:organization:469');

        self::assertNotNull($found_entity);
        self::assertEquals($classification->id, $found_entity->id);

        $not_found_entity = $repository->find_by_urn('urn:li:organization:458');
        self::assertNull($not_found_entity);
    }
}