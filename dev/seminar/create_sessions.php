<?php
/**
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 */

use mod_facetoface\{asset_helper, facilitator_helper, room_helper};
use mod_facetoface\{seminar, signup, signup_status};
use mod_facetoface\signup\state\{booked, event_cancelled, waitlisted};

define('CLI_SCRIPT', true);

require(__DIR__.'/../../server/config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'facilitators' => false,
        'rooms' => false,
        'assets' => false,
        'help' => false,
    ],
    [
        'f' => 'facilitators',
        'r' => 'rooms',
        'a' => 'assets',
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (!empty($options['help'])) {
    cli_writeln('Generate a learner, a trainer, courses, seminars with events, sessions and resources for testing.

Options:
    -f, --facilitators    Do *NOT* create facilitators
    -a, --assets          Do *NOT* create assets
    -r, --rooms           Do *NOT* create rooms
    -h, --help            Print out this help
');
    exit(0);
}

$gen = \core\testing\generator::instance();
$users = ['f2flearner' => ['Learner', 'student'], 'f2ftrainer' => ['Trainer', 'teacher']];

if (empty($options['facilitators'])) {
    $users['f2ffacilitator'] = ['Facilitator', 'student'];
}

/** @var moodle_database $DB */
$catid = $DB->get_field('course_categories', 'id', ['id' => $CFG->defaultrequestcategory, 'issystem' => 0]);
if (!$catid) {
    $catid = $DB->get_field('course_categories', 'id', ['name' => 'Miscellaneous', 'issystem' => 0]);
}
if (!$catid) {
    cli_error('Cannot find a usable category. Make sure $CFG->defaultrequestcategory points to a valid category ID.');
}

$userids = [];
foreach ($users as $username => [$lastname, ]) {
    if (($userid = $DB->get_field('user', 'id', ['username' => $username])) == false) {
        $user = array(
            'firstname' => 'Seminar',
            'lastname' => $lastname,
            'firstnamephonetic' => '',
            'lastnamephonetic' => '',
            'middlename' => 'Test',
            'alternatename' => '',
            'idnumber' => '',
            'username' => $username,
            'password' => $username,
            'email' => $username . '@example.com',
        );
        $userid = $gen->create_user($user)->id;
        cli_writeln("User {$username} created.");
    }
    $userids[$username] = $userid;
}

$f2fgen = \mod_facetoface\testing\generator::instance();

$roomids = [];
if (empty($options['rooms'])) {
    $types = ['Physical' => '', 'Virtual' => $CFG->dirroot];
    foreach ($types as $type => $url) {
        $name = "Test {$type} Room";
        if (($roomid = $DB->get_field('facetoface_room', 'id', ['name' => $name])) == false) {
            $roomid = $f2fgen->add_site_wide_room([
                'name' => $name,
                'capacity' => 30,
                'allowconflicts' => 1,
                'url' => $url,
                'description' => "{$type} room for testing",
                'usercreated' => 2, // admin
            ])->id;
            cli_writeln("{$name} created.");
        }
        $roomids[] = $roomid;
    }
}

$assetids = [];
if (empty($options['assets'])) {
    $name = 'Test Asset';
    if (($assetid = $DB->get_field('facetoface_asset', 'id', ['name' => $name])) == false) {
        $assetid = $f2fgen->add_site_wide_asset([
            'name' => $name,
            'allowconflicts' => 1,
            'description' => "asset for testing",
            'usercreated' => 2, // admin
        ])->id;
        cli_writeln("{$name} created.");
    }
    $assetids[] = $assetid;
}

$facilitatorids = [];
if (empty($options['facilitators'])) {
    $create_if_not_exists = function ($type, $stype, $userid) use ($DB, $f2fgen) {
        $name = "Test {$stype} Facilitator";
        if (($facilitatorid = $DB->get_field('facetoface_facilitator', 'id', ['name' => $name])) == false) {
            $facilitatorid = $f2fgen->add_site_wide_facilitator([
                'name' => $name,
                'userid' => $userid,
                'allowconflicts' => 1,
                'description' => "{$type} facilitator for testing",
                'usercreated' => 2, // admin
            ])->id;
            cli_writeln("{$name} created.");
        }
        return $facilitatorid;
    };
    $facilitatorids[] = $create_if_not_exists('External', 'Ext', 0);
    $facilitatorids[] = $create_if_not_exists('Internal', 'Int', $userids['f2ffacilitator']);
}


$time = time() + 900;
$timeses = [
    'Near future' => [
        (object)[
            'sessiontimezone' => '99',
            'timestart' => $time,
            'timefinish' => $time + 3600,
        ]
    ],
    'Future' => [
        (object)[
            'sessiontimezone' => '99',
            'timestart' => strtotime('5 May next year 5am'),
            'timefinish' => strtotime('5 May next year 5pm'),
        ]
    ],
    'Waitlist' => [
    ],
    'Ongoing 1' => [
        (object)[
            'sessiontimezone' => '99',
            'timestart' => strtotime('3 Mar last year 3am'),
            'timefinish' => strtotime('3 Mar next year 3pm'),
        ]
    ],
    'Ongoing 2' => [
        (object)[
            'sessiontimezone' => '99',
            'timestart' => strtotime('2 Feb last year 2am'),
            'timefinish' => strtotime('2 Feb last year 2pm'),
        ],
        (object)[
            'sessiontimezone' => '99',
            'timestart' => strtotime('4 Apr next year 4am'),
            'timefinish' => strtotime('4 Apr next year 4pm'),
        ]
    ],
    'Past' => [
        (object)[
            'sessiontimezone' => '99',
            'timestart' => strtotime('1 Jan last year 1am'),
            'timefinish' => strtotime('1 Jan last year 1pm'),
        ]
    ],
];

for ($i = 1; $i <= 2; $i++) {
    for ($j = 1; $j <= 2; $j++) {
        $idnum = "course-f2f-gen-test-{$i}-{$j}";
        $stat = '';
        if ($i == 1) {
            $stat .= ' Booked';
        }
        if ($j == 2) {
            $stat .= ' Cancelled';
        }
        if (($courseid = $DB->get_field('course', 'id', ['idnumber' => $idnum])) == false) {
            $name = 'CTF2F';
            if ($i == 1) {
                $name .= 'B';
            }
            if ($j == 2) {
                $name .= 'C';
            }
            $courseid = $gen->create_course([
                'fullname' => "Course Test for Seminar{$stat}",
                'shortname' => $name,
                'idnumber' => $idnum,
                'category' => $catid,
                'summary' => "Test course for TL-28458",
                'enablecompletion' => 1,
            ])->id;
            $context = context_course::instance($courseid);
            foreach ($userids as $username => $userid) {
                $rolename = $users[$username][1];
                if ($rolename != 'student') {
                    $trainerrole = $DB->get_record('role', ['shortname' => $rolename]);
                    $gen->role_assign($trainerrole->id, $userid, $context->id);
                }
                $gen->enrol_user($userid, $courseid, $rolename);
            }
            cli_writeln("Course {$idnum} created.");
        }
        foreach ($DB->get_records('facetoface', ['course' => $courseid], 'id') as $f2f) {
            (new seminar())->map_instance($f2f)->delete();
            cli_writeln("Seminar {$f2f->shortname} deleted.");
        }

        $k = 0;
        foreach ($timeses as $spec => $times) {
            $k++;
            $name = "Seminar {$spec}{$stat}";
            $shortname = "seminar-f2f-gen-{$i}-{$j}-{$k}";
            $f2fid = $f2fgen->create_instance([
                'course' => $courseid,
                'intro' => '',
                'description' => '',
                'capacity' => 100,
                'name' => $name,
                'shortname' => $shortname,
                'sessionattendance' => 2,
                'attendancetime' => 2,
                'eventgradingmanual' => 1,
                'completionpass' => 1,
            ])->id;
            cli_writeln("{$name} created.");

            $eventid = $f2fgen->add_session([
                'facetoface' => $f2fid,
                'sessiondates' => $times,
            ]);
            foreach ($DB->get_records('facetoface_sessions_dates', ['sessionid' => $eventid], 'timestart DESC') as $sess) {
                room_helper::sync($sess->id, $roomids);
                facilitator_helper::sync($sess->id, $facilitatorids);
                asset_helper::sync($sess->id, $assetids);
            }
            if ($i == 1) {
                foreach ($userids as $username => $userid) {
                    if ($username == 'f2ffacilitator') {
                        continue;
                    }
                    $signup = new signup();
                    $signup->set_userid($userid);
                    $signup->set_sessionid($eventid);
                    $signup->save();
                    if (empty($times)) {
                        signup_status::create($signup, new waitlisted($signup))->save();
                    } else {
                        signup_status::create($signup, new booked($signup))->save();
                    }
                    if ($j == 2) {
                        $signup = signup::create($userid, $eventid, 0);
                        signup_status::create($signup, new event_cancelled($signup))->save();
                    }
                }
            }
            if ($j == 2) {
                $DB->set_field('facetoface_sessions', 'cancelledstatus', 1, array('id' => $eventid));
            }
            cli_writeln("Event {$eventid} created.");
        }
    }
}
