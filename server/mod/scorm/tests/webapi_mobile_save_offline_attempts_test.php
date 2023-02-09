<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package mod_scorm
 */

use \totara_webapi\graphql;
use core\webapi\execution_context;

class mod_scorm_webapi_mobile_save_offline_attempts_testcase extends advanced_testcase {
    public function test_execute() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/scorm/locallib.php');

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $scorm = $this->getDataGenerator()->create_module('scorm', ['course' => $course->id, 'grademethod' => GRADEHIGHEST]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $identifier = 'sometestid';
        $sco = new stdClass();
        $sco->scorm = $scorm->id;
        $sco->manifest = $identifier;
        $sco->organization = '';
        $sco->parent = '/';
        $sco->identifier = $identifier;
        $sco->launch = '';
        $sco->scormtype = 'sco';
        $sco->title = 'Some SCO';
        $sco->sortorder = 1;
        $sco->id = $DB->insert_record('scorm_scoes', $sco);

        $this->setUser($user);

        $timestart1 = time() - 4000;
        $timestart2 = time() - 3000;
        $data = [
            'scormid' => $scorm->id,
            'attempts' => [
                [
                    'timestarted' => $timestart1,
                    'tracks' => [
                        ['identifier' => $identifier, 'element' => 'cmi.core.lesson_status', 'value' => 'failed', 'timemodified' => $timestart1 + 20],
                        ['identifier' => $identifier, 'element' => 'cmi.core.score.raw', 'value' => '25.0', 'timemodified' => $timestart1 + 20],
                    ]
                ],
                [
                    'timestarted' => $timestart2,
                    'tracks' => [
                        ['identifier' => $identifier, 'element' => 'cmi.core.lesson_status', 'value' => 'passed', 'timemodified' => $timestart2 + 30],
                        ['identifier' => $identifier, 'element' => 'cmi.core.score.raw', 'value' => '95.0', 'timemodified' => $timestart2 + 30],
                    ]
                ],
            ],
        ];

        $this->setCurrentTimeStart();
        $result = graphql::execute_operation(execution_context::create('mobile', 'mod_scorm_save_offline_attempts'), $data);
        $result = $result->toArray(true);
        $expected = [
            'data' => [
                'attempts' => [
                    'attempts_accepted' => [
                        0 => true,
                        1 => true
                    ],
                    'maxattempt' => 0,
                    'attempts_current' => 3,
                    'completion' => 'tracking_none',
                    'completionview' => false,
                    'completionstatusrequired' => null,
                    'completionscorerequired' => null,
                    'completionstatusallscos' => false,
                    'completionstatus' => 'incomplete',
                    'gradefinal' => 95.0,
                    'grademax' => 100.0,
                    'gradepercentage' => 95.0,
                    '__typename' => 'mod_scorm_save_offline_attempts_result'
                ]
            ]
        ];
        $this->assertSame($expected, $result);

        $sql = "SELECT sst.element, sst.*
                  FROM {scorm_scoes_track} sst
                 WHERE sst.scoid = :scoid
                   AND sst.attempt = :attempt
                   AND sst.userid = :userid
              ORDER BY sst.id asc";
        $records = $DB->get_records_sql($sql, ['scoid' => $sco->id, 'attempt' => 1, 'userid' => $user->id]);
        $this->assertCount(4, $records);
        $this->assertSame('1', $records['x.offline.attempt']->value);
        $this->assertTimeCurrent($records['x.offline.attempt']->timemodified);
        $this->assertSame((string)$timestart1, $records['x.start.time']->value);
        $this->assertEquals($timestart1, $records['x.start.time']->timemodified);
        $this->assertSame('failed', $records['cmi.core.lesson_status']->value);
        $this->assertEquals($timestart1 + 20, $records['cmi.core.lesson_status']->timemodified);
        $this->assertSame('25.0', $records['cmi.core.score.raw']->value);
        $this->assertEquals($timestart1 + 20, $records['cmi.core.lesson_status']->timemodified);

        $records = $DB->get_records_sql($sql, ['scoid' => $sco->id, 'attempt' => 2, 'userid' => $user->id]);
        $this->assertCount(4, $records);
        $this->assertSame('1', $records['x.offline.attempt']->value);
        $this->assertTimeCurrent($records['x.offline.attempt']->timemodified);
        $this->assertSame((string)$timestart2, $records['x.start.time']->value);
        $this->assertEquals($timestart2, $records['x.start.time']->timemodified);
        $this->assertSame('passed', $records['cmi.core.lesson_status']->value);
        $this->assertEquals($timestart2 + 30, $records['cmi.core.lesson_status']->timemodified);
        $this->assertSame('95.0', $records['cmi.core.score.raw']->value);
        $this->assertEquals($timestart2 + 30, $records['cmi.core.lesson_status']->timemodified);

        $this->assertEquals(0, $DB->count_records('scorm_scoes_track', ['scoid' => $sco->id, 'attempt' => 3]));
        $this->assertEquals(0, $DB->count_records('scorm_scoes_track', ['scoid' => $sco->id, 'attempt' => 4]));

        // Test first error stops inserting of attempts.

        $timestart3 = time() - 2000;
        $timestart4 = time() - 1000;
        $data = [
            'scormid' => $scorm->id,
            'attempts' => [
                [
                    'timestarted' => $timestart3,
                    'tracks' => [
                        ['identifier' => $identifier, 'element' => 'cmi.core.lesson_status', 'value' => 'completed', 'timemodified' => $timestart3 + 40],
                    ]
                ],
                [
                    'timestarted' => $timestart4,
                    'tracks' => [
                        ['identifier' => $identifier, 'element' => 'cmi.core.score.raw', 'value' => '95.0', 'timemodified' => $timestart4 + 50],
                    ]
                ],
                [
                    'timestarted' => $timestart4,
                    'tracks' => [
                        ['identifier' => $identifier, 'element' => 'cmi.core.lesson_status', 'value' => 'completed', 'timemodified' => $timestart4 + 40],
                        ['identifier' => $identifier, 'element' => 'cmi.core.score.raw', 'value' => '95.0', 'timemodified' => $timestart4 + 50],
                    ]
                ],
            ],
        ];

        $result = graphql::execute_operation(execution_context::create('mobile', 'mod_scorm_save_offline_attempts'), $data);
        $result = $result->toArray(true);
        $expected = [
            'data' => [
                'attempts' => [
                    'attempts_accepted' => [
                        0 => true,
                        1 => false,
                        2 => false
                    ],
                    'maxattempt' => 0,
                    'attempts_current' => 4,
                    'completion' => 'tracking_none',
                    'completionview' => false,
                    'completionstatusrequired' => null,
                    'completionscorerequired' => null,
                    'completionstatusallscos' => false,
                    'completionstatus' => 'incomplete',
                    'gradefinal' => 95.0,
                    'grademax' => 100.0,
                    'gradepercentage' => 95.0,
                    '__typename' => 'mod_scorm_save_offline_attempts_result'
                ]
            ]
        ];
        $this->assertSame($expected, $result);
        $this->assertEquals(4, $DB->count_records('scorm_scoes_track', ['scoid' => $sco->id, 'attempt' => 1]));
        $this->assertEquals(4, $DB->count_records('scorm_scoes_track', ['scoid' => $sco->id, 'attempt' => 2]));

        $records = $DB->get_records_sql($sql, ['scoid' => $sco->id, 'attempt' => 3, 'userid' => $user->id]);
        $this->assertCount(3, $records);
        $this->assertSame('1', $records['x.offline.attempt']->value);
        $this->assertTimeCurrent($records['x.offline.attempt']->timemodified);
        $this->assertSame((string)$timestart3, $records['x.start.time']->value);
        $this->assertEquals($timestart3, $records['x.start.time']->timemodified);
        $this->assertSame('completed', $records['cmi.core.lesson_status']->value);
        $this->assertEquals($timestart3 + 40, $records['cmi.core.lesson_status']->timemodified);

        $this->assertEquals(0, $DB->count_records('scorm_scoes_track', ['scoid' => $sco->id, 'attempt' => 4]));
        $this->assertEquals(0, $DB->count_records('scorm_scoes_track', ['scoid' => $sco->id, 'attempt' => 5]));

        // Test offline can be disabled.

        $DB->set_field('scorm', 'allowmobileoffline', 0, ['id' => $scorm->id]);

        $timestart4 = time() - 1000;
        $data = [
            'scormid' => $scorm->id,
            'attempts' => [
                [
                    'timestarted' => $timestart4,
                    'tracks' => [
                        ['identifier' => $identifier, 'element' => 'cmi.core.lesson_status', 'value' => 'completed', 'timemodified' => $timestart4 + 40],
                        ['identifier' => $identifier, 'element' => 'cmi.core.score.raw', 'value' => '95.0', 'timemodified' => $timestart4 + 50],
                    ]
                ],
            ],
        ];
        $result = graphql::execute_operation(execution_context::create('mobile', 'mod_scorm_save_offline_attempts'), $data);
        $result = $result->toArray(true);
        $expected = [
            'data' => [
                'attempts' => [
                    'attempts_accepted' => [
                        0 => false
                    ],
                    'maxattempt' => 0,
                    'attempts_current' => 4,
                    'completion' => 'tracking_none',
                    'completionview' => false,
                    'completionstatusrequired' => null,
                    'completionscorerequired' => null,
                    'completionstatusallscos' => false,
                    'completionstatus' => 'incomplete',
                    'gradefinal' => 95.0,
                    'grademax' => 100.0,
                    'gradepercentage' => 95.0,
                    '__typename' => 'mod_scorm_save_offline_attempts_result'
                ]
            ]
        ];
        $this->assertSame($expected, $result);

        // Test maxattempts are respected.

        $DB->set_field('scorm', 'allowmobileoffline', 1, ['id' => $scorm->id]);
        $DB->set_field('scorm', 'maxattempt', 4, ['id' => $scorm->id]);

        $timestart4 = time() - 1000;
        $timestart5 = time() - 500;
        $data = [
            'scormid' => $scorm->id,
            'attempts' => [
                [
                    'timestarted' => $timestart4,
                    'tracks' => [
                        ['identifier' => $identifier, 'element' => 'cmi.core.lesson_status', 'value' => 'completed', 'timemodified' => $timestart4 + 40],
                    ]
                ],
                [
                    'timestarted' => $timestart5,
                    'tracks' => [
                        ['identifier' => $identifier, 'element' => 'cmi.core.lesson_status', 'value' => 'completed', 'timemodified' => $timestart5 + 40],
                    ]
                ],
            ],
        ];

        $result = graphql::execute_operation(execution_context::create('mobile', 'mod_scorm_save_offline_attempts'), $data);
        $result = $result->toArray(true);
        $expected = [
            'data' => [
                'attempts' => [
                    'attempts_accepted' => [
                        0 => true,
                        1 => false
                    ],
                    'maxattempt' => 4,
                    'attempts_current' => 5,
                    'completion' => 'tracking_none',
                    'completionview' => false,
                    'completionstatusrequired' => null,
                    'completionscorerequired' => null,
                    'completionstatusallscos' => false,
                    'completionstatus' => 'incomplete',
                    'gradefinal' => 95.0,
                    'grademax' => 100.0,
                    'gradepercentage' => 95.0,
                    '__typename' => 'mod_scorm_save_offline_attempts_result'
                ]
            ]
        ];

        $this->assertSame($expected, $result);
        $this->assertEquals(3, $DB->count_records('scorm_scoes_track', ['scoid' => $sco->id, 'attempt' => 4]));
        $this->assertEquals(0, $DB->count_records('scorm_scoes_track', ['scoid' => $sco->id, 'attempt' => 5]));

        // Multiple SCOes.

        $DB->delete_records('scorm_scoes_track', []);

        $identifier2 = 'sometestid2';
        $sco2 = new stdClass();
        $sco2->scorm = $scorm->id;
        $sco2->manifest = $identifier2;
        $sco2->organization = '';
        $sco2->parent = '/';
        $sco2->identifier = $identifier2;
        $sco2->launch = '';
        $sco2->scormtype = 'sco';
        $sco2->title = 'other SCO';
        $sco2->sortorder = 2;
        $sco2->id = $DB->insert_record('scorm_scoes', $sco2);

        $timestart1 = time() - 4000;
        $timestart2 = time() - 3000;
        $data = [
            'scormid' => $scorm->id,
            'attempts' => [
                [
                    'timestarted' => $timestart1,
                    'tracks' => [
                        ['identifier' => $identifier, 'element' => 'cmi.core.lesson_status', 'value' => 'failed', 'timemodified' => $timestart1 + 20],
                        ['identifier' => $identifier, 'element' => 'cmi.core.score.raw', 'value' => '25.0', 'timemodified' => $timestart1 + 20],
                        ['identifier' => $identifier2, 'element' => 'cmi.core.lesson_status', 'value' => 'passed', 'timemodified' => $timestart1 + 25],
                        ['identifier' => $identifier2, 'element' => 'cmi.core.score.raw', 'value' => '66.0', 'timemodified' => $timestart1 + 25],
                    ]
                ],
                [
                    'timestarted' => $timestart2,
                    'tracks' => [
                        ['identifier' => $identifier2, 'element' => 'cmi.core.lesson_status', 'value' => 'passed', 'timemodified' => $timestart2 + 15],
                        ['identifier' => $identifier2, 'element' => 'cmi.core.score.raw', 'value' => '99.0', 'timemodified' => $timestart2 + 15],
                        ['identifier' => $identifier, 'element' => 'cmi.core.lesson_status', 'value' => 'passed', 'timemodified' => $timestart2 + 30],
                        ['identifier' => $identifier, 'element' => 'cmi.core.score.raw', 'value' => '95.0', 'timemodified' => $timestart2 + 30],
                    ]
                ],
            ],
        ];

        $this->setCurrentTimeStart();
        $result = graphql::execute_operation(execution_context::create('mobile', 'mod_scorm_save_offline_attempts'), $data);
        $result = $result->toArray(true);
        $expected = [
            'data' => [
                'attempts' => [
                    'attempts_accepted' => [
                        0 => true,
                        1 => true
                    ],
                    'maxattempt' => 4,
                    'attempts_current' => 3,
                    'completion' => 'tracking_none',
                    'completionview' => false,
                    'completionstatusrequired' => null,
                    'completionscorerequired' => null,
                    'completionstatusallscos' => false,
                    'completionstatus' => 'incomplete',
                    'gradefinal' => 99.0,
                    'grademax' => 100.0,
                    'gradepercentage' => 99.0,
                    '__typename' => 'mod_scorm_save_offline_attempts_result'
                ]
            ]
        ];
        $this->assertSame($expected, $result);

        $records = $DB->get_records_sql($sql, ['scoid' => $sco->id, 'attempt' => 1, 'userid' => $user->id]);
        $this->assertCount(4, $records);
        $this->assertSame('1', $records['x.offline.attempt']->value);
        $this->assertTimeCurrent($records['x.offline.attempt']->timemodified);
        $this->assertSame((string)$timestart1, $records['x.start.time']->value);
        $this->assertEquals($timestart1, $records['x.start.time']->timemodified);
        $this->assertSame('failed', $records['cmi.core.lesson_status']->value);
        $this->assertEquals($timestart1 + 20, $records['cmi.core.lesson_status']->timemodified);
        $this->assertSame('25.0', $records['cmi.core.score.raw']->value);
        $this->assertEquals($timestart1 + 20, $records['cmi.core.lesson_status']->timemodified);

        $records = $DB->get_records_sql($sql, ['scoid' => $sco2->id, 'attempt' => 1, 'userid' => $user->id]);
        $this->assertCount(4, $records);
        $this->assertSame('1', $records['x.offline.attempt']->value);
        $this->assertTimeCurrent($records['x.offline.attempt']->timemodified);
        $this->assertSame((string)$timestart1, $records['x.start.time']->value);
        $this->assertEquals($timestart1, $records['x.start.time']->timemodified);
        $this->assertSame('passed', $records['cmi.core.lesson_status']->value);
        $this->assertEquals($timestart1 + 25, $records['cmi.core.lesson_status']->timemodified);
        $this->assertSame('66.0', $records['cmi.core.score.raw']->value);
        $this->assertEquals($timestart1 + 25, $records['cmi.core.lesson_status']->timemodified);

        $records = $DB->get_records_sql($sql, ['scoid' => $sco->id, 'attempt' => 2, 'userid' => $user->id]);
        $this->assertCount(4, $records);
        $this->assertSame('1', $records['x.offline.attempt']->value);
        $this->assertTimeCurrent($records['x.offline.attempt']->timemodified);
        $this->assertSame((string)$timestart2, $records['x.start.time']->value);
        $this->assertEquals($timestart2, $records['x.start.time']->timemodified);
        $this->assertSame('passed', $records['cmi.core.lesson_status']->value);
        $this->assertEquals($timestart2 + 30, $records['cmi.core.lesson_status']->timemodified);
        $this->assertSame('95.0', $records['cmi.core.score.raw']->value);
        $this->assertEquals($timestart2 + 30, $records['cmi.core.lesson_status']->timemodified);

        $records = $DB->get_records_sql($sql, ['scoid' => $sco2->id, 'attempt' => 2, 'userid' => $user->id]);
        $this->assertCount(4, $records);
        $this->assertSame('1', $records['x.offline.attempt']->value);
        $this->assertTimeCurrent($records['x.offline.attempt']->timemodified);
        $this->assertSame((string)$timestart2, $records['x.start.time']->value);
        $this->assertEquals($timestart2, $records['x.start.time']->timemodified);
        $this->assertSame('passed', $records['cmi.core.lesson_status']->value);
        $this->assertEquals($timestart2 + 15, $records['cmi.core.lesson_status']->timemodified);
        $this->assertSame('99.0', $records['cmi.core.score.raw']->value);
        $this->assertEquals($timestart2 + 15, $records['cmi.core.lesson_status']->timemodified);
    }
}
