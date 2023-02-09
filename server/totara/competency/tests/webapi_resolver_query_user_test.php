<?php
/**
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
 * @package totara_competency
 */

use core\orm\query\builder;
use core_user\profile\card_display;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_user_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const QUERY = 'totara_competency_user';
    private const TYPE = 'core_user';

    /**
     * @var stdClass
     */
    private $subject_user;

    /**
     * @var stdClass
     */
    private $role;

    /**
     * @var context
     */
    private $context;

    protected function setUp(): void {
        parent::setUp();
        $this->subject_user = self::getDataGenerator()->create_user();
        $this->role = builder::table('role')->where('shortname', 'user')->one();
        $this->context = context_user::instance($this->subject_user->id);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->subject_user = null;
        $this->role = null;
        $this->context = null;
    }

    public function test_requires_login(): void {
        self::setAdminUser();
        $this->resolve_graphql_query(self::QUERY, ['user_id' => $this->subject_user->id]);

        self::setUser(null);
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(self::QUERY, ['user_id' => $this->subject_user->id]);
    }

    public function test_requires_competency_assignment_advanced_feature(): void {
        self::setAdminUser();
        $this->resolve_graphql_query(self::QUERY, ['user_id' => $this->subject_user->id]);

        advanced_feature::disable('competency_assignment');

        $this->expectException(feature_not_available_exception::class);
        $this->resolve_graphql_query(self::QUERY, ['user_id' => $this->subject_user->id]);
    }

    public function test_no_capabilities(): void {
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches('/View profile of other users/');
        $this->resolve_graphql_query(self::QUERY, ['user_id' => $this->subject_user->id]);
    }

    public function capabilities_provider(): array {
        return [
            'totara/competency:view_other_profile' => ['totara/competency:view_other_profile'],
            'totara/competency:assign_other' => ['totara/competency:assign_other'],
            'totara/competency:rate_other_competencies' => ['totara/competency:rate_other_competencies'],
        ];
    }

    /**
     * @dataProvider capabilities_provider
     * @param string|null $capability
     */
    public function test_capabilities(string $capability): void {
        $this->disable_features();

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        assign_capability($capability, CAP_ALLOW, $this->role->id, $this->context->id);
        $resolved_user = $this->resolve_graphql_query(self::QUERY, ['user_id' => $this->subject_user->id]);
        $this->resolve_graphql_type(self::TYPE, 'fullname', $resolved_user, [], $this->context);
        $card = $this->resolve_graphql_type(self::TYPE, 'card_display', $resolved_user, [], $this->context);
        $this->assertInstanceOf(card_display::class, $card);
    }

    public function test_appraiser_can_resolve(): void {
        $this->disable_features();

        $user = self::getDataGenerator()->create_user();
        $job = job_assignment::create(['userid' => $this->subject_user->id, 'appraiserid' => $user->id, 'idnumber' => '1']);
        self::setUser($user);

        $resolved_user = $this->resolve_graphql_query(self::QUERY, ['user_id' => $this->subject_user->id]);
        $this->resolve_graphql_type(self::TYPE, 'fullname', $resolved_user, [], $this->context);
        $card = $this->resolve_graphql_type(self::TYPE, 'card_display', $resolved_user, [], $this->context);
        $this->assertInstanceOf(card_display::class, $card);

        job_assignment::delete($job);
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches('/View profile of other users/');
        $this->resolve_graphql_query(self::QUERY, ['user_id' => $this->subject_user->id]);
    }

    public function test_resolves_correct_user(): void {
        self::setAdminUser();
        $admin_user = get_admin();

        $subject_user_result = $this->resolve_graphql_query(self::QUERY, ['user_id' => $this->subject_user->id]);
        $this->assertEquals($this->subject_user->id, $subject_user_result->id);
        $this->assertNotEquals($admin_user->id, $subject_user_result->id);

        $admin_user_result = $this->resolve_graphql_query(self::QUERY, ['user_id' => $admin_user->id]);
        $this->assertEquals($admin_user->id, $admin_user_result->id);
        $this->assertNotEquals($this->subject_user->id, $admin_user_result->id);
    }

    /**
     * We want to disable every feature that isn't competencies to ensure that the
     * competencies user access controller hook override works correctly.
     */
    private function disable_features(): void {
        foreach (advanced_feature::get_available() as $feature) {
            if ($feature !== 'competency_assignment') {
                advanced_feature::disable($feature);
            }
        }
    }

}
