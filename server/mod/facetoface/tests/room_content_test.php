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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

use core\orm\query\builder;
use mod_facetoface\detail\content_generator;
use mod_facetoface\detail\room_content;
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_helper;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\waitlisted;
use mod_facetoface\signup_status;
use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\http\clients\simple_mock_client;
use totara_core\virtualmeeting\plugin\provider\provider;
use totara_core\virtualmeeting\virtual_meeting as virtual_meeting_model;
use virtualmeeting_poc_app\poc_factory;

/**
 * @covers mod_facetoface\detail\room_content
 */
class mod_facetoface_room_content_testcase extends advanced_testcase {
    /** @var content_generator */
    private $generator;

    /** @var ReflectionMethod */
    private $method_card;

    /** @var ReflectionMethod */
    private $method_banner;

    /** @var stdClass */
    private $site_admin;

    /** @var stdClass */
    private $site_trainer;

    /** @var stdClass */
    private $site_manager;

    /** @var stdClass */
    private $course_trainer;

    /** @var stdClass */
    private $course_manager;

    /** @var stdClass */
    private $facilitator;

    /** @var stdClass */
    private $learner;

    /** @var stdClass */
    private $waiter;

    /** @var stdClass */
    private $pariah;

    /** @var seminar_session */
    private $session_future;

    /** @var seminar_session */
    private $session_near;

    /** @var seminar_session */
    private $session_present;

    /** @var seminar_session */
    private $session_past;

    /** @var mod_facetoface_generator */
    private $f2fgen;

    public function setUp(): void {
        parent::setUp();

        $this->generator = new room_content('reallydontcare', '/');
        $this->method_card = new ReflectionMethod($this->generator, 'render_card');
        $this->method_card->setAccessible(true);
        $this->method_banner = new ReflectionMethod($this->generator, 'render_banner');
        $this->method_banner->setAccessible(true);

        $this->site_admin = core_user::get_user(2, '*', MUST_EXIST);
        $this->site_trainer = $this->getDataGenerator()->create_user(['username' => 'site_trainer']);
        $this->site_manager = $this->getDataGenerator()->create_user(['username' => 'site_manager']);
        $this->course_trainer = $this->getDataGenerator()->create_user(['username' => 'course_trainer']);
        $this->course_manager = $this->getDataGenerator()->create_user(['username' => 'course_manager']);
        $this->facilitator = $this->getDataGenerator()->create_user(['username' => 'facilitator']);
        $this->learner = $this->getDataGenerator()->create_user(['username' => 'learner']);
        $this->waiter = $this->getDataGenerator()->create_user(['username' => 'waiter']);
        $this->pariah = $this->getDataGenerator()->create_user(['username' => 'pariah']);

        /** @var mod_facetoface_generator */
        $f2fgen = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $f2f_future = $f2fgen->create_instance(['name' => 'Future seminar', 'course' => $course->id]);
        $f2f_present = $f2fgen->create_instance(['name' => 'Present seminar', 'course' => $course->id]);
        $f2f_past = $f2fgen->create_instance(['name' => 'Past seminar', 'course' => $course->id]);
        $facilitator = $f2fgen->add_internal_facilitator(null, $this->facilitator);
        $pariah = $f2fgen->add_internal_facilitator(null, $this->pariah);
        $time = time();
        $event_future = new seminar_event(
            $f2fgen->add_session([
                'facetoface' => $f2f_future->id,
                'sessiondates' => [
                    (object)[
                        'timestart' => $time + YEARSECS,
                        'timefinish' => $time + YEARSECS + DAYSECS,
                        'sessiontimezone' => 'Pacific/Auckland',
                        'facilitatorids' => [$facilitator->id],
                    ],
                    (object)[
                        'timestart' => $time + YEARSECS + WEEKSECS,
                        'timefinish' => $time + YEARSECS + WEEKSECS + DAYSECS,
                        'sessiontimezone' => 'Pacific/Auckland',
                        'facilitatorids' => [$pariah->id],
                    ],
                ]
            ])
        );
        $event_near = new seminar_event(
            $f2fgen->add_session([
                'facetoface' => $f2f_present->id,
                'sessiondates' => [
                    (object)[
                        'timestart' => $time + 9 * MINSECS,
                        'timefinish' => $time + HOURSECS * 2,
                        'sessiontimezone' => 'Pacific/Auckland',
                        'facilitatorids' => [$facilitator->id],
                    ]
                ]
            ])
        );
        $event_present = new seminar_event(
            $f2fgen->add_session([
                'facetoface' => $f2f_present->id,
                'sessiondates' => [
                    (object)[
                        'timestart' => $time - HOURSECS,
                        'timefinish' => $time + HOURSECS,
                        'sessiontimezone' => 'Pacific/Auckland',
                        'facilitatorids' => [$facilitator->id],
                    ]
                ]
            ])
        );
        $event_past = new seminar_event(
            $f2fgen->add_session([
                'facetoface' => $f2f_past->id,
                'sessiondates' => [
                    (object)[
                        'timestart' => $time - YEARSECS,
                        'timefinish' => $time - YEARSECS + DAYSECS,
                        'sessiontimezone' => 'Pacific/Auckland',
                        'facilitatorids' => [$facilitator->id],
                    ],
                    (object)[
                        'timestart' => $time - YEARSECS + WEEKSECS,
                        'timefinish' => $time - YEARSECS + WEEKSECS + DAYSECS,
                        'sessiontimezone' => 'Pacific/Auckland',
                        'facilitatorids' => [$pariah->id],
                    ],
                ]
            ])
        );

        $this->session_future = $event_future->get_sessions()->get_first();
        $this->session_near = $event_near->get_sessions()->get_first();
        $this->session_present = $event_present->get_sessions()->get_first();
        $this->session_past = $event_past->get_sessions()->get_first();
        $this->f2fgen = $f2fgen;

        /** @var array<string, stdClass> */
        $roles = builder::table('role')->select(['shortname', 'id'])->fetch();
        role_assign($roles['teacher']->id, $this->site_trainer->id, context_system::instance());
        role_assign($roles['manager']->id, $this->site_manager->id, context_system::instance());

        $this->getDataGenerator()->enrol_user($this->course_trainer->id, $course->id, $roles['teacher']->id);
        $this->getDataGenerator()->enrol_user($this->course_manager->id, $course->id, $roles['manager']->id);
        $this->getDataGenerator()->enrol_user($this->learner->id, $course->id, $roles['student']->id);
        $this->getDataGenerator()->enrol_user($this->waiter->id, $course->id, $roles['student']->id);
        $this->getDataGenerator()->enrol_user($this->pariah->id, $course->id, $roles['student']->id);

        foreach ([$event_future, $event_near, $event_present, $event_past] as $event) {
            $signup = signup::create($this->learner->id, $event, 0)->save();
            signup_status::create($signup, new booked($signup))->save();
            $signup = signup::create($this->waiter->id, $event, 0)->save();
            signup_status::create($signup, new waitlisted($signup))->save();
        }
    }

