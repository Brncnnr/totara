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
 * @package mod_contentmarketplace
 */

use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_contentmarketplace\interactor\content_marketplace_interactor;
use mod_contentmarketplace\model\content_marketplace;
use core\entity\user_enrolment;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_webapi_resolver_mutation_request_non_interactive_enrol_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var string
     */
    private const MUTATION = 'mod_contentmarketplace_request_non_interactive_enrol';

    /**
     * @var stdClass
     */
    protected $cm;

    /**
     * @var generator
     */
    protected $generator;

    /**
     * @var enrol_plugin
     */
    protected $self_plugin;

    /**
     * @var enrol_plugin
     */
    protected $guest_plugin;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        global $DB;
        $this->generator = self::getDataGenerator();
        $course = $this->generator->create_course(['enablecompletion' => 1]);
        $cm = $this->generator->create_module(
            'contentmarketplace',
            [
                'course' => $course->id,
                'completion' => COMPLETION_TRACKING_MANUAL
            ]
        );

        $this->cm = content_marketplace::load_by_id($cm->id);

        // Enabled self enrolment.
        $this->self_plugin = enrol_get_plugin('self');
        $instance = $DB->get_record(
            'enrol',
            [
                'courseid' => $this->cm->get_course_id(),
                'enrol' => 'self'
            ],
            '*',
            MUST_EXIST
        );
        $this->self_plugin->update_status($instance, ENROL_INSTANCE_ENABLED);

        // Enabled guest access.
        $enrol_instance = $DB->get_record(
            'enrol',
            ['enrol' => 'guest', 'courseid' => $this->cm->get_course_id()],
            '*',
            MUST_EXIST
        );
        $this->guest_plugin = enrol_get_plugin('guest');
        $this->guest_plugin->update_status($enrol_instance, ENROL_INSTANCE_ENABLED);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void {
        $this->generator = null;
        $this->cm = null;
        $this->self_plugin = null;
        $this->guest_plugin = null;
    }

    /**
     * @return void
     */
    public function test_request_self_enrol_by_admin(): void {
        self::setAdminUser();

        $interactor = new content_marketplace_interactor($this->cm);
        self::assertTrue($interactor->can_enrol());
        self::assertEquals(0, user_enrolment::repository()->count());
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );

        self::assertTrue($result);
        self::assertFalse($interactor->can_enrol());
        self::assertEquals(1, user_enrolment::repository()->count());
        self::assertTrue(user_enrolment::repository()->where('userid', get_admin()->id)->exists());
    }

    /**
     * @return void
     */
    public function test_request_self_enrol_by_admin_when_self_enrol_disabled(): void {
        self::setAdminUser();
        $this->disable_enrol_plugin('self');

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Not support non interactive enrol');
        $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );
    }

    /**
     * @return void
     */
    public function test_request_self_enrol_by_course_guest_when_guest_access_enabled(): void {
        $user = $this->generator->create_user();
        self::setUser($user);

        $interactor = new content_marketplace_interactor($this->cm);
        self::assertTrue($interactor->can_enrol());
        self::assertEquals(0, user_enrolment::repository()->count());

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );

        self::assertTrue($result);
        self::assertFalse($interactor->can_enrol());
        self::assertEquals(1, user_enrolment::repository()->count());
        self::assertTrue(user_enrolment::repository()->where('userid', $user->id)->exists());
    }

    /**
     * @return void
     */
    public function test_request_self_enrol_by_course_guest_when_guest_access_disabled(): void {
        $user = $this->generator->create_user();
        self::setUser($user);
        $this->disable_enrol_plugin();

        self::expectException(require_login_exception::class);
        self::expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );

    }

    /**
     * @return void
     */
    public function test_request_self_enrol_by_site_guest(): void {
        self::setGuestUser();
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Site guest can not request self enrol');
        $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );
    }

    /**
     * @return void
     */
    public function test_request_self_enrol_by_site_guest_when_guest_access_disabled(): void {
        self::setGuestUser();
        $this->disable_enrol_plugin();

        self::expectException(require_login_exception::class);
        self::expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );
    }

    /**
     * @param string $enrol_plugin
     *  @return void
     */
    private function disable_enrol_plugin(string $enrol_plugin = 'guest'): void {
        global $DB;

        // Disabled gusest access.
        $enrol_instance = $DB->get_record(
            'enrol',
            ['enrol' => $enrol_plugin, 'courseid' => $this->cm->get_course_id()],
            '*',
            MUST_EXIST
        );

        if ($enrol_plugin == 'guest') {
            $this->guest_plugin->update_status($enrol_instance, ENROL_INSTANCE_DISABLED);
            return;
        }

        $this->self_plugin->update_status($enrol_instance, ENROL_INSTANCE_DISABLED);
    }

    /**
     * @return void
     */
    public function test_request_self_enrol_with_password_setting(): void {
        $user = $this->generator->create_user();
        self::setUser($user);

        $this->update_self_enrol_setting('password', 'aaaa');

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Not support non interactive enrol');
        $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );
    }

    /**
     * @return void
     */
    public function test_request_self_enrol_with_new_enrol_setting(): void {
        $user = $this->generator->create_user();
        self::setUser($user);

        $this->update_self_enrol_setting('customint6', 0);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Not support non interactive enrol');
        $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );
    }

    /**
     * @return void
     */
    public function test_request_self_enrol_with_enrol_date_duration(): void {
        $user = $this->generator->create_user();
        self::setUser($user);

        $this->update_self_enrol_setting('enrolstartdate', time() - 10);
        $this->update_self_enrol_setting('enrolenddate', time() - 5);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Not support non interactive enrol');
        $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );
    }

    /**
     * @return void
     */
    public function test_request_self_enrol_with_audience_enabled(): void {
        $user = $this->generator->create_user();
        self::setUser($user);

        $this->update_self_enrol_setting('customint5', 1);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Not support non interactive enrol');
        $this->resolve_graphql_mutation(
            self::MUTATION,
            ['cm_id' => $this->cm->course_module->id]
        );
    }

    /**
     * @param string $key
     * @param string $value
     *
     */
    private function update_self_enrol_setting(string $key, string $value): void {
        global $DB;

        $instance = $DB->get_record(
            'enrol',
            [
                'courseid' => $this->cm->get_course_id(),
                'enrol' => 'self'
            ],
            '*',
            MUST_EXIST
        );

        $instance->{$key} = $value;
        $DB->update_record('enrol', $instance);
    }
}