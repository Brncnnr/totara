<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package dev_learn
 */

use core\testing\generator;
use hierarchy_organisation\entity\organisation;
use totara_hierarchy\testing\generator as hierarchy_generator;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment;
use totara_program\testing\generator as program_generator;

$conf = new stdClass();
$conf->usercount = 5000;
// Creates categories_per_level ^ category_depth categories
$conf->categories_per_level = 4;
$conf->category_depth = 3;
$conf->courses_per_category = [0, 1, 1, 5, 5, 5, 20, 100];
$conf->activities_per_course = [0, 1, 1, 5, 5, 10, 20, 100];
$conf->enrolments_per_course = [0, 3, 6, 12, 24, 48, 384, 1536];
$conf->chance_of_role_overrides = 0.5;
$conf->min_role_overrides_per_cat = 1;
$conf->max_role_overrides_per_cat = 10;
$conf->learning_items_created = 0;
// This sets an upper limit on the number of courses/programs/certs
$conf->max_learning_items = 240000;

// Creates orgs_per_level ^ org_depth organisations
$conf->orgs_per_level = 6;
$conf->org_depth = 5;

$test = new stdClass();
$test->usercount = 200;
$test->categories_per_level = 2;
$test->category_depth = 2;
$conf->courses_per_category = [0, 1, 2, 5];
$conf->activities_per_course = [0, 1, 2, 5];
$conf->enrolments_per_course = [0, 1, 2, 5];
$test->orgs_per_level = 3;
$test->org_depth = 3;

define('CLI_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', true);

// No logging.
define('LOG_MANAGER_CLASS', '\core\log\dummy_manager');

$config = __DIR__ . '/../../server/config.php';
require($config);
require_once($CFG->libdir . '/clilib.php');

[$options, $filters] = cli_get_params(
    ['help' => false, 'agree' => false],
    ['h' => 'help', 'a' => 'agree', 't' => 'test']
);