    public function tearDown(): void {
        $this->generator = null;
        $this->method_card = null;
        $this->method_banner = null;
        $this->site_admin = null;
        $this->site_trainer = null;
        $this->site_manager = null;
        $this->course_trainer = null;
        $this->course_manager = null;
        $this->facilitator = null;
        $this->learner = null;
        $this->waiter = null;
        $this->pariah = null;
        $this->session_future = null;
        $this->session_near = null;
        $this->session_present = null;
        $this->session_past = null;
        $this->f2fgen = null;
        parent::tearDown();
    }

    /**
     * Instantiate the mod_facetoface_renderer, set context and initialise page.
     *
     * @param context $context
     * @return mod_facetoface_renderer
     */
    private function create_f2f_renderer(context $context): mod_facetoface_renderer {
        global $PAGE, $CFG;
        /** @var \moodle_page $PAGE */

        require_once($CFG->dirroot.'/mod/facetoface/renderer.php');

        $renderer = new mod_facetoface_renderer($PAGE, null);
        $renderer->setcontext($context);
        $PAGE->reset_theme_and_output();
        $PAGE->set_context($context);
        $PAGE->set_url('/');

        return $renderer;
    }

    /**
     * Cut down card template data.
     *
     * @param array $input
     * @return array
     */
    private static function sanitise_card(array $input): array {
        global $CFG;
        $output = [];
        $url_regexp = '@^' . preg_quote($CFG->wwwroot, '@').'/integrations/virtualmeeting/poc_[a-z_]+/meet.php\?.*(host=\d)@';
        foreach ($input as $key => $value) {
            if (!in_array($key, ['heading', 'subtitle', 'simple', 'inactive', 'has_buttons', 'buttons', 'detailsection', 'instruction', 'copy', 'preview', 'button', 'multibutton'])) {
                continue;
            }
            if ($key == 'heading') {
                $value = preg_replace('/\d$/', '', $value);
            } else if ($key == 'detailsection' && is_array($value)) {
                $value = [
                    'details' => array_map(
                        function (array $detail) {
                            return [
                                'label' => $detail['label']
                            ];
                        },
                        $value['details']
                    )
                ];
            } else if ($key == 'copy') {
                $value['url'] = preg_replace($url_regexp, 'https://virtualmeeting.example.com/meet.php?$1', $value['url']);
                unset($value['icon']['context']);
            } else if (($key == 'multibutton' || $key == 'buttons') && is_array($value)) {
                foreach ($value as $child => $x) {
                    $value[$child]['url'] = preg_replace($url_regexp, 'https://virtualmeeting.example.com/meet.php?$1', $value[$child]['url']);
                }
            }
            $output[$key] = $value;
        }
        return $output;
    }

