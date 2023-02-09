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
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\data_provider\learning_objects;
use contentmarketplace_linkedin\entity\learning_object as learning_object_entity;
use contentmarketplace_linkedin\formatter\timespan_field_formatter;
use contentmarketplace_linkedin\model\learning_object;
use contentmarketplace_linkedin\testing\generator;
use contentmarketplace_linkedin\webapi\resolver\query\catalog_import_learning_objects;
use contentmarketplace_linkedin\webapi\resolver\type\catalog_import_learning_objects_result;
use contentmarketplace_linkedin\webapi\resolver\type\classification;
use core\date_format;
use core\format;
use core\orm\collection;
use contentmarketplace_linkedin\entity\learning_object_classification;
use totara_contentmarketplace\testing\generator as market_generator;
use core_phpunit\testcase;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_contentmarketplace\testing\helper;
use totara_contentmarketplace\testing\mock\create_course_interactor;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @covers \contentmarketplace_linkedin\webapi\resolver\query\catalog_import_learning_objects
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_resolver_query_catalog_import_learning_objects_testcase extends testcase {

    use webapi_phpunit_helper;

    private const QUERY = 'contentmarketplace_linkedin_catalog_import_learning_objects';

    private const TYPE = 'contentmarketplace_linkedin_learning_object';

    /**
     * @var learning_object[]
     */
    protected $data;

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
        $plugin = contentmarketplace::plugin('linkedin');
        $plugin->enable();
    }

    private function create_data_from_fixture(): void {
        $result = generator::instance()->get_mock_result_from_fixtures('response_1');
        learning_object::create_bulk_from_result($result);
        $this->data = learning_object_entity::repository()
            ->order_by('title')
            ->order_by('id')
            ->get()
            ->map_to(learning_object::class)
            ->all();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->data = null;
    }

    public function test_pagination(): void {
        $this->create_data_from_fixture();

        // Get first page
        $first_result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options([
            'page' => 1,
            'limit' => 1,
        ]));
        $this->assertCount(1, $first_result['items']);
        $this->assertEquals(2, $first_result['total']);
        $this->assertNotEmpty($first_result['next_cursor']);
        $this->assertEquals($this->data[1]->id, $first_result['items']->first()->id);

        // Get next result set
        $second_result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options([
            'cursor' => $first_result['next_cursor'],
        ]));
        $this->assertCount(1, $second_result['items']);
        $this->assertEquals(2, $second_result['total']);
        $this->assertEmpty($second_result['next_cursor']);
        $this->assertEquals($this->data[0]->id, $second_result['items']->first()->id);
    }

    public function test_sort_by(): void {
        $this->create_data_from_fixture();

        // sort by latest
        $result_latest = $this->resolve_graphql_query(
            self::QUERY,
            $this->get_query_options(null, [], learning_objects::SORT_BY_LATEST)
        );
        $this->assertEquals($this->data[0]->id, $result_latest['items']->last()->id);
        $this->assertEquals($this->data[1]->id, $result_latest['items']->first()->id);

        // sort by alphabetical
        $result_alpha = $this->resolve_graphql_query(
            self::QUERY,
            $this->get_query_options(null, [], learning_objects::SORT_BY_ALPHABETICAL)
        );
        $this->assertEquals($this->data[0]->id, $result_alpha['items']->first()->id);
        $this->assertEquals($this->data[1]->id, $result_alpha['items']->last()->id);
    }

    public function test_language_filter(): void {
        $this->create_data_from_fixture();

        // language filter: english
        $result_en = $this->resolve_graphql_query(
            self::QUERY,
            $this->get_query_options(null, ['language' => 'en'], learning_objects::SORT_BY_ALPHABETICAL)
        );
        $this->assertEquals($this->data[0]->id, $result_en['items']->first()->id);
        $this->assertEquals($this->data[1]->id, $result_en['items']->last()->id);

        // language filter: french
        $result_fr = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, ['language' => 'fr']));
        $this->assertEmpty($result_fr['items']);
    }

    public function test_search_filter(): void {
        $learning_object_1 = generator::instance()->create_learning_object('1', [
            'title' => 'Flash For Beginners',
            'short_description' => 'Flash not Photoshop',
            'description' => 'A great course!',
            "last_updated_at" => time() + HOURSECS
        ]);
        $learning_object_2 = generator::instance()->create_learning_object('2', [
            'title' => 'Flash For Experts',
            'short_description' => 'adobe flash is an out of date technology',
            'description' => 'why would anyone use it now days?',
            "last_updated_at" => time() + DAYSECS
        ]);
        $learning_object_3 = generator::instance()->create_learning_object('3', [
            'title' => 'Photoshop Pro 2021',
            'short_description' => 'Become a master',
            'description' => 'A course for experts',
        ]);

        $result_search_for_adobe_flash = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'search' => 'Adobe Flash',
        ]));
        $this->assertEquals(1, $result_search_for_adobe_flash['total']);
        $this->assertEquals($learning_object_2->id, $result_search_for_adobe_flash['items']->first()->id);

        $result_search_for_photoshop = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'search' => '     photoshop     ',
        ]));

        $this->assertEquals(2, $result_search_for_photoshop['total']);
        $this->assertEquals($learning_object_1->id, $result_search_for_photoshop['items']->first()->id);
        $this->assertEquals($learning_object_3->id, $result_search_for_photoshop['items']->last()->id);

        $result_search_for_course = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'search' => 'COURSE',
        ]));
        $this->assertEquals(2, $result_search_for_course['total']);
        $this->assertEquals($learning_object_1->id, $result_search_for_course['items']->first()->id);
        $this->assertEquals($learning_object_3->id, $result_search_for_course['items']->last()->id);

        $result_search_no_results = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'search' => '   UNKNOWN  ',
        ]));
        $this->assertEquals(0, $result_search_no_results['total']);

        $result_search_whitespace = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'search' => '     ',
        ]));
        $this->assertEquals(3, $result_search_whitespace['total']);
    }

    public function test_time_to_complete_filter(): void {
        $learning_object_1_min = generator::instance()->create_learning_object('1', [
            'time_to_complete' => MINSECS,
            'title' => '1',
        ]);
        $learning_object_30_mins = generator::instance()->create_learning_object('2', [
            'time_to_complete' => MINSECS * 30,
            'title' => '2',
        ]);
        $learning_object_1_hour = generator::instance()->create_learning_object('3', [
            'time_to_complete' => HOURSECS,
            'title' => '3',
        ]);
        $learning_object_3_hours = generator::instance()->create_learning_object('4', [
            'time_to_complete' => HOURSECS * 3,
            'title' => '4',
        ]);

        $result_under_10_mins = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'time_to_complete' => [
                json_encode([
                    'max' => MINSECS * 10,
                ]),
            ],
        ], learning_objects::SORT_BY_ALPHABETICAL));
        $this->assertEquals(1, $result_under_10_mins['total']);
        $this->assertEquals($learning_object_1_min->id, $result_under_10_mins['items']->first()->id);

        $result_1_to_2_hours = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'time_to_complete' => [
                json_encode([
                    'min' => HOURSECS,
                    'max' => HOURSECS * 2,
                ]),
            ],
        ], learning_objects::SORT_BY_ALPHABETICAL));
        $this->assertEquals(1, $result_1_to_2_hours['total']);
        $this->assertEquals($learning_object_1_hour->id, $result_1_to_2_hours['items']->first()->id);

        $result_10_to_30_mins_and_over_3_hours = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'time_to_complete' => [
                json_encode([
                    'min' => MINSECS * 10,
                    'max' => MINSECS * 30,
                ]),
                json_encode([
                    'min' => HOURSECS * 3,
                ]),
            ],
        ], learning_objects::SORT_BY_ALPHABETICAL));
        $this->assertEquals(2, $result_10_to_30_mins_and_over_3_hours['total']);
        $this->assertEquals($learning_object_30_mins->id, $result_10_to_30_mins_and_over_3_hours['items']->first()->id);
        $this->assertEquals($learning_object_3_hours->id, $result_10_to_30_mins_and_over_3_hours['items']->last()->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('A min or a max value must be specified for the time_to_complete filter.');
        $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'time_to_complete' => [
                json_encode([
                    'min' => 0,
                    'max' => null,
                ]),
            ],
        ]));
    }

    public function test_asset_type_filter(): void {
        $learning_object_course = generator::instance()->create_learning_object('1', [
            'asset_type' => constants::ASSET_TYPE_COURSE,
            'title' => '1',
        ]);
        $learning_object_video = generator::instance()->create_learning_object('2', [
            'asset_type' => constants::ASSET_TYPE_VIDEO,
            'title' => '2',
        ]);
        $learning_object_chapter = generator::instance()->create_learning_object('3', [
            'asset_type' => constants::ASSET_TYPE_CHAPTER,
            'title' => '3',
        ]);

        $result_courses = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'asset_type' => [constants::ASSET_TYPE_COURSE],
        ], learning_objects::SORT_BY_ALPHABETICAL));
        $this->assertEquals(1, $result_courses['total']);
        $this->assertEquals($learning_object_course->id, $result_courses['items']->first()->id);

        $result_videos_or_chapters = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'asset_type' => [constants::ASSET_TYPE_VIDEO, constants::ASSET_TYPE_CHAPTER],
        ], learning_objects::SORT_BY_ALPHABETICAL));
        $this->assertEquals(2, $result_videos_or_chapters['total']);
        $this->assertEquals($learning_object_video->id, $result_videos_or_chapters['items']->first()->id);
        $this->assertEquals($learning_object_chapter->id, $result_videos_or_chapters['items']->last()->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid asset type: NOT_A_TYPE');
        $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null,  [
            'asset_type' => ['NOT_A_TYPE'],
        ]));
    }

    public function test_in_catalog_filter(): void {
        $generator = market_generator::instance();

        $learning_object_in_catalog_1 = $generator->create_learning_object('contentmarketplace_linkedin');
        $in_catalog[] = $learning_object_in_catalog_1->id;
        $learning_object_not_in_catalog_1 = $generator->create_learning_object('contentmarketplace_linkedin');
        $not_in_catalog[] = $learning_object_not_in_catalog_1->id;
        $learning_object_in_catalog_2 = $generator->create_learning_object('contentmarketplace_linkedin');
        $in_catalog[] = $learning_object_in_catalog_2->id;
        $learning_object_not_in_catalog_2 = $generator->create_learning_object('contentmarketplace_linkedin');
        $not_in_catalog[] = $learning_object_not_in_catalog_2->id;
        $learning_object_in_catalog_3 = $generator->create_learning_object('contentmarketplace_linkedin');
        $in_catalog[] = $learning_object_in_catalog_3->id;

        // Create courses from learning objects.
        $category = helper::get_default_course_category_id();
        $interactor = new create_course_interactor();

        $course_builder_1 = new course_builder($learning_object_in_catalog_1, $category, $interactor);
        $course_builder_1->create_course();
        $course_builder_1->create_course(); // Create second course from the same marketplace object.

        $course_builder_2 = new course_builder($learning_object_in_catalog_2, $category, $interactor);
        $course_builder_2->create_course();

        $course_builder_3 = new course_builder($learning_object_in_catalog_3, $category, $interactor);
        $course_builder_3->create_course();

        // In catalogue.
        $result_courses_in_catalog = $this->resolve_graphql_query(
            self::QUERY,
            $this->get_query_options(null, ['in_catalog' => ['yes']], learning_objects::SORT_BY_ALPHABETICAL)
        );
        self::assertEquals(3, $result_courses_in_catalog['total']);
        $in_catalog_ids = array_map(function($o) { return $o->id; }, $result_courses_in_catalog['items']->all());
        self::assertEqualsCanonicalizing($in_catalog, $in_catalog_ids);

        // Not in catalogue.
        $result_courses_not_in_catalog = $this->resolve_graphql_query(
            self::QUERY,
            $this->get_query_options(null, ['in_catalog' => ['no']], learning_objects::SORT_BY_ALPHABETICAL)
        );
        self::assertEquals(2, $result_courses_not_in_catalog['total']);
        $in_catalog_ids = array_map(function($o) { return $o->id; }, $result_courses_not_in_catalog['items']->all());
        self::assertEqualsCanonicalizing($not_in_catalog, $in_catalog_ids);

        // Both filters applied: show entire catalogue.
        $result_courses_both = $this->resolve_graphql_query(
            self::QUERY,
            $this->get_query_options(null, ['in_catalog' => ['yes', 'no']], learning_objects::SORT_BY_ALPHABETICAL)
        );
        self::assertEquals(5, $result_courses_both['total']);
        $both_ids = array_map(function($o) { return $o->id; }, $result_courses_both['items']->all());
        self::assertEqualsCanonicalizing(array_merge($in_catalog, $not_in_catalog), $both_ids);
    }

    public function test_ids_filter(): void {
        $learning_object_1 = generator::instance()->create_learning_object('1');
        $learning_object_2 = generator::instance()->create_learning_object('2', ["last_updated_at" => time() + DAYSECS]);
        $learning_object_3 = generator::instance()->create_learning_object('3', ["last_updated_at" => time() + HOURSECS]);

        $result_1 = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'ids' => [$learning_object_1->id],
        ]));
        $this->assertEquals(1, $result_1['total']);
        $this->assertEquals($learning_object_1->id, $result_1['items']->first()->id);

        $result_2_and_3 = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'ids' => [$learning_object_2->id, $learning_object_3->id],
        ]));
        $this->assertEquals(2, $result_2_and_3['total']);
        $this->assertEquals($learning_object_2->id, $result_2_and_3['items']->first()->id);
        $this->assertEquals($learning_object_3->id, $result_2_and_3['items']->last()->id);

        $result_empty = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, [
            'ids' => [],
        ]));
        $this->assertEquals(3, $result_empty['total']);
    }

    public function selected_filters_provider(): array {
        return [
            'No filters specified' => [
                [
                    'subjects' => [],
                    'asset_type' => [],
                    'time_to_complete' => [],
                    'in_catalog' => [],
                ],
                [],
            ],
            'Time to complete' => [
                [
                    'subjects' => [],
                    'asset_type' => [],
                    'time_to_complete' => ['{"min":600,"max":1800}', '{"min":7200,"max":10800}'],
                    'in_catalog' => [],
                ],
                [
                    get_string('catalog_filter_timespan_10_to_30_minutes', 'contentmarketplace_linkedin'),
                    get_string('catalog_filter_timespan_2_to_3_hours', 'contentmarketplace_linkedin'),
                ],
            ],
            'Asset types' => [
                [
                    'subjects' => [],
                    'asset_type' => [constants::ASSET_TYPE_COURSE, constants::ASSET_TYPE_VIDEO],
                    'time_to_complete' => [],
                    'in_catalog' => []
                ],
                [
                    get_string('asset_type_course_plural', 'contentmarketplace_linkedin'),
                    get_string('asset_type_video_plural', 'contentmarketplace_linkedin'),
                ],
            ],
            'Added to your catalogue' => [
                [
                    'subjects' => [],
                    'asset_type' => [],
                    'time_to_complete' => [],
                    'in_catalog' => ['yes', 'no'],
                ],
                [
                    get_string('catalog_filter_in_catalog', 'contentmarketplace_linkedin'),
                    get_string('catalog_filter_not_in_catalog', 'contentmarketplace_linkedin'),
                ],
            ],
            'Subjects' => [
                [
                    'subjects' => [],
                    'asset_type' => [],
                    'time_to_complete' => [],
                    'in_catalog' => [],
                ],
                [],
            ],
            'Multiple types' => [
                [
                    'subjects' => [],
                    'asset_type' => [constants::ASSET_TYPE_VIDEO],
                    'time_to_complete' => ['{"min":7200,"max":10800}'],
                    'in_catalog' => [],
                ],
                [
                    get_string('asset_type_video_plural', 'contentmarketplace_linkedin'),
                    get_string('catalog_filter_timespan_2_to_3_hours', 'contentmarketplace_linkedin'),
                ],
            ],
        ];
    }

    /**
     * @dataProvider selected_filters_provider
     */
    public function test_selected_filters_list(array $filters_input, array $expected_strings): void {
        $query_result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, $filters_input));
        $result = $this->resolve_graphql_type(
            $this->get_graphql_name(catalog_import_learning_objects_result::class),
            'selected_filters',
            $query_result
        );
        $this->assertEquals($expected_strings, $result);
    }

    public function test_type_resolver(): void {
        $this->create_data_from_fixture();

        $results = $this->resolve_graphql_query(self::QUERY, $this->get_query_options());
        $item = $results['items']->first();

        $expected_item_field_data = [
            'name' => ['Visio 2007 Essential Training', format::FORMAT_PLAIN],
            'short_description' => [
                'Explores how Visio 2007 can be used to create business and planning documents such as flow charts and floor layouts.',
                format::FORMAT_PLAIN,
            ],
            'last_updated_at' => ['17 February 2021', date_format::FORMAT_DATE],
            'published_at' => ['27 March 2007', date_format::FORMAT_DATE],
            'level' => ['BEGINNER'],
            'display_level' => ['Beginner'],
            'time_to_complete' => ['8h 55m', timespan_field_formatter::FORMAT_HUMAN],
            'asset_type' => ['COURSE'],
            'image_url' => ['https://cdn.lynda.com/course/260/260-636456652549313738-16x9.jpg'],
        ];
        self::assertEmpty($item->get_courses());
        foreach ($expected_item_field_data as $field => $data) {
            $this->assertEquals(
                $data[0],
                $this->resolve_graphql_type(self::TYPE, $field, $item, isset($data[1]) ? ['format' => $data[1]] : [])
            );
        }
    }

    public function test_plugin_disabled(): void {
        $plugin = contentmarketplace::plugin('linkedin');

        $plugin->enable();
        $this->resolve_graphql_query(self::QUERY, $this->get_query_options());

        $plugin->disable();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('LinkedIn Learning content marketplace disabled');
        $this->resolve_graphql_query(self::QUERY, $this->get_query_options());
    }

    public function test_no_permission(): void {
        $role_id = helper::get_authenticated_user_role();
        $context_id = helper::get_default_course_category_context()->id;
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        assign_capability('totara/contentmarketplace:add', CAP_ALLOW, $role_id, $context_id);
        $this->resolve_graphql_query(self::QUERY, $this->get_query_options());

        unassign_capability('totara/contentmarketplace:add', $role_id, $context_id);
        $this->expectException(required_capability_exception::class);
        $this->resolve_graphql_query(self::QUERY, $this->get_query_options());
    }

    private function get_query_options($pagination = null, $filters = [], $sort_by = 'LATEST'): array {
        return [
            'input' => [
                'pagination' => $pagination ?? [
                    'page' => null,
                    'limit' => 10,
                ],
                'filters' => array_merge([
                    'language' => 'en',
                    'subjects' => [],
                    'asset_type' => [],
                    'time_to_complete' => [],
                    'in_catalog' => [],
                    'ids' => [],
                ], $filters),
                'sort_by' => $sort_by,
            ],
        ];
    }

    /**
     * @return void
     */
    public function test_filter_learning_objects_with_subject(): void {
        $generator = generator::instance();
        $classification_type = $this->get_graphql_name(classification::class);
        $learning_object_entity = $generator->create_learning_object(
            'urn:li:lyndaCourse:252',
            ['asset_type' => constants::ASSET_TYPE_COURSE]
        );

        $classification_one = $generator->create_classification('1', ['name' => '<script>Subject One</script>']);
        $classification_two = $generator->create_classification('2');

        $map = new learning_object_classification();
        $map->learning_object_id = $learning_object_entity->id;
        $map->classification_id = $classification_one->id;
        $map->save();

        self::setAdminUser();
        $result_one = $this->resolve_graphql_query(
            $this->get_graphql_name(catalog_import_learning_objects::class),
            [
                'input' => [
                    'filters' => [
                        'language' => 'en',
                        'subjects' => [$classification_one->id],
                        'asset_type' => [],
                        'time_to_complete' => []
                    ],
                    'pagination' => [],
                    'sort_by' => learning_objects::SORT_BY_ALPHABETICAL
                ]
            ]
        );

        self::assertIsArray($result_one);
        self::assertArrayHasKey('items', $result_one);
        self::assertNotEmpty($result_one['items']);
        self::assertCount(1, $result_one['items']);

        /** @var collection $collection */
        $collection = $result_one['items'];
        self::assertInstanceOf(collection::class, $collection);
        self::assertEquals(1, $collection->count());

        /** @var learning_object $first_item */
        $first_item = $collection->first();
        self::assertInstanceOf(learning_object::class, $first_item);
        self::assertEquals($learning_object_entity->id, $first_item->id);

        /** @var \contentmarketplace_linkedin\model\classification $subject */
        $subject = $first_item->subjects->first();
        self::assertEquals($classification_one->id, $this->resolve_graphql_type(
            $classification_type,
            'id',
            $subject
        ));
        self::assertEquals($classification_one->type, $this->resolve_graphql_type(
            $classification_type,
            'type',
            $subject
        ));
        self::assertNotEquals($classification_one->name, $this->resolve_graphql_type(
            $classification_type,
            'name',
            $subject
        ));
        self::assertEquals('Subject One', $this->resolve_graphql_type(
            $classification_type,
            'name',
            $subject
        ));

        $result_two = $this->resolve_graphql_query(
            $this->get_graphql_name(catalog_import_learning_objects::class),
            [
                'input' => [
                    'filters' => [
                        'language' => 'en',
                        'subjects' => [$classification_two->id],
                        'asset_type' => [],
                        'time_to_complete' => []
                    ],
                    'pagination' => [],
                    'sort_by' => learning_objects::SORT_BY_ALPHABETICAL
                ]
            ]
        );

        self::assertIsArray($result_two);
        self::assertArrayHasKey('items', $result_two);
        self::assertCount(0, $result_two['items']);

        /** @var collection $result_two_collection */
        $result_two_collection = $result_two['items'];
        self::assertInstanceOf(collection::class, $result_two_collection);
        self::assertEquals(0, $result_two_collection->count());
    }

    public function test_learning_objects_for_courses_field(): void {
        $generator = market_generator::instance();

        // Create one course from learning object
        $learning_object = $generator->create_learning_object('contentmarketplace_linkedin');
        $course_builder = new course_builder($learning_object, helper::get_default_course_category_id(), new create_course_interactor());
        $result = $course_builder->create_course();
        $results = $this->resolve_graphql_query(self::QUERY, $this->get_query_options());
        $item = $results['items']->first();
        /** @var collection $courses */
        $courses = $item->get_courses();
        $courses = $courses->to_array();

        self::assertNotEmpty($courses);
        self::assertIsArray($courses);
        self::assertCount(1, $courses);
        $course = reset($courses);

        self::assertIsArray($course);
        self::assertEquals($result->get_course_id(), $course['id']);
    }

    /**
     * @return void
     */
    public function test_fetch_selected_learning_objects_ignore_all_other_filters(): void {
        $generator = generator::instance();

        $learning_object_one = $generator->create_learning_object("urn:lyndaCourse:252", ["locale_language" => "en"]);
        $learning_object_two = $generator->create_learning_object("urn:lyndaCourse:496", ["locale_language" => "ja"]);

        $result_one = $this->resolve_graphql_query(
            $this->get_graphql_name(catalog_import_learning_objects::class),
            [
                "input" => [
                    "filters" => [
                        "language" => "en",
                        "subjects" => []
                    ],
                    "sort_by" => "LATEST",
                    "pagination" => [
                        "page" => null,
                        "limit" => null
                    ]
                ]
            ]
        );

        self::assertIsArray($result_one);
        self::assertArrayHasKey("items", $result_one);

        /** @var collection $result_one_items */
        $result_one_items = $result_one["items"];
        self::assertInstanceOf(collection::class, $result_one_items);

        self::assertEquals(1, $result_one_items->count());

        /** @var learning_object $single_item */
        $single_item = $result_one_items->first();
        self::assertInstanceOf(learning_object::class, $single_item);

        self::assertNotEquals($learning_object_two->id, $single_item->id);
        self::assertEquals($learning_object_one->id, $single_item->id);

        $result_two = $this->resolve_graphql_query(
            $this->get_graphql_name(catalog_import_learning_objects::class),
            [
                "input" => [
                    "filters" => [
                        "language" => "en",
                        "ids" => [$learning_object_one->id, $learning_object_two->id],
                        "subjects" => []
                    ],
                    "sort_by" => "LATEST",
                    "pagination" => [
                        "page" => null,
                        "limit" => null
                    ]
                ]
            ]
        );

        self::assertIsArray($result_two);
        self::assertArrayHasKey("items", $result_two);

        /** @var collection $result_two_items */
        $result_two_items = $result_two["items"];
        self::assertInstanceOf(collection::class, $result_two_items);
        self::assertNotEquals(1, $result_two_items->count());
        self::assertEquals(2, $result_two_items->count());
    }
}