if (!empty($options['help'])) {
    $basename = basename(__FILE__);
    cli_writeln("Create a large site with an SF-182 workflow and many overrides and applications.

Usage:
    php {$basename} {-a|--agree} [-t|--test]

Required parameters:
    -a, --agree       Agree that this is for development purposes only

Options:
    -h, --help        Print out this help
    -t, --test        Install just a few items instead of thousands

Example:
  $ php {$basename} --agree
");
    exit(2);
}

if (empty($options['agree'])) {
    cli_error(
        cli_logo(0, true) . PHP_EOL .
        'A required parameter is missing.' . PHP_EOL .
        'Please pass `--help` to see the help.' . PHP_EOL .
        'This script is for development purposes only, do not use it on a production site.',
        1
    );
}

if (!empty($options['test'])) {
    foreach ($test as $prop => $value) {
        $conf->{$prop} = $value;
    }
}

// Do stuff as admin user
core\session\manager::set_user(get_admin());

$conf->gen = generator::instance();
$conf->proggen = program_generator::instance();
$conf->orggen = hierarchy_generator::instance();
$conf->activity_gen = $conf->gen->get_plugin_generator('mod_assign');
$conf->roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
$conf->audience_visibilities = [COHORT_VISIBLE_AUDIENCE, COHORT_VISIBLE_ENROLLED, COHORT_VISIBLE_ALL, COHORT_VISIBLE_NOUSERS];
$conf->permissions = [CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT];
$conf->capabilities = ['moodle/course:view', 'moodle/course:visibility', 'moodle/course:viewhiddencourses'];

print "Creating users...\n";
$conf->users = array();
for ($u = 1; $u <= $conf->usercount; $u++) {
    $user = $conf->gen->create_user();
    $conf->users[$u] = $user->id;
    print ".";
    if ($u % 50 == 0) {
        print " {$u}\n";
    }
}
$u--;
print "\nDone with {$u} users.\n\n";

print "Creating audiences...\n";
$conf->audience1 = $conf->gen->create_cohort();
$conf->audience2 = $conf->gen->create_cohort();
$conf->audience3 = $conf->gen->create_cohort();
$conf->audience4 = $conf->gen->create_cohort();
$audiences = [$conf->audience1->id, $conf->audience2->id, $conf->audience3->id, $conf->audience4->id];
foreach ($conf->users as $u => $user) {
    cohort_add_member($audiences[array_rand($audiences)], $user);
    print ".";
    if ($u % 50 == 0) {
        print " {$u}\n";
    }
}
print "\nDone with $u audiences.\n\n";

function create_subcategories( $cat, $depth ) {
    global $conf;
    $user_limit = $conf->usercount;
    $next_depth = $depth + 1;
    for ($tlc = 1; $tlc <= $conf->categories_per_level; $tlc++) {
        $uniq = substr(uniqid(), -4);
        $visible = round(rand(4, 10) / 10);
        if ($depth < 2) {
            $visible = 1;
        }
        if (!empty($cat->name)) {
            $catname = $cat->name . '-'.$uniq;
        } else {
            $catname = $uniq;
        }
        if ($visible < 1) {
            $catname .= '()';
        }
        $newcat = $conf->gen->create_category(['name' => $catname, 'parent' => $cat->id, 'visible' => $visible, 'description' => 'Test']);
        print "Created category {$newcat->name}\n";
        $catcontext = context_coursecat::instance($newcat->id);

        // Maybe assign a role to a random user in this category.
        if ($depth > 2 && rand(0,2)) {
            role_assign($conf->roleids['editingteacher'], $conf->users[rand(0,$conf->usercount)], $catcontext);
        }

        // Maybe add some role overrides in this category.
        if ($depth > 1 && (mt_rand() / mt_getrandmax()) <= $conf->chance_of_role_overrides) {
            $num = rand($conf->min_role_overrides_per_cat, $conf->max_role_overrides_per_cat);
            print "Creating {$num} role overrides\n";
            for ($i = 1; $i < $num; $i++) {
                $cap = $conf->capabilities[array_rand($conf->capabilities)];
                $perm = $conf->permissions[array_rand($conf->permissions)];
                role_change_permission($conf->roleids['editingteacher'], $catcontext, $cap, $perm);
            }
        }

        if ($next_depth <= $conf->category_depth) {
            create_subcategories($newcat, $next_depth);
            if ($conf->learning_items_created > $conf->max_learning_items) {
                return;
            }
        }
        $courses_limit = $conf->courses_per_category[rand(0, count($conf->courses_per_category) - 1)];
        for ($c = 1; $c <= $courses_limit; $c++) {
            $conf->learning_items_created++;
            if ($conf->learning_items_created > $conf->max_learning_items) {
                print "Done creating items.\n\n";
                return;
            }
            $course_def = [];
            $course_def['visible'] = round(rand(3, 10) / 10);
            $course_def['audiencevisible'] = $conf->audience_visibilities[rand(0,3)];
            $course_def['fullname'] = $uniq . ' course ' . $c . ' ';
            if (!$course_def['visible']) {
                $course_def['fullname'] .= '(hidden)';
            }
            if ($course_def['audiencevisible']) {
                $course_def['fullname'] .= '(audiencevis)';
            }
            $course_def['shortname'] = $uniq . ' course ' . $c . ' '.uniqid();
            $course_def['category'] = $newcat->id;
            $course_def['summary'] = 'Summary here.';
            // course, program, or certification?
            if ($conf->learning_items_created < 10) {
                $coursetype = 0;
            } else {
                $coursetype = rand(0,2);
            }
            switch ($coursetype) {
                case 0:
                    $activity_limit = $conf->activities_per_course[rand(0, count($conf->activities_per_course) - 1)];
                    $enrol_limit = $conf->enrolments_per_course[rand(0, count($conf->enrolments_per_course) - 1)];
                    $course_def['fullname'] .= "({$activity_limit} activities)({$enrol_limit} enrolees)";
                    $course = $conf->gen->create_course($course_def);
                    print "{$conf->learning_items_created}: Created Course {$course->fullname}\n";

                    // Create some activities
                    for ($i = 0; $i < $activity_limit; $i++) {
                        $conf->activity_gen->create_instance(array('course' => $course->id));
                    }
                    print "     - created {$activity_limit} activities\n";

                    $a = rand(1, 4);
                    totara_cohort_add_association($conf->{'audience' . $a}->id, $course->id, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);

                    // Enrol a teacher
                    $seed = rand(1, $user_limit);
                    $conf->gen->enrol_user($conf->users[$seed], $course->id, $conf->roleids['teacher']);

                    // Enrol some learners
                    for ($i = 0; $i < $enrol_limit; $i++) {
                        $seed = rand(1, $user_limit);
                        $conf->gen->enrol_user($conf->users[$seed], $course->id, $conf->roleids['student']);
                    }
                    print "     - enrolled {$enrol_limit} users\n";
                    break;

                case 1:
                    $course_def['fullname'] = str_replace('course', 'program', $course_def['fullname']);

                    $assign_limit = $conf->enrolments_per_course[rand(0, count($conf->enrolments_per_course) - 1)];
                    $numcourses = $conf->courses_per_category[rand(0, count($conf->courses_per_category) - 1)];
                    $course_def['fullname'] .= "({$assign_limit} individuals)({$numcourses} courses)";
                    $program = $conf->proggen->create_program($course_def);

                    print "{$conf->learning_items_created}: Created Program {$course_def['fullname']}\n";

                    $a = rand(1,4);
                    totara_cohort_add_association($conf->{'audience'.$a}->id, $program->id, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_VALUE_VISIBLE);

                    // Assign some learners
                    for ($i = 0; $i < $assign_limit; $i++) {
                        $seed = rand(1, $user_limit);
                        $conf->proggen->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $conf->users[ $seed ]);
                    }
                    print "     - assigned {$assign_limit} individual users\n";

                    // Add some courses
                    if ($numcourses > 0) {
                        $conf->proggen->add_courseset_to_program($program->id, 1, $numcourses);
                        print "     - added {$numcourses} as a course set\n";
                    }
                    break;

                case 2:
                    $course_def['fullname'] = str_replace('course', 'certification', $course_def['fullname']);

                    $assign_limit = $conf->enrolments_per_course[rand(0, count($conf->enrolments_per_course) - 1)];
                    $numcourses = $conf->courses_per_category[rand(0, count($conf->courses_per_category) - 1)];
                    $course_def['fullname'] .= "({$assign_limit} individuals)({$numcourses} courses)";

                    $certification = $conf->proggen->create_certification($course_def);

                    print "{$conf->learning_items_created}: Created Certification {$course_def['fullname']}\n";

                    $a = rand(1,4);
                    totara_cohort_add_association($conf->{'audience'.$a}->id, $certification->id, COHORT_ASSN_ITEMTYPE_CERTIF, COHORT_ASSN_VALUE_VISIBLE);

                    // Assign some learners
                    for ($i = 0; $i < $assign_limit; $i++) {
                        $seed = rand(1, $user_limit);
                        $conf->proggen->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $conf->users[ $seed ]);
                    }
                    print "     - assigned {$assign_limit} individual users\n";

                    // Add some courses
                    if ($numcourses > 0) {
                        $conf->proggen->add_courseset_to_program($certification->id, 1, $numcourses);
                        print "     - added {$numcourses} as a course set\n";
                    }
                    break;
            }
        }
    }
}

