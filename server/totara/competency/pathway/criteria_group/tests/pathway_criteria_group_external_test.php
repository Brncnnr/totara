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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_criteria_group
 */


use pathway_criteria_group\entity\criteria_group;
use pathway_criteria_group\external;
use pathway_manual\models\roles\manager;
use totara_competency\entity\pathway;
use totara_competency\linked_courses;
use totara_criteria\criterion;
use totara_criteria\entity\criterion as criterion_entity;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * @group totara_competency
 */
class pathway_criteria_group_external_testcase extends \advanced_testcase {

    private function setup_data() {
        global $DB;

        $this->setAdminUser();

        $data = new class() {
            public $competencies = [];
            public $courses = [];
        };

        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $data->competencies['Comp A'] = $generator->create_competency('Comp A');
        $data->competencies['Comp B'] = $generator->create_competency('Comp B', null, ['parentid' => $data->competencies['Comp A']->id]);

        $prefix = 'Course ';
        for ($i = 1; $i <= 3; $i++) {
            $record = [
                'shortname' => $prefix . $i,
                'fullname' => $prefix . $i,
                'enablecompletion' => true,
            ];

            $data->courses[$i] = $this->getDataGenerator()->create_course($record);
        }

        return $data;
    }

    /**
     * Test create with linkedcourses, update with adding coursecompletion
     */
    public function test_create_linkedcoursed_update_add_coursecompletion() {
        $data = $this->setup_data();
        $comp_a = $data->competencies['Comp A'];
        $scalevalue = $comp_a->scale->default_value;

        linked_courses::set_linked_courses($comp_a->id, [
            ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => $data->courses[2]->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
        ]);

        $pw_id = external::create($comp_a->id,
            1,
            $scalevalue->id,
            [
                [
                    'aggregation' => [
                        'method' => criterion::AGGREGATE_ALL,
                    ],
                    'metadata' => [
                        [
                            'metakey' => "competency_id",
                            'metavalue' => $comp_a->id,
                        ],
                    ],
                    'type' => "linkedcourses",
                ],
            ],
            time()
        );

        /** @var pathway $pw */
        $pw = new pathway($pw_id);
        $this->assertEquals(1, $pw->valid);

        /** @var criteria_group $cg */
        $cg = criteria_group::repository()
            ->where('id', $pw->path_instance_id)
            ->with('criterions')
            ->one();

        $this->assertSame(1, $cg->criterions->count());

        $criterion = $cg->criterions->first();
        $this->assertEquals('linkedcourses', $criterion->criterion_type);

        /** @var criterion_entity $lc */
        $lc = new criterion_entity($criterion->criterion_id);
        $this->assertEquals(1, $lc->valid);

        // Now we add a second criterion to the same criteria_group. Should have no impact on the existing criterion

        external::update($pw_id,
            1,
            $scalevalue->id,
            [
                [
                    'id' => $lc->id,
                    'aggregation' => [
                        'method' => criterion::AGGREGATE_ALL,
                    ],
                    'metadata' => [
                        [
                            'metakey' => "competency_id",
                            'metavalue' => $comp_a->id,
                        ],
                    ],
                    'type' => "linkedcourses",
                ],
                [
                    'aggregation' => [
                        'reqitems' => 1,
                        'method' => criterion::AGGREGATE_ALL,
                    ],
                    'type' => "coursecompletion",
                    'itemids' => [
                        $data->courses[2]->id,
                        $data->courses[3]->id,
                    ]
                ],
            ],
            time()
        );

        // Re-fetching everything to be sure
        $pw = new pathway($pw_id);
        $this->assertEquals(1, $pw->valid);

        /** @var criteria_group $cg */
        $cg = criteria_group::repository()
            ->where('id', $pw->path_instance_id)
            ->with('criterions')
            ->one();

        $this->assertSame(2, $cg->criterions->count());

        $criterions = $cg->criterions->all();
        foreach ($criterions as $criterion) {
            $instance = new criterion_entity($criterion->criterion_id);
            $this->assertEquals(1, $instance->valid);
        }
    }

    /**
     * Test create with childcompetency, update with adding coursecompletion
     */
    public function test_create_childcompetency_update_add_coursecompletion() {
        $data = $this->setup_data();
        $comp_a = $data->competencies['Comp A'];
        $comp_b = $data->competencies['Comp B'];
        $scalevalue = $comp_a->scale->default_value;

        // Adding a manual pathway to CompB (the child) to ensure that users can achieve proficiency

        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $generator->create_manual($comp_b->id, [manager::class]);

        $pw_id = external::create($comp_a->id,
            1,
            $scalevalue->id,
            [
                [
                    'aggregation' => [
                        'method' => criterion::AGGREGATE_ALL,
                    ],
                    'metadata' => [
                        [
                            'metakey' => "competency_id",
                            'metavalue' => $comp_a->id,
                        ],
                    ],
                    'type' => "childcompetency",
                ],
            ],
            time()
        );

        /** @var pathway $pw */
        $pw = new pathway($pw_id);
        $this->assertEquals(1, $pw->valid);

        /** @var criteria_group $cg */
        $cg = criteria_group::repository()
            ->where('id', $pw->path_instance_id)
            ->with('criterions')
            ->one();

        $this->assertSame(1, $cg->criterions->count());

        $criterion = $cg->criterions->first();
        $this->assertEquals('childcompetency', $criterion->criterion_type);

        /** @var criterion_entity $cc */
        $cc = new criterion_entity($criterion->criterion_id);
        $this->assertEquals(1, $cc->valid);

        // Now we add a second criterion to the same criteria_group. Should have no impact on the existing criterion

        external::update($pw_id,
            1,
            $scalevalue->id,
            [
                [
                    'id' => $cc->id,
                    'aggregation' => [
                        'method' => criterion::AGGREGATE_ALL,
                    ],
                    'metadata' => [
                        [
                            'metakey' => "competency_id",
                            'metavalue' => $comp_a->id,
                        ],
                    ],
                    'type' => "childcompetency",
                ],
                [
                    'aggregation' => [
                        'reqitems' => 1,
                        'method' => criterion::AGGREGATE_ALL,
                    ],
                    'type' => "coursecompletion",
                    'itemids' => [
                        $data->courses[2]->id,
                        $data->courses[3]->id,
                    ]
                ],
            ],
            time()
        );

        // Re-fetching everything to be sure
        $pw = new pathway($pw_id);
        $this->assertEquals(1, $pw->valid);

        /** @var criteria_group $cg */
        $cg = criteria_group::repository()
            ->where('id', $pw->path_instance_id)
            ->with('criterions')
            ->one();

        $this->assertSame(2, $cg->criterions->count());

        $criterions = $cg->criterions->all();
        foreach ($criterions as $criterion) {
            $instance = new criterion_entity($criterion->criterion_id);
            $this->assertEquals(1, $instance->valid);
        }
    }


}
