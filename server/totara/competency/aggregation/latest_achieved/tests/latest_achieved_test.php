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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package aggregation_latest_achieved
*/

use aggregation_latest_achieved\latest_achieved;
use core\orm\collection;
use core_phpunit\testcase;
use pathway_test_pathway\test_pathway;
use totara_competency\entity\pathway_achievement;
use totara_competency\pathway;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\scale;

/**
 * @group totara_competency
 */
class aggregation_latest_achieved_aggregation_testcase extends testcase {

    public function test_aggregation_type() {
        $this->assertSame('latest_achieved', latest_achieved::aggregation_type());
    }

    public function do_aggregation_dataprovider(): array {
        $now = time();

        return [
            [
                'pathways' => [],
                'expected' => [
                    'achieved_value' => null,
                    'achieved_via' => [],
                ]
            ],
            [
                'pathways' => [
                    1 => [
                        [
                            'achieved_value' => null,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now,
                            'last_aggregated' => $now,
                        ]
                    ],
                ],
                'expected' => [
                    'achieved_value' => null,
                    'achieved_via' => [1],
                ],
            ],
            [
                'pathways' => [
                    1 => [
                        [
                            'achieved_value' => null,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now,
                            'last_aggregated' => $now,
                        ]
                    ],
                    2 => [
                        [
                            'achieved_value' => 2,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now + 1,
                            'last_aggregated' => $now + 1,
                        ]
                    ],
                    3 => [
                        [
                            'achieved_value' => 4,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now + 2,
                            'last_aggregated' => $now + 2,
                        ]
                    ],
                ],
                'expected' => [
                    'achieved_value' => 4,
                    'achieved_via' => [3],
                ],
            ],
            [
                'pathways' => [
                    1 => [
                        [
                            'achieved_value' => null,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now,
                            'last_aggregated' => $now,
                        ]
                    ],
                    2 => [
                        [
                            'achieved_value' => 2,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now,
                            'last_aggregated' => $now,
                        ]
                    ],
                    3 => [
                        [
                            'achieved_value' => 4,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now,
                            'last_aggregated' => $now,
                        ]
                    ],
                ],
                'expected' => [
                    'achieved_value' => 2,
                    'achieved_via' => [2],
                ],
            ],
            [
                'pathways' => [
                    1 => [
                        [
                            'achieved_value' => 2,
                            'status' => pathway_achievement::STATUS_ARCHIVED,
                            'date_achieved' => $now,
                            'last_aggregated' => $now,
                        ],
                        [
                            'achieved_value' => null,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now + 5,
                            'last_aggregated' => $now + 5,
                        ]
                    ],
                    2 => [
                        [
                            'achieved_value' => 2,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now + 1,
                            'last_aggregated' => $now + 1,
                        ]
                    ],
                    3 => [
                        [
                            'achieved_value' => 4,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now + 2,
                            'last_aggregated' => $now + 2,
                        ]
                    ],
                ],
                'expected' => [
                    'achieved_value' => null,
                    'achieved_via' => [1],
                ],
            ],
            [
                'pathways' => [
                    1 => [
                        [
                            'achieved_value' => 2,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now + 1,
                            'last_aggregated' => $now + 1,
                        ],
                    ],
                    2 => [
                        [
                            'achieved_value' => 1,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now,
                            'last_aggregated' => $now,
                        ]
                    ],
                    3 => [
                        [
                            'achieved_value' => 2,
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'date_achieved' => $now + 1,
                            'last_aggregated' => $now + 1,
                        ]
                    ],
                ],
                'expected' => [
                    'achieved_value' => 2,
                    'achieved_via' => [1, 3],
                ],
            ]
        ];
    }

    /**
     * @dataProvider do_aggregation_dataprovider
     * @param array $pathways
     * @param array $expected
     */
    public function test_do_aggregation(array $pathways, array $expected) {
        $user = $this->getDataGenerator()->create_user();

        $created_pathways = [];
        $active_achievements = [];

        if (!empty($pathways)) {
            /** @var \totara_competency\testing\generator $competency_generator */
            $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

            /** @var scale $scale */
            $scale = $competency_generator->create_scale(
                'comp',
                'Test scale',
                [
                    1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                    2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                    3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                    4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                    5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                ]
            );

            /** @var collection $scale_values */
            $scale_values = $scale->sorted_values_high_to_low->key_by('sortorder');

            /** @var competency_framework $framework */
            $framework = $competency_generator->create_framework($scale, 'Test framework');
            /** @var competency $competency */
            $competency = $competency_generator->create_competency('Test competency', $framework);

            /** @var test_pathway[] $pathways */
            foreach ($pathways as $key => $achievements) {
                $created_pathways[$key] = $competency_generator->create_test_pathway($competency);
                $active_achievements[$key] = $this->create_achievements($created_pathways[$key], $user->id, $scale_values, $achievements);
            }
        }

        $aggregation = new latest_achieved();
        $aggregation->set_pathways($created_pathways)
            ->aggregate_for_user($user->id);

        if (!is_null($expected['achieved_value'] ?? null)) {
            $expected_scale_value_id = $scale_values->item($expected['achieved_value'])->id;
        } else {
            $expected_scale_value_id = null;
        }
        $this->assertSame($expected_scale_value_id, $aggregation->get_achieved_value_id($user->id));
        $expected_achievement_ids = [];
        foreach ($expected['achieved_via'] as $pathway_idx) {
            $this->assertTrue(isset($active_achievements[$pathway_idx]), "Pathway {$pathway_idx} doesn't have any active achievements");
            $expected_achievement_ids[] = $active_achievements[$pathway_idx];
        }

        $achieved_via = $aggregation->get_achieved_via($user->id);
        $this->assertContainsOnlyInstancesOf(pathway_achievement::class, $achieved_via);
        $achieved_via_ids = collection::new($achieved_via)->pluck('id');
        $this->assertEqualsCanonicalizing($expected_achievement_ids, $achieved_via_ids);
    }

    /**
     * @param pathway $pathway
     * @param int $user_id
     * @param collection $scale_values
     * @param int|null Id of the active achievement
     */
    private function create_achievements(pathway $pathway, int $user_id, collection $scale_values, array $achievements): ?int {
        $now = time();

        $active_achievement = null;
        foreach ($achievements as $achievement) {
            if (!is_null($achievement['achieved_value'] ?? null)) {
                $scale_value_id = $scale_values->item($achievement['achieved_value'])->id;
            } else {
                $scale_value_id = null;
            }

            $to_create = new pathway_achievement();
            $to_create->pathway_id = $pathway->get_id();
            $to_create->user_id = $user_id;
            $to_create->scale_value_id = $scale_value_id;
            $to_create->status = $achievement['status'] ?? pathway_achievement::STATUS_CURRENT;
            $to_create->date_achieved = $achievement['date_achieved'] ?? $now;
            $to_create->last_aggregated = $achievement['last_aggregated'] ?? null;

            $to_create->save();

            if ($to_create->status == pathway_achievement::STATUS_CURRENT) {
                $active_achievement = $to_create->id;
            }
        }

        return $active_achievement;
    }
}