function create_organisations($org, int $depth) {
    global $conf;
    $next_depth = $depth + 1;
    for ($tlc = 1; $tlc <= $conf->orgs_per_level; $tlc++) {
        $uniq = substr(uniqid(), -4);
        if (!empty($org->fullname)) {
            $orgname = $org->fullname . '-'.$uniq;
            $shortname = $org->id . '_' . $uniq;
        } else {
            $orgname = $uniq;
            $shortname = $uniq;
        }

        /* @param \hierarchy_organisation\entity\organisation $org */
        $params = [
            'frameworkid' => $org->frameworkid,
            'fullname' => $orgname,
            'idnumber' => 'org_' . $uniq . '_' . $depth . '_' . $tlc,
            'shortname' => $shortname,
            'parentid' => $org->id
        ];

        $neworg = $conf->orggen->create_org($params);
        print "Created organisation {$neworg->fullname}\n";

        // Create a user in the organisation, with a job assignment with a manager.
        $applicant = new \core\entity\user($conf->users[rand(1, $conf->usercount)]);
        $manager = new \core\entity\user($conf->users[rand(1, $conf->usercount)]);
        if ($applicant->id == $manager->id) {
            print "** Skipping applicant manager conflict.\n";
        }

        $data = [
            'userid' => $manager->id,
            'idnumber' => 'manager_' . $neworg->idnumber,
            'organisationid' => $neworg->id,
            'fullname' => $neworg->fullname . ' Manager',
        ];
        $rec = job_assignment::create($data);
        $manager_ja = new \totara_job\entity\job_assignment($rec->id);

        $data = [
            'userid' => $applicant->id,
            'idnumber' => 'applicant_' . $neworg->idnumber,
            'organisationid' => $neworg->id,
            'fullname' => $neworg->fullname . ' Applicant',
            'managerjaid' => $manager_ja->id,
        ];
        $rec = job_assignment::create($data);
        $ja = new job_assignment_entity($rec->id);

        if ($next_depth <= $conf->org_depth) {
            create_organisations($neworg, $next_depth);
        }
    }
}

$now = time();
core\session\manager::set_user(get_admin());

$hierarchy_generator = hierarchy_generator::instance();
$uniq = substr(uniqid(), -4);
$framework = $hierarchy_generator->create_framework('organisation', ['fullname' => $uniq . ' Framework']);
$params = [
    'frameworkid' => $framework->id,
    'fullname' => 'Top ' . $uniq,
    'idnumber' => $uniq,
    'shortname' => 'org'
];
$org = $hierarchy_generator->create_org($params);
$top_org = new organisation($org->id);
create_organisations($top_org, 0);

$top = new stdClass();
$top->name = '';
$top->id = 0;
create_subcategories($top, 0);

print "Done\n";