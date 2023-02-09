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

use mod_facetoface\{asset_helper, calendar, facilitator_helper, resource_helper, room_helper, seminar_event, seminar_session};
use mod_facetoface\{signup, signup_status};
use mod_facetoface\signup\state\{booked};

define('CLI_SCRIPT', true);

require(__DIR__.'/../../server/config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'magnitude' => false,
        'commit' => false,
        'direct' => false,
        'help' => false,
    ],
    [
        'm' => 'magnitude',
        'c' => 'commit',
        'd' => 'direct',
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (!empty($options['help'])) {
    cli_writeln('Generate a seminar with lots of events, sessions and resources for stress testing.

Options:
    -m, --magnitude=size    Specify one of S, M, L, XL, XXL or Lunatic
    -c, --commit            Commit transaction
    -d, --direct            Do not use transaction
    -h, --help              Print out this help
');
    exit(0);
}

function fake_rand(int $min, int $max): int {
    static $seed = 20210301; // init seed is fixed
    if ($min >= $max) {
        [$min, $max] = [$max, $min];
    }
    $seed = ($seed * 125530871 + 12294533) % 65536;
    return $min + (int)(($max - $min) * ($seed / 65536));
}

class stopwatch {
    private $time;
    public function start(): void {
        $this->time = microtime();
    }
    public function stop(): string {
        return sprintf('Took %.06f seconds.', microtime_diff($this->time, microtime()));
    }
}

$magnitudesmap = [
    's' => [5, 3, 1],
    'm' => [10, 5, 3],
    'l' => [100, 10, 5],
    'xl' => [500, 30, 10],
    'xxl' => [2000, 100, 20],
    'lunatic' => [10000, 1000, 100],
];
if (!empty($options['magnitude'])) {
    $m = strtolower($options['magnitude']);
    if (isset($magnitudesmap[$m])) {
        $magnitudes = $magnitudesmap[$m];
    }
}
if (!isset($magnitudes)) {
    $magnitudes = $magnitudesmap['m'];
}

/** @var moodle_database $DB */
$catid = $DB->get_field('course_categories', 'id', ['id' => $CFG->defaultrequestcategory, 'issystem' => 0]);
if (!$catid) {
    $catid = $DB->get_field('course_categories', 'id', ['name' => 'Miscellaneous', 'issystem' => 0]);
}
if (!$catid) {
    cli_error('Cannot find a usable category. Make sure $CFG->defaultrequestcategory points to a valid category ID.');
}

$gen = \core\testing\generator::instance();
$f2fgen = \mod_facetoface\testing\generator::instance();

if (empty($options['direct'])) {
    $tx = $DB->start_delegated_transaction();
} else {
    $tx = null;
}

$watch = new stopwatch();
/** @var integer[] */
$userids = [];
$watch->start();
for ($i = 1; $i <= $magnitudes[1]; $i++) {
    $username = "moreuser{$i}";
    $user = array(
        'firstname' => 'Seminar',
        'lastname' => 'User',
        'firstnamephonetic' => '',
        'lastnamephonetic' => '',
        'middlename' => 'More',
        'alternatename' => '',
        'idnumber' => '',
        'username' => $username,
        'password' => $username,
        'email' => $username . '@example.com',
    );
    $userids[$username] = $gen->create_user($user)->id;
}
cli_writeln(count($userids) . " users created. " . $watch->stop());

$i = null;
do {
    $name = "CTF2FS{$i}";
    $i++;
} while ($DB->record_exists('course', ['shortname' => $name]));

$watch->start();
$courseid = $gen->create_course([
    'fullname' => "Course Test for Stress Seminar",
    'shortname' => $name,
    'category' => $catid,
    'summary' => "Test course for TL-29800",
    'enablecompletion' => 1,
])->id;
$context = context_course::instance($courseid);
foreach ($userids as $username => $userid) {
    $rolename = 'student';
    if (fake_rand(0, 10) < 7) {
        $rolename = 'teacher';
        $trainerrole = $DB->get_record('role', ['shortname' => $rolename]);
        $gen->role_assign($trainerrole->id, $userid, $context->id);
    }
    $gen->enrol_user($userid, $courseid, $rolename);
}
cli_writeln("Course {$name} created. " . $watch->stop());

$watch->start();
$name = "Stress Seminar";
$f2fid = $f2fgen->create_instance([
    'course' => $courseid,
    'intro' => '',
    'description' => '',
    'capacity' => 100,
    'name' => $name,
    'sessionattendance' => 2,
    'attendancetime' => 2,
    'eventgradingmanual' => 1,
    'completionpass' => 1,
    'showoncalendar' => 1, // F2F_CAL_COURSE
    'usercalentry' => 1,
])->id;
cli_writeln("{$name} created. " . $watch->stop());

$watch->start();
$numsessions = 0;
/** @var seminar_event[] */
$events = [];
for ($i = 0; $i < $magnitudes[0]; $i++) {
    $event = new seminar_event();
    $event->set_facetoface($f2fid)->set_capacity(fake_rand(1, 100));
    $event->save();
    $events[$event->get_id()] = $event;
}
cli_writeln(count($events) . " events created. " . $watch->stop());

$watch->start();
$time = strtotime('6am');
foreach ($events as $event) {
    // give it a nice curve.
    $r = 0.00001 * fake_rand(0, 100000);
    $maxsessions = $magnitudes[1] * (6 * pow($r, 5) - 15 * pow($r, 4) + 10 * pow($r, 3));
    $timestarts = [];
    while (count($timestarts) < $maxsessions) {
        $t = fake_rand(-5000, 5000);
        $timestarts[$t] = $time + $t * 2 + HOURSECS;
    }
    $sessiondates = [];
    foreach ($timestarts as $timestart) {
        $sess = new seminar_session();
        $sess->set_sessionid($event->get_id())->set_timestart($timestart)->set_timefinish($timestart + HOURSECS)->set_sessiontimezone('99');
        $sess->save();
    }
    $numsessions += $event->get_sessions()->count();
}
cli_writeln("{$numsessions} sessions created. " . $watch->stop());

$watch->start();
$resourceids = ['room' => [], 'asset' => [], 'facilitator' => []];
for ($i = 1; $i <= $magnitudes[2]; $i++) {
    $url = $i % 2 ? $CFG->wwwroot : '';
    $resourceids['room'][] = $f2fgen->add_site_wide_room([
        'name' => "Stress Room {$i}",
        'capacity' => fake_rand(1, 30),
        'allowconflicts' => 1,
        'url' => $url,
        'description' => "room for testing",
        'usercreated' => 2, // admin
    ])->id;
    $resourceids['asset'][] = $f2fgen->add_site_wide_asset([
        'name' => "Stress Asset {$i}",
        'allowconflicts' => 1,
        'description' => "asset for testing",
        'usercreated' => 2, // admin
    ])->id;
    $r = fake_rand(0, count($userids) * 5);
    if ($r < count($userids)) {
        $userid = array_values($userids)[$r];
    } else {
        $userid = 0;
    }
    $resourceids['facilitator'][] = $f2fgen->add_site_wide_facilitator([
        'name' => "Stress Facilitator {$i}",
        'userid' => $userid,
        'allowconflicts' => 1,
        'description' => "facilitator for testing",
        'usercreated' => 2, // admin
    ])->id;
}
cli_writeln(count($resourceids['room']) . " × 3 resources created. " . $watch->stop());

$numresources = 0;
foreach ($events as $event) {
    /** @var seminar_session */
    foreach ($event->get_sessions() as $sess) {
        foreach ($resourceids as $type => $ids) {
            // give it another nice curve.
            $r = 0.00001 * fake_rand(0, 100000);
            $size = (int)(count($ids) * (1 - pow((1 - $r), 1.5)));
            if ($size > 0) {
                $syncids = array_slice($ids, 0, $size);
                resource_helper::sync_resources($sess->get_id(), $syncids, $type);
                $numresources += $size;
            }
        }
    }
}
cli_writeln("{$numresources} resources synchronised. " . $watch->stop());

$watch->start();
$numsignups = 0;
foreach ($events as $event) {
    foreach ($userids as $username => $userid) {
        if (fake_rand(0, 10) > 7) {
            $signup = new signup();
            $signup->set_userid($userid);
            $signup->set_sessionid($event->get_id());
            $signup->save();
            signup_status::create($signup, new booked($signup))->save();
            $numsignups++;
        }
    }
}
cli_writeln("{$numsignups} signed up. " . $watch->stop());

$watch->start();
foreach ($events as $event) {
    calendar::update_entries($event);
}
cli_writeln("Calendar entries updated for no reason. " . $watch->stop());

$watch->start();
foreach ($events as $event) {
    /** @var seminar_session */
    foreach ($event->get_sessions() as $sess) {
        if (fake_rand(0, 10) > 7) {
            $timediff = 5 * YEARSECS * (fake_rand(0, 100 > 50) ? -1 : +1);
            $sess->set_timestart($sess->get_timestart() + $timediff);
            $sess->set_timefinish($sess->get_timefinish() + $timediff);
            $sess->save();
        }
    }
    calendar::update_entries($event);
}
cli_writeln("Calendar entries updated for a reason. " . $watch->stop());

if ($tx) {
    if (!empty($options['commit'])) {
        $tx->allow_commit();
    } else {
        $tx->rollback();
    }
}