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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */

use core\format;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

class core_webapi_resolver_type_category_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_category_type_name_with_exception(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        // Login as user.
        $this->setUser($user);
        $cat = $generator->create_category();

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('You are not allowed to view category name');
        $this->resolve_graphql_type(
            'core_category',
            'name',
            $cat,
            ['format' => format::FORMAT_RAW]
        );
    }

    /**
     * @return void
     */
    public function test_category_type_name_with_admin(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $cat = $generator->create_category();
        self::assertEquals(
            $cat->name,
            $this->resolve_graphql_type(
                'core_category',
                'name',
                $cat,
                ['format' => format::FORMAT_RAW]
            )
        );
    }

}