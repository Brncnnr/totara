<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package core
 * @category test
 */

use core\entity\user;

/**
 * Class core_orm_entity_user_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_entity_user_testcase extends advanced_testcase {

    private $user_1;

    private $user_2;

    private $user_1_entity;

    private $user_2_entity;

    protected function setUp(): void {
        parent::setUp();
        $this->user_1 = self::getDataGenerator()->create_user([
            'firstname' => 'Samuel',
            'middlename' => 'Leroy',
            'lastname' => 'Jackson',
        ]);
        $this->user_2 = self::getDataGenerator()->create_user([
            'firstname' => 'Ewan',
            'middlename' => 'Gordon',
            'lastname' => 'McGregor',
        ]);
        $this->user_1_entity = new user($this->user_1);
        $this->user_2_entity = new user($this->user_2);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->user_1 = $this->user_2 = $this->user_1_entity = $this->user_2_entity = null;
    }

    /**
     * @covers \core\entity\user::logged_in
     */
    public function test_get_logged_in(): void {
        self::setUser($this->user_1);
        $this->assertEquals($this->user_1_entity->id, user::logged_in()->id);

        // Make sure the expected properties are there
        $this->assertEquals($this->user_1->firstname, user::logged_in()->firstname);
        $this->assertEquals($this->user_1->lastname, user::logged_in()->lastname);
        $this->assertEquals($this->user_1->email, user::logged_in()->email);

        self::setUser($this->user_2);
        $this->assertEquals($this->user_2_entity->id, user::logged_in()->id);

        self::setUser(null);
        $this->assertEquals(null, user::logged_in());
    }

    /**
     * @covers \core\entity\user::is_logged_in
     */
    public function test_is_logged_in(): void {
        self::setUser($this->user_1);
        $this->assertTrue($this->user_1_entity->is_logged_in());
        $this->assertTrue($this->user_1_entity->is_logged_in);
        $this->assertFalse($this->user_2_entity->is_logged_in());
        $this->assertFalse($this->user_2_entity->is_logged_in);

        self::setUser($this->user_2);
        $this->assertFalse($this->user_1_entity->is_logged_in());
        $this->assertFalse($this->user_1_entity->is_logged_in);
        $this->assertTrue($this->user_2_entity->is_logged_in());
        $this->assertTrue($this->user_2_entity->is_logged_in);

        self::setUser(null);
        $this->assertFalse($this->user_1_entity->is_logged_in());
        $this->assertFalse($this->user_1_entity->is_logged_in);
        $this->assertFalse($this->user_2_entity->is_logged_in());
        $this->assertFalse($this->user_2_entity->is_logged_in);
    }

    /**
     * @covers \core\entity\user::get_fullname_attribute
     */
    public function test_get_fullname_attribute(): void {
        $this->assertNotEquals(fullname($this->user_1), fullname($this->user_2));
        $this->assertEquals(fullname($this->user_1), $this->user_1_entity->fullname);
        $this->assertEquals(fullname($this->user_2), $this->user_2_entity->fullname);
        $this->assertNotEquals($this->user_1_entity->fullname, $this->user_2_entity->fullname);
    }

}
