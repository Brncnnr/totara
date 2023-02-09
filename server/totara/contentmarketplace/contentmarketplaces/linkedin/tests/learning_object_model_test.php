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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\entity\learning_object as learning_object_entity;
use contentmarketplace_linkedin\model\learning_object;
use contentmarketplace_linkedin\testing\generator;
use core\collection;
use core_phpunit\testcase;

/**
 * @covers learning_object
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_learning_object_model_testcase extends testcase {

    protected function setUp(): void {
        parent::setUp();
        generator::instance()->set_up_configuration();
    }

    /**
     * @return void
     */
    public function test_create_from_element(): void {
        $result = generator::instance()->get_mock_result_from_fixtures('response_1');
        $element = $result->get_elements()[1];

        $this->assertEquals(0, learning_object_entity::repository()->count());

        $created_model = learning_object::create_from_element($element);
        $this->assertEquals(1, learning_object_entity::repository()->count());
        $this->assertTrue(learning_object_entity::repository()->where('urn', $element->get_urn())->exists());

        $loaded_model = learning_object::load_by_urn($element->get_urn());
        $this->assertEquals($loaded_model->urn, $element->get_urn());
        $this->assertEquals($created_model->id, $loaded_model->id);

        // Make sure values correspond with what is in response_1.json
        $expected_values = [
            'urn' => 'urn:li:lyndaCourse:260',
            'title' => 'Visio 2007 Essential Training',
            'description' => 'David Rivers explores the many ways Visio 2007 can be used to create effective business and planning documents, from flow charts to floor layout diagrams. In Visio 2007 Essential Training , users new to Visio or updating to this version will learn how to incorporate diagrams with integrated data, create and utilize text fields, edit templates and projects with AutoConnect, and create and use themes for consistency. Exercise files accompany the training.',
            'description_include_html' => 'David Rivers explores the many ways Visio 2007 can be used to create effective business and planning documents, from flow charts to floor layout diagrams. In <em>Visio 2007 Essential Training </em>, users new to Visio or updating to this version will learn how to incorporate diagrams with integrated data, create and utilize text fields, edit templates and projects with AutoConnect, and create and use themes for consistency. Exercise files accompany the training.',
            'short_description' => 'Explores how Visio 2007 can be used to create business and planning documents such as flow charts and floor layouts.',
            'last_updated_at' => 1613522086,
            'published_at' => 1174953600,
            'level' => 'BEGINNER',
            'image_url' => 'https://cdn.lynda.com/course/260/260-636456652549313738-16x9.jpg',
            'time_to_complete' => 32153,
            'web_launch_url' => 'https://www.linkedin.com/learning/visio-2007-essential-training',
            'availability' => 'AVAILABLE'
        ];

        foreach ($expected_values as $attribute => $expected_value) {
            $this->assertEquals($expected_value, $loaded_model->$attribute);
        }
    }

    /**
     * @return void
     */
    public function test_create_bulk_from_result(): void {
        $result = generator::instance()->get_mock_result_from_fixtures('response_1');
        $this->assertEquals(0, learning_object_entity::repository()->count());

        learning_object::create_bulk_from_result($result);

        /** @var collection|learning_object[] $models */
        $models = learning_object_entity::repository()
            ->order_by('urn')
            ->get()
            ->map_to(learning_object::class)
            ->all(false);
        $this->assertCount(2, $models);

        // Make sure values correspond with what is in response_1.json
        $expected_values = [
            [
                'urn' => 'urn:li:lyndaCourse:252',
                'title' => 'Excel 2007 Essential Training',
                'description' => 'Like the other applications in Microsoft Office 2007, Excel 2007 boasts upgraded features and a brand-new look. In Excel 2007 Essential Training , instructor Lorna A. Daly introduces the new version in detail. The training begins with the essentials of using the program, including how and why to use a spreadsheet, how to set up and modify worksheets, and how to import and export data. Lorna then moves on to teach more advanced features, such as working with functions and macros. Exercise files accompany the tutorials.',
                'description_include_html' => 'Like the other applications in Microsoft Office 2007, Excel 2007 boasts upgraded features and a brand-new look. In <em> Excel 2007 Essential Training </em>, instructor Lorna A. Daly introduces the new version in detail. The training begins with the essentials of using the program, including how and why to use a spreadsheet, how to set up and modify worksheets, and how to import and export data. Lorna then moves on to teach more advanced features, such as working with functions and macros. Exercise files accompany the tutorials.',
                'short_description' => 'A detailed look at the features and uses of Excel 2007, including how and why to use spreadsheets.',
                'last_updated_at' => 1613522076,
                'published_at' => 1170201600,
                'level' => 'BEGINNER',
                'image_url' => 'https://cdn.lynda.com/course/252/252-636282989834935258-16x9.jpg',
                'time_to_complete' => 18790,
                'availability' => 'AVAILABLE'
            ], [
                'urn' => 'urn:li:lyndaCourse:260',
                'title' => 'Visio 2007 Essential Training',
                'description' => 'David Rivers explores the many ways Visio 2007 can be used to create effective business and planning documents, from flow charts to floor layout diagrams. In Visio 2007 Essential Training , users new to Visio or updating to this version will learn how to incorporate diagrams with integrated data, create and utilize text fields, edit templates and projects with AutoConnect, and create and use themes for consistency. Exercise files accompany the training.',
                'description_include_html' => 'David Rivers explores the many ways Visio 2007 can be used to create effective business and planning documents, from flow charts to floor layout diagrams. In <em>Visio 2007 Essential Training </em>, users new to Visio or updating to this version will learn how to incorporate diagrams with integrated data, create and utilize text fields, edit templates and projects with AutoConnect, and create and use themes for consistency. Exercise files accompany the training.',
                'short_description' => 'Explores how Visio 2007 can be used to create business and planning documents such as flow charts and floor layouts.',
                'last_updated_at' => 1613522086,
                'published_at' => 1174953600,
                'level' => 'BEGINNER',
                'image_url' => 'https://cdn.lynda.com/course/260/260-636456652549313738-16x9.jpg',
                'time_to_complete' => 32153,
                'availability' => 'AVAILABLE'
            ],
        ];
        foreach ($expected_values as $index => $values) {
            foreach ($values as $attribute => $expected_value) {
                $this->assertEquals($expected_value, $models[$index]->$attribute);
            }
        }
    }

}