    /**
     * Cut down banner template data.
     *
     * @param array $input
     * @return array
     */
    private static function sanitise_banner(array $input): array {
        $output = [];
        foreach ($input as $key => $value) {
            if (!in_array($key, ['message', 'extraclasses'])) {
                continue;
            }
            if ($key === 'message') {
                $value = preg_replace('/https?:\/\/.+\/events\/edit.php\?.*(backtoallsessions=\d+)/', 'https://example.com/mod/facetoface/events/edit.php?$1', $value);
            }
            $output[$key] = $value;
        }
        return $output;
    }

    /**
     * Get card template data as if visited from the manage page.
     *
     * @param room $room
     * @param stdClass $user
     * @return array|null
     */
    private function visit_card_from_manage(room $room, stdClass $user): ?array {
        $this->setUser();
        $context = context_system::instance();
        $renderer = $this->create_f2f_renderer($context);
        $template = $this->method_card->invoke($this->generator, null, $room, $user, $renderer);
        if ($template !== null) {
            return self::sanitise_card($template->get_template_data());
        } else {
            return null;
        }
    }

    /**
     * Get card template data as if visited from the seminar event dashboard.
     *
     * @param seminar_session $session
     * @param room $room
     * @param stdClass $user
     * @return array|null
     */
    private function visit_card_from_dashboard(seminar_session $session, room $room, stdClass $user): ?array {
        $this->setUser();
        $context = context_module::instance($session->get_seminar_event()->get_seminar()->get_coursemodule()->id);
        $renderer = $this->create_f2f_renderer($context);
        $template = $this->method_card->invoke($this->generator, $session, $room, $user, $renderer);
        if ($template !== null) {
            return self::sanitise_card($template->get_template_data());
        } else {
            return null;
        }
    }

    /**
     * Get banner template data as if visited from the manage page.
     *
     * @param room $room
     * @param stdClass $user
     * @return array|null
     */
    private function visit_banner_from_manage(room $room, stdClass $user): ?array {
        $this->setUser();
        $context = context_system::instance();
        $renderer = $this->create_f2f_renderer($context);
        $template = $this->method_banner->invoke($this->generator, null, $room, $user, $renderer);
        if ($template !== null) {
            return self::sanitise_banner($template->export_for_template($renderer));
        } else {
            return null;
        }
    }

    /**
     * Get banner template data as if visited from the seminar event dashboard.
     *
     * @param seminar_session $session
     * @param room $room
     * @param stdClass $user
     * @return array|null
     */
    private function visit_banner_from_dashboard(seminar_session $session, room $room, stdClass $user): ?array {
        $this->setUser();
        $context = context_module::instance($session->get_seminar_event()->get_seminar()->get_coursemodule()->id);
        $renderer = $this->create_f2f_renderer($context);
        $template = $this->method_banner->invoke($this->generator, $session, $room, $user, $renderer);
        if ($template !== null) {
            return self::sanitise_banner($template->export_for_template($renderer));
        } else {
            return null;
        }
    }

    /**
     * Create a virtual meeting in a seminar session.
     *
     * @param string $name
     * @param seminar_session $session
     * @param integer|null $status
     * @param integer|null $userid
     * @return room
     */
    private function add_virtualmeeting(string $name, seminar_session $session, ?int $status = room_dates_virtualmeeting::STATUS_AVAILABLE, ?int $userid = null): room {
        $room = new room($this->f2fgen->add_virtualmeeting_room(['name' => $name], ['userid' => $userid ?? $this->site_admin->id, 'plugin' => 'poc_app'])->id);
        $client = new simple_mock_client();
        $vm = virtual_meeting_model::create('poc_app', $this->site_admin->id, "<POC: $name>", DateTime::createFromFormat('U', $session->get_timestart()), DateTime::createFromFormat('U', $session->get_timefinish()), $client);
        $roomdate_vm = (new room_dates_virtualmeeting())->set_roomid($room->get_id())->set_sessionsdateid($session->get_id())->set_virtualmeetingid($vm->id);
        if ($status !== null) {
            $roomdate_vm->set_status($status);
        }
        $roomdate_vm->save();
        return $room;
    }

