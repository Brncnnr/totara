<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
use core_user\profile\display_setting;

/**
 * Class core_orm_entity_user_repository_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_entity_user_repository_testcase extends advanced_testcase {

    private $user_1;

    private $user_2;

    protected function setUp(): void {
        parent::setUp();
        $this->user_1 = self::getDataGenerator()->create_user([
            'username' => 'sljackson',
            'firstname' => 'Samuel',
            'middlename' => 'Leroy',
            'lastname' => 'Jackson',
            'country' => 'US',
            'department' => 'Makeup',
        ]);
        $this->user_2 = self::getDataGenerator()->create_user([
            'username' => 'ewgreg',
            'firstname' => 'Ewan',
            'middlename' => 'Gordon',
            'lastname' => 'Leroy',
            'country' => 'UK',
            'department' => 'Lighting',
        ]);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->user_1 = $this->user_2 = null;
    }

    /**
     * This is just a sanity check for the repository specifically,
     * The actual logic is thoroughly tested in core_datalib_testcase.
     */
    public function test_filter_users_by_fullname(): void {
        // 'On' is found in user_1's lastname, and in user_2's middlename.
        $query_string = 'On';

        $simple_query = user::repository()
            ->filter_by_full_name($query_string);

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(1, $simple_query_result);
        $this->assertEquals($this->user_1->id, $simple_query_result[0]->id);

        $complex_query = user::repository()
            ->as('auser')
            ->select_full_name_fields()
            ->add_select_raw('COUNT(prefs.id) AS preferences_count')
            ->left_join(['user_preferences', 'prefs'], 'id', 'userid')
            ->group_by('id')
            ->filter_by_full_name($query_string);

        $complex_query_result = $complex_query->get()->all();
        $this->assertCount(1, $complex_query_result);
        $this->assertEquals($this->user_1->id, $complex_query_result[0]->id);
        $this->assertEquals(0, $complex_query_result[0]->preferences_count);
    }

    /**
     * Check to ensure that no debugging message displays when using select_full_name_fields
     * with the \core\entity\user fullname property.
     */
    public function test_user_fullname_select(): void {
        set_config('fullnamedisplay', 'firstname lastname');

        $users = user::repository()
            ->select_full_name_fields()
            ->where('lastname', 'Leroy')
            ->get()
            ->all();

        $this->assertCount(1, $users);
        $this->assertSame('Ewan Leroy', $users[0]->fullname);

        set_config('fullnamedisplay', 'firstname middlename lastname');

        $users = user::repository()
            ->select_full_name_fields()
            ->where('lastname', 'Jackson')
            ->get()
            ->all();

        $this->assertCount(1, $users);
        $this->assertSame('Samuel Leroy Jackson', $users[0]->fullname);
    }

    /**
     * This is just a sanity check for the repository specifically,
     * The actual logic is thoroughly tested in core_datalib_testcase.
     */
    public function test_filter_users_by_fullname_with_different_fullnamedisplay_config(): void {
        set_config('fullnamedisplay', 'firstname middlename lastname');

        $simple_query = user::repository()
            ->filter_by_full_name('Leroy');

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(2, $simple_query_result);

        $simple_query = user::repository()
            ->filter_by_full_name('Samuel Leroy');

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(1, $simple_query_result);
        $this->assertEquals($this->user_1->id, $simple_query_result[0]->id);

        $simple_query = user::repository()
            ->filter_by_full_name('Samuel Jackson');

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(0, $simple_query_result);


        set_config('fullnamedisplay', 'middlename');

        $simple_query = user::repository()
            ->filter_by_full_name('Leroy');

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(1, $simple_query_result);
        $this->assertEquals($this->user_1->id, $simple_query_result[0]->id);

        // The result should not depend on upper lower case
        $simple_query = user::repository()
            ->filter_by_full_name('leroY');

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(1, $simple_query_result);
        $this->assertEquals($this->user_1->id, $simple_query_result[0]->id);

        // Searching by firstname only should get no result
        $simple_query = user::repository()
            ->filter_by_full_name('Samuel');

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(0, $simple_query_result);

        set_config('fullnamedisplay', 'lastname, firstname');

        $simple_query = user::repository()
            ->filter_by_full_name('Jackson Samuel');

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(1, $simple_query_result);
        $this->assertEquals($this->user_1->id, $simple_query_result[0]->id);

        // A comma in the result is not supported
        $simple_query = user::repository()
            ->filter_by_full_name('Jackson, Samuel');

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(0, $simple_query_result);
    }

    /**
     * This is just a sanity check for the repository specifically,
     * The actual logic is thoroughly tested in core_datalib_testcase.
     */
    public function test_order_users_by_fullname(): void {
        $simple_query = user::repository()
            ->where_in('id', [$this->user_1->id, $this->user_2->id])
            ->order_by_full_name();

        $simple_query_result = $simple_query->get()->all();
        $this->assertCount(2, $simple_query_result);
        $this->assertEquals($this->user_2->id, $simple_query_result[0]->id);
        $this->assertEquals($this->user_1->id, $simple_query_result[1]->id);

        $complex_query = user::repository()
            ->as('auser')
            ->select_full_name_fields()
            ->add_select_raw('COUNT(prefs.id) AS preferences_count')
            ->left_join(['user_preferences', 'prefs'], 'id', 'userid')
            ->group_by(['id', 'firstname', 'lastname'])
            ->filter_by_full_name('e')
            ->where_in('id', [$this->user_1->id, $this->user_2->id])
            ->order_by_full_name();

        $complex_query_result = $complex_query->get()->all();
        $this->assertCount(2, $complex_query_result);
        $this->assertEquals($this->user_2->id, $complex_query_result[0]->id);
        $this->assertEquals($this->user_1->id, $complex_query_result[1]->id);
        $this->assertEquals(0, $complex_query_result[0]->preferences_count);
        $this->assertEquals(0, $complex_query_result[1]->preferences_count);
    }

    /**
     * This is just a sanity check for the repository specifically,
     * The actual logic is thoroughly tested in core_outputcomponents_testcase.
     */
    public function test_select_user_picture_fields(): void {
        $simple_query = user::repository()
            ->where('id', $this->user_1->id)
            ->select_user_picture_fields();

        $simple_query_result = $simple_query->get()->all();
        $this->assertEquals($this->user_1->id, $simple_query_result[0]->id);
        $this->assertEquals($this->user_1->picture, $simple_query_result[0]->picture);

        $complex_query = user::repository()
            ->as('auser')
            ->select_user_picture_fields()
            ->add_select_raw('COUNT(prefs.id) AS preferences_count')
            ->left_join(['user_preferences', 'prefs'], 'id', 'userid')
            ->group_by(['id', 'firstname', 'lastname'])
            ->filter_by_full_name('e')
            ->where('id', $this->user_1->id);

        $complex_query_result = $complex_query->get()->all();
        $this->assertEquals($this->user_1->id, $complex_query_result[0]->id);
        $this->assertEquals($this->user_1->picture, $complex_query_result[0]->picture);
        $this->assertEquals(0, $complex_query_result[0]->preferences_count);
    }

    /**
     * Test that select_profile_summary_card_fields() selects only the fields defined by an admin on the user profile summary card
     * config page.
     *
     * @see /server/user/profile_summary_card_edit.php
     */
    public function test_select_profile_summary_card_fields(): void {
        // By default user picture is included, and there are 2 fields for fullname and department.
        $query1 = user::repository()
            ->where('id', $this->user_1->id)
            ->select_profile_summary_card_fields()
            ->get()
            ->all();
        $this->assertEquals($this->user_1->id, $query1[0]->id);
        $this->assertEquals($this->user_1->firstname, $query1[0]->firstname);
        $this->assertEquals($this->user_1->lastname, $query1[0]->lastname);
        $this->assertEquals($this->user_1->department, $query1[0]->department);
        $this->assertEquals($this->user_1->picture, $query1[0]->picture);
        $this->assertObjectNotHasAttribute('username', $query1[0]);
        $this->assertObjectNotHasAttribute('country', $query1[0]);

        // Don't include picture if it is disabled in settings.
        display_setting::save_display_user_profile(false);
        $query2 = user::repository()
            ->where('id', $this->user_1->id)
            ->select_profile_summary_card_fields()
            ->get()
            ->all();
        $this->assertEquals($this->user_1->id, $query2[0]->id);
        $this->assertEquals($this->user_1->department, $query2[0]->department);
        $this->assertObjectNotHasAttribute('picture', $query2[0]);
        $this->assertObjectNotHasAttribute('imagealt', $query2[0]);
        $this->assertObjectNotHasAttribute('username', $query2[0]);
        $this->assertObjectNotHasAttribute('country', $query2[0]);

        // Don't include picture if we specify it in the method param.
        display_setting::save_display_user_profile(true);
        $query3 = user::repository()
            ->where('id', $this->user_1->id)
            ->select_profile_summary_card_fields(false)
            ->get()
            ->all();
        $this->assertEquals($this->user_1->id, $query3[0]->id);
        $this->assertEquals($this->user_1->department, $query3[0]->department);
        $this->assertObjectNotHasAttribute('picture', $query3[0]);
        $this->assertObjectNotHasAttribute('imagealt', $query3[0]);
        $this->assertObjectNotHasAttribute('username', $query3[0]);
        $this->assertObjectNotHasAttribute('country', $query3[0]);

        // Change what fields will be selected
        display_setting::save_display_fields(['username', 'country', '', '']);
        $query4 = user::repository()
            ->where('id', $this->user_1->id)
            ->select_profile_summary_card_fields(false)
            ->get()
            ->all();
        $this->assertEquals($this->user_1->id, $query4[0]->id);
        $this->assertEquals($this->user_1->username, $query4[0]->username);
        $this->assertEquals($this->user_1->country, $query4[0]->country);
        $this->assertObjectNotHasAttribute('firstname', $query4[0]);
        $this->assertObjectNotHasAttribute('lastname', $query4[0]);
        $this->assertObjectNotHasAttribute('picture', $query4[0]);
        $this->assertObjectNotHasAttribute('imagealt', $query4[0]);
        $this->assertObjectNotHasAttribute('department', $query4[0]);

        // Complex query where we select the maximum amount profile display fields.
        display_setting::save_display_fields(['fullname', 'username', 'department', 'country']);
        $complex_query = user::repository()
            ->as('auser')
            ->select_profile_summary_card_fields()
            ->add_select_raw('COUNT(prefs.id) AS preferences_count')
            ->left_join(['user_preferences', 'prefs'], 'id', 'userid')
            ->group_by(['id', 'firstname', 'lastname'])
            ->filter_by_full_name('e')
            ->where('id', $this->user_1->id)
            ->get()
            ->all();
        $this->assertEquals($this->user_1->id, $complex_query[0]->id);
        $this->assertEquals($this->user_1->picture, $complex_query[0]->picture);
        $this->assertEquals($this->user_1->firstname, $complex_query[0]->firstname);
        $this->assertEquals($this->user_1->lastname, $complex_query[0]->lastname);
        $this->assertEquals($this->user_1->department, $complex_query[0]->department);
        $this->assertEquals($this->user_1->username, $complex_query[0]->username);
        $this->assertEquals($this->user_1->country, $complex_query[0]->country);
        $this->assertEquals(0, $complex_query[0]->preferences_count);
    }

}
