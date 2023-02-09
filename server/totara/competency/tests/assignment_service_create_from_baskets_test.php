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

use core_phpunit\testcase;
use totara_competency\entity\assignment;
use totara_competency\task\expand_assignment_task;
use core\entity\user;
use totara_competency\user_groups;
use totara_core\basket\session_basket;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_assignment_service_create_from_baskets_testcase extends testcase {

    use \totara_core\phpunit\webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_create_draft_assignments() {
        $data = $this->generate_data();

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => assignment::STATUS_DRAFT
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(2, $result);

        // Check first assignment
        /** @var assignment $assignment */
        $assignment = (object) $result[0];
        $this->assertEquals($data['competency']->id, $assignment->competency_id);
        $this->assertEquals(user_groups::USER, $assignment->user_group_type);
        $this->assertEquals($data['baskets'][user_groups::USER][0], $assignment->user_group_id);
        $this->assertEquals(assignment::STATUS_DRAFT, $assignment->status);

        // Check second assignment
        $assignment = (object) $result[1];
        $this->assertEquals($data['competency']->id, $assignment->competency_id);
        $this->assertEquals(user_groups::COHORT, $assignment->user_group_type);
        $this->assertEquals($data['baskets'][user_groups::COHORT][0], $assignment->user_group_id);
        $this->assertEquals(assignment::STATUS_DRAFT, $assignment->status);

        global $DB;

        // For draft assignments no tasks should be scheduled
        $tasks = $DB->get_records('task_adhoc', ['classname' => '\\'.expand_assignment_task::class]);
        $this->assertEmpty($tasks);
    }

    public function test_baskets_get_deleted() {
        $data = $this->generate_data();

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => assignment::STATUS_DRAFT
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(2, $result);

        $this->assertEmpty(
            (new session_basket('comp_basket'))->load(),
            'The create assignment endpoint should clean the basket after it has finished'
        );

        foreach ($data['user_groups'] as $basket_key) {
            $basket = new session_basket($basket_key);
            $this->assertEmpty($basket->load());
        }
    }

    public function test_create_active_assignments() {
        global $DB;

        $data = $this->generate_data();

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => assignment::STATUS_ACTIVE
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(2, $result);

        foreach ($result as $assignment) {
            $this->assertEquals(assignment::STATUS_ACTIVE, $assignment['status']);
        }

        $assignment_ids = [];
        foreach ($result as $assignment) {
            $assignment_ids[] = $assignment['id'];
        }

        // Check that for both assignments a task was scheduled
        $tasks = $DB->get_records('task_adhoc', ['classname' => '\\'.expand_assignment_task::class]);
        $this->assertCount(1, $tasks);
        $task = array_shift($tasks);
        $task_data = json_decode($task->customdata, true);
        $this->assertArrayHasKey('assignment_ids', $task_data);
        $this->assertEqualsCanonicalizing($assignment_ids, $task_data['assignment_ids']);
    }

    public function test_success_notifications_draft_plural() {
        $data = $this->generate_data();

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => assignment::STATUS_DRAFT
        ]);
        $this->assert_webservice_success($res);
        $this->assert_has_notification(
            \core\notification::SUCCESS,
            get_string('confirm_assignment_creation_draft_plural', 'totara_competency', (object) ['created' => 2])
        );
    }

    public function test_success_notifications_active_plural() {
        $data = $this->generate_data();

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => assignment::STATUS_ACTIVE
        ]);
        $this->assert_webservice_success($res);
        $this->assert_has_notification(
            \core\notification::SUCCESS,
            get_string('confirm_assignment_creation_active_plural', 'totara_competency', (object) ['created' => 2])
        );
    }

    public function test_success_notifications_draft_singular() {
        $this->generate_data();

        // draft singular
        $user = $this->generator()->create_user();
        $basket = new session_basket('single');
        $basket->add([$user->id]);

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => [user_groups::USER => 'single'],
            'status' => assignment::STATUS_DRAFT
        ]);
        $this->assert_webservice_success($res);
        $this->assert_has_notification(
            \core\notification::SUCCESS,
            get_string('confirm_assignment_creation_draft_singular', 'totara_competency', (object) ['created' => 1])
        );
    }

    public function test_success_notifications_active_singular() {
        $this->generate_data();

        // draft singular
        $user = $this->generator()->create_user();
        $basket = new session_basket('single');
        $basket->add([$user->id]);

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => [user_groups::USER => 'single'],
            'status' => assignment::STATUS_ACTIVE
        ]);
        $this->assert_webservice_success($res);
        $this->assert_has_notification(
            \core\notification::SUCCESS,
            get_string('confirm_assignment_creation_active_singular', 'totara_competency', (object) ['created' => 1])
        );
    }

    public function test_success_notifications_skipped() {
        $this->generate_data();

        $comp_basket = new session_basket('comp_basket');
        $comps = $comp_basket->load();

        // draft singular
        $user = $this->generator()->create_user();
        $basket = new session_basket('single');
        $basket->add([$user->id]);

        $this->assert_ws_notification('confirm_assignment_creation_draft_singular', 1, 0);

        // readd comps
        $comp_basket->add($comps);

        // readd previous user
        $basket->add([$user->id]);
        // add a second one
        $user2 = $this->generator()->create_user();
        $basket->add([$user2->id]);

        $this->assert_ws_notification('confirm_assignment_creation_singular_skipped_singular', 1, 1);

        // readd comps
        $comp_basket->add($comps);

        // readd previous users
        $basket->add([$user->id, $user2->id]);
        // add a third one
        $user3 = $this->generator()->create_user();
        $basket->add([$user3->id]);

        $this->assert_ws_notification('confirm_assignment_creation_singular_skipped_plural', 1, 2);

        // readd comps
        $comp_basket->add($comps);

        // readd previous users
        $basket->add([$user->id, $user2->id]);
        // add a third one
        $user4 = $this->generator()->create_user();
        $user5 = $this->generator()->create_user();
        $basket->add([$user4->id, $user5->id]);

        $this->assert_ws_notification('confirm_assignment_creation_plural_skipped_plural', 2, 2);

        // readd comps
        $comp_basket->add($comps);

        // readd previous users
        $basket->add([$user->id]);
        // add a third one
        $user6 = $this->generator()->create_user();
        $user7 = $this->generator()->create_user();
        $basket->add([$user6->id, $user7->id]);

        $this->assert_ws_notification('confirm_assignment_creation_plural_skipped_singular', 2, 1);
    }

    private function assert_ws_notification(string $key, int $created, int $skipped) {
        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => [user_groups::USER => 'single'],
            'status' => assignment::STATUS_DRAFT
        ]);
        $this->assert_webservice_success($res);

        $this->assert_has_notification(
            \core\notification::SUCCESS,
            get_string($key, 'totara_competency', (object)['created' => $created, 'skipped' => $skipped])
        );
    }

    public function test_validation_of_competencies() {
        $data = $this->generate_data();

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'bad_comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => assignment::STATUS_DRAFT
        ]);

        $this->assert_webservice_success($res);
        $this->assertEmpty($res['data']);
        $this->assert_has_notification(\core\notification::ERROR);
    }

    public function test_validation_of_user_groups() {
        $data = $this->generate_data();

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['bad_user_groups'],
            'status' => assignment::STATUS_DRAFT
        ]);

        $this->assert_webservice_success($res);
        $this->assertEmpty($res['data']);
        $this->assert_has_notification(\core\notification::ERROR);
    }

    public function test_validation_of_assignment_status() {
        $data = $this->generate_data();

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => 325
        ]);

        $this->assert_webservice_error($res);
        $this->assert_webservice_has_exception_message('Invalid assignment status supplied', $res);
    }

    public function test_no_duplicates_are_created() {
        $data = $this->generate_data();

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => assignment::STATUS_DRAFT
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(2, $result);

        // Rerun the same assignment creation again, it should not create new ones

        $basket = new session_basket('comp_basket');
        $basket->add([$data['competency']->id]);

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => assignment::STATUS_DRAFT
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(0, $result);

        // Add a non existing assignment

        $basket = new session_basket('comp_basket');
        $basket->add([$data['competency']->id]);

        $user = $this->generator()->create_user();
        // Add a user to the basket
        $basket = new session_basket($data['user_groups'][user_groups::USER]);
        $basket->add([$user->id]);

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'comp_basket',
            'usergroups' => $data['user_groups'],
            'status' => assignment::STATUS_DRAFT
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(1, $result);
    }

    public function test_no_assignment_for_invisible_competencies_created() {
        $data = $this->generate_data();

        $one_user_group = [$key = array_keys($data['user_groups'])[0] => $data['user_groups'][$key]];

        $res = $this->call_webservice_api('totara_competency_assignment_create_from_baskets', [
            'basket' => 'hidden_basket',
            'usergroups' => $one_user_group,
            'status' => assignment::STATUS_ACTIVE
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(0, $result);
        $this->assert_has_notification(\core\notification::ERROR);
    }

    private function assert_has_notification($notification_type, $notification_message = null, $message = '') {
        /** @var \core\output\notification[] $notifications */
        $notifications = \core\notification::fetch();
        $notification_found = null;
        foreach ($notifications as $notification) {
            if ($notification_message === null) {
                if ($notification->get_message_type() == $notification_type) {
                    $notification_found = $notification;
                }
            } else {
                if ($notification->get_message_type() == $notification_type
                    && $notification->get_message() == $notification_message
                ) {
                    $notification_found = $notification;
                }
            }
        }
        if (empty($notification_found)) {
            $message = $message != '' ? $message : 'Failed asserting that there\'s a notification with given type and message.';
            $this->fail($message);
        }
    }

    /**
     * Date generator shortcut
     *
     * @return \core\testing\generator
     */
    protected function generator() {
        return self::getDataGenerator();
    }

    /**
     * Generate some dummy data.
     *
     * @return array
     */
    protected function generate_data() {
        $user = $this->generator()->create_user();
        $cohort = $this->generator()->create_cohort();

        /** @var totara_competency_generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $fw = $competency_generator->create_framework(null, 'Competency framework', '',
            [
                'sortorder' => 1,
                'visible' => true,
                'hidecustomfields' => false,
                'usermodified' => 2,
            ]
        );

        $fw2 = $competency_generator->create_framework(null, 'Competency framework 2', '',
            [
                'sortorder' => 2,
                'visible' => false,
                'hidecustomfields' => false,
                'usermodified' => 2,
            ]
        );

        $competency = $competency_generator->create_competency(null, $fw,
            [
                'parentid' => 0,
                'visible' => 1,
                'aggregationmethod' => 0,
                'proficiencyexpected' => 0,
                'evidencecount' => 0,
                'timecreated' => time(),
                'timemodified' => 0,
                'usermodified' => user::logged_in()->id,
            ]
        );

        $competency2 = $competency_generator->create_competency(null, $fw2,
            [
                'parentid' => 0,
                'visible' => 1,
                'aggregationmethod' => 0,
                'proficiencyexpected' => 0,
                'evidencecount' => 0,
                'timecreated' => time(),
                'timemodified' => 0,
                'usermodified' => user::logged_in()->id,
            ]
        );

        $competency3 = $competency_generator->create_competency(null, $fw,
            [
                'parentid' => 0,
                'visible' => 0,
                'aggregationmethod' => 0,
                'proficiencyexpected' => 0,
                'evidencecount' => 0,
                'timecreated' => time(),
                'timemodified' => 0,
                'usermodified' => user::logged_in()->id,
            ]
        );

        $basket = new session_basket('comp_basket');
        $basket->add([$competency->id]);

        $bad_basket = new session_basket('bad_comp_basket');
        $bad_basket->add([$competency->id]);
        $bad_basket->add([999]);

        $hidden_basket = new session_basket('hidden_basket');
        $hidden_basket->add([$competency->id, $competency2->id, $competency3->id]);

        $user_basket = new session_basket('user_basket');
        $user_basket->add([$user->id]);

        $cohort_basket = new session_basket('cohort_basket');
        $cohort_basket->add([$cohort->id]);

        $bad_pos_basket = new session_basket('bad_pos_basket');
        $bad_pos_basket->add([1, 2, 3, 4, 5]);

        return [
            'baskets' => [
                user_groups::USER => $user_basket->load(),
                user_groups::COHORT => $cohort_basket->load(),
                user_groups::POSITION => $bad_pos_basket->load()
            ],
            'user_groups' => [
                user_groups::USER => $user_basket->get_key(),
                user_groups::COHORT => $cohort_basket->get_key()
            ],
            'bad_user_groups' => [
                user_groups::USER => $user_basket->get_key(),
                user_groups::COHORT => $cohort_basket->get_key(),
                user_groups::POSITION => $bad_pos_basket->get_key()
            ],
            'competency' => $competency,
            'competencies' => [$competency, $competency2, $competency3],
            'comp_basket' => $basket,
            'bad_comp_basket' => $bad_basket,
            'hidden_basket' => $hidden_basket,
        ];
    }

}