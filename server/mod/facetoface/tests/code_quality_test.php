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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

// NOTE: Declare one by one instead of bulky `use \mod_facetoface\{seminar, signup}` to possibly avoid merge conflict

// Model classes
use mod_facetoface\seminar;
use mod_facetoface\seminar_attachment_item;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\signup;
use mod_facetoface\signup_status;
use mod_facetoface\session_status;

// State classes
use mod_facetoface\signup\state\state;
use mod_facetoface\signup\condition\condition;
use mod_facetoface\signup\restriction\restriction;
use mod_facetoface\signup\transition;

// Other classes
use mod_facetoface\event_dates;
use mod_facetoface\render_event_info_option;
use mod_facetoface\attendance\event_attendee;
use mod_facetoface\attendance\attendance_helper;

use mod_facetoface\dashboard\filter_list;
use mod_facetoface\dashboard\render_session_option;
use mod_facetoface\dashboard\render_session_list_config;
use mod_facetoface\dashboard\filters\filter as dashboard_filter;
use mod_facetoface\detail\content_generator as detail_content_generator;
use mod_facetoface\query\query_interface;
use mod_facetoface\query\statement;
use mod_facetoface\query\event\filter\filter as query_filter;
use mod_facetoface\query\event\sortorder\sortorder as query_sortorder;
use mod_facetoface\traits\crud_mapper;

use mod_facetoface\external;
use mod_facetoface\query\query_helper;
use mod_facetoface\query\event\filter_factory;
use mod_facetoface\internal\mod_facetoface_renderer_deprecated;

use mod_facetoface\calendar;

// Asset / Facilitator / Room classes
use mod_facetoface\asset;
use mod_facetoface\asset_list;
use mod_facetoface\bulk_list;
use mod_facetoface\facilitator;
use mod_facetoface\facilitator_user;
use mod_facetoface\facilitator_list;
use mod_facetoface\output\helper\virtualroom_card_factory;
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar_event_list;
use mod_facetoface\seminar_session_list;
use mod_facetoface\signup_list;
use mod_facetoface\task\manage_virtualmeetings_adhoc_task;
use mod_facetoface\watcher\virtualmeeting_watcher;

// Renderer class - mod_facetoface_renderer
require_once(__DIR__ . '/../renderer.php');
require_once(__DIR__ . '/../../../totara/core/tests/code_quality_testcase.php');

/**
 * Class mod_facetoface_code_quality_testcase
 */
class mod_facetoface_code_quality_testcase extends totara_core_code_quality_testcase_base {

    /**
     * @var string[]
     */
    private $tested_classes = [
        // self test
        mod_facetoface_code_quality_testcase::class,

        seminar::class,
        seminar_event::class,
        seminar_session::class,
        signup::class,
        signup_status::class,
        session_status::class,
        room_virtualmeeting::class,
        room_dates_virtualmeeting::class,

        transition::class,
        event_attendee::class,
        attendance_helper::class,

        event_dates::class,
        filter_list::class,
        render_session_option::class,
        render_session_list_config::class,
        render_event_info_option::class,
        statement::class,
        crud_mapper::class,

        mod_facetoface_renderer::class,
        mod_facetoface_renderer_deprecated::class,
        external::class,
        query_helper::class,
        filter_factory::class,

        calendar::class,

        asset::class,
        facilitator::class,
        facilitator_user::class,
        facilitator_list::class,
        room::class,

        virtualroom_card_factory::class,
        virtualmeeting_watcher::class,
        manage_virtualmeetings_adhoc_task::class,
    ];

    /**
     * @inheritDoc
     */
    protected function get_classes_to_test(): array {
        $tested_classes = $this->tested_classes;
        // Load all attachment classes
        self::add_inherited_classes($tested_classes, null, seminar_attachment_item::class, 'classes');
        // Load all state classes
        self::add_inherited_classes($tested_classes, 'signup\state', state::class, 'classes/signup/state');
        // Load all condition classes
        self::add_inherited_classes($tested_classes, 'signup\condition', condition::class, 'classes/signup/condition');
        // Load all restriction classes
        self::add_inherited_classes($tested_classes, 'signup\restriction', restriction::class, 'classes/signup/restriction');
        // Load all dashboard filter classes
        self::add_inherited_classes($tested_classes, 'dashboard\filters', dashboard_filter::class, 'classes/dashboard/filters');
        // Load all query classes
        self::add_inherited_classes($tested_classes, 'query', query_interface::class, 'classes/query');
        // Load all query filter classes
        self::add_inherited_classes($tested_classes, 'query\event\filter', query_filter::class, 'classes/query/event/filter');
        // Load all query sortorder classes
        self::add_inherited_classes($tested_classes, 'query\event\sortorder', query_sortorder::class, 'classes/query/event/sortorder');
        // Load all detail content generator classes
        self::add_inherited_classes($tested_classes, 'detail', detail_content_generator::class, 'classes/detail');
        // Load all hook classes
        self::add_inherited_classes($tested_classes, 'hook', null, 'classes/hook');
        // Load all service classes
        self::add_inherited_classes($tested_classes, 'hook\service', null, 'classes/hook/service');
        // Load all template classes
        self::add_inherited_classes($tested_classes, 'output', null, 'classes/output');
        // Load all template builder classes
        self::add_inherited_classes($tested_classes, 'output\builder', null, 'classes/output/builder');
        // Load all xxx_helper and xxx_list classes
        self::add_matching_classes($tested_classes, '/^mod_facetoface\\\\[^\\\\]+(_helper|_list)$/', 'classes');
        return $tested_classes;
    }

    /**
     * Make sure the list classes prevent devs from calling its constructor with no parameters.
     */
    public function test_deprecated_list_constructor(): void {
        $blacklist = [
            bulk_list::class,
            filter_list::class,
        ];
        $whitelist = [
            asset_list::class,
            facilitator_list::class,
            seminar_event_list::class,
            seminar_session_list::class,
            signup_list::class,
        ];
        $debugging_expected = function ($class) {
            $debugging = $this->getDebuggingMessages();
            $this->resetDebugging();
            $this->assertCount(1, $debugging, "debugging expected: {$class}");
            $this->assertEquals(DEBUG_DEVELOPER, $debugging[0]->level, "debugging level expected: {$class}");
            $this->assertTrue(!!preg_match('/(Creating an instance without|Passing null to) the first parameter is deprecated/', $debugging[0]->message), "debugging message expected: {$class}");
        };
        $classes = core_component::get_component_classes_in_namespace('mod_facetoface');
        // Check constructors.
        foreach ($classes as $class => $x) {
            if (!preg_match('/^mod_facetoface\\\\[^\\\\]+_list$/', $class) || in_array($class, $blacklist)) {
                continue;
            }
            new $class();
            if (in_array($class, $whitelist)) {
                $this->assertDebuggingNotCalled();
            } else {
                $debugging_expected($class);
            }
        }
        // Check factory methods too.
        signup_list::from_conditions();
        $debugging_expected(signup_list::class);
    }
}
