<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use totara_competency\entity;
use totara_competency\entity\assignment;
use totara_core\basket\session_basket;
use totara_core\phpunit\webservice_utils;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_assignment_action_service_testcase extends advanced_testcase {

    use webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_action_both_null() {
        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'archive',
            'basket' => null,
            'id' => null,
            'extra' => []
        ]);

        $this->assert_webservice_error($res);
        $this->assert_webservice_has_exception_message('You must supply either basket_id or assignment_id, not both of them', $res);
    }

    public function test_action_both_not_null() {
        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'archive',
            'basket' => 'bla',
            'id' => 1,
            'extra' => []
        ]);

        $this->assert_webservice_error($res);
        $this->assert_webservice_has_exception_message('You must supply either basket_id or assignment_id, not both of them', $res);
    }

    public function test_action_archive() {
        ['ass' => $assignments] = $this->generate_competencies();

        $assignment1 = new assignment($assignments[0]);
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment1->status);

        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'archive',
            'basket' => null,
            'id' => $assignment1->id,
            'extra' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([$assignment1->id], $result);

        $assignment1->refresh();

        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment1->status);
    }

    public function test_action_archive_via_basket() {
        ['ass' => $assignments] = $this->generate_competencies();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_ACTIVE;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ACTIVE;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ACTIVE;
        $assignment3->save();

        $basket = new session_basket('mytestbasket');
        $basket->add([$assignment1->id, $assignment2->id]);

        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'archive',
            'basket' => $basket->get_key(),
            'id' => null,
            'extra' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEqualsCanonicalizing([$assignment1->id, $assignment2->id], $result);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment1->status);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment2->status);
        // this one is untouched
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment3->status);
    }

    public function test_action_archive_via_basket_mix() {
        ['ass' => $assignments] = $this->generate_competencies();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_ACTIVE;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ACTIVE;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_DRAFT;
        $assignment3->save();

        $basket = new session_basket('mytestbasket');
        $basket->add([$assignment1->id, $assignment2->id, $assignment3->id]);

        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'archive',
            'basket' => $basket->get_key(),
            'id' => null,
            'extra' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);

        // assignment 3 is not in the result as it could not be archived
        $this->assertEqualsCanonicalizing([$assignment1->id, $assignment2->id], $result);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment1->status);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment2->status);
        // this one is untouched
        $this->assertEquals(assignment::STATUS_DRAFT, $assignment3->status);
    }

    public function test_unknown_action() {
        ['ass' => $assignments] = $this->generate_competencies();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'notarealaction',
            'basket' => null,
            'id' => $assignment1->id,
            'extra' => []
        ]);

        $this->assert_webservice_error($res);
        $this->assert_webservice_has_exception_message('unknown action for update webservice', $res);
    }

    public function test_action_activate() {
        ['ass' => $assignments] = $this->generate_competencies();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'activate',
            'basket' => null,
            'id' => $assignment1->id,
            'extra' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([$assignment1->id], $result);

        $assignment1->refresh();

        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment1->status);
    }

    public function test_action_activate_via_basket() {
        ['ass' => $assignments] = $this->generate_competencies();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_DRAFT;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ARCHIVED;
        $assignment3->save();

        $basket = new session_basket('mytestbasket');
        $basket->add([$assignment1->id, $assignment2->id]);

        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'activate',
            'basket' => $basket->get_key(),
            'id' => null,
            'extra' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEqualsCanonicalizing([$assignment1->id, $assignment2->id], $result);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment1->status);
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment2->status);
        // this one is untouched
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment3->status);
    }

    public function test_action_activate_via_basket_mix() {
        ['ass' => $assignments] = $this->generate_competencies();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ACTIVE;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ARCHIVED;
        $assignment3->save();

        $basket = new session_basket('mytestbasket');
        $basket->add([$assignment1->id, $assignment2->id, $assignment3->id]);

        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'activate',
            'basket' => $basket->get_key(),
            'id' => null,
            'extra' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEqualsCanonicalizing([$assignment1->id], $result);

        $assignment1->refresh();
        $assignment2->refresh();
        $assignment3->refresh();

        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment1->status);
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment2->status);
        $this->assertEquals(assignment::STATUS_ARCHIVED, $assignment3->status);
    }

    public function test_action_delete() {
        ['ass' => $assignments] = $this->generate_competencies();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'delete',
            'basket' => null,
            'id' => $assignment1->id,
            'extra' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([$assignment1->id], $result);

        // assignment is gone
        $this->assertEmpty(assignment::repository()->find($assignment1->id));
    }

    public function test_action_delete_via_basket() {
        ['ass' => $assignments] = $this->generate_competencies();

        $assignment1 = new assignment($assignments[0]);
        $assignment1->status = assignment::STATUS_DRAFT;
        $assignment1->save();

        $assignment2 = new assignment($assignments[1]);
        $assignment2->status = assignment::STATUS_ACTIVE;
        $assignment2->save();

        $assignment3 = new assignment($assignments[2]);
        $assignment3->status = assignment::STATUS_ARCHIVED;
        $assignment3->save();

        $basket = new session_basket('mytestbasket');
        $basket->add([$assignment1->id, $assignment2->id, $assignment3->id]);

        $res = $this->call_webservice_api('totara_competency_assignment_action', [
            'action' => 'delete',
            'basket' => $basket->get_key(),
            'id' => null,
            'extra' => []
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEqualsCanonicalizing([$assignment1->id, $assignment3->id], $result);

        // assignments are gone
        $this->assertEmpty(assignment::repository()->find($assignment1->id));
        $this->assertEmpty(assignment::repository()->find($assignment3->id));

        // this one is untouched
        $assignment2->refresh();
        $this->assertEquals(assignment::STATUS_ACTIVE, $assignment2->status);
    }

    /**
     * Create a few competencies with knows names to test search
     */
    protected function generate_competencies() {
        $data = [
            'comps' => [],
            'fws' => [],
            'ass' => [],
            'types' => [],
            'pos' => [],
            'org' => []
        ];

        $hierarchy_generator = $this->generator()->hierarchy_generator();

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 2']);
        $data['pos'][] = $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Framework 3']);
        $data['org'][] = $org1 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);

        $data['fws'][] = $fw = $hierarchy_generator->create_comp_frame([]);
        $data['fws'][] = $fw2 = $hierarchy_generator->create_comp_frame([]);

        $data['types'][] = $type1 = $hierarchy_generator->create_comp_type(['idnumber' => 'type1']);
        $data['types'][] = $type2 = $hierarchy_generator->create_comp_type(['idnumber' => 'type2']);

        $data['comps'][] = $one = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type1,
        ]);

        $data['comps'][] = $two = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'typeid' => $type1,
        ]);

        $data['comps'][] = $three = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'des',
            'fullname' => 'Designing interiors',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $one->id,
            'typeid' => $type2,
        ]);

        // Create an assignment for a competency
        $gen = $this->generator()->assignment_generator();
        $data['ass'][] = $gen->create_user_assignment($one->id, null, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);
        $data['ass'][] = $gen->create_user_assignment($two->id, null, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_SELF]);
        $data['ass'][] = $gen->create_user_assignment($three->id, null, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_SYSTEM]);
        $data['ass'][] = $gen->create_position_assignment($three->id, $pos1->id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);
        $data['ass'][] = $gen->create_organisation_assignment($three->id, $org1->id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);

        return $data;
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }
}