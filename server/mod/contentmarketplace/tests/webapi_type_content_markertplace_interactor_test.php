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
use mod_contentmarketplace\model\content_marketplace;
use mod_contentmarketplace\webapi\resolver\type\content_marketplace_interactor as type_content_marketplace_interactor;
use mod_contentmarketplace\interactor\content_marketplace_interactor;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_webapi_type_content_markertplace_interactor_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var enrol_plugin|null
     */
    protected $self_plugin;

    /**
     * @var enrol_plugin|null
     */
    protected $guest_plugin;

    /**
     * @var int|null
     */
    protected $course_module_id;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $marketplace = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course->id,
                'completion' => COMPLETION_TRACKING_MANUAL
            ]
        );

        $this->course_module_id = $marketplace->cmid;

        // Enabled self enrolment.
        $this->self_plugin = enrol_get_plugin('self');
        $instance = $DB->get_record(
            'enrol',
            [
                'courseid' => $marketplace->course,
                'enrol' => 'self'
            ],
            '*',
            MUST_EXIST
        );

        $this->self_plugin->update_status($instance, ENROL_INSTANCE_ENABLED);

        // Enabled guest access.
        $enrol_instance = $DB->get_record(
            'enrol',
            [
                'enrol' => 'guest',
                'courseid' => $marketplace->course
            ],
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
        $this->self_plugin = null;
        $this->guest_plugin = null;
        $this->course_module_id = null;
    }

    /**
     * @return void
     */
    public function test_content_markertplace_interactor_type_has_view_capability(): void {
        $interactor = new content_marketplace_interactor(
            content_marketplace::from_course_module_id($this->course_module_id),
            get_admin()->id
        );

        self::assertTrue($interactor->has_view_capability());

        self::assertEquals(
            $interactor->has_view_capability(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_content_marketplace_interactor::class),
                'has_view_capability',
                $interactor
            )
        );
    }

    /**
     * @return void
     */
    public function test_content_markertplace_interactor_type_can_enrol(): void {
        $interactor = new content_marketplace_interactor(
            content_marketplace::from_course_module_id($this->course_module_id),
            get_admin()->id
        );

        self::assertTrue($interactor->can_enrol());
        self::assertEquals(
            $interactor->can_enrol(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_content_marketplace_interactor::class),
                'can_enrol',
                $interactor
            )
        );
    }

    /**
     * @return void
     */
    public function test_content_markertplace_interactor_type_is_site_guest(): void {
        $guest_user = guest_user();
        $interactor = new content_marketplace_interactor(
            content_marketplace::from_course_module_id($this->course_module_id),
            $guest_user->id
        );

        self::assertTrue($interactor->is_site_guest());
        self::assertEquals(
            $interactor->is_site_guest(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_content_marketplace_interactor::class),
                'is_site_guest',
                $interactor
            )
        );
    }

    /**
     * @return void
     */
    public function test_content_markertplace_interactor_type_non_interactive_enrol_instance_enabled(): void {
        $interactor = new content_marketplace_interactor(
            content_marketplace::from_course_module_id($this->course_module_id),
            get_admin()->id
        );

        self::assertEquals(
            $interactor->can_enrol(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_content_marketplace_interactor::class),
                'non_interactive_enrol_instance_enabled',
                $interactor
            )
        );
    }

    /**
     * @return void
     */
    public function test_content_markertplace_interactor_type_supports_non_interactive_enrol(): void {
        $interactor = new content_marketplace_interactor(
            content_marketplace::from_course_module_id($this->course_module_id),
            get_admin()->id
        );

        self::assertEquals(
            $interactor->can_enrol(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_content_marketplace_interactor::class),
                'supports_non_interactive_enrol',
                $interactor
            )
        );
    }
}