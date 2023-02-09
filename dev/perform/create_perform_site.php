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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

use core\entity\cohort as cohort_entity;
use core\orm\query\builder;
use core\webapi\execution_context;
use criteria_childcompetency\childcompetency;
use criteria_coursecompletion\coursecompletion;
use criteria_linkedcourses\linkedcourses;
use criteria_onactivate\onactivate;
use mod_perform\constants;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\subject_instance;
use mod_perform\expand_task as expand_assignments_task;
use mod_perform\models\activity\track;
use mod_perform\models\activity\track_assignment_type;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\user_groups\grouping;
use mod_perform\webapi\resolver\mutation\update_track_schedule;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\models\assignment_actions;
use core\entity\user;
use totara_competency\linked_courses;
use totara_criteria\criterion;
use totara_evidence\entity\evidence_type;
use totara_evidence\models\evidence_item;
use totara_job\job_assignment;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

define('CLI_SCRIPT', 1);

require __DIR__ . '/../../server/config.php';

/** @var core_config $CFG */

require_once($CFG->dirroot . '/lib/clilib.php');
require_once($CFG->dirroot . '/lib/gdlib.php');

global $options;
[$options, $cli_unrecognized] = cli_get_params([
    'help' => false,
    'multilang' => false,
    'multitenancy' => false,
]);

if ($options['help']) {
    echo "Perform site generator.
Use this script to create a site with users, job assignments, positions, organisations, competencies, courses, learning plans and evidence.

Usage: php totara/competency/cli/create_perform_site.php [options]

Options:

  --multilang        Enable multilang header and content strings for generated data
  --multitenancy     Enable multitenancy on this site and put users into tenants
  --help             Show this screen

";
    exit(1);
}

if (competency::repository()->exists()) {
    echo "This script has already been run on this installation. Please try again on a clean site.\n";
    exit(1);
}

echo "This script will create data for the competency profile functionality testing and demo.\n";
echo "This will take ~15 seconds.";

// Do stuff as admin user
core\session\manager::set_user(get_admin());

db()->transaction(Closure::fromCallable('create_data'));

echo "\nSite setup complete!\n";