    public function test_permission_check() {
        $method = new ReflectionMethod($this->generator, 'has_edit_capability');
        $method->setAccessible(true);
        $room = (new room())->from_record((object)['id' => -42]);

        $context = context_system::instance();
        $this->assertTrue($method->invoke($this->generator, $room, $context, $this->site_admin));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->site_trainer));
        $this->assertTrue($method->invoke($this->generator, $room, $context, $this->site_manager));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->course_trainer));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->course_manager));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->facilitator));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->learner));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->waiter));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->pariah));
        $this->assertTrue(has_capability('mod/facetoface:manageadhocrooms', $context, $this->site_admin));
        $this->assertTrue(has_capability('mod/facetoface:manageadhocrooms', $context, $this->site_trainer));
        $this->assertTrue(has_capability('mod/facetoface:manageadhocrooms', $context, $this->site_manager));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->course_trainer));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->course_manager));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->facilitator));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->learner));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->waiter));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->pariah));
        $this->assertTrue(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->site_admin));
        $this->assertTrue(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->site_trainer));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->site_manager));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->course_trainer));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->course_manager));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->facilitator));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->learner));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->waiter));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->pariah));

        $context = context_module::instance($this->session_future->get_seminar_event()->get_seminar()->get_coursemodule()->id);
        $this->assertTrue($method->invoke($this->generator, $room, $context, $this->site_admin));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->site_trainer));
        $this->assertTrue($method->invoke($this->generator, $room, $context, $this->site_manager));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->course_trainer));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->course_manager));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->facilitator));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->learner));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->waiter));
        $this->assertFalse($method->invoke($this->generator, $room, $context, $this->pariah));
        $this->assertTrue(has_capability('mod/facetoface:manageadhocrooms', $context, $this->site_admin));
        $this->assertTrue(has_capability('mod/facetoface:manageadhocrooms', $context, $this->site_trainer));
        $this->assertTrue(has_capability('mod/facetoface:manageadhocrooms', $context, $this->site_manager));
        $this->assertTrue(has_capability('mod/facetoface:manageadhocrooms', $context, $this->course_trainer));
        $this->assertTrue(has_capability('mod/facetoface:manageadhocrooms', $context, $this->course_manager));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->facilitator));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->learner));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->waiter));
        $this->assertFalse(has_capability('mod/facetoface:manageadhocrooms', $context, $this->pariah));
        $this->assertTrue(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->site_admin));
        $this->assertTrue(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->site_trainer));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->site_manager));
        $this->assertTrue(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->course_trainer));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->course_manager));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->facilitator));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->learner));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->waiter));
        $this->assertFalse(has_capability('mod/facetoface:joinanyvirtualroom', $context, $this->pariah));
    }

    public function test_render_card_of_physical_room() {
        $room = (new room())->from_record($this->f2fgen->add_custom_room(['name' => 'Physical class room']));
        $this->assertNull($this->visit_card_from_manage($room, $this->site_admin));
        $this->assertNull($this->visit_card_from_manage($room, $this->site_trainer));
        $this->assertNull($this->visit_card_from_manage($room, $this->site_manager));
        $this->assertNull($this->visit_card_from_manage($room, $this->course_trainer));
        $this->assertNull($this->visit_card_from_manage($room, $this->course_manager));
        $this->assertNull($this->visit_card_from_manage($room, $this->facilitator));
        $this->assertNull($this->visit_card_from_manage($room, $this->learner));
        $this->assertNull($this->visit_card_from_manage($room, $this->waiter));
        $this->assertNull($this->visit_card_from_manage($room, $this->pariah));

        $session = $this->session_present;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertNull($this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertNull($this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertNull($this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertNull($this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertNull($this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertNull($this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertNull($this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertNull($this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertNull($this->visit_card_from_dashboard($session, $room, $this->pariah));
    }

    public function test_render_card_of_virtual_room() {
        $available = [
            'heading' => 'Virtual room',
            'simple' => true,
            'inactive' => false,
            'has_buttons' => true,
            'buttons' => [
                [
                    'text' => 'Go to room',
                    'url' => 'https://example.com?q=kia+ora#koutou',
                    'style' => 'primary',
                    'hint' => "Go to 'Virtual class room'",
                ],
            ],
            'copy' => [
                'text' => 'Copy room link',
                'icon' => [
                    'template' => 'core/flex_icon',
                ],
                'url' => 'https://example.com?q=kia+ora#koutou',
            ],
        ];
        $unavailable = [
            'heading' => 'Virtual room is unavailable',
            'simple' => true,
            'inactive' => true,
        ];
        $unwaitable = [
            'heading' => 'Virtual room is no longer available',
            'simple' => true,
            'inactive' => true,
        ];
        $waitable = [
            'heading' => 'Virtual room will open 15 minutes before next session',
            'simple' => false,
            'inactive' => false,
            'detailsection' => [
                'details' => [
                    [
                        'label' => 'Seminar',
                    ],
                    [
                        'label' => 'Session time',
                    ],
                ],
            ],
        ];
        $joinable = [
            'heading' => 'Virtual room',
            'simple' => false,
            'inactive' => false,
            'has_buttons' => true,
            'buttons' => [
                [
                    'text' => 'Join now',
                    'url' => 'https://example.com?q=kia+ora#koutou',
                    'style' => 'primary',
                    'hint' => "Join 'Virtual class room' now",
                ],
            ],
            'detailsection' => [
                'details' => [
                    [
                        'label' => 'Seminar',
                    ],
                    [
                        'label' => 'Session time',
                    ],
                ],
            ],
            'copy' => [
                'text' => 'Copy room link',
                'icon' => [
                    'template' => 'core/flex_icon',
                ],
                'url' => 'https://example.com?q=kia+ora#koutou',
            ],
        ];

        $room = (new room())->from_record($this->f2fgen->add_custom_room(['name' => 'Virtual class room', 'url' => 'https://example.com?q=kia+ora#koutou']));
        $this->assertEquals($available, $this->visit_card_from_manage($room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_manage($room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_manage($room, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_manage($room, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_manage($room, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_manage($room, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_manage($room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_manage($room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_manage($room, $this->pariah));

        $session = $this->session_future;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_near;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($joinable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_present;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($joinable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_past;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
    }

    /**
     * @group virtualmeeting
     */
    public function test_render_card_of_virtual_meeting_on_manage() {
        $room = $this->add_virtualmeeting('Virtual meeting room', $this->session_present);
        $this->assertNull($this->visit_card_from_manage($room, $this->site_admin));
        $this->assertNull($this->visit_card_from_manage($room, $this->site_trainer));
        $this->assertNull($this->visit_card_from_manage($room, $this->site_manager));
        $this->assertNull($this->visit_card_from_manage($room, $this->course_trainer));
        $this->assertNull($this->visit_card_from_manage($room, $this->course_manager));
        $this->assertNull($this->visit_card_from_manage($room, $this->facilitator));
        $this->assertNull($this->visit_card_from_manage($room, $this->learner));
        $this->assertNull($this->visit_card_from_manage($room, $this->waiter));
        $this->assertNull($this->visit_card_from_manage($room, $this->pariah));
    }

    /**
     * @group virtualmeeting
     */
    public function test_render_card_of_ghost_virtual_meeting_on_dashboard() {
        $unavailable = [
            'heading' => 'Virtual room is unavailable',
            'simple' => true,
            'inactive' => true,
        ];

        $room_future = $this->add_virtualmeeting('Virtual meeting room', $this->session_future);
        $room_near = $this->add_virtualmeeting('Virtual meeting room', $this->session_near);
        $room_present = $this->add_virtualmeeting('Virtual meeting room', $this->session_present);
        $room_past = $this->add_virtualmeeting('Virtual meeting room', $this->session_past);
        room_helper::sync($this->session_future->get_id(), [$room_future->get_id()]);
        room_helper::sync($this->session_near->get_id(), [$room_near->get_id()]);
        room_helper::sync($this->session_present->get_id(), [$room_present->get_id()]);
        room_helper::sync($this->session_past->get_id(), [$room_past->get_id()]);

        // Delete all virtual meeting records. This should leave room_dates_virtualmeeting orphaned.
        virtual_meeting_entity::repository()->delete();

        $session = $this->session_future;
        $room = $room_future;
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_near;
        $room = $room_near;
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_present;
        $room = $room_present;
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_past;
        $room = $room_past;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        poc_factory::toggle_info('poc_app', provider::INFO_HOST_URL, false);
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
    }

    /**
     * @group virtualmeeting
     */
    public function test_render_card_of_zombie_virtual_meeting_on_dashboard() {
        $unavailable = [
            'heading' => 'Virtual room is unavailable',
            'simple' => true,
            'inactive' => true,
        ];

        $room_future = $this->add_virtualmeeting('Virtual meeting room', $this->session_future);
        $room_near = $this->add_virtualmeeting('Virtual meeting room', $this->session_near);
        $room_present = $this->add_virtualmeeting('Virtual meeting room', $this->session_present);
        $room_past = $this->add_virtualmeeting('Virtual meeting room', $this->session_past);
        room_helper::sync($this->session_future->get_id(), [$room_future->get_id()]);
        room_helper::sync($this->session_near->get_id(), [$room_near->get_id()]);
        room_helper::sync($this->session_present->get_id(), [$room_present->get_id()]);
        room_helper::sync($this->session_past->get_id(), [$room_past->get_id()]);

        poc_factory::toggle('poc_app', false);

        $session = $this->session_future;
        $room = $room_future;
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_near;
        $room = $room_near;
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_present;
        $room = $room_present;
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_past;
        $room = $room_past;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        poc_factory::toggle_info('poc_app', provider::INFO_HOST_URL, false);
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
    }

    /**
     * @group virtualmeeting
     */
    public function test_render_card_of_available_virtual_meeting_on_dashboard() {
        $available = [
            'heading' => 'Virtual room: Fake Dev App',
            'simple' => true,
            'inactive' => false,
            'has_buttons' => true,
            'buttons' => [
                [
                    'text' => 'Go to room',
                    'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
                    'style' => 'primary',
                    'hint' => "Go to 'Virtual meeting room'",
                ],
            ],
            'preview' => '<p>info from admin</p>',
            'copy' => [
                'text' => 'Copy room link',
                'icon' => [
                    'template' => 'core/flex_icon',
                ],
                'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
            ],
        ];
        $unavailable = [
            'heading' => 'Virtual room is unavailable',
            'simple' => true,
            'inactive' => true,
        ];
        $unwaitable = [
            'heading' => 'Virtual room is no longer available',
            'simple' => true,
            'inactive' => true,
        ];
        $waitable = [
            'heading' => 'Virtual room will open 15 minutes before next session',
            'simple' => false,
            'inactive' => false,
            'detailsection' => [
                'details' => [
                    [
                        'label' => 'Seminar',
                    ],
                    [
                        'label' => 'Session time',
                    ],
                ]
            ],
        ];
        $joinable = [
            'heading' => 'Virtual room: Fake Dev App',
            'simple' => false,
            'inactive' => false,
            'has_buttons' => true,
            'buttons' => [
                [
                    'text' => 'Join now',
                    'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
                    'style' => 'primary',
                    'hint' => "Join 'Virtual meeting room' now",
                ],
            ],
            'detailsection' => [
                'details' => [
                    [
                        'label' => 'Seminar',
                    ],
                    [
                        'label' => 'Session time',
                    ],
                ]
            ],
            'copy' => [
                'text' => 'Copy room link',
                'icon' => [
                    'template' => 'core/flex_icon',
                ],
                'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
            ],
        ];

        $room_future = $this->add_virtualmeeting('Virtual meeting room', $this->session_future);
        $room_near = $this->add_virtualmeeting('Virtual meeting room', $this->session_near);
        $room_present = $this->add_virtualmeeting('Virtual meeting room', $this->session_present);
        $room_past = $this->add_virtualmeeting('Virtual meeting room', $this->session_past);

        poc_factory::toggle_info('poc_app', provider::INFO_HOST_URL, false);

        $session = $this->session_future;
        $room = $room_future;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_near;
        $room = $room_near;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($joinable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_present;
        $room = $room_present;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($joinable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_past;
        $room = $room_past;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
    }

    /**
     * @group virtualmeeting
     */
    public function test_render_card_of_hostable_virtual_meeting_on_dashboard() {
        $available = [
            'heading' => 'Virtual room: Fake Dev App',
            'simple' => true,
            'inactive' => false,
            'has_buttons' => true,
            'buttons' => [
                [
                    'text' => 'Go to room',
                    'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
                    'style' => 'primary',
                    'hint' => "Go to 'Virtual meeting room'",
                ],
            ],
            'preview' => '<p>info from admin</p>',
            'copy' => [
                'text' => 'Copy room link',
                'icon' => [
                    'template' => 'core/flex_icon',
                ],
                'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
            ],
        ];
        $hostable = [
            'heading' => 'Virtual room: Fake Dev App',
            'simple' => true,
            'inactive' => false,
            'has_buttons' => true,
            'buttons' => [
                [
                    'text' => 'Host meeting',
                    'url' => 'https://virtualmeeting.example.com/meet.php?host=1',
                    'style' => 'primary',
                    'hint' => "Start meeting as host of 'Virtual meeting room'",
                ],
                [
                    'text' => 'Join as attendee',
                    'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
                    'hint' => "Join 'Virtual meeting room' as attendee",
                ],
            ],
            'preview' => '<p>info from admin</p>',
            'copy' => [
                'text' => 'Copy room link',
                'icon' => [
                    'template' => 'core/flex_icon',
                ],
                'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
            ],
        ];
        $unavailable = [
            'heading' => 'Virtual room is unavailable',
            'simple' => true,
            'inactive' => true,
        ];
        $unwaitable = [
            'heading' => 'Virtual room is no longer available',
            'simple' => true,
            'inactive' => true,
        ];
        $waitable = [
            'heading' => 'Virtual room will open 15 minutes before next session',
            'simple' => false,
            'inactive' => false,
            'detailsection' => [
                'details' => [
                    [
                        'label' => 'Seminar',
                    ],
                    [
                        'label' => 'Session time',
                    ],
                ]
            ],
        ];
        $joinable = [
            'heading' => 'Virtual room: Fake Dev App',
            'simple' => false,
            'inactive' => false,
            'has_buttons' => true,
            'buttons' => [
                [
                    'text' => 'Join now',
                    'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
                    'style' => 'primary',
                    'hint' => "Join 'Virtual meeting room' now",
                ],
            ],
            'detailsection' => [
                'details' => [
                    [
                        'label' => 'Seminar',
                    ],
                    [
                        'label' => 'Session time',
                    ],
                ]
            ],
            'copy' => [
                'text' => 'Copy room link',
                'icon' => [
                    'template' => 'core/flex_icon',
                ],
                'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
            ],
        ];

        $room_future = $this->add_virtualmeeting('Virtual meeting room', $this->session_future);
        $room_near = $this->add_virtualmeeting('Virtual meeting room', $this->session_near);
        $room_present = $this->add_virtualmeeting('Virtual meeting room', $this->session_present);
        $room_past = $this->add_virtualmeeting('Virtual meeting room', $this->session_past);

        poc_factory::toggle_info('poc_app', provider::INFO_HOST_URL, true);

        $session = $this->session_future;
        $room = $room_future;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($hostable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($hostable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_near;
        $room = $room_near;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($hostable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($joinable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($hostable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_present;
        $room = $room_present;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($hostable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($joinable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($hostable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));

        $session = $this->session_past;
        $room = $room_past;
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $this->assertEquals($hostable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
        $session->get_seminar_event()->set_cancelledstatus(1)->save();
        $this->assertEquals($hostable, $this->visit_card_from_dashboard($session, $room, $this->site_admin));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->site_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_trainer));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->course_manager));
        $this->assertEquals($available, $this->visit_card_from_dashboard($session, $room, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($session, $room, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($session, $room, $this->pariah));
    }

    /**
     * @group virtualmeeting
     */
    public function test_render_card_based_on_virtual_meeting_status() {
        $available = [
            'heading' => 'Virtual room: Fake Dev App',
            'simple' => true,
            'inactive' => false,
            'has_buttons' => true,
            'buttons' => [
                [
                    'text' => 'Go to room',
                    'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
                    'style' => 'primary',
                    'hint' => "Go to 'Virtual meeting room'",
                ],
            ],
            'copy' => [
                'text' => 'Copy room link',
                'icon' => [
                    'template' => 'core/flex_icon',
                ],
                'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
            ],
        ];
        $waitable = [
            'heading' => 'Updating virtual room',
            'simple' => true,
            'inactive' => true,
            'instruction' => "Please wait\u{2026}",
        ];
        $unavailable = [
            'heading' => 'Virtual room is unavailable',
            'simple' => true,
            'inactive' => true,
        ];
        $unwaitable = [
            'heading' => 'Virtual room is no longer available',
            'simple' => true,
            'inactive' => true,
        ];
        $joinable = [
            'heading' => 'Virtual room: Fake Dev App',
            'simple' => false,
            'inactive' => false,
            'has_buttons' => true,
            'buttons' => [
                [
                    'text' => 'Join now',
                    'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
                    'style' => 'primary',
                    'hint' => "Join 'Virtual meeting room' now",
                ],
            ],
            'detailsection' => [
                'details' => [
                    [
                        'label' => 'Seminar',
                    ],
                    [
                        'label' => 'Session time',
                    ],
                ]
            ],
            'copy' => [
                'text' => 'Copy room link',
                'icon' => [
                    'template' => 'core/flex_icon',
                ],
                'url' => 'https://virtualmeeting.example.com/meet.php?host=0',
            ],
        ];

        poc_factory::toggle_info('poc_app', provider::INFO_HOST_URL, false);
        poc_factory::toggle_info('poc_app', provider::INFO_PREVIEW, false);

        $room_available = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_AVAILABLE);
        $room_unavailable = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_UNAVAILABLE);
        $room_legacy = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_LEGACY);
        $room_updating = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_PENDING_UPDATE);
        $room_deleting = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_PENDING_DELETION);
        $room_cannot_create = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_FAILURE_CREATION);
        $room_cannot_update = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_FAILURE_UPDATE);
        $room_cannot_delete = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_FAILURE_DELETION);

        $this->assertEquals($available, $this->visit_card_from_dashboard($this->session_present, $room_available, $this->site_admin));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_unavailable, $this->site_admin));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_legacy, $this->site_admin));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_updating, $this->site_admin));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_deleting, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_create, $this->site_admin));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_update, $this->site_admin));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_delete, $this->site_admin));

        $this->assertEquals($available, $this->visit_card_from_dashboard($this->session_present, $room_available, $this->site_manager));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_unavailable, $this->site_manager));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_legacy, $this->site_manager));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_updating, $this->site_manager));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_deleting, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_create, $this->site_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_update, $this->site_manager));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_delete, $this->site_manager));

        $this->assertEquals($available, $this->visit_card_from_dashboard($this->session_present, $room_available, $this->site_trainer));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_unavailable, $this->site_trainer));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_legacy, $this->site_trainer));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_updating, $this->site_trainer));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_deleting, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_create, $this->site_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_update, $this->site_trainer));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_delete, $this->site_trainer));

        $this->assertEquals($available, $this->visit_card_from_dashboard($this->session_present, $room_available, $this->course_manager));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_unavailable, $this->course_manager));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_legacy, $this->course_manager));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_updating, $this->course_manager));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_deleting, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_create, $this->course_manager));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_update, $this->course_manager));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_delete, $this->course_manager));

        $this->assertEquals($available, $this->visit_card_from_dashboard($this->session_present, $room_available, $this->course_trainer));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_unavailable, $this->course_trainer));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_legacy, $this->course_trainer));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_updating, $this->course_trainer));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_deleting, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_create, $this->course_trainer));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_update, $this->course_trainer));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_delete, $this->course_trainer));

        $this->assertEquals($available, $this->visit_card_from_dashboard($this->session_present, $room_available, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_unavailable, $this->facilitator));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_legacy, $this->facilitator));
        $this->assertEquals($waitable, $this->visit_card_from_dashboard($this->session_present, $room_updating, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_deleting, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_create, $this->facilitator));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_update, $this->facilitator));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_delete, $this->facilitator));

        $this->assertEquals($joinable, $this->visit_card_from_dashboard($this->session_present, $room_available, $this->learner));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_unavailable, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_legacy, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_updating, $this->learner));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_deleting, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_create, $this->learner));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_update, $this->learner));
        $this->assertEquals($unwaitable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_delete, $this->learner));

        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_available, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_unavailable, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_legacy, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_updating, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_deleting, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_create, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_update, $this->waiter));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_delete, $this->waiter));

        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_available, $this->pariah));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_unavailable, $this->pariah));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_legacy, $this->pariah));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_updating, $this->pariah));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_deleting, $this->pariah));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_create, $this->pariah));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_update, $this->pariah));
        $this->assertEquals($unavailable, $this->visit_card_from_dashboard($this->session_present, $room_cannot_delete, $this->pariah));
    }

    /**
     * @group virtualmeeting
     */
    public function test_render_banner_based_on_virtual_meeting_status() {
        $retryable = [
            'message' => 'Failed to update the virtual room. <a href="https://example.com/mod/facetoface/events/edit.php?backtoallsessions=1">Please click to assign the room again.</a>',
            'extraclasses' => 'mod_facetoface__resource-card__notification',
        ];

        $men_in_black = $this->getDataGenerator()->create_user();
        $room_sitewide = (new room())->from_record($this->f2fgen->add_site_wide_room(['name' => 'Virtual class room']));
        $room_custom = (new room())->from_record($this->f2fgen->add_custom_room(['name' => 'Virtual class room', 'url' => 'https://example.com?q=kia+ora#koutou']));
        $room_available = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_AVAILABLE, $men_in_black->id);
        $room_unavailable = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_UNAVAILABLE, $men_in_black->id);
        $room_legacy = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_LEGACY, $men_in_black->id);
        $room_updating = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $men_in_black->id);
        $room_deleting = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_PENDING_DELETION, $men_in_black->id);
        $room_cannot_create = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_FAILURE_CREATION, $men_in_black->id);
        $room_cannot_update = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_FAILURE_UPDATE, $men_in_black->id);
        $room_cannot_delete = $this->add_virtualmeeting('Virtual meeting room', $this->session_present, room_dates_virtualmeeting::STATUS_FAILURE_DELETION, $men_in_black->id);

        $users = [$this->site_admin, $this->site_trainer, $this->site_manager, $this->course_trainer, $this->course_manager, $this->facilitator, $this->learner, $this->waiter, $this->pariah];
        foreach ($users as $user) {
            $this->assertNull($this->visit_banner_from_manage($room_sitewide, $user));
            $this->assertNull($this->visit_banner_from_manage($room_custom, $user));
            $this->assertNull($this->visit_banner_from_manage($room_available, $user));
            $this->assertNull($this->visit_banner_from_manage($room_unavailable, $user));
            $this->assertNull($this->visit_banner_from_manage($room_legacy, $user));
            $this->assertNull($this->visit_banner_from_manage($room_updating, $user));
            $this->assertNull($this->visit_banner_from_manage($room_deleting, $user));
            $this->assertNull($this->visit_banner_from_manage($room_cannot_create, $user));
            $this->assertNull($this->visit_banner_from_manage($room_cannot_update, $user));
            $this->assertNull($this->visit_banner_from_manage($room_cannot_delete, $user));

            $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_available, $user));
            $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_unavailable, $user));
            $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_legacy, $user));
            $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_updating, $user));
            $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_deleting, $user));
            $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_cannot_create, $user));
            $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_cannot_update, $user));
            $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_cannot_delete, $user));
        }

        $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_sitewide, $men_in_black));
        $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_custom, $men_in_black));
        $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_available, $men_in_black));
        $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_unavailable, $men_in_black));
        $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_legacy, $men_in_black));
        $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_updating, $men_in_black));
        $this->assertNull($this->visit_banner_from_dashboard($this->session_present, $room_deleting, $men_in_black));
        $this->assertEquals($retryable, $this->visit_banner_from_dashboard($this->session_present, $room_cannot_create, $men_in_black));
        $this->assertEquals($retryable, $this->visit_banner_from_dashboard($this->session_present, $room_cannot_update, $men_in_black));
        $this->assertEquals($retryable, $this->visit_banner_from_dashboard($this->session_present, $room_cannot_delete, $men_in_black));
    }
}
