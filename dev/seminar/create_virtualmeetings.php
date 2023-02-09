<?php
/**
 * This file is part of Totara LMS
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
 */

use core\plugininfo\virtualmeeting;
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_helper;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use totara_core\entity\virtual_meeting;
use totara_core\entity\virtual_meeting_config;
use totara_core\http\clients\simple_mock_client;

define('CLI_SCRIPT', true);

require(__DIR__.'/../../server/config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'username' => false,
        'help' => false,
    ],
    [
        'u' => 'username',
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (!empty($options['help'])) {
    cli_writeln('Generate seminars with virtual meetings for testing.

** WARNING **
The edit event page displays a "too many virtual meetings" error.
The generated virtual meetings have an invalid meeting ID.
Some plugin settings might be overridden by the generator.

Options:
    -u, --username        Set the username of the meeting host.
    -h, --help            Print out this help
');
    exit(0);
}

if (!empty($options['username'])) {
    $userid = core_user::get_user_by_username($options['username'], 'id', null, MUST_EXIST)->id;
} else {
    $userid = 2; // admin
}

abstract class meeting_seeder {
    abstract protected function init(virtualmeeting $plugin);
    abstract public function name(): string;
    abstract public function seed(virtual_meeting $vm): void;

    public static function create(): meeting_seeder {
        $plugins = virtualmeeting::get_all_plugins();
        if ($plugin = $plugins['msteams'] ?? null) {
            $seeder = new msteams_seeder();
        } else if ($plugin = $plugins['poc_app'] ?? null) {
            $seeder = new poc_seeder();
        } else {
            throw new Exception('No generators available for the current setup');
        }
        /** @var meeting_seeder $seeder */
        $seeder->init($plugin);
        return $seeder;
    }
}

class msteams_seeder extends meeting_seeder {
    protected function init(virtualmeeting $plugin) {
        if (!$plugin->is_available()) {
            foreach (['client_id', 'client_secret'] as $name) {
                if (!get_config('virtualmeeting_msteams', $name)) {
                    set_config($name, 'x', 'virtualmeeting_msteams');
                }
            }
        }
    }
    public function name(): string {
        return 'msteams';
    }
    public function seed(virtual_meeting $vm): void {
        global $CFG;
        $vmc = new virtual_meeting_config();
        $vmc->virtualmeetingid = $vm->id;
        $vmc->name = 'meeting_id';
        $vmc->value = random_string();
        $vmc->save();
        $vmc = new virtual_meeting_config();
        $vmc->virtualmeetingid = $vm->id;
        $vmc->name = 'join_url';
        $vmc->value = $CFG->wwwroot;
        $vmc->save();
        $vmc = new virtual_meeting_config();
        $vmc->virtualmeetingid = $vm->id;
        $vmc->name = 'preview';
        $vmc->value = self::preview($CFG->wwwroot, $CFG->wwwroot . '/totara/catalog/index.php');
        $vmc->save();
    }
    private static function preview(string $join_url, string $option_url) {
        $div = function ($class, $style, $contents) {
            return html_writer::div($contents, $class, ['style' => $style]);
        };
        $span = function ($class, $style, $contents) {
            return html_writer::span($contents, $class, ['style' => $style]);
        };
        $a = function ($class, $style, $url, $text) {
            return html_writer::link($url, $text, ['class' => $class, 'style' => $style, 'target' => '_blank', 'rel' => 'noreferrer noopener']);
        };
        $empty1 = $div('', "font-size:14px;margin-bottom:4px;font-family:'Segoe UI','Helvetica Neue',Helvetica,Arial,sans-serif;", '');
        $empty2 = $div('', 'font-size:12px;', '');
        $body = $div(
            'me-email-text',
            "color:#252424;font-family:'Segoe UI','Helvetica Neue',Helvetica,Arial,sans-serif;",
            $div(
                '',
                'margin-top:24px;margin-bottom:20px;',
                $span(
                    '',
                    'font-size:24px;color:#252424',
                    'Microsoft Teams meeting'
                )
            ) .
            $div(
                '',
                'margin-bottom:20px;',
                $div(
                    '',
                    'margin-top:0px;margin-bottom:0px;font-weight:bold',
                    $span('', 'font-size:14px;color:#252424', 'Join on your computer or mobile app')
                ) .
                $a(
                    'me-email-headline',
                    "font-size:14px;font-family:'Segoe UI Semibold','Segoe UI','Helvetica Neue',Helvetica,Arial,sans-serif;text-decoration:underline;color:#6264a7;",
                    $join_url,
                    'Click here to join the meeting'
                )
            ) .
            $div(
                '',
                'margin-bottom:24px;margin-top:20px;',
                $a(
                    'me-email-link',
                    "font-size:14px;text-decoration:underline;color:#6264a7;font-family:'Segoe UI','Helvetica Neue',Helvetica,Arial,sans-serif;",
                    'https://aka.ms/JoinTeamsMeeting',
                    'Learn More'
                ) . ' | ' .
                $a(
                    'me-email-link',
                    "font-size:14px;text-decoration:underline;color:#6264a7;font-family:'Segoe UI','Helvetica Neue',Helvetica,Arial,sans-serif;",
                    $option_url,
                    'Meeting options'
                )
            )
        );
        return $body . $empty1 . $empty2;
    }
}

class poc_seeder extends meeting_seeder {
    protected function init(virtualmeeting $plugin) {
        if (!$plugin->is_available()) {
            set_config('virtualmeeting_poc_app_enabled', 1, 'totara_core');
        }
        set_config('virtualmeeting_poc_app_host_url', 1, 'totara_core');
    }
    public function name(): string {
        return 'poc_app';
    }
    public function seed(virtual_meeting $vm): void {
        global $CFG;
        $vmc = new virtual_meeting_config();
        $vmc->virtualmeetingid = $vm->id;
        $vmc->name = 'id';
        $vmc->value = random_string();
        $vmc->save();
        $vmc = new virtual_meeting_config();
        $vmc->virtualmeetingid = $vm->id;
        $vmc->name = 'age';
        $vmc->value = 1;
        $vmc->save();
        $vmc = new virtual_meeting_config();
        $vmc->virtualmeetingid = $vm->id;
        $vmc->name = 'join_url';
        $vmc->value = $CFG->wwwroot;
        $vmc->save();
        $vmc = new virtual_meeting_config();
        $vmc->virtualmeetingid = $vm->id;
        $vmc->name = 'host_url';
        $vmc->value = $CFG->wwwroot . '/totara/dashboard/index.php';
        $vmc->save();
    }
}

$gen = \core\testing\generator::instance();
$f2fgen = \mod_facetoface\testing\generator::instance();
$seeder = meeting_seeder::create();

/** @var moodle_database $DB */

$trans = $DB->start_delegated_transaction();
try {
    $fullname = "Test course for seminar virtual meetings";
    $shortname = 'TCFSVM';
    for ($i = 1;; $i++) {
        if (!$DB->record_exists('course', ['shortname' => $shortname])) {
            break;
        }
        $fullname = "Test course for seminar virtual meetings ({$i})";
        $shortname = "TCFSVM{$i}";
    }
    $courseid = $gen->create_course([
        'fullname' => $fullname,
        'shortname' => $shortname,
        'idnumber' => '',
        'category' => $CFG->defaultrequestcategory ?? 1,
        'summary' => "Test course",
        'enablecompletion' => 1,
    ])->id;

    $time = time() + 900;
    $timeses = [
        'Near future' => [
            [
                'timestart' => $time,
                'timefinish' => $time + 3600,
            ]
        ],
        'Future' => [
            [
                'timestart' => strtotime('5 May next year 5am'),
                'timefinish' => strtotime('5 May next year 5pm'),
            ]
        ],
        'Waitlist' => [
        ],
        'Ongoing 1' => [
            [
                'timestart' => strtotime('3 Mar last year 3am'),
                'timefinish' => strtotime('3 Mar next year 3pm'),
            ]
        ],
        'Ongoing 2' => [
            [
                'timestart' => strtotime('2 Feb last year 2am'),
                'timefinish' => strtotime('2 Feb last year 2pm'),
            ],
            [
                'timestart' => strtotime('4 Apr next year 4am'),
                'timefinish' => strtotime('4 Apr next year 4pm'),
            ]
        ],
        'Past' => [
            [
                'timestart' => strtotime('1 Jan last year 1am'),
                'timefinish' => strtotime('1 Jan last year 1pm'),
            ]
        ],
    ];
    $statuses = [
        'Unavailable' => [room_dates_virtualmeeting::STATUS_UNAVAILABLE, true],
        'Available' => [room_dates_virtualmeeting::STATUS_AVAILABLE, true],
        'Creating' => [room_dates_virtualmeeting::STATUS_PENDING_UPDATE, false],
        'Updating' => [room_dates_virtualmeeting::STATUS_PENDING_UPDATE, true],
        'Deleting' => [room_dates_virtualmeeting::STATUS_PENDING_DELETION, true],
        "\u{1F525} create" => [room_dates_virtualmeeting::STATUS_FAILURE_CREATION, false],
        "\u{1F525} update" => [room_dates_virtualmeeting::STATUS_FAILURE_UPDATE, true],
        "\u{1F525} delete" => [room_dates_virtualmeeting::STATUS_FAILURE_DELETION, true],
    ];
    foreach ($timeses as $name => $times) {
        $seminar = new seminar($f2fgen->create_instance([
            'course' => $courseid,
            'intro' => '',
            'description' => '',
            'capacity' => 100,
            'name' => "Seminar {$name}",
            'shortname' => $name,
            'sessionattendance' => 2,
            'attendancetime' => 2,
            'eventgradingmanual' => 1,
            'completionpass' => 1,
        ])->id);
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($seminar->get_id())->save();
        $rooms = [];
        foreach ($statuses as $sname => [$status, $create]) {
            $room = room::create_custom_room();
            $room->set_name($sname)->save();
            (new room_virtualmeeting())
                ->set_roomid($room->get_id())
                ->set_plugin($seeder->name())
                ->set_userid($userid)
                ->set_status(room_virtualmeeting::STATUS_CONFIRMED)
                ->save();
            $rooms[$room->get_id()] = [$status, $room];
        }
        foreach ($times as $time) {
            $session = new seminar_session();
            $session->set_sessionid($seminarevent->get_id())->set_timestart($time['timestart'])->set_timefinish($time['timefinish'])->set_sessiontimezone('99')->save();
            foreach ($rooms as [$status, $room]) {
                /** @var room $room */
                $vmid = null;
                if ($create) {
                    $vm = new virtual_meeting();
                    $vm->plugin = $seeder->name();
                    $vm->userid = $userid;
                    $vm->save();
                    $seeder->seed($vm);
                    $vmid = $vm->id;
                }
                (new room_dates_virtualmeeting())
                    ->set_sessionsdateid($session->get_id())
                    ->set_roomid($room->get_id())
                    ->set_virtualmeetingid($vmid)
                    ->set_status($status)
                    ->save();
            }
            room_helper::sync($session->get_id(), array_keys($rooms));
        }
    }
    $trans->allow_commit();
    cli_writeln("Generated seminars and virtualmeetings in {$fullname} as user #{$userid}.");
} catch (Throwable $ex) {
    $trans->rollback();
    cli_error('Failed. ' . $ex->getMessage());
}