function create_data() {
    global $options, $CFG, $DB;

    if ($options['multitenancy']) {
        enable_multitenancy();
    }

    if ($options['multilang']) {
        enable_multilang();
    }

    $generator = generator();
    $competency_generator = competency_generator();
    $evidence_generator = evidence_generator();
    $evidence_generator->set_create_files(true);
    $hierarchy_generator = hierarchy_generator();
    $admin_user = get_admin();

    $data = [
        'tenants' => null,
        'users' => [],
        'pos' => [],
        'orgs' => [],
        'audiences' => [],
        'scales' => [],
        'comps' => [],
        'description' => '<h1>What to look for on this site?</h1><hr/><p>
This is to have some demonstration of the competency profile and related functionality, so far there is a few pages available,
including the competency profile page itself with various graphs and table. Different competencies have different scales, some users have achievements, some not.
Please remember: <i>This is still work in progress.</i><br/>
Feel free to browse, list of users is below, their password is 12345.
</p>'
    ];

    $data['tenants'] = create_tenants();

    // First we need to create a few users
    $users = [
        'jm' => [
            'firstname' => 'Jannie',
            'lastname' => 'Malkovich',
            'caps' => [
                'totara/competency:view_own_profile',
            ],
            'description' => multilang('Has all available assignments (no archived) and has completed every course.'),
            'tenant' => 2,
            'city' => 'Wellington',
            'country' => 'NZ',
            'institution' => 'strange land',
            'department' => 'video game/movie',
        ],
        'ss' => [
            'firstname' => 'Sine',
            'lastname' => 'Seagal',
            'caps' => [
                'totara/competency:view_own_profile',
            ],
            'description' => multilang('Has all available assignments, some archived.'),
            'tenant' => 2,
            'city' => 'Auckland',
            'country' => 'NZ',
            'institution' => 'UV',
            'department' => 'system',
        ],
        'dt' => [
            'firstname' => 'Denny',
            'lastname' => 'Thomsen',
            'caps' => [
                'totara/competency:view_own_profile',
            ],
            'description' => multilang('Has one current and one archived assignment.'),
            'tenant' => 1,
            'city' => 'Sydney',
            'country' => 'AU',
            'institution' => "Psyc",
            'department' => "Ward",
        ],
        'jt' => [
            'firstname' => 'Joan',
            'lastname' => 'Travolta',
            'caps' => [
                'totara/competency:view_own_profile',
            ],
            'description' => multilang('Has 7 assignments'),
            'tenant' => 1,
            'city' => 'Hororata',
            'country' => 'NZ',
            'institution' => 'strange land',
            'department' => 'video game/movie',
        ],
        'ut' => [
            'firstname' => 'Ulla',
            'lastname' => 'Thuesen',
            'caps' => [
                'totara/competency:view_own_profile'
            ],
            'description' => multilang('Has 10 assignments + archived'),
            'tenant' => 2,
            'city' => 'Wellington',
            'country' => 'US',
            'institution' => "Psyc",
            'department' => "Ward",
        ],
        'sj' => [
            'firstname' => 'Samuel',
            'lastname' => 'Jansen',
            'caps' => [
                'totara/competency:view_own_profile'
            ],
            'description' => multilang('Has 3 assignments'),
            'tenant' => 2,
            'city' => 'Denver',
            'country' => 'US',
            'institution' => 'UV',
            'department' => 'system',
        ],
        'tr' => [
            'firstname' => 'Tim',
            'lastname' => 'Rosenberg',
            'caps' => [
                'totara/competency:view_own_profile'
            ],
            'description' => multilang('Has something.'),
            'tenant' => 2,
            'city' => 'Los Angeles',
            'country' => 'US',
            'institution' => 'strange land',
            'department' => 'video game/movie',
        ],
        'bw' => [
            'firstname' => 'Birthe',
            'lastname' => 'Willis',
            'caps' => [
                'totara/competency:view_own_profile'
            ],
            'description' => multilang('Has a self-assignment and an audience assignment.'),
            'tenant' => 2,
            'city' => 'Wellinton',
            'country' => 'NZ',
            'institution' => 'strange land',
            'department' => 'video game/movie',
        ],
        'vp' => [
            'firstname' => 'Victoria',
            'lastname' => 'Povlsen',
            'caps' => [
                'totara/competency:view_own_profile'
            ],
            'description' => multilang('Has no assignments.'),
            'tenant' => 1,
            'city' => 'Los Angeles',
            'country' => 'US',
            'institution' => 'strange land',
            'department' => 'video game/movie',
        ],
        'bo' => [
            'firstname' => 'Birthe',
            'lastname' => 'Olsen',
            'caps' => [
            ],
            'description' => multilang('Has assignments, but cannot view his competency profile.'),
            'tenant' => 1,
            'city' => 'Sydney',
            'country' => 'AU',
            'institution' => "Psyc",
            'department' => "Ward",
        ],
        'gb' => [
            'firstname' => 'Gunnar',
            'lastname' => 'Bush',
            'caps' => [
                'totara/competency:view_own_profile'
            ],
            'description' => multilang('Has only archived assignments.'),
            'tenant' => 1,
            'city' => 'New York',
            'country' => 'US',
            'institution' => 'UV',
            'department' => 'system',
        ],
        'gm' => [
            'firstname' => 'Glenn',
            'lastname' => 'Matthews',
            'caps' => [
                'totara/competency:view_own_profile'
            ],
            'description' => multilang('Has exactly one current assignment'),
            'tenant' => 1,
            'city' => 'Auckland',
            'country' => 'NZ',
            'institution' => 'strange land',
            'department' => 'video game/movie'
        ],
    ];

    $counter = count($users);
    foreach ($users as $key => $user) {
        $user['idnumber'] = random_string(5) . $key;
        $user['address'] = mt_rand(1, 100).' '.random_string(8).' Street, '.random_string(8);
        $user['phone1'] = implode('-',str_split(mt_rand(100000000,999999999),3));
        $user['phone2'] = implode('-',str_split(mt_rand(100000000,999999999),3));
        $new_user = create_user($user, $key, $data['tenants']);

        // Upload user icon to current new user
        if ($counter % 3 === 0) {
            $new_picture = (int)process_new_icon(context_user::instance($new_user->id), 'user', 'icon', 0, $CFG->dirroot . '/pix/g/f2.png');
            if ($new_picture > 0) {
                $DB->set_field('user', 'picture', $new_picture, ['id' => $new_user->id]);
            }
        }
        $counter--;
        $data['users'][$key] = $new_user;
    }

    // Then we need to create a few scale values
    $scales = [
        'low-scale' => [
            'name' => multilang('Low detail scale value'),
            'description' => multilang('This is a rough competency scale value'),
            'values' => [
                [
                    'name' => multilang('Competent'),
                    'description' => multilang('Rough definition of being competent'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('Not competent'),
                    'description' => multilang('Rough definition of not being competent'),
                ],
            ]
        ],

        'overboard-scale' => [
            'name' => multilang('Unnecessary detailed scale'),
            'description' => multilang('This is a very descriptive competency scale value'),
            'values' => [
                [
                    'name' => multilang('Extremely competent'),
                    'description' => multilang('No doubt this fella is competent'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('Competent'),
                    'description' => multilang('There is some merit co call it competent'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('Competent on Tuesdays'),
                    'description' => multilang('Competent, but only on Tuesdays, do not ask why.'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('Not competent on Tuesdays'),
                    'description' => multilang('Not competent, but only on Tuesdays, do not ask why.'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('Below average'),
                    'description' => multilang('We can not call it competent for just yet, maybe wait for Tuesday.'),
                ],
                [
                    'name' => multilang('Not competent'),
                    'description' => multilang('Why does this value even exist?'),
                ],
            ]
        ],

        '4-value-scale' => [
            'name' => multilang('4 steps to success'),
            'description' => multilang('Marketing driven scale'),
            'values' => [
                [
                    'name' => multilang('Competent'),
                    'description' => multilang('Competent. Full stop.'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('Competent under supervision'),
                    'description' => multilang('Success master supervision is required at all times'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('On a path to success'),
                    'description' => multilang('Not competent, but only on Tuesdays, do not ask why.'),
                ],
                [
                    'name' => multilang('Newcomer'),
                    'description' => multilang('Checkout is in the far left corner'),
                ],
            ]
        ],

        'star-wars' => [
            'name' => multilang('To infinity and beyond'),
            'description' => multilang('Force driven scale'),
            'values' => [
                [
                    'name' => multilang('Sith Lord'),
                    'description' => multilang('Cannot go beyond that. Do not confuse with sikh.'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('Joined the dark side'),
                    'description' => multilang('Clearly on a path to success, your lightsaber glows red now.'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('Jedi'),
                    'description' => multilang('It is pronounced Jed i'),
                    'proficient' => true,
                ],
                [
                    'name' => multilang('Young padawan'),
                    'description' => multilang('There is much to learn on your path. Do not kill your mentor.'),
                ],
                [
                    'name' => multilang('Youngling'),
                    'description' => multilang('If a new padawan puts a hood on - run away.'),
                ],
            ]
        ],
    ];
    $data['scales']['default'] = (object) totara_hierarchy\entity\scale::repository()->order_by('id')->first()->to_array();

    foreach ($scales as $key => $scale) {
        $data['scales'][$key] = create_scale($scale);
    }

    // Then we need to create some competency types
    $competency_types = [
        ['fullname' => multilang('Machinery & transport'), 'idnumber' => 'machine'],
        ['fullname' => multilang('Management'), 'idnumber' => 'management'],
        ['fullname' => multilang('Medical'), 'idnumber' => 'medical'],
    ];
    foreach ($competency_types as $type) {
        $data['competency-types'][$type['idnumber']] = $hierarchy_generator->create_comp_type($type);
    }

    // Then we need to create a few competency frameworks
    $competencies = [
        'binary' => [
            'fullname' => multilang('Binary competencies'),
            'description' => multilang('Descriptions that suppose to have competent or not only values'),
            'scale' => $data['scales']['low-scale']->id,
            'competencies' => [
                'literate' => [
                    'fullname' => multilang('Being literate'),
                    'description' => multilang('The name speaks for itself'),
                ],

                'doer' => [
                    'fullname' => multilang('Complete tasks independently'),
                    'description' => multilang('No need for constant badgering to complete a task'),
                    'parent' => 'literate',
                ],

                'initiative' => [
                    'fullname' => multilang('Show initiative and come up with ideas'),
                    'description' => multilang('Being able to come up with ideas'),
                    'parent' => 'literate',
                ],

                'collider' => [
                    'fullname' => multilang('Manage The Large Hadron Collider at CERN'),
                    'description' => multilang('Nothing too complicated, just another box ticked'),
                    'parent' => 'doer',
                    'typeid' => $data['competency-types']['machine'],
                ],
            ],
        ],

        'complex' => [
            'fullname' => multilang('Fine-grained competencies'),
            'description' => multilang('Various competencies that require fine-tuned skills assessment to determine proficiency.'),
            'scale' => $data['scales']['overboard-scale']->id,
            'competencies' => [
                'consultant' => [
                    'fullname' => multilang('Sales consultant'),
                    'description' => multilang('It is not as easy as you think to become a sales consultant'),
                ],

                'nurse' => [
                    'fullname' => multilang('Registered nurse'),
                    'description' => multilang('You can not create content without adding a registered nurse in there'),
                    'typeid' => $data['competency-types']['medical'],
                ],

                'administrative-nurse' => [
                    'fullname' => multilang('Registered administrative nurse'),
                    'description' => multilang('Become an administrative registered nurse'),
                    'typeid' => $data['competency-types']['medical'],
                ],

                'surgeon' => [
                    'fullname' => multilang('Fully qualified surgeon'),
                    'description' => multilang('Surgeries are serious business'),
                    'typeid' => $data['competency-types']['medical'],
                ],

                'priest' => [
                    'fullname' => multilang('Fully qualified reverent'),
                    'description' => multilang('9 circles of hell'),
                ],

                'zoo-keeper' => [
                    'fullname' => multilang('Fully qualified zoo keeper'),
                    'description' => multilang('Do not provoke the gator'),
                ],

                'camp-ground-manager' => [
                    'fullname' => multilang('Fully qualified camp ground manager'),
                    'description' => multilang('You know, this one is on the skilled migrant shortage list.'),
                    'typeid' => $data['competency-types']['management'],
                ],
            ],
        ],

        '4-value' => [
            'fullname' => multilang('Random competencies'),
            'description' => multilang('Mediocrity and courage'),
            'scale' => $data['scales']['4-value-scale']->id,
            'competencies' => [
                'netflix' => [
                    'fullname' => multilang('Netflix Qualified'),
                    'description' => multilang('It takes some skills to pick a show on Netflix, can you?'),
                ],
                'shop-keeper' => [
                    'fullname' => multilang('Shop keeper'),
                    'description' => multilang('Keep a hillbilly away and try to survive'),
                    'typeid' => $data['competency-types']['management'],
                ],
                'machinery-operator' => [
                    'fullname' => multilang('Heavy machinery operator on a rainy day'),
                    'description' => multilang('Fire up the digger and start shoveling'),
                    'typeid' => $data['competency-types']['machine'],
                ],
                'it' => [
                    'fullname' => multilang('Internet Troll'),
                    'description' => multilang('Do you have what it takes to troll people on the Internet?'),
                ],
                'sommelier' => [
                    'fullname' => multilang('Professional Sommelier'),
                    'description' => multilang('Do you smell it?'),
                ],
                'barista' => [
                    'fullname' => multilang('Professional Barista'),
                    'description' => multilang('Please put your cups on the coffee machine to pre-warm it for the customers'),
                ],
                'bartender' => [
                    'fullname' => multilang('Professional Bartender'),
                    'description' => multilang('The first thing they teach you is not to drink on the job'),
                ],
                'mad-preacher' => [
                    'fullname' => multilang('Mad preacher'),
                    'description' => multilang('Nuff said, you must excel to be proficient in this discipline.'),
                ],
            ]
        ],

        'star-wars' => [
            'fullname' => multilang('Fantasy saga competencies'),
            'description' => multilang('In a galaxy far far away...'),
            'scale' => $data['scales']['star-wars']->id,
            'competencies' => [
                'lightsaber' => [
                    'fullname' => multilang('Mastering a lightsaber'),
                    'description' => multilang('It takes time to master an art of using lightsaber in a combat and make it effective against blasters'),
                ],
                'pod-racer' => [
                    'fullname' => multilang('Pod racer'),
                    'description' => multilang('On your path to success, you will need to master pod racing'),
                    'typeid' => $data['competency-types']['machine'],
                ],
                'storm-trooper' => [
                    'fullname' => multilang('Qualified storm trooper'),
                    'description' => multilang('Start a path to be a professional storm trooper, be above an average clone to stand out'),
                ],
                'sith-lord' => [
                    'fullname' => multilang('Become the Sith Lord'),
                    'description' => multilang('Begin your journey to become an evil mastermind starting in a dark corner of Tatooine'),
                ],
            ]
        ],

        'arbitrary' => [
            'fullname' => multilang('Casual competencies'),
            'description' => multilang('Something you might want to achieve casually'),
            'scale' => $data['scales']['default']->id,
            'competencies' => [
                'teeth-whitening' => [
                    'fullname' => multilang('Teeth whitening'),
                    'description' => multilang('Professional dentists study for a long time to perform that'),
                    'typeid' => $data['competency-types']['medical'],
                ],
                'hoarder' => [
                    'fullname' => multilang('Extra-compulsive hoarder'),
                    'description' => multilang('I don\'t think that I can let this competency slide'),
                ],
                'cc' => [
                    'fullname' => multilang('Couch critic'),
                    'description' => multilang('This competency is more like an achievement'),
                ],
            ]
        ],

        'bs' => [
            'fullname' => multilang('PDPD Behavioural Competency Guide'),
            'description' => multilang('These are examples of the observable behaviours which relate to the competency. They are grouped and ordered to reflect complexity, level 1 being indicators for lower level jobs and level 4/5 indicators for senior or specialist roles and therefore demanding a higher level of competency, however, this does not mean for higher level roles the less complex indicators are not relevant or important. Note there is not a direct read across between the levels in the indicators and the grade structure, as some specialist roles at more junior levels may demand a higher level of application for some competencies. Therefore, the manager/reviewer and member of staff should have a discussion to agree the expected level of competency required for the role and level of the role holder. https://www.nottingham.ac.uk/hr/guidesandsupport/performanceatwork/pdpr/pdpr-behavioural-competency-guide/competency-framework.aspx'),
            'scale' => $data['scales']['default']->id,
            'competencies' => [
                // Achieving and delivery
                'drive' => [
                    'fullname' => multilang('Drive for Results'),
                    'description' => multilang('Success is not just about following the rules. We need people committed to making the University a success. ‘Drive for results’ is the enthusiasms and desire to meet and exceed objectives, University targets and improve one’s own performance. It is about being frustrated with the status quo, wanting to improve the way we do things and making it happen. At a higher level it is about calculated risk taking in the interest of improving overall University performance.'),
                ],
                'serving' => [
                    'fullname' => multilang('Serving the Customer'),
                    'description' => multilang('This is the desire to anticipate, meet and exceed the needs and expectations of customers (internally and externally). It implies working together and building long-term customer relationships and focusing one\'s efforts on delivering increased customer value. At levels D and E it requires effective championing and partnership working.'),
                ],
                'quality' => [
                    'fullname' => multilang('Quality Focus'),
                    'description' => multilang('This is about demonstrating the underlying drive to ensure that quality is not compromised within the working environment. It includes the identification and maintenance of standards to meet the needs of the University, together with a desire for accuracy, order and safety in the workplace. At levels 3 and 4 it is about encouraging and monitoring the actions of others to maintain high standards.'),
                ],
                'integrity' => [
                    'fullname' => multilang('Integrity'),
                    'description' => multilang('This is about acting in a way that is consistent with what one says or values and the expectations of both the University and the HE Sector. It requires a demonstration of commitment to openness and ethical values. It includes taking time to respect and understand others and be transparent and honest in all dealing with people internal and external to the University.'),
                ],

                // Personal effectiveness
                'planning' => [
                    'fullname' => multilang('Planning, organising and flexibility'),
                    'description' => multilang('This is about adopting a methodical approach to work. It involves planning and organising oneself and others in order to deliver work and prevent future problems. This includes the ability to adapt and change plans as the requirements of the situation change. At the higher levels it involves thinking long-term, strategically and creatively.'),
                ],
                'confidence' => [
                    'fullname' => multilang('Confidence and self-control'),
                    'description' => multilang('This is a belief in one\'s own capability to accomplish a task and select an effective approach to a task or problem. This includes confidence in one\'s ability as expressed in increasingly challenging circumstances and confidence in one\'s decisions and opinions. The essence of this behaviour is the question, \'Does the person take on risky or difficulty tasks or measured conflicts with those in power over that person\'? Level D and E are primarily about assertiveness and confidence with one\'s boss or others in more senior positions, not with staff or peers.'),
                ],
                'problem-solving' => [
                    'fullname' => multilang('Problem solving and initiative'),
                    'description' => multilang('This is about engaging in proactive behaviour, seizing opportunities and originating action which goes beyond simply responding to the obvious needs of the situation or to direct requests from others. It is coming up with new or different ideas, or adapting ideas from elsewhere in the University or externally. It is concerned with moving the University forward by applying new ideas or old ideas in a new way to generate solutions and approaches. At the higher levels it is about thinking laterally and creating new concepts.'),
                ],
                'info-seeking' => [
                    'fullname' => multilang('Critical information seeking'),
                    'description' => multilang('Critical information seeking requires a selective approach to gathering information aimed at getting the really crucial pieces of information. The ability to seek out information based on an underlying curiosity or desire to know more about subject area, University issues, people, and the sector. It includes asking questions that go beyond what is routine, in order to \'dig\' or press for exact information. Critical information seeking is essential for making sure your decisions are firmly grounded in reality, and that they are the best they can be. '),
                ],

                // Working together
                'communication' => [
                    'fullname' => multilang('Communicating with clarity'),
                    'description' => multilang('This is about the ability to impart accurate information (both verbal and written) in a timely way and be receptive to other peoples\' opinions. It is also about sharing information across University boundaries and externally. At the higher level, it is about making University communication and understanding with other bodies outside the University more effective.'),
                ],
                'embracing' => [
                    'fullname' => multilang('Embracing change'),
                    'description' => multilang('This is about the ability to make changes to the way you work, adapting to changing circumstances in the University by accepting new and different ideas and approaches. It includes the ability to sustain performance under conditions of rapid change. At higher levels, it is concerned with supporting others through change and having the willingness and ability to enable changes to take place in the most productive way.'),
                ],
                'collaborating' => [
                    'fullname' => multilang('Collaborating with others'),
                    'description' => multilang('This competency implies the intention of working co-operatively with others, to be part of a team, to work together as opposed to working separately or competitively. For this behaviour to be effective, the intention should be genuine. Team work and co-operation may be considered whenever the subject is a member of a group of people functioning as a team. This competency emphasises activity as a member of a group (rather than as a leader); e.g. Level E reflects a peer supporting their group rather than a leader managing the group.'),
                ],
                'influencing' => [
                    'fullname' => multilang('Influencing and relationship building'),
                    'description' => multilang('This is the ability to persuade, convince or influence others in order to get them to go along with or support a particular agenda, or get ‘buy in’ from others. It requires the ability to plan how to win support, gain co-operation or overcome barriers using a variety of approaches. Having gained support, it is the ability to build and maintain relationships with networks of people who may be able to effectively assist the organisation. At lower levels it is about presenting clear, logical arguments. At the higher level it requires taking a sophisticated strategic approach to influencing.'),
                ],

                // Thinking and innovation
                'innovation' => [
                    'fullname' => multilang('Innovation and creativity'),
                    'description' => multilang('This is about creating and identifying novel approaches to address challenging academic, technical or commercial situations and problems. It is about coming up with new or different ideas, or adapting ideas from elsewhere in the University or externally. It is concerned with moving the University forward by applying new ideas or old ideas in a new way to generate solutions and approaches. At the higher levels it is about thinking laterally and creating new concepts.'),
                ],
                'thinking' => [
                    'fullname' => multilang('Conceptual and strategic thinking'),
                    'description' => multilang('	This is the ability to see things as a whole, identify key issues, see relationships and draw elements together into broad coherent frameworks. This competency describes the ability to relate different events and key pieces of information; to make connections, see patterns and trends; to draw information together into models and frameworks which can then be used to interpret complex situations and identify their salient features. The strategic element involves looking into the future, considering the future needs of the University, Faculty or Department and thinking about how present policies, processes and methods might be progressively affected by future developments and trends; developing long term goals and strategies extending over significant time-spans.'),
                ],

                // Managing, leading and developing others
                'managing' => [
                    'fullname' => multilang('Managing and leading the team'),
                    'description' => multilang('Leading a team or function is about managing and developing others. This competency therefore reflects that to get the best out of people we need to build and integrate all aspects of the performance cycle, including:
<ul>
    <li>Being clear about what has to be achieved</li>
    <li>Assembling the necessary resources to meet what has to be done</li>
    <li>Monitoring and addressing gaps in staff development and performance</li>
    <li>Reviewing this people/work match in the light of setting future objectives and leading change to meet University needs.</li>
</ul>'),
                    'typeid' => $data['competency-types']['management'],
                ],
            ]
        ]
    ];

    foreach ($competencies as $key => $framework) {
        $data['comps'][$key] = create_competency_framework($framework);
    }

    // Then we need to create some manual job assignments so we have some managers and appraisers etc
    $job_assignments = [
        'jm' => [
            manager::class => [
                'ut',
                'sj',
            ],
            appraiser::class => [
                'tr',
            ],
        ],
        'ss' => [
            manager::class => [
                'tr',
                'bw',
            ],
            appraiser::class => [
                'ut',
            ],
        ],
        'dt' => [
            manager::class => [
                'vp',
                'bo',
            ],
            appraiser::class => [
                'gb',
            ],
        ],
        'jt' => [
            manager::class => [
                'gb',
                'gm',
            ],
            appraiser::class => [
                'gm',
            ],
        ],
    ];

    foreach ($job_assignments as $user => $assignments) {
        $data['job_assignments'][$user] = create_manual_job_assignments($user, $assignments, $data);
    }

    // Then we need to create a few positions
    $positions = [
        'pp' => [
            'fullname' => multilang('Primary positions'),
            'description' => multilang('The positions we can not live without...'),
            'positions' => [
                'janitor' => [
                    'fullname' => multilang('Janitor'),
                    'description' => multilang('No institution or company can survive without a clean closet'),
                    'members' => [
                        'gm' => get_user('gm', $data),
                    ],
                    'competencies' => [
                        'priest' => get_competency('complex', 'priest', $data),
                        'teeth-whitening' => get_competency('arbitrary', 'teeth-whitening', $data),
                        'hoarder' => get_competency('arbitrary', 'hoarder', $data),
                        'netflix' => get_competency('4-value', 'netflix', $data),
                    ],
                ],
                'stargazer' =>  [
                    'fullname' => multilang('Stargazer'),
                    'description' => multilang('There is no point in arguing that this is very important'),
                    'members' => [
                        'jm' => get_user('jm', $data),
                        'ss' => get_user('ss', $data),
                        'ut' => get_user('ut', $data),
                        'tr' => get_user('tr', $data),
                    ],
                    'competencies' => [
                        'doer' => get_competency('binary', 'doer', $data),
                        'hoarder' => get_competency('arbitrary', 'hoarder', $data),
                        'administrative-nurse' => get_competency('complex', 'administrative-nurse', $data),
                        'it' => get_competency('4-value', 'it', $data),
                        'storm-trooper' => get_competency('star-wars', 'storm-trooper', $data),
                        'integrity' => get_competency('bs', 'integrity', $data),
                    ],
                ],
                'theologist' => [
                    'fullname' => multilang('Theologist'),
                    'description' => multilang('Endless conversations about religion with a drink in the middle'),
                    'members' => [
                        'jm' => get_user('jm', $data),
                        'dt' => get_user('dt', $data),
                        'ss' => get_user('ss', $data),
                        'tr' => get_user('tr', $data),
                        'sj' => get_user('sj', $data),
                        'ut' => get_user('ut', $data),
                    ],
                    'competencies' => [
                        'literate' => get_competency('binary', 'literate', $data),
                        'cc' => get_competency('arbitrary', 'cc', $data),
                        'consultant' => get_competency('complex', 'consultant', $data),
                        'drive' => get_competency('bs', 'drive', $data),
                    ],
                ],
                'meter-reader' => [
                    'fullname' => multilang('Meter reader'),
                    'description' => multilang('Reading meters is a unique art of getting analogue or digital readings from various types of meters'),
                    'members' => [
                        'jm' => get_user('jm', $data),
                        'ss' => get_user('ss', $data),
                        'tr' => get_user('tr', $data),
                        'ut' => get_user('ut', $data),
                        'bo' => get_user('bo', $data),
                    ],
                    'competencies' => [
                        'literate' => get_competency('binary', 'literate', $data),
                        'cc' => get_competency('arbitrary', 'cc', $data),
                        'consultant' => get_competency('complex', 'consultant', $data),
                        'shop-keeper' => get_competency('4-value', 'shop-keeper', $data),
                        'lightsaber' => get_competency('star-wars', 'lightsaber', $data),
                        'drive' => get_competency('bs', 'drive', $data),
                    ],
                ],
                'analyst' => [
                    'fullname' => multilang('Static asset analyst in the dynamic environment'),
                    'description' => multilang('Analyzing assets statically is quite important in the dynamic environment of our modern ever-changing world'),
                    'members' => [
                        'jm' => get_user('jm', $data),
                        'dt' => get_user('dt', $data),
                        'bo' => get_user('bo', $data),
                        'tr' => get_user('tr', $data),
                        'ss' => get_user('ss', $data),
                        'ut' => get_user('ut', $data),
                    ],
                    'competencies' => [
                        'netflix' => get_competency('4-value', 'netflix', $data),
                        'shop-keeper' => get_competency('4-value', 'shop-keeper', $data),
                        'machinery-operator' => get_competency('4-value', 'machinery-operator', $data),
                        'it' => get_competency('4-value', 'it', $data),
                        'sommelier' => get_competency('4-value', 'sommelier', $data),
                        'barista' => get_competency('4-value', 'barista', $data),
                        'bartender' => get_competency('4-value', 'bartender', $data),
                        'mad-preacher' => get_competency('4-value', 'mad-preacher', $data),
                        'teeth-whitening' => get_competency('arbitrary', 'teeth-whitening', $data),
                        'hoarder' => get_competency('arbitrary', 'hoarder', $data),
                        'cc' => get_competency('arbitrary', 'cc', $data),
                        'consultant' => get_competency('complex', 'consultant', $data),
                        'nurse' => get_competency('complex', 'nurse', $data),
                        'administrative-nurse' => get_competency('complex', 'administrative-nurse', $data),
                        'surgeon' => get_competency('complex', 'surgeon', $data),
                        'camp-ground-manager' => get_competency('complex', 'camp-ground-manager', $data),
                    ],
                ],
            ],
        ],
        'sp' => [
            'fullname' => multilang('Secondary positions'),
            'descriptions' => multilang('These positions are also important, but not as much as primary positions'),
            'positions' => [
                'ceo' => [
                    'fullname' => multilang('CEO'),
                    'description' => multilang('Chief Executive Officer'),
                    'members' => [
                        'jt' => get_user('jt', $data),
                        'ut' => get_user('ut', $data),
                        'bo' => get_user('bo', $data),
                    ],
                    'competencies' => [
                        'literate' => get_competency('binary', 'literate', $data),
                        'cc' => get_competency('arbitrary', 'cc', $data),
                        'consultant' => get_competency('complex', 'consultant', $data),
                        'shop-keeper' => get_competency('4-value', 'shop-keeper', $data),
                        'lightsaber' => get_competency('star-wars', 'lightsaber', $data),
                        'drive' => get_competency('bs', 'drive', $data),
                    ],
                ],
                'chief-accountant' => [
                    'fullname' => multilang('Chief Accountant'),
                    'description' => multilang('Very important accountant'),
                    'members' => [
                        'jt' => get_user('jt', $data), // To assign and archive
                        'gb' => get_user('gb', $data),
                        'bo' => get_user('bo', $data),
                    ],
                    'competencies' => [
                        'zoo-keeper' => get_competency('complex', 'zoo-keeper', $data),
                        'collider' => get_competency('binary', 'collider', $data),
                        'sommelier' => get_competency('4-value', 'sommelier', $data),
                        'pod-racer' => get_competency('star-wars', 'pod-racer', $data),
                    ]
                ],
                'accountant' => [
                    'fullname' => multilang('Regular accountant'),
                    'description' => multilang('Not as important as chief accountant, but still pretty important'),
                ],
                'hr' => [
                    'fullname' => multilang('Human Relation Manager'),
                    'description' => multilang('No one can survive without HR manager, especially the one making decisions without being competent in the area'),
                    'members' => [
                        'gb' => get_user('gb', $data), // To assign and archive
                        'bo' => get_user('bo', $data),
                    ],
                    'competencies' => [
                        'literate' => get_competency('binary', 'literate', $data),
                        'doer' => get_competency('binary', 'doer', $data),
                        'initiative' => get_competency('binary', 'initiative', $data),
                        'collider' => get_competency('binary', 'collider', $data),
                    ],
                ],
            ]
        ]
    ];

    foreach ($positions as $key => $position) {
        $data['pos'][$key] = create_position_framework($position);
    }

    // Then we need to create a few organisations
    $organisations = [
        'europe' => [
            'fullname' => multilang('European organizations'),
            'description' => multilang('The organisations we are relying upon in Europe'),
            'organisations' => [
                'wwf' => [
                    'fullname' => multilang('World Wildlife Fund'),
                    'description' => multilang('The panda on the logo is so cute...'),
                    'members' => [
                        'jm' => get_user('jm', $data),
                        'dt' => get_user('dt', $data),
                        'tr' => get_user('tr', $data),
                        'ut' => get_user('ut', $data),
                        'ss' => get_user('ss', $data),
                    ],
                    'competencies' => [
                        'nurse' => get_competency('complex', 'nurse', $data),
                        'surgeon' => get_competency('complex', 'surgeon', $data),
                        'priest' => get_competency('complex', 'priest', $data),
                        'zoo-keeper' => get_competency('complex', 'zoo-keeper', $data),
                        'camp-ground-manager' => get_competency('complex', 'camp-ground-manager', $data),
                        'barista' => get_competency('4-value', 'barista', $data),
                        'it' => get_competency('4-value', 'it', $data),
                        'shop-keeper' => get_competency('4-value', 'shop-keeper', $data),
                        'netflix' => get_competency('4-value', 'netflix', $data),
                    ],
                ],
                'cola' =>  [
                    'fullname' => multilang('Coca-Cola European Partners'),
                    'description' => multilang('Coca-Cola European Partners plc is a multinational bottling company dedicated to the marketing, production, and distribution of Coca-Cola products. Wikipedia'),
                    'members' => [
                        'jm' => get_user('jm', $data),
                        'bo' => get_user('bo', $data),
                        'ss' => get_user('ss', $data),
                        'dt' => get_user('dt', $data),
                    ],
                    'competencies' => [
                        'literate' => get_competency('binary', 'literate', $data),
                        'doer' => get_competency('binary', 'doer', $data),
                        'initative' => get_competency('binary', 'initiative', $data),
                        'collider' => get_competency('binary', 'collider', $data),
                        'mad-preacher' => get_competency('4-value', 'mad-preacher', $data),
                        'bartender' => get_competency('4-value', 'bartender', $data),
                    ],
                ],
                'nestle' => [
                    'fullname' => multilang('Nestlé'),
                    'description' => multilang('It is like Nescafe, but better'),
                    'members' => [
                        'jm' => get_user('jm', $data),
                        'tr' => get_user('tr', $data),
                        'ut' => get_user('ut', $data),
                        'ss' => get_user('ss', $data),
                    ],
                    'competencies' => [

                    ],
                ],
                'nescafe' => [
                    'fullname' => multilang('Nescafé'),
                    'description' => multilang('It is like Nestlé, but wait it is a part of Nesrlé...'),
                    'members' => [
                        'jm' => get_user('jm', $data),
                        'ss' => get_user('ss', $data),
                        'tr' => get_user('tr', $data),
                        'bo' => get_user('bo', $data),
                        'ut' => get_user('ut', $data),
                        'sj' => get_user('sj', $data),
                    ],
                    'competencies' => [
                        'storm-trooper' => get_competency('star-wars', 'storm-trooper', $data),
                        'sith-lord' => get_competency('star-wars', 'sith-lord', $data),
                        'communication' => get_competency('bs', 'communication', $data),
                        'embracing' => get_competency('bs', 'embracing', $data),
                        'mad-preacher' => get_competency('4-value', 'mad-preacher', $data),
                        'bartender' => get_competency('4-value', 'bartender', $data),
                        'collaborating' => get_competency('bs', 'collaborating', $data),
                    ]
                ],
                'mercedes' => [
                    'fullname' => multilang('Mercedes Benz'),
                    'description' => multilang('A division of Daimler'),
                    'members' => [
                        'jm' => get_user('jm', $data),
                        'dt' => get_user('dt', $data),
                        'tr' => get_user('tr', $data),
                        'bo' => get_user('bo', $data),
                        'ss' => get_user('ss', $data),
                    ],
                    'competencies' => [
                        'lightsaber' => get_competency('star-wars', 'lightsaber', $data),
                        'pod-racer' => get_competency('star-wars', 'pod-racer', $data),
                        'storm-trooper' => get_competency('star-wars', 'storm-trooper', $data),
                        'sith-lord' => get_competency('star-wars', 'sith-lord', $data),
                    ]
                ],
            ],
        ],
        'na' => [
            'fullname' => multilang('North America'),
            'descriptions' => multilang('We favour these in the North America'),
            'organisations' => [
                'greenpeace' => [
                    'fullname' => multilang('GreenPeace'),
                    'description' => multilang('Keeping peace, doing green stuff'),
                ],
                'cola' => [
                    'fullname' => multilang('Coca Cola LLC LTD etc'),
                    'description' => multilang('Nothing beats a warm cola on a hot day'),
                ],
                'apple' => [
                    'fullname' => multilang('Apple'),
                    'description' => multilang('Apple is not the same without Steve'),
                ],
                'google' => [
                    'fullname' => multilang('Google'),
                    'description' => multilang('Please update your Chrome Browser'),
                ],
            ]
        ]
    ];

    foreach ($organisations as $key => $organisation) {
        $data['orgs'][$key] = create_position_framework($organisation);
    }

    // Then we need to create a few audiences
    $audiences = [
        'cr' => [
            'name' => 'Content makers',
            'description' => 'This audience is for creative staff members',
            'members' => [
                'jm' => get_user('jm', $data),
                'ss' => get_user('ss', $data),
                'dt' => get_user('dt', $data),
                'ut' => get_user('ut', $data),
                'sj' => get_user('sj', $data),
            ],
            'competencies' => [
                'drive' => get_competency('bs', 'drive', $data),
                'serving' => get_competency('bs', 'serving', $data),
                'quality' => get_competency('bs', 'quality', $data),
                'integrity' => get_competency('bs', 'integrity', $data),
                'planning' => get_competency('bs', 'planning', $data),
                'confidence' => get_competency('bs', 'confidence', $data),
                'problem-solving' => get_competency('bs', 'problem-solving', $data),
                'info-seeking' => get_competency('bs', 'info-seeking', $data),
                'communication' => get_competency('bs', 'communication', $data),
                'embracing' => get_competency('bs', 'embracing', $data),
                'collaborating' => get_competency('bs', 'collaborating', $data),
                'influencing' => get_competency('bs', 'influencing', $data),
                'innovation' => get_competency('bs', 'innovation', $data),
                'thinking' => get_competency('bs', 'thinking', $data),
                'managing' => get_competency('bs', 'managing', $data),
            ],
        ],
        'it' => [
            'name' => 'IT Department',
            'description' => 'Every respectful company needs to have at least one in-house IT department',
            'members' => [
                'jm' => get_user('jm', $data),
                'ss' => get_user('ss', $data),
            ],
            'competencies' => [
                'zoo-keeper' => get_competency('complex', 'zoo-keeper', $data),
                'collider' => get_competency('binary', 'collider', $data),
                'sommelier' => get_competency('4-value', 'sommelier', $data),
                'barista' => get_competency('4-value', 'barista', $data),
                'it' => get_competency('4-value', 'it', $data),
                'machinery-operator' => get_competency('4-value', 'machinery-operator', $data),
                'pod-racer' => get_competency('star-wars', 'pod-racer', $data),
                'hoarder' => get_competency('arbitrary', 'hoarder', $data),
                'cc' => get_competency('arbitrary', 'cc', $data),
            ]
        ],
        'vip' => [
            'name' => 'VIP',
            'description' => 'Privileged members group',
            'members' => [
                'jm' => get_user('jm', $data),
                'ss' => get_user('ss', $data),
                'dt' => get_user('dt', $data),
            ],
            'competencies' => [
                'lightsaber' => get_competency('star-wars', 'lightsaber', $data),
                'pod-racer' => get_competency('star-wars', 'pod-racer', $data),
                'storm-trooper' => get_competency('star-wars', 'storm-trooper', $data),
                'sith-lord' => get_competency('star-wars', 'sith-lord', $data),
                'consultant' => get_competency('complex', 'consultant', $data),
                'nurse' => get_competency('complex', 'nurse', $data),
                'administrative-nurse' => get_competency('complex', 'administrative-nurse', $data),
            ],
        ],
    ];

    foreach ($audiences as $key => $audience) {
        $data['audiences'][$key] = create_audience($audience, $data);
    }

    // Let's create individual assignments:
    $user_assignments = [
        'jm-initiative' => [get_user('jm', $data), get_competency('binary', 'initiative', $data)],
        'sj-literate' => [get_user('sj', $data), get_competency('binary', 'literate', $data)],
        'sj-doer' => [get_user('sj', $data), get_competency('binary', 'doer', $data)],
        'sj-initiative' => [get_user('sj', $data), get_competency('binary', 'initiative', $data)],
        'sj-collider' => [get_user('sj', $data), get_competency('binary', 'collider', $data)],
        'sj-consultant' => [get_user('sj', $data), get_competency('complex', 'consultant', $data)],
        'sj-nurse' => [get_user('sj', $data), get_competency('complex', 'nurse', $data)],
        'sj-administrative-nurse' => [get_user('sj', $data), get_competency('complex', 'administrative-nurse', $data)],
        'sj-surgeon' => [get_user('sj', $data), get_competency('complex', 'surgeon', $data)],
        'sj-priest' => [get_user('sj', $data), get_competency('complex', 'priest', $data)],
        'sj-zoo-keeper' => [get_user('sj', $data), get_competency('complex', 'zoo-keeper', $data)],
        'sj-camp-ground-manager' => [get_user('sj', $data), get_competency('complex', 'camp-ground-manager', $data)],
        'sj-netflix' => [get_user('sj', $data), get_competency('4-value', 'netflix', $data)],
        'sj-shop-keeper' => [get_user('sj', $data), get_competency('4-value', 'shop-keeper', $data)],
        'sj-it' => [get_user('sj', $data), get_competency('4-value', 'it', $data)],
        'sj-sommelier' => [get_user('sj', $data), get_competency('4-value', 'sommelier', $data)],
        'sj-barista' => [get_user('sj', $data), get_competency('4-value', 'barista', $data)],
        'sj-bartender' => [get_user('sj', $data), get_competency('4-value', 'bartender', $data)],
        'sj-mad-preacher' => [get_user('sj', $data), get_competency('4-value', 'mad-preacher', $data)],
        'sj-lightsaber' => [get_user('sj', $data), get_competency('star-wars', 'lightsaber', $data)],
        'sj-pod-racer' => [get_user('sj', $data), get_competency('star-wars', 'pod-racer', $data)],
        'sj-storm-trooper' => [get_user('sj', $data), get_competency('star-wars', 'storm-trooper', $data)],
        'sj-sith-lord' => [get_user('sj', $data), get_competency('star-wars', 'sith-lord', $data)],
        'sj-teeth-whitening' => [get_user('sj', $data), get_competency('arbitrary', 'teeth-whitening', $data)],
        'sj-hoarder' => [get_user('sj', $data), get_competency('arbitrary', 'hoarder', $data)],
        'sj-cc' => [get_user('sj', $data), get_competency('arbitrary', 'cc', $data)],
        'sj-drive' => [get_user('sj', $data), get_competency('bs', 'drive', $data)],
        'sj-serving' => [get_user('sj', $data), get_competency('bs', 'serving', $data)],
        'sj-quality' => [get_user('sj', $data), get_competency('bs', 'quality', $data)],
        'sj-integrity' => [get_user('sj', $data), get_competency('bs', 'integrity', $data)],
        'sj-planning' => [get_user('sj', $data), get_competency('bs', 'planning', $data)],
        'sj-confidence' => [get_user('sj', $data), get_competency('bs', 'confidence', $data)],
        'sj-problem-solving' => [get_user('sj', $data), get_competency('bs', 'problem-solving', $data)],
        'sj-info-seeking' => [get_user('sj', $data), get_competency('bs', 'info-seeking', $data)],
        'sj-communication' => [get_user('sj', $data), get_competency('bs', 'communication', $data)],
        'sj-embracing' => [get_user('sj', $data), get_competency('bs', 'embracing', $data)],
        'sj-collaborating' => [get_user('sj', $data), get_competency('bs', 'collaborating', $data)],
        'sj-influencing' => [get_user('sj', $data), get_competency('bs', 'influencing', $data)],
        'sj-innovation' => [get_user('sj', $data), get_competency('bs', 'innovation', $data)],
        'sj-thinking' => [get_user('sj', $data), get_competency('bs', 'thinking', $data)],
        'sj-managing' => [get_user('sj', $data), get_competency('bs', 'managing', $data)],
        'ss-literate' => [get_user('ss', $data), get_competency('binary', 'literate', $data)],
        'ss-collider' => [get_user('ss', $data), get_competency('binary', 'collider', $data)],
        'ss-consultant' => [get_user('ss', $data), get_competency('complex', 'consultant', $data)],
        'ss-nurse' => [get_user('ss', $data), get_competency('complex', 'nurse', $data)],
        'ss-administrative-nurse' => [get_user('ss', $data), get_competency('complex', 'administrative-nurse', $data)],
        'ss-surgeon' => [get_user('ss', $data), get_competency('complex', 'surgeon', $data)],
        'ss-priest' => [get_user('ss', $data), get_competency('complex', 'priest', $data)],
        'ss-zoo-keeper' => [get_user('ss', $data), get_competency('complex', 'zoo-keeper', $data)],
        'ss-camp-ground-manager' => [get_user('ss', $data), get_competency('complex', 'camp-ground-manager', $data)],
        'ss-netflix' => [get_user('ss', $data), get_competency('4-value', 'netflix', $data)],
        'ss-shop-keeper' => [get_user('ss', $data), get_competency('4-value', 'shop-keeper', $data)],
        'ss-machinery-operator' => [get_user('ss', $data), get_competency('4-value', 'machinery-operator', $data)],
        'ss-bartender' => [get_user('ss', $data), get_competency('4-value', 'bartender', $data)],
        'ss-mad-preacher' => [get_user('ss', $data), get_competency('4-value', 'mad-preacher', $data)],
        'ss-lightsaber' => [get_user('ss', $data), get_competency('star-wars', 'lightsaber', $data)],
        'ss-pod-racer' => [get_user('ss', $data), get_competency('star-wars', 'pod-racer', $data)],
        'ss-storm-trooper' => [get_user('ss', $data), get_competency('star-wars', 'storm-trooper', $data)],
        'ss-serving' => [get_user('ss', $data), get_competency('bs', 'serving', $data)],
        'ss-quality' => [get_user('ss', $data), get_competency('bs', 'quality', $data)],
        'ss-integrity' => [get_user('ss', $data), get_competency('bs', 'integrity', $data)],
        'ss-planning' => [get_user('ss', $data), get_competency('bs', 'planning', $data)],
        'ss-confidence' => [get_user('ss', $data), get_competency('bs', 'confidence', $data)],
        'ss-influencing' => [get_user('ss', $data), get_competency('bs', 'influencing', $data)],
        'ss-innovation' => [get_user('ss', $data), get_competency('bs', 'innovation', $data)],
        'ss-thinking' => [get_user('ss', $data), get_competency('bs', 'thinking', $data)],
        'ss-managing' => [get_user('ss', $data), get_competency('bs', 'managing', $data)],
    ];

    $data['individual_assignments'] = create_user_assignments($user_assignments, $data);

    // Let's create legacy assignments:
    $legacy_assignments = [
        'sj-literate' => [get_user('sj', $data), get_competency('binary', 'literate', $data), 1],
        'sj-doer' => [get_user('sj', $data), get_competency('binary', 'doer', $data), 1],
        'sj-initiative' => [get_user('sj', $data), get_competency('binary', 'initiative', $data), 1],
        'sj-collider' => [get_user('sj', $data), get_competency('binary', 'collider', $data), 1],
        'sj-nurse' => [get_user('sj', $data), get_competency('complex', 'nurse', $data), 1],
        'sj-administrative-nurse' => [get_user('sj', $data), get_competency('complex', 'administrative-nurse', $data), 1],
        'sj-zoo-keeper' => [get_user('sj', $data), get_competency('complex', 'zoo-keeper', $data), 1],
        'sj-camp-ground-manager' => [get_user('sj', $data), get_competency('complex', 'camp-ground-manager', $data), 1],
        'sj-netflix' => [get_user('sj', $data), get_competency('4-value', 'netflix', $data), 1],
        'sj-shop-keeper' => [get_user('sj', $data), get_competency('4-value', 'shop-keeper', $data), 1],
        'sj-sommelier' => [get_user('sj', $data), get_competency('4-value', 'sommelier', $data), 2],
        'sj-barista' => [get_user('sj', $data), get_competency('4-value', 'barista', $data), 3],
        'sj-bartender' => [get_user('sj', $data), get_competency('4-value', 'bartender', $data), 1],
        'sj-mad-preacher' => [get_user('sj', $data), get_competency('4-value', 'mad-preacher', $data), 1],
        'sj-lightsaber' => [get_user('sj', $data), get_competency('star-wars', 'lightsaber', $data), 2],
        'sj-pod-racer' => [get_user('sj', $data), get_competency('star-wars', 'pod-racer', $data), 1],
        'sj-sith-lord' => [get_user('sj', $data), get_competency('star-wars', 'sith-lord', $data), 3],
        'sj-teeth-whitening' => [get_user('sj', $data), get_competency('arbitrary', 'teeth-whitening', $data), 1],
        'sj-hoarder' => [get_user('sj', $data), get_competency('arbitrary', 'hoarder', $data), 1],
        'sj-cc' => [get_user('sj', $data), get_competency('arbitrary', 'cc', $data), 1],
        'sj-serving' => [get_user('sj', $data), get_competency('bs', 'serving', $data), 1],
        'sj-quality' => [get_user('sj', $data), get_competency('bs', 'quality', $data), 3],
        'sj-integrity' => [get_user('sj', $data), get_competency('bs', 'integrity', $data), 1],
        'sj-problem-solving' => [get_user('sj', $data), get_competency('bs', 'problem-solving', $data), 2],
        'sj-info-seeking' => [get_user('sj', $data), get_competency('bs', 'info-seeking', $data), 1],
        'sj-embracing' => [get_user('sj', $data), get_competency('bs', 'embracing', $data), 1],
        'sj-collaborating' => [get_user('sj', $data), get_competency('bs', 'collaborating', $data), 1],
        'sj-influencing' => [get_user('sj', $data), get_competency('bs', 'influencing', $data), 2],
        'sj-innovation' => [get_user('sj', $data), get_competency('bs', 'innovation', $data), 1],
        'sj-thinking' => [get_user('sj', $data), get_competency('bs', 'thinking', $data), 1],
        'ss-literate' => [get_user('ss', $data), get_competency('binary', 'literate', $data), 1],
        'ss-doer' => [get_user('ss', $data), get_competency('binary', 'doer', $data), 1],
        'ss-initiative' => [get_user('ss', $data), get_competency('binary', 'initiative', $data), 1],
        'ss-collider' => [get_user('ss', $data), get_competency('binary', 'collider', $data), 1],
        'ss-consultant' => [get_user('ss', $data), get_competency('complex', 'consultant', $data), 1],
        'ss-nurse' => [get_user('ss', $data), get_competency('complex', 'nurse', $data), 1],
        'ss-administrative-nurse' => [get_user('ss', $data), get_competency('complex', 'administrative-nurse', $data), 1],
        'ss-zoo-keeper' => [get_user('ss', $data), get_competency('complex', 'zoo-keeper', $data), 3],
        'ss-camp-ground-manager' => [get_user('ss', $data), get_competency('complex', 'camp-ground-manager', $data), 1],
        'ss-netflix' => [get_user('ss', $data), get_competency('4-value', 'netflix', $data), 4],
        'ss-shop-keeper' => [get_user('ss', $data), get_competency('4-value', 'shop-keeper', $data), 1],
        'ss-machinery-operator' => [get_user('ss', $data), get_competency('4-value', 'machinery-operator', $data), 1],
        'ss-it' => [get_user('ss', $data), get_competency('4-value', 'it', $data), 1],
        'ss-bartender' => [get_user('ss', $data), get_competency('4-value', 'bartender', $data), 1],
        'ss-mad-preacher' => [get_user('ss', $data), get_competency('4-value', 'mad-preacher', $data), 1],
        'ss-pod-racer' => [get_user('ss', $data), get_competency('star-wars', 'pod-racer', $data), 2],
        'ss-teeth-whitening' => [get_user('ss', $data), get_competency('arbitrary', 'teeth-whitening', $data), 1],
        'ss-hoarder' => [get_user('ss', $data), get_competency('arbitrary', 'hoarder', $data), 1],
        'ss-cc' => [get_user('ss', $data), get_competency('arbitrary', 'cc', $data), 2],
        'ss-drive' => [get_user('ss', $data), get_competency('bs', 'drive', $data), 3],
        'ss-serving' => [get_user('ss', $data), get_competency('bs', 'serving', $data), 2],
        'ss-quality' => [get_user('ss', $data), get_competency('bs', 'quality', $data), 1],
        'ss-integrity' => [get_user('ss', $data), get_competency('bs', 'integrity', $data), 1],
        'ss-planning' => [get_user('ss', $data), get_competency('bs', 'planning', $data), 1],
        'ss-confidence' => [get_user('ss', $data), get_competency('bs', 'confidence', $data), 3],
        'ss-info-seeking' => [get_user('ss', $data), get_competency('bs', 'info-seeking', $data), 1],
        'ss-communication' => [get_user('ss', $data), get_competency('bs', 'communication', $data), 2],
        'ss-influencing' => [get_user('ss', $data), get_competency('bs', 'influencing', $data), 1],
        'ss-innovation' => [get_user('ss', $data), get_competency('bs', 'innovation', $data), 2],
        'ss-managing' => [get_user('ss', $data), get_competency('bs', 'managing', $data), 1],
    ];

    // Currently they are just the same as user assignments
    $data['legacy_assignment'] = create_legacy_assignments($legacy_assignments, $data);

    run_tasks();

    // Then we need to create courses and enrol users to use for the competencies
    $courses = [
        'infosec' => [
            'fullname' => 'Information security',
            'shortname' => 'Infosec',
        ],
        'recruit' => [
            'fullname' => 'Recruitment basics',
            'shortname' => 'Basics',
        ],
        'health' => [
            'fullname' => 'Health and safety for all',
            'shortname' => 'Health',
        ],
        'orientation' => [
            'fullname' => 'New employee orientation',
            'shortname' => 'Orientation',
        ],
        'conversations' => [
            'fullname' => 'Having difficult conversations',
            'shortname' => 'Conversations',
        ],
        'communication' => [
            'fullname' => 'Communication skills',
            'shortname' => 'Communication',
        ],
        'waitangi' => [
            'fullname' => 'Te Tiriti o Waitangi',
            'shortname' => 'Waitangi',
        ],
        'nursing' => [
            'fullname' => 'Professional, ethical and legislated requirements for nursing',
            'shortname' => 'Nursing',
        ],
        'management' => [
            'fullname' => 'Management essentials',
            'shortname' => 'Management',
        ],
        'listening' => [
            'fullname' => 'Introduction to active listening',
            'shortname' => 'Listening',
        ],
    ];

    foreach ($courses as $key => $record) {
        $data['courses'][$key] = create_course_($record, $data, $generator, $key);
    }

    // Then we need to create course completions for users enrolled in the courses
    $course_completion = [
        [get_course_('infosec', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('infosec', $data), get_user('ss', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('infosec', $data), get_user('jt', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('infosec', $data), get_user('ut', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('infosec', $data), get_user('sj', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('infosec', $data), get_user('tr', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('infosec', $data), get_user('bw', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('infosec', $data), get_user('vp', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('infosec', $data), get_user('bo', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('infosec', $data), get_user('gm', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('recruit', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('recruit', $data), get_user('dt', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('recruit', $data), get_user('jt', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('recruit', $data), get_user('bw', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('recruit', $data), get_user('vp', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('recruit', $data), get_user('gb', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('recruit', $data), get_user('gm', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('health', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('health', $data), get_user('dt', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('health', $data), get_user('jt', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('health', $data), get_user('ut', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('health', $data), get_user('tr', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('health', $data), get_user('bw', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('health', $data), get_user('vp', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('health', $data), get_user('gb', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('health', $data), get_user('gm', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('orientation', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('orientation', $data), get_user('ss', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('orientation', $data), get_user('jt', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('orientation', $data), get_user('sj', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('orientation', $data), get_user('tr', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('orientation', $data), get_user('bw', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('orientation', $data), get_user('bo', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('orientation', $data), get_user('gb', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('conversations', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('conversations', $data), get_user('ss', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('conversations', $data), get_user('dt', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('conversations', $data), get_user('jt', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('conversations', $data), get_user('sj', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('conversations', $data), get_user('tr', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('conversations', $data), get_user('bo', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('conversations', $data), get_user('gb', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('communication', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('communication', $data), get_user('dt', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('communication', $data), get_user('bw', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('communication', $data), get_user('vp', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('communication', $data), get_user('bo', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('communication', $data), get_user('gb', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('communication', $data), get_user('gm', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('waitangi', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('waitangi', $data), get_user('tr', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('waitangi', $data), get_user('vp', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('waitangi', $data), get_user('gb', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('nursing', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('nursing', $data), get_user('ss', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('nursing', $data), get_user('dt', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('nursing', $data), get_user('jt', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('nursing', $data), get_user('tr', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('nursing', $data), get_user('bw', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('nursing', $data), get_user('bo', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('nursing', $data), get_user('gm', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('management', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('management', $data), get_user('ss', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('management', $data), get_user('dt', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('management', $data), get_user('jt', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('management', $data), get_user('tr', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('management', $data), get_user('gb', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('management', $data), get_user('gm', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('listening', $data), get_user('jm', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('listening', $data), get_user('ss', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('listening', $data), get_user('jt', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('listening', $data), get_user('sj', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('listening', $data), get_user('tr', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('listening', $data), get_user('bw', $data), COMPLETION_STATUS_INPROGRESS],
        [get_course_('listening', $data), get_user('vp', $data), COMPLETION_STATUS_COMPLETEVIARPL],
        [get_course_('listening', $data), get_user('gb', $data), COMPLETION_STATUS_COMPLETE],
        [get_course_('listening', $data), get_user('gm', $data), COMPLETION_STATUS_INPROGRESS],
    ];

    foreach ($course_completion as [$course, $user, $completion_status]) {
        if (is_array($course)) {
            foreach ($course as $a_course) {
                $course_context = context_course::instance($a_course->id);
                $user_context = context_user::instance($user->id);
                if ($course_context->tenantid != $user_context->tenantid) {
                    continue;
                }
                $competency_generator->create_course_enrollment_and_completion($a_course, $user, $completion_status);
            }
        } else {
            $competency_generator->create_course_enrollment_and_completion($course, $user, $completion_status);
        }
    }

    // Then we need to link the courses to the competencies
    create_course_links([
        'binary_literate' => [
            'infosec' => linked_courses::LINKTYPE_OPTIONAL,
            'nursing' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'binary_doer' => [
            'orientation' => linked_courses::LINKTYPE_MANDATORY,
            'communication' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'binary_initiative' => [
            'health' => linked_courses::LINKTYPE_MANDATORY,
            'orientation' => linked_courses::LINKTYPE_OPTIONAL,
            'conversations' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'binary_collider' => [
            'recruit' => linked_courses::LINKTYPE_MANDATORY,
            'health' => linked_courses::LINKTYPE_OPTIONAL,
            'listening' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'complex_consultant' => [
            'conversations' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'complex_nurse' => [],
        'complex_administrative-nurse' => [
            'conversations' => linked_courses::LINKTYPE_MANDATORY,
            'management' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'complex_surgeon' => [
            'recruit' => linked_courses::LINKTYPE_MANDATORY,
            'conversations' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'complex_priest' => [
            'orientation' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'complex_zoo-keeper' => [
            'management' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'complex_camp-ground-manager' => [
            'conversations' => linked_courses::LINKTYPE_OPTIONAL,
            'waitangi' => linked_courses::LINKTYPE_MANDATORY,
            'listening' => linked_courses::LINKTYPE_MANDATORY,
        ],
        '4-value_netflix' => [
            'nursing' => linked_courses::LINKTYPE_MANDATORY,
        ],
        '4-value_shop-keeper' => [],
        '4-value_machinery-operator' => [],
        '4-value_it' => [
            'waitangi' => linked_courses::LINKTYPE_MANDATORY,
            'management' => linked_courses::LINKTYPE_MANDATORY,
        ],
        '4-value_sommelier' => [],
        '4-value_barista' => [],
        '4-value_bartender' => [
            'infosec' => linked_courses::LINKTYPE_OPTIONAL,
            'recruit' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        '4-value_mad-preacher' => [
            'communication' => linked_courses::LINKTYPE_OPTIONAL,
            'management' => linked_courses::LINKTYPE_OPTIONAL,
            'listening' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'star-wars_lightsaber' => [
            'infosec' => linked_courses::LINKTYPE_MANDATORY,
            'conversations' => linked_courses::LINKTYPE_MANDATORY,
            'communication' => linked_courses::LINKTYPE_OPTIONAL,
            'management' => linked_courses::LINKTYPE_MANDATORY,
            'listening' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'star-wars_pod-racer' => [
            'infosec' => linked_courses::LINKTYPE_MANDATORY,
            'communication' => linked_courses::LINKTYPE_MANDATORY,
            'nursing' => linked_courses::LINKTYPE_OPTIONAL,
            'management' => linked_courses::LINKTYPE_OPTIONAL,
            'listening' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'star-wars_storm-trooper' => [],
        'star-wars_sith-lord' => [
            'orientation' => linked_courses::LINKTYPE_MANDATORY,
            'waitangi' => linked_courses::LINKTYPE_MANDATORY,
            'nursing' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'arbitrary_teeth-whitening' => [
            'listening' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'arbitrary_hoarder' => [
            'conversations' => linked_courses::LINKTYPE_MANDATORY,
            'nursing' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'arbitrary_cc' => [
            'infosec' => linked_courses::LINKTYPE_MANDATORY,
            'communication' => linked_courses::LINKTYPE_MANDATORY,
            'management' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'bs_drive' => [
            'waitangi' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_serving' => [
            'infosec' => linked_courses::LINKTYPE_OPTIONAL,
            'health' => linked_courses::LINKTYPE_OPTIONAL,
            'listening' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_quality' => [
            'infosec' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_integrity' => [
            'nursing' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_planning' => [
            'health' => linked_courses::LINKTYPE_MANDATORY,
            'conversations' => linked_courses::LINKTYPE_OPTIONAL,
            'communication' => linked_courses::LINKTYPE_OPTIONAL,
            'nursing' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_confidence' => [
            'infosec' => linked_courses::LINKTYPE_MANDATORY,
            'management' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_problem-solving' => [
            'infosec' => linked_courses::LINKTYPE_MANDATORY,
            'conversations' => linked_courses::LINKTYPE_OPTIONAL,
            'communication' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_info-seeking' => [
            'recruit' => linked_courses::LINKTYPE_OPTIONAL,
            'management' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_communication' => [
            'recruit' => linked_courses::LINKTYPE_MANDATORY,
            'nursing' => linked_courses::LINKTYPE_OPTIONAL,
            'management' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'bs_embracing' => [
            'waitangi' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_collaborating' => [
            'conversations' => linked_courses::LINKTYPE_OPTIONAL,
            'waitangi' => linked_courses::LINKTYPE_MANDATORY,
            'nursing' => linked_courses::LINKTYPE_MANDATORY,
            'listening' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_influencing' => [
            'health' => linked_courses::LINKTYPE_MANDATORY,
            'orientation' => linked_courses::LINKTYPE_MANDATORY,
            'waitangi' => linked_courses::LINKTYPE_MANDATORY,
            'management' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_innovation' => [
            'health' => linked_courses::LINKTYPE_MANDATORY,
            'orientation' => linked_courses::LINKTYPE_OPTIONAL,
            'conversations' => linked_courses::LINKTYPE_OPTIONAL,
            'waitangi' => linked_courses::LINKTYPE_MANDATORY,
        ],
        'bs_thinking' => [
            'nursing' => linked_courses::LINKTYPE_OPTIONAL,
        ],
        'bs_managing' => [],
    ], $data);

    // Then we need to create learning plans with competencies assigned
    $learning_plans = [
        'jm' => [
            'jm-1' => ['complex_consultant' => 1, 'star-wars_lightsaber' => 1],
            'jm-2' => ['arbitrary_cc' => 1, 'bs_drive' => 1, 'binary_literate' => 1],
        ],
        'ss' => [
            'ss-1' => ['binary_literate' => 1, '4-value_shop-keeper' => 1],
            'ss-2' => ['star-wars_lightsaber' => 1, 'bs_drive' => 2],
        ],
        'dt' => [
            'dt-1' => ['binary_literate' => 2, 'complex_consultant' => 2],
            'dt-2' => ['4-value_shop-keeper' => 1, 'arbitrary_cc' => 2],
        ],
        'jt' => [
            'jt-1' => ['complex_consultant' => 4, 'star-wars_lightsaber' => 2],
            'jt-2' => ['arbitrary_cc' => 3, 'bs_drive' => 2],
        ],
        'ut' => [
            'ut-1' => ['binary_literate' => 1, 'complex_consultant' => 6],
            'ut-2' => ['4-value_shop-keeper' => 4, 'star-wars_lightsaber' => 4],
            'ut-3' => ['arbitrary_cc' => 3, 'bs_drive' => 1],
        ],
        'sj' => [
            'sj-1' => ['binary_literate' => 2, 'complex_consultant' => 6],
            'sj-2' => ['4-value_shop-keeper' => 2, 'star-wars_lightsaber' => 1, 'bs_drive' => 2],
        ],
        'tr' => [
            'tr-1' => ['binary_literate' => 2, 'complex_consultant' => 5, '4-value_shop-keeper' => 4],
            'tr-2' => ['star-wars_lightsaber' => 3, 'arbitrary_cc' => 1],
        ],
        'bo' => [
            'bo-1' => ['binary_literate' => 1, 'complex_consultant' => 4, '4-value_shop-keeper' => 2],
            'bo-2' => ['star-wars_lightsaber' => 3, 'arbitrary_cc' => 3, 'bs_drive' => 2],
        ],
        'gb' => [
            'gb-1' => ['binary_literate' => 1],
        ],
    ];

    $data['learning_plans'] = [];
    foreach ($learning_plans as $user => $plans) {
        $data['learning_plans'] = array_merge($data['learning_plans'],
            create_learning_plans(get_user($user, $data), $plans, $data, $competency_generator)
        );
    }

    // Then we need to create criteria pathways for the competencies
    create_criteria_pathways([
        'binary_literate' => [
            1 => [
                [
                    [
                        'criterion' => childcompetency::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                    ],
                ],
                [
                    'criterion' => childcompetency::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                ],
            ],
            2 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'binary_doer' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                        'courses' => [
                            get_course_('recruit', $data),
                            get_course_('nursing', $data),
                            get_course_('listening', $data),
                        ],
                    ],
                ],
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                    'courses' => [
                        get_course_('recruit', $data),
                        get_course_('nursing', $data),
                        get_course_('listening', $data),
                    ],
                ],
            ],
            2 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'binary_initiative' => [
            1 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 2,
                ],
            ],
            2 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'binary_collider' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('conversations', $data),
                            get_course_('nursing', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'complex_consultant' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                        'courses' => [
                            get_course_('conversations', $data),
                            get_course_('waitangi', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('infosec', $data),
                            get_course_('waitangi', $data),
                            get_course_('listening', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                ],
            ],
            4 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                ],
            ],
            6 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'complex_nurse' => [
            1 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                    'courses' => [
                        get_course_('nursing', $data),
                        get_course_('infosec', $data),
                        get_course_('communication', $data),
                    ],
                ],
            ],
            3 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                    'courses' => [
                        get_course_('nursing', $data),
                    ],
                ],
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 1,
                    'courses' => [
                        get_course_('infosec', $data),
                        get_course_('communication', $data),
                    ],
                ],
            ],
            5 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                    'courses' => [
                        get_course_('nursing', $data),
                    ],
                ],
            ],
            6 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'complex_administrative-nurse' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('recruit', $data),
                            get_course_('listening', $data),
                        ],
                    ],
                ],
            ],
            4 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('listening', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                    ],
                ],
            ],
            6 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'complex_surgeon' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('conversations', $data),
                        ],
                    ],
                ],
            ],
            4 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                ],
            ],
            6 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'complex_priest' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('orientation', $data),
                        ],
                    ],
                ],
            ],
            6 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'complex_zoo-keeper' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('infosec', $data),
                            get_course_('orientation', $data),
                            get_course_('management', $data),
                            get_course_('conversations', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('orientation', $data),
                        ],
                    ],
                ],
            ],
            4 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                ],
            ],
            6 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'complex_camp-ground-manager' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 3,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('orientation', $data),
                        ],
                    ],
                ],
            ],
            6 => [
                ['criterion' => onactivate::class],
            ],
        ],
        '4-value_netflix' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('conversations', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                ],
            ],
            4 => [
                ['criterion' => onactivate::class],
            ],
        ],
        '4-value_shop-keeper' => [
            1 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                    'aggregation_required_count' => 2,
                    'courses' => [
                        get_course_('waitangi', $data),
                        get_course_('listening', $data),
                    ],
                ],
            ],
            4 => [
                ['criterion' => onactivate::class],
            ],
        ],
        '4-value_machinery-operator' => [
            1 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                    'courses' => [
                        get_course_('health', $data),
                        get_course_('nursing', $data),
                        get_course_('listening', $data),
                    ],
                ],
            ],
            4 => [
                ['criterion' => onactivate::class],
            ],
        ],
        '4-value_it' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 4,
                        'courses' => [
                            get_course_('infosec', $data),
                            get_course_('conversations', $data),
                            get_course_('management', $data),
                            get_course_('listening', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('infosec', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                    ],
                ],
            ],
            3 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 1,
                ],
            ],
            4 => [
                ['criterion' => onactivate::class],
            ],
        ],
        '4-value_sommelier' => [
            1 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 3,
                    'courses' => [
                        get_course_('orientation', $data),
                        get_course_('conversations', $data),
                        get_course_('nursing', $data),
                    ],
                ],
            ],
            4 => [
                ['criterion' => onactivate::class],
            ],
        ],
        '4-value_barista' => [
            1 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                    'aggregation_required_count' => 2,
                    'courses' => [
                        get_course_('infosec', $data),
                        get_course_('recruit', $data),
                    ],
                ],
            ],
            4 => [
                ['criterion' => onactivate::class],
            ],
        ],
        '4-value_bartender' => [
            1 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 2,
                ],
            ],
            4 => [
                ['criterion' => onactivate::class],
            ],
        ],
        '4-value_mad-preacher' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('conversations', $data),
                            get_course_('waitangi', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('conversations', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                    ],
                ],
            ],
            3 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 1,
                ],
            ],
            4 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'star-wars_lightsaber' => [
            1 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 5,
                ],
            ],
            2 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 5,
                ],
            ],
            5 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'star-wars_pod-racer' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 3,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('waitangi', $data),
                            get_course_('nursing', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('management', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                    ],
                ],
            ],
            5 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'star-wars_storm-trooper' => [
            1 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 3,
                    'courses' => [
                        get_course_('recruit', $data),
                        get_course_('orientation', $data),
                        get_course_('conversations', $data),
                        get_course_('management', $data),
                    ],
                ],
            ],
            3 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('management', $data),
                        ],
                    ],
                ],
            ],
            5 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'star-wars_sith-lord' => [
            1 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 4,
                        'courses' => [
                            get_course_('infosec', $data),
                            get_course_('recruit', $data),
                            get_course_('communication', $data),
                            get_course_('waitangi', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 3,
                    ],
                ],
            ],
            3 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 3,
                ],
            ],
            5 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'arbitrary_teeth-whitening' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('listening', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'arbitrary_hoarder' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('waitangi', $data),
                            get_course_('management', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'arbitrary_cc' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('conversations', $data),
                            get_course_('communication', $data),
                            get_course_('nursing', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_drive' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('communication', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('communication', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_serving' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('waitangi', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_quality' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('infosec', $data),
                            get_course_('waitangi', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ANY_N,
                    'aggregation_required_count' => 1,
                    'courses' => [
                        get_course_('infosec', $data),
                        get_course_('waitangi', $data),
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_integrity' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('waitangi', $data),
                            get_course_('nursing', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_planning' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 4,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('nursing', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_confidence' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('conversations', $data),
                            get_course_('management', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('conversations', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_problem-solving' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('listening', $data),
                            get_course_('conversations', $data),
                            get_course_('communication', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('listening', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_info-seeking' => [
            1 => [
                [
                    'criterion' => linkedcourses::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_communication' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 3,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 3,
                        'courses' => [
                            get_course_('recruit', $data),
                            get_course_('orientation', $data),
                            get_course_('communication', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_embracing' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 4,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('listening', $data),
                            get_course_('recruit', $data),
                            get_course_('health', $data),
                            get_course_('conversations', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('orientation', $data),
                            get_course_('listening', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_collaborating' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                        'courses' => [
                            get_course_('recruit', $data),
                            get_course_('management', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('recruit', $data),
                            get_course_('management', $data),
                        ],
                    ],
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 1,
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_influencing' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 4,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('recruit', $data),
                            get_course_('nursing', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_innovation' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('communication', $data),
                            get_course_('management', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_thinking' => [
            1 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 3,
                        'courses' => [
                            get_course_('health', $data),
                            get_course_('waitangi', $data),
                            get_course_('infosec', $data),
                            get_course_('conversations', $data),
                        ],
                    ],
                ],
            ],
            2 => [
                [
                    [
                        'criterion' => linkedcourses::class,
                        'aggregation' => criterion::AGGREGATE_ALL,
                    ],
                    [
                        'criterion' => coursecompletion::class,
                        'aggregation' => criterion::AGGREGATE_ANY_N,
                        'aggregation_required_count' => 2,
                        'courses' => [
                            get_course_('infosec', $data),
                            get_course_('conversations', $data),
                        ],
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
        'bs_managing' => [
            1 => [
                [
                    'criterion' => coursecompletion::class,
                    'aggregation' => criterion::AGGREGATE_ALL,
                    'courses' => [
                        get_course_('communication', $data),
                        get_course_('waitangi', $data),
                        get_course_('listening', $data),
                    ],
                ],
            ],
            3 => [
                ['criterion' => onactivate::class],
            ],
        ],
    ], $data, $competency_generator);

    // Then we need to create manual pathways for the competencies
    create_manual_rating_pathways([
        [
            'roles' => [
                self_role::class,
            ],
            'competencies' => [
                get_competency('complex', 'priest', $data),
                get_competency('arbitrary', 'teeth-whitening', $data),
                get_competency('arbitrary', 'hoarder', $data),
                get_competency('4-value', 'netflix', $data),
            ],
        ], [
            'roles' => [
                self_role::class,
                manager::class,
            ],
            'competencies' => [
                get_competency('binary', 'doer', $data),
                get_competency('arbitrary', 'hoarder', $data),
                get_competency('complex', 'administrative-nurse', $data),
                get_competency('4-value', 'it', $data),
                get_competency('star-wars', 'storm-trooper', $data),
                get_competency('bs', 'integrity', $data),
            ],
        ], [
            'roles' => [
                self_role::class,
                manager::class,
                appraiser::class,
            ],
            'competencies' => [
                get_competency('binary', 'literate', $data),
                get_competency('arbitrary', 'cc', $data),
                get_competency('complex', 'consultant', $data),
                get_competency('bs', 'drive', $data),
            ],
        ], [
            'roles' => [
                manager::class,
                appraiser::class,
            ],
            'competencies' => [
                get_competency('binary', 'literate', $data),
                get_competency('arbitrary', 'cc', $data),
                get_competency('complex', 'consultant', $data),
                get_competency('4-value', 'shop-keeper', $data),
                get_competency('star-wars', 'lightsaber', $data),
                get_competency('bs', 'drive', $data),
            ],
        ], [
            'roles' => [
                manager::class,
            ],
            'competencies' => [
                get_competency('4-value', 'netflix', $data),
                get_competency('4-value', 'shop-keeper', $data),
                get_competency('4-value', 'machinery-operator', $data),
                get_competency('4-value', 'it', $data),
                get_competency('4-value', 'sommelier', $data),
                get_competency('4-value', 'barista', $data),
                get_competency('4-value', 'bartender', $data),
                get_competency('4-value', 'mad-preacher', $data),
                get_competency('arbitrary', 'teeth-whitening', $data),
                get_competency('arbitrary', 'hoarder', $data),
                get_competency('arbitrary', 'cc', $data),
                get_competency('complex', 'consultant', $data),
                get_competency('complex', 'nurse', $data),
                get_competency('complex', 'administrative-nurse', $data),
                get_competency('complex', 'surgeon', $data),
                get_competency('complex', 'camp-ground-manager', $data),
            ],
        ],
    ]);

    // Then we need to create some learning plan pathways
    $learning_plan_pathways = [
        get_competency('binary', 'literate', $data)->id,
        get_competency('arbitrary', 'cc', $data)->id,
        get_competency('complex', 'consultant', $data)->id,
        get_competency('4-value', 'shop-keeper', $data)->id,
        get_competency('star-wars', 'lightsaber', $data)->id,
        get_competency('bs', 'drive', $data)->id,
    ];
    foreach ($learning_plan_pathways as $competency) {
        $competency_generator->create_learning_plan_pathway($competency);
    }

    // Then let's create some manual ratings
    create_manual_ratings([
        'jm_binary_literate' => [['self', 'jm', 'low-scale_1']],
        'jm_binary_doer' => [['self', 'jm', 'low-scale_0']],
        'jm_complex_consultant' => [['self', 'jm', 'overboard-scale_5']],
        'jm_complex_administrative-nurse' => [['self', 'jm', 'overboard-scale_1']],
        'jm_4-value_netflix' => [['self', 'jm', '4-value-scale_0']],
        'jm_4-value_it' => [['self', 'jm', '4-value-scale_2']],
        'jm_star-wars_storm-trooper' => [['self', 'jm', 'star-wars_0']],
        'ss_binary_literate' => [['self', 'ss', 'low-scale_1']],
        'ss_binary_doer' => [['self', 'ss', 'low-scale_0']],
        'ss_complex_consultant' => [['self', 'ss', 'overboard-scale_1']],
        'ss_complex_administrative-nurse' => [['self', 'ss', 'overboard-scale_1']],
        'ss_complex_priest' => [['self', 'ss', 'overboard-scale_0']],
        'ss_4-value_netflix' => [['self', 'ss', '4-value-scale_2']],
        'ss_4-value_it' => [['self', 'ss', '4-value-scale_2']],
        'ss_star-wars_storm-trooper' => [['self', 'ss', 'star-wars_1']],
        'dt_binary_literate' => [['self', 'dt', 'low-scale_0']],
        'dt_complex_consultant' => [['self', 'dt', 'overboard-scale_5']],
        'dt_complex_administrative-nurse' => [['self', 'dt', 'overboard-scale_4']],
        'dt_4-value_netflix' => [['self', 'dt', '4-value-scale_1']],
        'dt_4-value_it' => [['self', 'dt', '4-value-scale_0']],
        'dt_star-wars_storm-trooper' => [['self', 'dt', 'star-wars_1']],
        'jt_binary_literate' => [['self', 'jt', 'low-scale_0']],
        'jt_complex_consultant' => [['self', 'jt', 'overboard-scale_2']],
        'ut_binary_literate' => [
            ['self', 'ut', 'low-scale_1'],
            ['manager', 'jm', 'low-scale_0'],
            ['manager', 'ss', 'low-scale_0', timestamp('2019-08-11')]
        ],
        'ut_binary_doer' => [['self', 'ut', 'low-scale_0'], ['manager', 'jm', 'low-scale_0']],
        'ut_complex_consultant' => [['self', 'ut', 'overboard-scale_5'], ['manager', 'jm', 'overboard-scale_1']],
        'ut_complex_nurse' => [['manager', 'jm', 'overboard-scale_2']],
        'ut_complex_administrative-nurse' => [['self', 'ut', 'overboard-scale_1'], ['manager', 'jm', 'overboard-scale_3']],
        'ut_complex_surgeon' => [['manager', 'jm', 'overboard-scale_1']],
        'ut_complex_camp-ground-manager' => [['manager', 'jm', 'overboard-scale_3']],
        'ut_4-value_netflix' => [['self', 'ut', '4-value-scale_3'], ['manager', 'jm', '4-value-scale_1']],
        'ut_4-value_shop-keeper' => [['manager', 'jm', '4-value-scale_1']],
        'ut_4-value_machinery-operator' => [['manager', 'jm', '4-value-scale_1']],
        'ut_4-value_it' => [['self', 'ut', '4-value-scale_0'], ['manager', 'jm', '4-value-scale_0']],
        'ut_4-value_sommelier' => [['manager', 'jm', '4-value-scale_1']],
        'ut_4-value_barista' => [['manager', 'jm', '4-value-scale_3']],
        'ut_4-value_bartender' => [['manager', 'jm', '4-value-scale_1']],
        'ut_4-value_mad-preacher' => [['manager', 'jm', '4-value-scale_3']],
        'ut_star-wars_lightsaber' => [['manager', 'jm', 'star-wars_4']],
        'ut_star-wars_storm-trooper' => [['self', 'ut', 'star-wars_3'], ['manager', 'jm', 'star-wars_4']],
        'sj_binary_literate' => [
            ['self', 'sj', 'low-scale_1'],
            ['manager', 'jm', 'low-scale_1', timestamp('2019-11-02')],
            ['manager', 'ss', 'low-scale_1']
        ],
        'sj_binary_doer' => [['self', 'sj', 'low-scale_1'], ['manager', 'jm', 'low-scale_1']],
        'sj_complex_consultant' => [['self', 'sj', 'overboard-scale_2'], ['manager', 'jm', 'overboard-scale_4']],
        'sj_complex_nurse' => [['manager', 'jm', 'overboard-scale_2']],
        'sj_complex_administrative-nurse' => [['self', 'sj', 'overboard-scale_2'], ['manager', 'jm', 'overboard-scale_1']],
        'sj_complex_surgeon' => [['manager', 'jm', 'overboard-scale_5']],
        'sj_complex_priest' => [['self', 'sj', 'overboard-scale_1']],
        'sj_complex_camp-ground-manager' => [['manager', 'jm', 'overboard-scale_3']],
        'sj_4-value_netflix' => [['self', 'sj', '4-value-scale_3'], ['manager', 'jm', '4-value-scale_0']],
        'sj_4-value_shop-keeper' => [['manager', 'jm', '4-value-scale_2']],
        'sj_4-value_it' => [['self', 'sj', '4-value-scale_1'], ['manager', 'jm', '4-value-scale_3']],
        'sj_4-value_sommelier' => [['manager', 'jm', '4-value-scale_3']],
        'sj_4-value_barista' => [['manager', 'jm', '4-value-scale_0']],
        'sj_4-value_bartender' => [['manager', 'jm', '4-value-scale_1']],
        'sj_4-value_mad-preacher' => [['manager', 'jm', '4-value-scale_3']],
        'sj_star-wars_lightsaber' => [['manager', 'jm', 'star-wars_0']],
        'sj_star-wars_storm-trooper' => [['self', 'sj', 'star-wars_1'], ['manager', 'jm', 'star-wars_1']],
        'tr_binary_literate' => [
            ['self', 'tr', 'low-scale_0'], ['manager', 'ss', 'low-scale_0'], ['appraiser', 'jm', 'low-scale_1']
        ],
        'tr_binary_doer' => [['self', 'tr', 'low-scale_1'], ['manager', 'ss', 'low-scale_1']],
        'tr_complex_consultant' => [
            ['self', 'tr', 'overboard-scale_5'], ['manager', 'ss', 'overboard-scale_4'], ['appraiser', 'jm', 'overboard-scale_2']
        ],
        'tr_complex_nurse' => [['manager', 'ss', 'overboard-scale_2']],
        'tr_complex_administrative-nurse' => [['self', 'tr', 'overboard-scale_4'], ['manager', 'ss', 'overboard-scale_2']],
        'tr_complex_surgeon' => [['manager', 'ss', 'overboard-scale_0']],
        'tr_complex_camp-ground-manager' => [['manager', 'ss', 'overboard-scale_4']],
        'tr_4-value_netflix' => [['self', 'tr', '4-value-scale_0'], ['manager', 'ss', '4-value-scale_1']],
        'tr_4-value_shop-keeper' => [['manager', 'ss', '4-value-scale_1'], ['appraiser', 'jm', '4-value-scale_3']],
        'tr_4-value_machinery-operator' => [['manager', 'ss', '4-value-scale_2']],
        'tr_4-value_it' => [['self', 'tr', '4-value-scale_1'], ['manager', 'ss', '4-value-scale_1']],
        'tr_4-value_sommelier' => [['manager', 'ss', '4-value-scale_2']],
        'tr_4-value_barista' => [['manager', 'ss', '4-value-scale_1']],
        'tr_4-value_bartender' => [['manager', 'ss', '4-value-scale_0']],
        'tr_4-value_mad-preacher' => [['manager', 'ss', '4-value-scale_0']],
        'tr_star-wars_lightsaber' => [['manager', 'ss', 'star-wars_1'], ['appraiser', 'jm', 'star-wars_3']],
        'tr_star-wars_storm-trooper' => [['self', 'tr', 'star-wars_4'], ['manager', 'ss', 'star-wars_1']],
        'bo_binary_literate' => [['self', 'bo', 'low-scale_0'], ['manager', 'dt', 'low-scale_1']],
        'bo_binary_doer' => [['self', 'bo', 'low-scale_0'], ['manager', 'dt', 'low-scale_0']],
        'bo_complex_consultant' => [['self', 'bo', 'overboard-scale_2'], ['manager', 'dt', 'overboard-scale_4']],
        'bo_complex_nurse' => [['manager', 'dt', 'overboard-scale_0']],
        'bo_complex_administrative-nurse' => [['self', 'bo', 'overboard-scale_1'], ['manager', 'dt', 'overboard-scale_5']],
        'bo_complex_surgeon' => [['manager', 'dt', 'overboard-scale_0']],
        'bo_complex_camp-ground-manager' => [['manager', 'dt', 'overboard-scale_1']],
        'bo_4-value_netflix' => [['self', 'bo', '4-value-scale_2'], ['manager', 'dt', '4-value-scale_0']],
        'bo_4-value_shop-keeper' => [['manager', 'dt', '4-value-scale_1']],
        'bo_4-value_machinery-operator' => [['manager', 'dt', '4-value-scale_3']],
        'bo_4-value_it' => [['self', 'bo', '4-value-scale_0'], ['manager', 'dt', '4-value-scale_0']],
        'bo_4-value_sommelier' => [['manager', 'dt', '4-value-scale_0']],
        'bo_4-value_barista' => [['manager', 'dt', '4-value-scale_0']],
        'bo_4-value_bartender' => [['manager', 'dt', '4-value-scale_0']],
        'bo_4-value_mad-preacher' => [['manager', 'dt', '4-value-scale_2']],
        'bo_star-wars_lightsaber' => [['manager', 'dt', 'star-wars_2']],
        'gb_binary_literate' => [
            ['self', 'gb', 'low-scale_1'], ['manager', 'jt', 'low-scale_0'], ['appraiser', 'dt', 'low-scale_0']
        ],
        'gb_binary_doer' => [['self', 'gb', 'low-scale_1'], ['manager', 'jt', 'low-scale_0']],
        'gb_4-value_sommelier' => [['manager', 'jt', '4-value-scale_0']],
        'gm_complex_priest' => [['self', 'gm', 'overboard-scale_5']],
        'gm_4-value_netflix' => [['self', 'gm', '4-value-scale_1'], ['manager', 'jt', '4-value-scale_2']],
    ], $data);

    // Then let's archive some assignments
    $archive = [
        // Theologist POS
        [get_assignment('pos', 'pp', 'theologist', 'cc', $data)],
        [get_assignment('pos', 'pp', 'theologist', 'drive', $data), true],

        // IT audience
        [get_assignment('audience', '', 'it', 'collider', $data)],
        [get_assignment('audience', '', 'it', 'barista', $data), true],

        // VIP audience
        [get_assignment('audience', '', 'vip', 'consultant', $data), true],
        [get_assignment('audience', '', 'vip', 'nurse', $data)],
    ];

    foreach ($archive as $ass) {
        if (!is_array($ass)) {
            $ass = [$ass];
        }

        archive_assignment($ass[0], $ass[1] ?? false);
    }

    mark_competencies_self_assignable([
        'binary' => [
            'literate',
            'doer',
            'initiative',
            'collider',
        ],
        'complex' => [
            ['consultant', 1],
            ['nurse', 1],
            ['administrative-nurse', 1],
            ['surgeon', 1],
            ['priest', 1],
            ['zoo-keeper', 1],
            ['camp-ground-manager', 1],
        ],
        '4-value' => [
            ['netflix', 2],
            ['shop-keeper', 1],
            ['machinery-operator', 2],
            ['it', 1],
            ['sommelier', 2],
            ['barista', 1],
            ['bartender', 2],
            ['mad-preacher', 1],
        ],
        'star-wars' => [
            ['lightsaber', 1],
            ['pod-racer', 1],
            ['storm-trooper', 1],
            ['sith-lord', 1],
        ],
        'bs' => [
            ['drive', 1],
            ['serving', 2],
            ['quality', 1],
            ['integrity', 2],
            ['planning', 1],
            ['confidence', 2],
            ['problem-solving', 1],
            ['info-seeking', 2],
            ['communication', 1],
            ['embracing', 2],
            ['collaborating', 1],
            ['influencing', 2],
            ['innovation', 1],
            ['thinking', 2],
            ['managing', 1],
        ]
    ], $data);

    // Create some basic reusable evidence custom fields
    $evidence_fields = [
        'photo' => ['datatype' => 'file', 'fullname' => multilang('Photo(s) of document(s)'), 'shortname' => 'photo'],
        'notes' => [
            'datatype' => 'textarea',
            'defaultdata' => multilang('<p>Please add any extra relevant information here.</p>'),
            'param1' => '30',
            'param2' => '10',
            'fullname' => multilang('Additional Notes'),
            'shortname' => 'notes',
        ],
        'date' => [
            'datatype' => 'datetime',
            'fullname' => multilang('Completed on'),
            'shortname' => 'completed',
            'param1' => '2016',
            'param2' => '2019',
            'param3' => '1',
        ],
        'menu' => [
            'datatype' => 'menu',
            'fullname' => multilang('Type'),
            'shortname' => 'options',
        ],
        'confirm' => [
            'datatype'    => 'checkbox',
            'fullname'    => multilang('Manager approval'),
            'shortname'   => 'confirm',
        ],
        'text' => [
            'datatype'     => 'text',
            'param1'       => '30',
            'param2'       => '2048',
            'fullname'     => multilang('Qualification Name'),
            'shortname'    => 'name',
        ],
        'multi' => [
            'datatype'    => 'multiselect',
            'fullname'    => multilang('Other qualifications'),
            'shortname'   => 'degree',
        ],
    ];

    // Then we need to create some evidence types for the evidence
    $evidence_types = [
        'truck-licence' => [
            'name' => multilang('Commercial truck driver licence'),
            'description' => multilang('<p>In New Zealand, driver licensing is controlled by the NZ Transport Agency. There are six classes of motor-vehicle licence and nine licence endorsements. Class 1 governs vehicles with a GLW (gross laden weight) or GCW (gross combined weight) of less than 6,000 kg, and Class 6 governs motorcycles. Classes 2–5 govern heavy vehicles. </p><p>A Class 2 licence allows the holder to drive: </p> <ul><li>any rigid vehicle (including any tractor) with a GLW of more than 6,000 kg but less than 18,001 kg</li> <li>any combination vehicle with a GCW of 12,000 kg or less</li> <li>any combination vehicle consisting of a rigid vehicle with a GLW of 18,000 kg or less towing a light trailer (GLW of 3500 kg or less)</li> <li>any rigid vehicle with a GLW of more than 18,000 kg that has no more than two axles</li> <li>any vehicle covered in Class 1.</li></ul> <p>Class 3 allows the holder to drive: </p> <ul><li>any combination vehicle with a GCW of more than 12,000 kg but less than 25,001 kg</li> <li>any vehicle covered in classes 1 and 2.</li></ul> <p>Class 4 allows the holder to drive: </p> <ul><li>any rigid vehicle (including any tractor) with a GLW of more than 18,000 kg</li> <li>any combination vehicle consisting of a rigid vehicle with a GLW of more than 18,000 kg towing a light trailer (GLW of 3500 kg or less)</li> <li>vehicles covered in classes 1 and 2, but not Class 3.</li></ul> <p>Class 5 allows the holder to drive: </p> <ul><li>any combination vehicle with a GCW of more than 25,000 kg</li> <li>vehicles covered by classes 1, 2, 3 and 4.</li></ul> <p>Before getting a Class 2 licence, a driver must be at least 18 years of age and have held an unrestricted Class 1 licence for at least six months. Gaining a Class 5 is not dependent on holding a Class 3. Once a driver has a Class 2 they can progress straight through to Class 4 and Class 5. Each progression (2 to 3, 2 to 4, or 4 to 5) requires having held an unrestricted licence of the preceding class for at least six months. For drivers aged 25 or over the minimum period for holding the unrestricted time is reduced to three months, or waived entirely on completion of an approved course of instruction. </p><p>Additional endorsements on an NZ driver\'s licence govern provision of special commercial services. The endorsements are: </p> <ul><li>D - Dangerous Goods: transporting hazardous substances. Must be renewed every five years</li> <li>F - Forklift operator</li> <li>I - Driving Instructor: An "I" endorsement is awarded for a specific Class of licence, e.g.: 5-I</li> <li>O - Testing Officer: Driving assessors who test a person prior to being granted a particular class of licence</li> <li>P - Passenger: Transport of fare-paying passengers (bus and taxi drivers, limo-for-hire drivers, and dail-a-driver services)</li> <li>R - Roller: Special vehicle equipped with rollers</li> <li>T - Tracks: Special vehicle equipped with tracks</li> <li>V - Vehicle recovery: Operating a tow truck</li> <li>W - Wheels: Special vehicle equipped with wheels, other than >fire appliances, buses, tractors, vehicle-recovery vehicles, or trade vehicles.</li></ul> <p>The F, R, T and W endorsements are for operating special types of vehicle on the road. Where the holder also has a heavy vehicle (Class 2 or Class 4) licence, they are permitted to drive heavy special vehicles. Otherwise the limits for Class 1 (6,000 kg) apply. </p><p>Being granted an I, O, P and/or V endorsement requires that the applicant passes a "fit and proper person" check, to screen for people with criminal convictions or serious driving infringements. These endorsements are issued for one or five years, at the option of the applicant at the time of purchase. </p>'),
            'fields' => [
                array_merge($evidence_fields['menu'], ['param1' => implode("\n", [
                    multilang('Class 2'),
                    multilang('Class 3'),
                    multilang('Class 4'),
                    multilang('Class 5'),
                ])]),
                $evidence_fields['photo'],
                array_merge($evidence_fields['multi'], [
                    'param1' => json_encode([
                        ['option' => multilang('D - Dangerous Goods'), 'icon' => 'quality-management', 'default' => '0', 'delete' => '0'],
                        ['option' => multilang('R - Roller'), 'icon' => 'quality-management', 'default' => '0', 'delete' => '0'],
                        ['option' => multilang('T - Tracks'), 'icon' => 'quality-management', 'default' => '0', 'delete' => '0'],
                        ['option' => multilang('W - Wheels'), 'icon' => 'quality-management', 'default' => '0', 'delete' => '0'],
                    ]),
                ]),
            ],
        ],
        'participation' => [
            'name' => multilang('Participation in project work'),
            'description' => multilang('<p>The employee has successfully participated in project work and demonstrated they are able to work in a team</p>'),
            'fields' => [
                $evidence_fields['date'],
                $evidence_fields['notes'],
                $evidence_fields['confirm'],
            ],
            'add_image_to_description' => true,
        ],
        'electrical-safety' => [
            'name' => multilang('Electrical safety certificate'),
            'description' => multilang('<p>Proof that the employee is able to perform electrical work in compliance with This Electrical Safety Certificate also confirms that the electrical work complies with the building code for the purposes of Section 19(1)(e) of the Building Act 2004.</p>'),
            'fields' => [
                $evidence_fields['date'],
                $evidence_fields['notes'],
                $evidence_fields['confirm'],
            ],
        ],
        'seminar' => [
            'name' => multilang('Presenting a seminar to colleagues'),
            'description' => multilang('<p>The employee has presented a seminar in one of the following areas: <ul><li>A solo project they themselves have completed</li><li>A project that another employee has completed that they believe is to a high standard</li><li>A potential future project idea that is of interest</li></ul></p>'),
            'fields' => [
                $evidence_fields['date'],
                $evidence_fields['notes'],
                $evidence_fields['confirm'],
            ],
            'add_image_to_description' => true,
        ],
        'first-aid' => [
            'name' => multilang('First aid certificate'),
            'description' => multilang('<p>This course covers<ul><li>assessment of emergency situations</li><li>adult, child and infant resuscitation and choking</li><li>bleeding, shock, fractures, sprains and head injuries</li><li>hypothermia, burns and poisoning</li><li>medical emergencies, including asthma, diabetes and epilepsy</li><li>how to manage complex medical and traumatic emergency care situations</li></ul></p><p>On successful completion of this course you will be awarded a New Zealand Red Cross first aid certificate.</p> <p>Time commitment: 12 hours classroom learning</p> </div> <p>Valid for: 2 Year(s)</p> <h3>Standards</h3> <p><strong>NZQA Unit Standards:</strong> 06400, 06401, 06402</p> <p>If supplied with a valid NZQA ID number, New Zealand Red Cross will lodge these credits for you on successful completion of the course.</p> <p>This course also meets the Department of Labours - First Aid for Workplaces - A Good Practice Guide (September 2009) and all NZQA First Aid training requirements.</p>'),
            'fields' => [
                $evidence_fields['photo'],
                array_merge($evidence_fields['date'], ['fullname' => multilang('Obtained on')]),
                array_merge($evidence_fields['date'], ['fullname' => multilang('Expires on')]),
            ],
        ],
        'nursing' => [
            'name' => multilang('Completion of the Overseas Nursing Programme (ONP)'),
            'description' => multilang('Completion of this programme is required for any nurses who received their nursing qualification outside of the United Kingdom to work in the National Health Service.'),
            'fields' => [
                array_merge($evidence_fields['text'], ['fullname' => multilang('Origin country')]),
                $evidence_fields['photo'],
                $evidence_fields['date'],
                array_merge($evidence_fields['multi'], [
                    'param1' => json_encode([
                        ['option' => multilang('Associates'), 'icon' => 'learning-programs', 'default' => '0', 'delete' => '0'],
                        ['option' => multilang('Bachelors'), 'icon' => 'learning-programs', 'default' => '1', 'delete' => '0'],
                        ['option' => multilang('Masters'), 'icon' => 'learning-programs', 'default' => '0', 'delete' => '0'],
                        ['option' => multilang('Doctorate'), 'icon' => 'learning-programs', 'default' => '0', 'delete' => '0'],
                    ]),
                ]),
            ],
        ],
        'railway-safety' => [
            'name' => multilang('National Rail Safety Standards (NRSS) compliance'),
            'description' => multilang('<p>Rail Participants and Contractors operating and working on the National Rail System (NRS) are subject to the Railways Act 2005 and must either:<ul><li>Hold a track access agreement and rail license covering the scope of the intended activities, or</li><li>Be approved by a Rail Participant holding a track access agreement and a current rail license to work under the scope of those documents.</li></ul>Additionally, all parties operating on the NRS must comply with the provisions contained within the National Rail System Standards (NRSS). These standards provide a framework for the management of safety and change within a Rail Participants safety system. They also meet legislative requirements and New Zealand Transport Agency’s Rail Safety Licensing and Audit Guidelines.</p><p>NRS standards apply to all activities involving the operation of rail vehicles on the National Rail System, including:<ul><li>Definitions (language and terminology)</li><li>Safety Management</li><li>Health assessment of Rail Safety Workers</li><li>Risk Assessment</li><li>Occurrence Management</li><li>Mechanical Engineering Interoperability</li><li>Rail Operations Interoperability</li><li>Audit</li><li>Document Control</li><li>Crisis Management</li><li>Heritage Vehicle and Train Management</li></ul></p>'),
            'fields' => [
                array_merge($evidence_fields['menu'], ['param1' => implode("\n", [
                    multilang('NRSS/4'),
                    multilang('NRSS/5'),
                    multilang('NRSS/6'),
                ])]),
                array_merge($evidence_fields['date'], ['fullname' => multilang('Completed on')]),
                $evidence_fields['confirm'],
            ],
        ],
        'safety-checks' => [
            'name' => multilang('Can perform safety checks'),
            'description' => multilang('<p>The employee has demonstrated that they are able to independently conduct safety checks required for compliance.</p>'),
            'fields' => [
                $evidence_fields['date'],
                $evidence_fields['notes'],
                $evidence_fields['confirm'],
            ],
            'add_image_to_description' => true,
        ],
        'orientation' => [
            'name' => multilang('Completed new employee orientation'),
            'description' => multilang('<p>Orientation should be completed at the beginning of employment.</p>'),
            'fields' => [
                $evidence_fields['date'],
                $evidence_fields['confirm'],
            ],
            'add_image_to_description' => true,
        ],
        'staff-training' => [
            'name' => multilang('Can train new staff members'),
            'description' => multilang('<p>The employee has demonstrated that they are able to independently train new staff members.</p>'),
            'fields' => [
                $evidence_fields['date'],
                $evidence_fields['notes'],
                $evidence_fields['confirm'],
            ],
            'add_image_to_description' => true,
        ],
    ];

    foreach ($evidence_types as $key => $type) {
        $type['idnumber'] = $key;
        $type['created_by'] = $type['modified_by'] = $admin_user->id;
        $data['evidence_types'][$key] = $evidence_generator->create_evidence_type_entity($type);
    }
    $data['evidence_types']['coursecompletionimport'] = evidence_type::repository()
        ->where('idnumber', 'coursecompletionimport')->one();
    $data['evidence_types']['certificationcompletionimport'] = evidence_type::repository()
        ->where('idnumber', 'certificationcompletionimport')->one();
    $data['users']['admin'] = get_admin();

    // Then we need to create some evidence items for users
    $evidence_items = [
        'jm' => [
            ['type' => get_evidence_type('orientation', $data), 'created_by' => get_user('jm', $data)],
            ['type' => get_evidence_type('staff-training', $data), 'created_by' => get_user('jm', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '23', 'importid' => '1']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '2']
            ],
        ],
        'ss' => [
            ['type' => get_evidence_type('orientation', $data), 'created_by' => get_user('ss', $data)],
            ['type' => get_evidence_type('staff-training', $data), 'created_by' => get_user('ss', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '65', 'importid' => '3']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '4']
            ],
        ],
        'dt' => [
            ['type' => get_evidence_type('orientation', $data), 'created_by' => get_user('dt', $data)],
            ['type' => get_evidence_type('staff-training', $data), 'created_by' => get_user('dt', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '34', 'importid' => '5']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '6']
            ],
        ],
        'jt' => [
            ['type' => get_evidence_type('orientation', $data), 'created_by' => get_user('jt', $data)],
            ['type' => get_evidence_type('staff-training', $data), 'created_by' => get_user('jt', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '12', 'importid' => '7']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '8']
            ],
        ],
        'ut' => [
            ['type' => get_evidence_type('truck-licence', $data), 'created_by' => get_user('ut', $data)],
            ['type' => get_evidence_type('electrical-safety', $data), 'created_by' => get_user('ut', $data)],
            ['type' => get_evidence_type('staff-training', $data), 'created_by' => get_user('jm', $data)],
            ['type' => get_evidence_type('nursing', $data), 'created_by' => get_user('jm', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '44', 'importid' => '9']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '10']
            ],
        ],
        'sj' => [
            ['type' => get_evidence_type('staff-training', $data), 'created_by' => get_user('sj', $data)],
            ['type' => get_evidence_type('nursing', $data), 'created_by' => get_user('sj', $data)],
            ['type' => get_evidence_type('truck-licence', $data), 'created_by' => get_user('jm', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '77', 'importid' => '11']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '12']
            ],
        ],
        'tr' => [
            ['type' => get_evidence_type('electrical-safety', $data), 'created_by' => get_user('tr', $data)],
            ['type' => get_evidence_type('truck-licence', $data), 'created_by' => get_user('tr', $data)],
            ['type' => get_evidence_type('nursing', $data), 'created_by' => get_user('ss', $data)],
            ['type' => get_evidence_type('orientation', $data), 'created_by' => get_user('ss', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '98', 'importid' => '13']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '14']
            ],
        ],
        'bw' => [
            ['type' => get_evidence_type('staff-training', $data), 'created_by' => get_user('bw', $data)],
            ['type' => get_evidence_type('orientation', $data), 'created_by' => get_user('bw', $data)],
            ['type' => get_evidence_type('first-aid', $data), 'created_by' => get_user('ss', $data)],
            ['type' => get_evidence_type('seminar', $data), 'created_by' => get_user('ss', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '82', 'importid' => '15']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '16']
            ],
        ],
        'vp' => [
            ['type' => get_evidence_type('truck-licence', $data), 'created_by' => get_user('vp', $data)],
            ['type' => get_evidence_type('railway-safety', $data), 'created_by' => get_user('vp', $data)],
            ['type' => get_evidence_type('seminar', $data), 'created_by' => get_user('dt', $data)],
            ['type' => get_evidence_type('electrical-safety', $data), 'created_by' => get_user('dt', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '61', 'importid' => '17']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '18']
            ],
        ],
        'bo' => [
            ['type' => get_evidence_type('seminar', $data), 'created_by' => get_user('bo', $data)],
            ['type' => get_evidence_type('electrical-safety', $data), 'created_by' => get_user('bo', $data)],
            ['type' => get_evidence_type('nursing', $data), 'created_by' => get_user('dt', $data)],
            ['type' => get_evidence_type('orientation', $data), 'created_by' => get_user('dt', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '22', 'importid' => '19']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '20']
            ],
        ],
        'gb' => [
            ['type' => get_evidence_type('nursing', $data), 'created_by' => get_user('gb', $data)],
            ['type' => get_evidence_type('railway-safety', $data), 'created_by' => get_user('gb', $data)],
            ['type' => get_evidence_type('staff-training', $data), 'created_by' => get_user('jt', $data)],
            ['type' => get_evidence_type('orientation', $data), 'created_by' => get_user('jt', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '29', 'importid' => '21']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '22']
            ],
        ],
        'gm' => [
            ['type' => get_evidence_type('railway-safety', $data), 'created_by' => get_user('gm', $data)],
            ['type' => get_evidence_type('staff-training', $data), 'created_by' => get_user('jt', $data)],
            ['type' => get_evidence_type('orientation', $data), 'created_by' => get_user('jt', $data)],
            ['type' => get_evidence_type('coursecompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['courseshortname' => 'course', 'courseidnumber' => 'course', 'grade' => '92', 'importid' => '23']
            ],
            ['type' => get_evidence_type('certificationcompletionimport', $data), 'created_by' => get_user('admin', $data),
                'fields' => ['certificationshortname' => 'cert', 'certificationidnumber' => 'cert', 'importid' => '24']
            ],
        ],
    ];

    $data['evidence_items'] = create_evidence_items($evidence_items, $data, $evidence_generator);

    $linked_evidence = [
        'jm-1' => [
            'complex_consultant' => [get_evidence_item('jm', 0, $data), get_evidence_item('jm', 3, $data)],
            'star-wars_lightsaber' => [get_evidence_item('jm', 0, $data), get_evidence_item('jm', 3, $data)],
        ],
        'jm-2' => [
            'arbitrary_cc' => [get_evidence_item('jm', 0, $data), get_evidence_item('jm', 3, $data)],
            'bs_drive' => [get_evidence_item('jm', 0, $data), get_evidence_item('jm', 3, $data)],
            'binary_literate' => [get_evidence_item('jm', 0, $data), get_evidence_item('jm', 3, $data)],
        ],
        'ss-1' => [
            'binary_literate' => [get_evidence_item('ss', 0, $data), get_evidence_item('ss', 3, $data)],
            '4-value_shop-keeper' => [get_evidence_item('ss', 0, $data), get_evidence_item('ss', 3, $data)],
        ],
        'ss-2' => [
            'star-wars_lightsaber' => [get_evidence_item('ss', 0, $data), get_evidence_item('ss', 3, $data)],
            'bs_drive' => [get_evidence_item('ss', 0, $data), get_evidence_item('ss', 3, $data)],
        ],
        'dt-1' => [
            'binary_literate' => [get_evidence_item('dt', 0, $data), get_evidence_item('dt', 3, $data)],
            'complex_consultant' => [get_evidence_item('dt', 0, $data), get_evidence_item('dt', 3, $data)],
        ],
        'dt-2' => [
            '4-value_shop-keeper' => [get_evidence_item('dt', 0, $data), get_evidence_item('dt', 3, $data)],
            'arbitrary_cc' => [get_evidence_item('dt', 0, $data), get_evidence_item('dt', 3, $data)],
        ],
        'jt-1' => [
            'complex_consultant' => [get_evidence_item('jt', 0, $data), get_evidence_item('jt', 3, $data)],
            'star-wars_lightsaber' => [get_evidence_item('jt', 0, $data), get_evidence_item('jt', 3, $data)],
        ],
        'jt-2' => [
            'arbitrary_cc' => [get_evidence_item('jt', 0, $data), get_evidence_item('jt', 3, $data)],
            'bs_drive' => [get_evidence_item('jt', 0, $data), get_evidence_item('jt', 3, $data)],
        ],
        'ut-1' => [
            'binary_literate' => [get_evidence_item('ut', 0, $data), get_evidence_item('ut', 3, $data)],
            'complex_consultant' => [get_evidence_item('ut', 0, $data), get_evidence_item('ut', 3, $data)],
        ],
        'ut-2' => [
            '4-value_shop-keeper' => [get_evidence_item('ut', 0, $data), get_evidence_item('ut', 3, $data)],
            'star-wars_lightsaber' => [get_evidence_item('ut', 0, $data), get_evidence_item('ut', 3, $data)],
        ],
        'ut-3' => [
            'arbitrary_cc' => [get_evidence_item('ut', 0, $data), get_evidence_item('ut', 3, $data)],
            'bs_drive' => [get_evidence_item('ut', 0, $data), get_evidence_item('ut', 3, $data)],
        ],
        'sj-1' => [
            'binary_literate' => [get_evidence_item('sj', 0, $data), get_evidence_item('sj', 3, $data)],
            'complex_consultant' => [get_evidence_item('sj', 0, $data), get_evidence_item('sj', 3, $data)],
        ],
        'sj-2' => [
            '4-value_shop-keeper' => [get_evidence_item('sj', 0, $data), get_evidence_item('sj', 3, $data)],
            'star-wars_lightsaber' => [get_evidence_item('sj', 0, $data), get_evidence_item('sj', 3, $data)],
            'bs_drive' => [get_evidence_item('sj', 0, $data), get_evidence_item('sj', 3, $data)],
        ],
        'tr-1' => [
            'binary_literate' => [get_evidence_item('tr', 0, $data), get_evidence_item('tr', 3, $data)],
            'complex_consultant' => [get_evidence_item('tr', 0, $data), get_evidence_item('tr', 3, $data)],
            '4-value_shop-keeper' => [get_evidence_item('tr', 0, $data), get_evidence_item('tr', 3, $data)],
        ],
        'tr-2' => [
            'star-wars_lightsaber' => [get_evidence_item('tr', 0, $data), get_evidence_item('tr', 3, $data)],
            'arbitrary_cc' => [get_evidence_item('tr', 0, $data), get_evidence_item('tr', 3, $data)],
        ],
        'bo-1' => [
            'binary_literate' => [get_evidence_item('bo', 0, $data), get_evidence_item('bo', 3, $data)],
            'complex_consultant' => [get_evidence_item('bo', 0, $data), get_evidence_item('bo', 3, $data)],
            '4-value_shop-keeper' => [get_evidence_item('bo', 0, $data), get_evidence_item('bo', 3, $data)],
        ],
        'bo-2' => [
            'star-wars_lightsaber' => [get_evidence_item('bo', 0, $data), get_evidence_item('bo', 3, $data)],
            'arbitrary_cc' => [get_evidence_item('bo', 0, $data), get_evidence_item('bo', 3, $data)],
            'bs_drive' => [get_evidence_item('bo', 0, $data), get_evidence_item('bo', 3, $data)],
        ],
        'gb-1' => [
            'binary_literate' => [get_evidence_item('gb', 0, $data), get_evidence_item('gb', 3, $data)],
        ],
    ];

    foreach ($linked_evidence as $plan => $competencies) {
        link_evidence_to_plan($plan, $competencies, $data, $evidence_generator);
    }

    $activities = [
        [
            'general' => [
                'activity_name' => multilang('Mid-year appraisal'),
                'description' => multilang('Appraiser answers questions about an employee'),
                'activity_type' => 'appraisal',
                'status' => 'ACTIVE',
                'create_track' => true,
                'anonymous_responses' => false,
                'manual_relationships' => null,
                'tenant' => 1,
            ],
            'track_schedule' => [
                'schedule_is_open' => false,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => ['iso' => date('Y-m-d', time() - WEEKSECS), 'timezone' => 'Pacific/Auckland'],
                'schedule_fixed_to' => ['iso' => date('Y-m-d', time() + WEEKSECS), 'timezone' => 'Pacific/Auckland'],
                'due_date_is_enabled' => true,
                'due_date_is_fixed' => false,
                'due_date_offset' => [
                    'count' => 2,
                    'unit' => date_offset::UNIT_DAY
                ],
                'repeating_is_enabled' => false,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
            'assignments' => [
                'audiences' => [
                    'Content makers',
                    'IT Department',
                    'VIP',
                ]
            ],
            'relationships' => [
                constants::RELATIONSHIP_SUBJECT => [true, false], // can view, can answer
                constants::RELATIONSHIP_APPRAISER => [true, true],
            ]
        ],

        [
            'general' => [
                'activity_name' => multilang('Overdue feedback'),
                'description' => multilang('All the subject instances are set to overdue after this has been created'),
                'activity_type' => 'feedback',
                'status' => 'ACTIVE',
                'create_track' => true,
                'anonymous_responses' => false,
                'manual_relationships' => null,
                'tenant' => 1,
                'make_all_participants_overdue' => true,
            ],
            'track_schedule' => [
                'schedule_is_open' => false,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => ['iso' => date('Y-m-d', time() - WEEKSECS), 'timezone' => 'Pacific/Auckland'],
                'schedule_fixed_to' => ['iso' => date('Y-m-d', time() + WEEKSECS), 'timezone' => 'Pacific/Auckland'],
                'due_date_is_enabled' => true,
                'due_date_is_fixed' => true,
                'due_date_fixed' => ['iso' => date('Y-m-d', time() + (2 * WEEKSECS)), 'timezone' => 'Pacific/Auckland'],
                'repeating_is_enabled' => false,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
            'assignments' => [
                'audiences' => [
                    'Content makers',
                    'IT Department',
                    'VIP',
                ]
            ],
            'relationships' => [
                constants::RELATIONSHIP_SUBJECT => [true, true], // can view, can answer
                constants::RELATIONSHIP_MANAGER => [true, true],
                constants::RELATIONSHIP_MANAGERS_MANAGER => [true, false],
            ]
        ],

        [
            'general' => [
                'activity_name' => multilang('Check-in with due date'),
                'description' => multilang('This is created with a due date in two weeks'),
                'activity_type' => 'check-in',
                'status' => 'ACTIVE',
                'create_track' => true,
                'anonymous_responses' => false,
                'manual_relationships' => null,
                'tenant' => 2,
            ],
            'track_schedule' => [
                'schedule_is_open' => false,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => ['iso' => date('Y-m-d', time() - WEEKSECS), 'timezone' => 'Pacific/Auckland'],
                'schedule_fixed_to' => ['iso' => date('Y-m-d', time() + WEEKSECS), 'timezone' => 'Pacific/Auckland'],
                'due_date_is_enabled' => true,
                'due_date_is_fixed' => true,
                'due_date_fixed' => ['iso' => date('Y-m-d', time() + (2 * WEEKSECS)), 'timezone' => 'Pacific/Auckland'],
                'repeating_is_enabled' => false,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
            'assignments' => [
                'audiences' => [
                    'Content makers',
                    'IT Department',
                    'VIP',
                ]
            ],
            'relationships' => [
                constants::RELATIONSHIP_SUBJECT => [true, true], // can view, can answer
                constants::RELATIONSHIP_MANAGER => [true, false],
                constants::RELATIONSHIP_APPRAISER => [true, true],
            ]
        ],

        [
            'general' => [
                'activity_name' => multilang('Monthly Check-in'),
                'description' => multilang('Check-in with the line manager - just a basic draft'),
                'activity_type' => 'check-in',
                'status' => 'DRAFT',
                'create_track' => true,
                'anonymous_responses' => false,
                'manual_relationships' => null,
                'tenant' => 2,
            ],
        ],

        [
            'general' => [
                'activity_name' => multilang('Anonymous feedback about non-participating subject'),
                'description' => multilang('The subject user is not a participant in this activity. Responses remain anonymous. Still, please keep it civil.'),
                'activity_type' => 'feedback',
                'status' => 'ACTIVE',
                'create_track' => true,
                'anonymous_responses' => true,
                'manual_relationships' => null,
                'tenant' => 1,
            ],
            'track_schedule' => [
                'schedule_is_open' => false,
                'schedule_is_fixed' => true,
                'schedule_fixed_from' => ['iso' => date('Y-m-d', time() - WEEKSECS), 'timezone' => 'Pacific/Auckland'],
                'schedule_fixed_to' => ['iso' => date('Y-m-d', time() + WEEKSECS), 'timezone' => 'Pacific/Auckland'],
                'due_date_is_enabled' => true,
                'due_date_is_fixed' => false,
                'due_date_offset' => [
                    'count' => 5,
                    'unit' => date_offset::UNIT_DAY
                ],
                'repeating_is_enabled' => false,
                'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            ],
            'assignments' => [
                'audiences' => [
                    'Content makers',
                    'IT Department',
                    'VIP',
                ]
            ],
            'relationships' => [
                constants::RELATIONSHIP_MANAGER => [true, true], // can view, can answer
                constants::RELATIONSHIP_APPRAISER => [true, true],
            ]
        ],

    ];

    create_activities($activities, $data['tenants']);

    run_tasks();

    create_info_block($data);
}

function create_activities(array $activities_data, $tenants): void {
    foreach ($activities_data as $activity_data) {
        create_activity($activity_data, $tenants);
    }
    // Run the expand tasks for the activities.
    $expand_task = expand_assignments_task::create();
    $expand_task->expand_all();
    $expand_task = new subject_instance_creation();
    $expand_task->generate_instances();

    // Set subject instances to overdue for the activities where this is configured.
    foreach ($activities_data as $activity_data) {
        if ($activity_data['general']['status'] === 'ACTIVE'
            && isset($activity_data['general']['make_all_participants_overdue'])
            && $activity_data['general']['make_all_participants_overdue'] === true) {

            $activity = activity::repository()
                ->where('name', $activity_data['general']['activity_name'])
                ->one(true);

            subject_instance::repository()
                ->filter_by_activity_id($activity->id)
                ->get()
                ->map(function(subject_instance $subject_instance) {
                    $subject_instance->due_date = time() - DAYSECS;
                    $subject_instance->save();
                });
        }
    }
}

function create_activity(array $activity_data, $tenants): void {
    $perform_generator = perform_generator();

    // Set the tenant category if necessary.
    if (!empty($tenants) && isset($activity_data['general']['tenant'])) {
        if (!isset($tenants[$activity_data['general']['tenant']])) {
            throw new coding_exception('Unknown tenant ' . $activity_data['general']['tenant'] . ' for activity');
        }
        $tenant = $tenants[$activity_data['general']['tenant']];
        $activity_data['general']['category'] = $tenant->categoryid;
    }

    // Create activity
    $activity_data['general']['create_section'] = false; // We create the section ourselves later below.
    $activity_data['general']['activity_status'] = 'DRAFT'; // Always create as draft, we activate later below.

    $activity = $perform_generator->create_activity_in_container($activity_data['general']);
    /** @var track $track */
    $track = $activity->get_tracks()->first();

    // Track schedule
    if (isset($activity_data['track_schedule'])) {
        $activity_data['track_schedule']['track_id'] = $track->id;

        // Use the update_track_schedule resolver, so we can make use of the nice format that it expects.
        update_track_schedule::resolve(
            ['track_schedule' => $activity_data['track_schedule']],
            execution_context::create('dev')
        );
    }

    // Assignments
    if (isset($activity_data['assignments'])) {
        foreach ($activity_data['assignments'] as $type => $assignments) {
            switch ($type) {
                case 'audiences':
                    foreach ($assignments as $audience_name) {
                        $audience_name = empty($tenants)
                            ? $audience_name
                            : $audience_name . " (Tenant {$activity_data['general']['tenant']})";
                        $audience = cohort_entity::repository()->where('name', $audience_name)->one(true);
                        $track->add_assignment(track_assignment_type::ADMIN, grouping::cohort($audience->id));
                    }
                    break;
                default:
                    throw new coding_exception('Assignment type not implemented: ' . $type);
            }
        }
    }

    // Section relationships.
    $section_name = multilang("First section for {$activity->name}"); // Could be made configurable for multi-section.
    $section = $perform_generator->create_section($activity, ['title' => $section_name]);
    if (isset($activity_data['relationships'])) {
        foreach ($activity_data['relationships'] as $relationship_key => $permissions) {
            $perform_generator->create_section_relationship(
                $section,
                ['relationship' => $relationship_key],
                $permissions[0],
                $permissions[1]
            );
        }
    }

    // Create one section element
    $perform_generator->create_section_element_from_name([
        'section_name' => $section_name,
        'element_name' => 'short_text',
        'title' => multilang('First and only question')
    ]);

    if ($activity_data['general']['status'] === 'ACTIVE') {
        $activity->activate();
    }
}

/**
 * Create a user and assign given capabilities (if any)
 *
 * @param array $attributes
 * @param null $username
 * @param array|null $tenants
 * @return stdClass
 * @throws coding_exception
 * @throws dml_exception
 */
function create_user($attributes, $username = null, ?array $tenants = null) {

    $defaults = [
        'password' => '12345',
        'username' => $username
    ];

    if (!is_null($tenants) && isset($attributes['tenant'])) {
        if (!isset($tenants[$attributes['tenant']])) {
            throw new coding_exception('Unknown tenant '.$attributes['tenant'].' for user');
        }
        $tenant = $tenants[$attributes['tenant']];
        $attributes['tenantid'] = $tenant->id;
        unset($attributes['tenant']);
    }

    $user = generator()->create_user($attributes = array_merge($defaults, $attributes));

    $user->password = $attributes['password'];
    $user->description = $attributes['description'] ?? null;

    if (is_array($attributes['caps'] ?? null)) {
        // Let's allow the user what we want to.

        $role = db()->get_record('role', ['shortname' => 'user']);

        foreach ($attributes['caps'] as $cap) {
            if (!is_array($cap)) {
                $cap = [$cap];
            }

            assign_capability($cap[0], CAP_ALLOW, $role->id, $cap[1] ?? context_system::instance());
        }
    }

    return $user;
}

/**
 * Create competency framework
 *
 * @param array $attributes
 * @return stdClass
 */
function create_competency_framework($attributes) {
    $framework = hierarchy_generator()->create_comp_frame($attributes);

    if (is_array($attributes['competencies'] ?? null)) {
        $framework->competencies = [];

        foreach ($attributes['competencies'] as $key => $competency) {
            if (isset($competency['parent'])) {
                $competency['parentid'] = $framework->competencies[$competency['parent']]->id;
            }

            $framework->competencies[$key] = create_competency($competency, $framework);
        }
    }

    return $framework;
}

/**
 * Create competency
 *
 * @param array $attributes
 * @param stdClass $framework
 * @return stdClass
 */
function create_competency($attributes, $framework) {
    $attributes['frameworkid'] = $framework->id;

    return hierarchy_generator()->create_comp($attributes);
}

/**
 * Create competency scale
 *
 * @param $attributes
 * @return stdClass
 */
function create_scale($attributes) {

    if (is_array($attributes['values'] ?? null)) {
        $last_order = 1;

        foreach ($attributes['values'] as &$value) {
            if (isset($value['sortorder'])) {
                $last_order = $value['sortorder'];
            } else {
                $value['sortorder'] = $last_order;
                $last_order += 1;
            }
        }
    }

    $scale = hierarchy_generator()->create_scale('comp', $attributes, $attributes['values'] ?? []);

    $scale->values = db()->get_records('comp_scale_values', ['scaleid' => $scale->id], 'sortorder desc');

    return $scale;
}

/**
 * Create position
 *
 * @param array $attributes
 * @param stdClass $framework
 * @return stdClass
 */
function create_position($attributes, $framework) {
    $attributes['frameworkid'] = $framework->id;

    $pos = hierarchy_generator()->create_pos($attributes);

    if (is_array($attributes['members'] ?? null)) {
        foreach ($attributes['members'] as $member) {
            create_job_assignment('position', $pos, $member);
        }
    }

    if (is_array($attributes['competencies'] ?? null)) {
        $pos->competencies = [];

        foreach ($attributes['competencies'] as $key => $competency) {
            $pos->competencies[$key] = assignment_generator()->create_position_assignment($competency->id, $pos->id);
        }
    }

    return $pos;
}

/**
 * Create position framework
 *
 * @param array $attributes
 * @return stdClass
 */
function create_position_framework($attributes) {
    $framework = hierarchy_generator()->create_pos_frame($attributes);

    $framework->positions = [];

    if (is_array($attributes['positions'] ?? null)) {
        foreach ($attributes['positions'] as $key => $position) {
            $framework->positions[$key] = create_position($position, $framework);
        }
    }

    return $framework;
}

/**
 * Create organisation
 *
 * @param array $attributes
 * @param stdClass $framework
 * @return stdClass
 */
function create_organisation($attributes, $framework) {
    $attributes['frameworkid'] = $framework->id;

    $org = hierarchy_generator()->create_org($attributes);

    if (is_array($attributes['members'] ?? null)) {
        $org->assignments = [];

        foreach ($attributes['members'] as $member) {
            create_job_assignment('organisation', $org, $member);
        }
    }

    if (is_array($attributes['competencies'] ?? null)) {
        $org->competencies = [];

        foreach ($attributes['competencies'] as $key => $competency) {
            $org->competencies[$key] = assignment_generator()->create_organisation_assignment($competency->id, $org->id);
        }
    }

    return $org;
}

/**
 * Create organisation framework
 *
 * @param array $attributes
 * @return stdClass
 */
function create_organisation_framework($attributes) {
    $framework =  hierarchy_generator()->create_org_frame($attributes);

    $framework->organisations = [];

    if (is_array($attributes['organisations'] ?? null)) {
        foreach ($attributes['organisations'] as $key => $organisation) {
            $framework->organisations[$key] = create_organisation($organisation, $framework);
        }
    }

    return $framework;
}


/**
 * Create a job assignment for position organisation
 *
 * @param string $type Type organisation \ position
 * @param stdClass $target
 * @param stdClass $user
 * @return job_assignment
 */
function create_job_assignment(string $type, $target, $user) {
    $type = substr($type, 0 ,3);

    if (!in_array($type, ['org', 'pos'])) {
        throw new coding_exception('It is not supported currently to create a job assignment for "' . $type . '" type');
    }

    $get_id_column = function ($type) {
        switch ($type) {
            case 'pos':
                return 'positionid';
            case 'org':
                return 'organisationid';
            default:
                throw new coding_exception('It is not supported currently to create a job assignment for "' . $type . '" type');
        }
    };

    return job_assignment::create([
        'userid' => $user->id ,
        'idnumber' => 'ja_for_org_ass_' . $target->id . '_for_' . $user->id,
        'fullname' => 'Assigning ' . $type . ' ' . $target->fullname,
        $get_id_column($type) => $target->id
    ]);
}

/**
 * Create audience
 *
 * @param $attributes
 * @return stdClass|array
 */
function create_audience($attributes, $data) {
    $create_audience = function ($tenant = null) use ($attributes, $data) {
        // Make sure the audience is in the tenant context if multi tenancy is activated
        if (!empty($tenant)) {
            $attributes['contextid'] = context_coursecat::instance($tenant->categoryid)->id;
            $attributes['name'] .= ' ('.$tenant->name.')';
        } else {
            $attributes['contextid'] = context_system::instance()->id;
        }

        $attributes['name'] = multilang($attributes['name']);
        $attributes['description'] = multilang($attributes['description']);

        $audience = generator()->create_cohort($attributes);

        if (is_array($attributes['members'] ?? null)) {
            foreach ($attributes['members'] as $member) {
                // If multi tenancy is enabled only add members of that tenant to the audience
                if (!$tenant || $member->tenantid == $tenant->id) {
                    cohort_add_member($audience->id, $member->id);
                }
            }
        }

        if (is_array($attributes['competencies'] ?? null)) {
            $audience->competencies = [];

            foreach ($attributes['competencies'] as $key => $competency) {
                $audience->competencies[$key] = assignment_generator()->create_cohort_assignment($competency->id, $audience->id);
            }
        }

        return $audience;
    };

    // For tenants we want every audience to be present in both tenants
    if (!empty($data['tenants'])) {
        $audiences = [];
        foreach ($data['tenants'] as $key => $tenant) {
            $audiences[$key] = $create_audience($tenant);
        }
        return $audiences;
    } else {
        return $create_audience();
    }
}

function create_individual_assignment($competency, $user, bool $legacy = false) {
    $attributes = [];

    if ($legacy) {
        $attributes = [
            'type' => assignment::TYPE_LEGACY,
            'status' => assignment::STATUS_ARCHIVED,
            'created_at' => time(),
            'updated_at' => time(),
            'archived_at' => time(),
        ];
    }

    return assignment_generator()->create_user_assignment($competency->id, $user->id, $attributes);
}

function create_user_assignments($assignments, $data, $legacy = false) {
    $res = [];

    $assignments = array_map(function (array $item) {
        if (count($item) == 2) {
            $item[] = null;
        }

        return $item;
    }, $assignments);

    foreach ($assignments as $key => [$user, $competency, $achievement]) {
        $res[$key] = create_individual_assignment($competency, $user, $legacy);

        if (!is_null($achievement)) {
            create_achievement_record($user, $res[$key], $achievement);
        }
    }

    return $res;
}

function create_legacy_assignments($assignments, $data) {
    return create_user_assignments($assignments, $data, true);
}

/**
 * Get user from data
 *
 * @param $key
 * @param $data
 * @return stdClass|null
 */
function get_user($key, $data) {
    $user = $data['users'][$key] ?? null;

    if (is_null($user)) {
        throw new Exception('Requested user $data[\'users\'][\'' . $key , '\'] not found');
    }

    return $user;
}

/**
 * Get course from data
 *
 * @param $key
 * @param $data
 * @return stdClass|null
 */
function get_course_($key, $data) {
    $course = $data['courses'][$key] ?? null;

    if (is_null($course)) {
        throw new Exception('Requested user $data[\'courses\'][\'' . $key , '\'] not found');
    }

    return $course;
}

/**
 * Get competency from data
 *
 * @param string $fw
 * @param string|null $key
 * @param array $data
 * @return stdClass|null
 */
function get_competency($fw, $key, $data) {
    if (is_null($key) && strpos($fw, '_') !== false) {
        $competency = explode('_', $fw);
        return get_competency($competency[0], $competency[1], $data);
    }

    $comp = $data['comps'][$fw]->competencies[$key] ?? null;

    if (is_null($comp)) {
        throw new Exception('Requested competency $data[\'comps\'][\'' . $fw . '\']->competencies[\'' . $key . '\'] not found');
    }

    return $comp;
}

/**
 * Get evidence type from data
 *
 * @param $key
 * @param $data
 * @return evidence_type|null
 */
function get_evidence_type($key, $data) {
    $type = $data['evidence_types'][$key] ?? null;

    if (is_null($type)) {
        throw new Exception("Requested evidence type \$data['evidence_types']['$key'] not found");
    }

    return $type;
}

/**
 * Get evidence item from data
 *
 * @param string $user
 * @param int $index
 * @param $data
 * @return \totara_evidence\entity\evidence_item|null
 */
function get_evidence_item($user, $index, $data) {
    $evidence_items = $data['evidence_items'][$user] ?? null;

    if (is_null($evidence_items)) {
        throw new Exception("Requested evidence items \$data['evidence_items']['$user'] not found");
    }

    $item = $evidence_items[$index] ?? null;
    if (is_null($item)) {
        throw new Exception("Requested evidence item \$data['evidence_items']['$user']['$item'] not found");
    }

    return $item;
}

/**
 * Get competency from data
 *
 * @param string $type
 * @param string $fw
 * @param $item
 * @param string $key
 * @param array $data
 * @return stdClass|null
 */
function get_assignment($type, $fw, $item, $key, $data) {

    if ($type === 'audience') {
        if (!empty($data['tenants'])) {
            $audience = array_shift($data['audiences'][$item]);
        } else {
            $audience = $data['audiences'][$item];
        }
        $ass = $audience->competencies[$key] ?? null;
        $error = 'Requested assignment $data[\'' . $type . '\'][\'' . $item . '\']->competencies[\'' . $key . '\'] not found';
    } else {
        $plural = '';
        switch ($type) {
            case 'audience':
                $plural = 'audiences';
                break;
            case 'pos':
                $plural = 'positions';
                break;
            case 'orgs':
                $plural = 'organisations';
                break;

            default:
                throw new Exception('Unknown type given: ', $type);
        }

        $error = 'Requested assignment $data[\'' . $type . '\'][\'' . $fw . '\']->' . $plural . '[\'' . $item . '\']->competencies[\'' . $key . '\'] not found';

        $ass = $data[$type][$fw]->{$plural}[$item]->competencies[$key] ?? null;
    }

    if (is_null($ass)) {
        throw new Exception($error);
    }

    return $ass;
}

function archive_assignment($assignment, $continue_tracking = false) {
    $actions = new assignment_actions();
    $ids = $actions->archive($assignment->id, $continue_tracking);
    sleep(1);
    (new expand_task(db()))->expand_all();
    return !empty($ids);
}

function create_achievement_record($user, $assignment, $value) {

    $comp = builder::table('comp')->where('id', $assignment->competency_id)->one();

    $value = builder::table('comp_scale_values')
        ->join('comp_scale', 'scaleid', 'id')
        ->join('comp_scale_assignments', 'comp_scale.id', 'scaleid')
        ->where('comp_scale_assignments.frameworkid', $comp->frameworkid)
        ->where('sortorder', $value)
        ->one();

    if (is_null($value)) {
        throw new \Exception('Error getting value for: ' . $comp->fullname . ', sort order: ' . $value);
    }

    builder::table('totara_competency_achievement')
        ->insert([
            'competency_id' => $comp->id,
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'scale_value_id' => $value->id,
            'proficient' => $value->proficient,
            'status' => 0,
            'time_created' => time(),
            'time_status' => time(),
            'time_proficient' => time(),
            'time_scale_value' => time(),
            'last_aggregated' => time(),
        ]);
}

/**
 * Manually create job assignments for the specified user
 *
 * @param string $user User identifier
 * @param array $assignments Type of assignment (manager, appraiser, etc..) => List of users
 * @param $data
 *
 * @return array Created assignments
 */
function create_manual_job_assignments($user, $assignments, $data) {
    $manager_key = $user;
    $manager = get_user($manager_key, $data);
    $created_assignments = [];

    foreach ($assignments as $type => $users) {
        foreach ($users as $user_key) {
            $user = get_user($user_key, $data);
            switch ($type) {
                case manager::class:
                    $managerja = job_assignment::create_default($manager->id, [
                        'fullname' => multilang('Manager of ' . fullname($user)),
                        'idnumber' => $manager_key . '_manager_of_' . $user_key,
                    ]);
                    $created_assignments[] = job_assignment::create_default($user->id, [
                        'managerjaid' => $managerja->id,
                        'fullname' => multilang('Managed by ' . fullname($manager)),
                        'idnumber' => $user_key . '_managed_by_' . $manager_key,
                    ]);
                    break;
                case appraiser::class:
                    $created_assignments[] = job_assignment::create_default($user->id, [
                        'appraiserid' => $manager->id,
                        'fullname' => multilang('Appraised by ' . fullname($manager)),
                        'idnumber' => $user_key . '_appraised_by_' . $manager_key,
                    ]);
                    break;
                default:
                    echo 'Invalid Job Assignment Value: ' . $type;
            }
        }
    }

    return $created_assignments;
}

/**
 * Create courses and enroll users into them.
 *
 * @param array $record Course attributes
 * @param $data
 * @param \core\testing\generator $generator
 * @return stdClass|array|stdClass[] Course record
 */
function create_course_($record, $data, $generator) {
    $create_course = function ($data, $tenant = null, $key = null) use ($record, $generator) {
        if ($tenant) {
            $record['category'] = $tenant->categoryid;
            $record['shortname'] .= $tenant->id;
            $record['fullname'] .= " (Tenant {$key})";
            $record['fullname'] = multilang($record['fullname']);
        }

        $time = time();
        $course = $generator->create_course(array_merge(['enablecompletion' => COMPLETION_ENABLED], $record));

        foreach ($data['users'] as $user_key => $user) {
            if ($tenant && $user->tenantid != $tenant->id) {
                continue;
            }

            // Need to make sure the initial course completion record is created, otherwise there will be an error when enrolling
            if (!db()->record_exists('course_completions', ['course' => $course->id, 'userid' => $user->id])) {
                db()->insert_record(
                    'course_completions', [
                    'userid' => $user->id,
                    'course' => $course->id,
                    'timeenrolled' => $time,
                    'reaggregate' => $time,
                    'status' => COMPLETION_STATUS_NOTYETSTARTED,
                ]
                );
            }

            $generator->enrol_user($user->id, $course->id);
        }

        return $course;
    };

    if (!empty($data['tenants'])) {
        $courses = [];
        foreach ($data['tenants'] as $key => $tenant) {
            $courses[] = $create_course($data, $tenant, $key);
        }
        return $courses;
    } else {
        return $create_course($data);
    }
}

/**
 * Links courses to competencies.
 *
 * @param $records
 * @param $data
 */
function create_course_links($records, $data) {
    foreach ($records as $competency => $linked_courses) {
        $competency = get_competency($competency, null, $data);

        $links = [];
        foreach ($linked_courses as $course_key => $link_type) {
            $course = get_course_($course_key, $data);
            if (is_array($course)) {
                foreach ($course as $a_course) {
                    $links[] = [
                        'id' => $a_course->id,
                        'linktype' => $link_type,
                    ];
                }
            } else {
                $links[] = [
                    'id' => $course->id,
                    'linktype' => $link_type,
                ];
            }
        }
        linked_courses::set_linked_courses($competency->id, $links);
    }
}

/**
 * Create criteria pathways for the given competencies.
 *
 * @param array $competencies
 * @param array $data
 * @param \totara_competency\testing\generator $generator
 */
function create_criteria_pathways($competencies, $data, $generator) {
    $criteria_generator = \totara_criteria\testing\generator::instance();

    foreach ($competencies as $competency => $pathways) {
        $competency = new competency(get_competency($competency, null, $data), false);

        $scale_map = $competency->scale->values->key_by('sortorder')->all(true);

        foreach ($pathways as $scale_key => $criteria_groups) {
            foreach ($criteria_groups as $criteria_group) {
                if (!isset($criteria_group[0])) {
                    $criteria_group = [$criteria_group];
                }

                $criteria = create_criteria($criteria_group, $competency, $criteria_generator);
                $generator->create_criteria_group($competency, $criteria, $scale_map[$scale_key]);
            }
        }
    }
}

/**
 * Create an individual criterion for a criteria group.
 *
 * @param array $criteria_group
 * @param stdClass|competency $competency
 * @param \totara_criteria\testing\generator $generator
 * @return criterion[]
 */
function create_criteria($criteria_group, $competency, $generator) {
    $criteria = [];
    foreach ($criteria_group as $criterion) {
        $criterion_method = 'create_' . (new ReflectionClass($criterion['criterion']))->getShortName();
        $criteria_data = ['competency' => $competency->id];

        switch ($criterion['criterion']) {
            case coursecompletion::class:
                if (is_array($criterion['courses'][0])) {
                    $course_ids = [];
                    foreach ($criterion['courses'] as $courses) {
                        foreach ($courses as $course) {
                            $course_ids[] = $course->id;
                        }
                    }
                } else {
                    $course_ids = array_map(function (stdClass $course) {
                        return $course->id;
                    }, $criterion['courses']);
                }
                $criteria[] = $generator->$criterion_method(array_merge($criteria_data, [
                    'aggregation' => [
                        'method' => $criterion['aggregation'],
                        'req_count' => $criterion['aggregation_required_count'] ?? 1,
                    ],
                    'courseids' => $course_ids,
                ]));
                break;

            case linked_courses::class:
            case childcompetency::class:
                $criteria[] = $generator->$criterion_method(array_merge($criteria_data, [
                    'aggregation' => [
                        'method' => $criterion['aggregation'],
                        'req_count' => $criterion['aggregation_required_count'] ?? 1,
                    ],
                ]));
                break;

            default:
                $criteria[] = $generator->$criterion_method($criteria_data);
                break;
        }
    }
    return $criteria;
}

/**
 * Create manual rating pathways for the specified competencies
 *
 * @param array $pathways
 */
function create_manual_rating_pathways($pathways) {
    $generator = competency_generator();

    foreach ($pathways as $pathway) {
        foreach ($pathway['competencies'] as $competency) {
            $generator->create_manual($competency, $pathway['roles']);
        }
    }
}

/**
 * Create Manual Ratings
 *
 * @param array $manual_ratings
 * @param array $data
 */
function create_manual_ratings($manual_ratings, $data) {
    $time = time();

    foreach ($manual_ratings as $key => $values) {
        [$user, $fw, $comp] = explode('_', $key);
        $user = get_user($user, $data);
        $comp = get_competency($fw, $comp, $data);

        $placeholder_comment = multilang(
            "Placeholder Comment: Wow, this person definitely possesses this scale value, no doubt about it!"
        );

        foreach ($values as $rating) {
            [$scale, $index] = explode('_', $rating[2]);
            $scale = $data['scales'][$scale];
            $scale_value_id = array_keys($scale->values)[$index];

            (new \pathway_manual\entity\rating([
                'user_id' => $user->id,
                'competency_id' => $comp->id,
                'assigned_by' => get_user($rating[1], $data)->id,
                'assigned_by_role' => $rating[0],
                'scale_value_id' => $scale_value_id,
                'date_assigned' => $rating[3] ?? $time,
                'comment' => $rating[4] ?? $placeholder_comment,
            ]))->save();
        }
    }
}

/**
 * Create learning plans for a user, with competencies assigned with values.
 *
 * @param stdClass $user For user
 * @param array $plans Array of [competency => scale value]
 * @param array $data
 * @param \totara_competency\testing\generator $generator
 * @return array
 */
function create_learning_plans($user, $plans, $data, $generator) {
    $created = [];

    foreach ($plans as $key => $plan) {
        $competencies = [];
        foreach ($plan as $competency => $scale_value) {
            $competency = get_competency($competency, null, $data);
            $competency = new competency($competency, false);

            $scale_value = scale_value::repository()
                ->where('scaleid', $competency->scale->id)
                ->where('sortorder', $scale_value)
                ->one();

            $competencies[$competency->id] = $scale_value->id;
        }

        $created[$key] = $generator->create_learning_plan_with_competencies($user, $competencies);
    }

    return $created;
}

/**
 * Link a learning plan's competencies to the user's first and last evidence items.
 *
 * @param string $plan Plan key
 * @param array $competencies Competencies and evidence to link
 * @param array $data
 * @param \totara_evidence\testing\generator $generator
 */
function link_evidence_to_plan($plan, $competencies, $data, $generator) {
    $preplan = $plan;
    $plan = $data['learning_plans'][$plan];

    foreach ($competencies as $competency => $evidence_to_link) {
        $pre_comp = $competency;
        $competency = get_competency($competency, null, $data);

        $plan_competency_assign = builder::table('dp_plan_competency_assign')
            ->where('planid', $plan->id)
            ->where('competencyid', $competency->id)
            ->one();

        if (!$plan_competency_assign) {
            echo "\ncouldnt find plan assign comp $pre_comp  plan  $preplan\n";
            continue;
        }

        foreach ($evidence_to_link as $evidence) {
            $generator->create_evidence_plan_relation($evidence, [
                'planid' => $plan->id,
                'component' => 'competency',
                'itemid' => $plan_competency_assign->id,
            ]);
        }
    }
}

/**
 * Create evidence items for users.
 *
 * @param array $evidence_items
 * @param array $data
 * @param \totara_evidence\testing\generator $generator
 * @return array
 */
function create_evidence_items($evidence_items, $data, $generator) {
    $created = [];

    foreach ($evidence_items as $user_key => $items) {
        $created[$user_key] = [];
        $user_for = get_user($user_key, $data);

        foreach ($items as $item) {
            $created[$user_key][] = $generator->create_evidence_item_entity(array_merge($item, [
                'created_by' => $item['created_by']->id,
                'user_id' => $user_for->id,
                'name' => evidence_item::get_default_name(
                    new user($user_for),
                    \totara_evidence\models\evidence_type::load_by_entity($item['type'])
                ),
            ]));
        }
    }

    return $created;
}

/**
 * Return an instance of testing data generator
 *
 * @return \core\testing\generator;
 */
function generator() {
    return \core\testing\generator::instance();
}

/**
 * Get Competencies specific generator
 *
 * @return \totara_competency\testing\generator
 */
function competency_generator() {
    return \totara_competency\testing\generator::instance();
}

/**
 * Get Hierarchy specific generator
 *
 * @return \totara_hierarchy\testing\generator
 */
function hierarchy_generator() {
    return \totara_hierarchy\testing\generator::instance();
}

/**
 * Get Assignment specific generator
 *
 * @return \totara_competency\testing\assignment_generator
 */
function assignment_generator() {
    return generator()->get_plugin_generator('totara_competency')->assignment_generator();
}

/**
 * Get Evidence specific generator
 *
 * @return \totara_evidence\testing\generator
 */
function evidence_generator() {
    return \totara_evidence\testing\generator::instance();
}

/**
 * Get Perform generator
 *
 * @return \mod_perform\testing\generator
 */
function perform_generator() {
    return \mod_perform\testing\generator::instance();
}

function run_tasks() {
    (new expand_task(db()))->expand_all();
    (new totara_competency\task\competency_aggregation_all())->execute();
}

function mark_competencies_self_assignable($frameworks, $data) {
    foreach ($frameworks as $key => $competencies) {
        foreach ($competencies as $competency) {
            if (!is_array($competency)) {
                $competency = [$competency, 2];
            }

            $record = [
                'comp_id' => get_competency($key, $competency[0] ?? '', $data)->id ?? null,
                'availability' => $competency[1] ?? 2,
            ];

            builder::table('comp_assign_availability')->insert($record);
        }
    }
}

function create_info_block($data) {

    $html = '';

    if ($data['description']) {
        $html .= $data['description'];
    }

    $render_users = function($data, $tenant = null) {
        $html = '';

        // Let's build description.
        foreach ($data['users'] as $key => $user) {
            if (!is_null($tenant)) {
                if ($user->tenantid != $tenant->id) {
                    continue;
                }
            }
            $html .= '<div>
<p>
    <strong>' . $user->firstname . ' ' . $user->lastname . '</strong> - login: <storng>' . $user->username . '</storng>';

            if ($user->description) {
                $html .= '<br/>' . $user->description;
            }

            $html .= '</p>
</div>';
        }

        return $html;
    };

    if (!empty($data['tenants'])) {
        foreach ($data['tenants'] as $key => $tenant) {
            $html .= '<div><h3>'.$tenant->name.'</h3>';

            $html .= "<h4>Domain manager - login: t{$key}dm</h4>";
            $html .= "<h4>User manager - login: t{$key}um</h4>";

            $html .= $render_users($data, $tenant);
            $html .= '</div>';
        }
    } else {
        $html .= $render_users($data);
    }

    $object = [
        'blockname' => 'html',
        'parentcontextid' => context_system::instance()->id,
        'showinsubcontexts' => 0,
        'requiredbytheme' => 0,
        'pagetypepattern' => 'totara-dashboard-1',
        'defaultregion' => 'main',
        'defaultweight' => 1,
        'configdata' => base64_encode(serialize((object) [
            'text' => $html,
            'format' => 1,
        ])),
        'common_config' => json_encode([
            'title' => multilang('What to look for?'),
            'override_title' => true,
            'enable_hiding' => false,
            'show_header' => false,
            'show_border' => false
        ]),

        'timecreated' => time(),
        'timemodified' => time(),
    ];

    builder::table('block_instances')->insert($object);
}

/**
 * If multilang is enabled, create a multilang string out of the given string.
 *
 * @param string $string
 * @return string
 */
function multilang(string $string): string {
    global $options;
    if (!$options['multilang']) {
        return $string;
    }

    $multilang_string = '';
    foreach ($options['languages'] as $lang_code => $lang_name) {
        $multilang_string .= "<span lang=\"{$lang_code}\" class=\"multilang\">({$lang_name}) $string</span>";
    }
    return $multilang_string;
}

/**
 * Get the proper timestamp for a date formatted in YYYY-MM-DD.
 *
 * @param string $date
 * @return int timestamp
 */
function timestamp(string $date): int {
    return (new DateTime($date))->getTimestamp();
}

/**
 * Enable multilang header and content strings for the site.
 */
function enable_multilang() {
    global $options;

    // There is a limit to the number of languages we can have due to the length of name
    // fields in the DB, since we have to use crappy span tags for each language we want.
    $options['languages'] = [
        'en' => 'English', // Should probably keep this one!
        'fr' => 'Français',
    ];

    echo "\nEnabling multi-lang strings for the following languages: " .
        implode(', ', array_keys($options['languages'])) . ".\n";

    (new tool_langimport\controller())->install_languagepacks(array_keys($options['languages']));

    filter_set_global_state('multilang', TEXTFILTER_ON);
    filter_set_applies_to_strings('multilang', true);
}

/**
 * Enable multi tenancy
 */
function enable_multitenancy() {
    $multitenancy = \totara_tenant\testing\generator::instance();
    $multitenancy->enable_tenants();
}

/**
 * Create tenants (if multi tenancy is enabled)
 *
 * @return array|null
 */
function create_tenants(): ?array {
    global $options;
    if (!$options['multitenancy']) {
        return null;
    }

    $generator = generator();

    /** @var \totara_tenant\testing\generator $multitenancy */
    $multitenancy = $generator->get_plugin_generator('totara_tenant');

    $tenant1 = $multitenancy->create_tenant(['name' => 'Tenant 1']);

    $tenant1_cat_context = context_coursecat::instance($tenant1->categoryid);

    $tenant2 =  $multitenancy->create_tenant(['name' => 'Tenant 2']);

    $tenant2_cat_context = context_coursecat::instance($tenant2->categoryid);

    $tenants = [1 => $tenant1, 2 => $tenant2];

    // Now create the domain managers
    $domain_manager_role = builder::table('role')->where('shortname', 'tenantdomainmanager')->one(true);

    $dm1 = create_user([
        'firstname' => 'Tenant 1',
        'lastname' => 'Domain manager',
        'description' => multilang('Can manage the tenant 1 in all regards'),
        'tenant' => 1,
    ], 't1dm', $tenants);

    $generator->role_assign($domain_manager_role->id, $dm1->id, $tenant1_cat_context);

    $dm2 = create_user([
        'firstname' => 'Tenant 2',
        'lastname' => 'Domain manager',
        'description' => multilang('Can manage the tenant 2 in all regards'),
        'tenant' => 2,
    ], 't2dm', $tenants);

    $generator->role_assign($domain_manager_role->id, $dm2->id, $tenant2_cat_context);

    // Now create the user managers
    $user_manager_role = builder::table('role')->where('shortname', 'tenantusermanager')->one(true);

    $um1 = create_user([
        'firstname' => 'Tenant 1',
        'lastname' => 'User manager',
        'description' => multilang('Can manage the users of tenant 1'),
        'tenant' => 1,
    ], 't1um', $tenants);

    $generator->role_assign($user_manager_role->id, $um1->id, context_tenant::instance($tenant1->id));

    $um2 = create_user([
        'firstname' => 'Tenant 2',
        'lastname' => 'User manager',
        'description' => multilang('Can manage the users of tenant 2'),
        'tenant' => 2,
    ], 't2um', $tenants);

    $generator->role_assign($user_manager_role->id, $um2->id, context_tenant::instance($tenant2->id));

    return $tenants;
}

/**
 * Return an instance for Moodle Database
 *
 * @return moodle_database
 */
function db() {
    return $GLOBALS['DB'];
}