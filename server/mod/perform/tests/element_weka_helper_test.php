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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 */

use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\helpers\element_weka_helper;


/**
 * @group perform
 */
class element_weka_helper_testcase extends \core_phpunit\testcase {

    /**
     * @dataProvider add_weka_html_to_data_provider
     * @param array|null $in_element_data
     * @param array|null $expected_out_element_data
     * @param bool $encoded
     */
    public function test_add_weka_html_to_data(
        ?array $in_element_data,
        ?array $expected_out_element_data,
        bool $encoded = true
    ): void {
        $element = new element_entity();
        if ($encoded) {
            $in_element_data = json_encode($in_element_data, JSON_THROW_ON_ERROR);
        }

        $element->data = $in_element_data;
        $element_data = element_weka_helper::add_weka_html_to_data($element, 'wekaDoc', 'html');

        if ($encoded) {
            $element_data = json_decode($element_data, true, 512, JSON_THROW_ON_ERROR);
        }

        self::assertEquals($expected_out_element_data, $element_data);
    }

    public function add_weka_html_to_data_provider(): array {
        return [
            'JSON encoded null' => [null, null, false],
            'raw null' => [null, null, true],
            'Missing in weka' => [[], ['html' => null]],
            'Null in weka' => [
                ['wekaDoc' => null],
                ['wekaDoc' => null, 'html' => null]
            ],
            'Valid in weka' => [
                [
                    'wekaDoc' => $this->get_simple_weka_doc('hello world')
                ],
                [
                    'wekaDoc' => $this->get_simple_weka_doc('hello world'),
                    // It's important the formatter wraps the content in the tui-rendered div, or the styles will be wrong.
                    'html' => '<div class="tui-rendered"><p>hello world</p></div>'
                ],
            ],
        ];
    }

    /**
     * @dataProvider add_weka_html_to_data_iterable_provider
     * @param array|null $option
     * @param array|null $expected_out_option_data
     */
    public function test_add_weka_html_to_data_iterable(
        ?array $option,
        ?array $expected_out_option_data
    ): void {
        $element = new element_entity();

        $data = ['options' => [$option, $option]];

        $element->data = json_encode($data, JSON_THROW_ON_ERROR);
        $element_data = element_weka_helper::add_weka_html_to_data_iterable(
            $element,
            'options',
            'wekaDoc',
            'html'
        );


        $element_data = json_decode($element_data, true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(2, $element_data['options']);

        foreach ($element_data['options'] as $option) {
            self::assertEquals($expected_out_option_data, $option);
        }
    }

    public function add_weka_html_to_data_iterable_provider(): array {
        return [
            'null options' => [null, null],
            'Missing in weka' => [[], ['html' => null]],
            'Null in weka' => [
                ['wekaDoc' => null],
                ['wekaDoc' => null, 'html' => null]
            ],
            'Valid in weka' => [
                [
                    'wekaDoc' => $this->get_simple_weka_doc('hello world')
                ],
                [
                    'wekaDoc' => $this->get_simple_weka_doc('hello world'),
                    // It's important the formatter wraps the content in the tui-rendered div, or the styles will be wrong.
                    'html' => '<div class="tui-rendered"><p>hello world</p></div>'
                ],
            ],
        ];
    }

    /**
     * @param $expected_in_out
     * @dataProvider  add_weka_html_to_data_iterable_missing_options_array_provider
     */
    public function test_add_weka_html_to_data_iterable_missing_options_array($expected_in_out): void {
        $element = new element_entity();

        $element->data = $expected_in_out;
        $element_data = element_weka_helper::add_weka_html_to_data_iterable(
            $element,
            'options',
            'wekaDoc',
            'html'
        );

        self::assertEquals($expected_in_out, $element_data);
    }

    public function add_weka_html_to_data_iterable_missing_options_array_provider(): array {
        return [
            'Missing options field' => ['[]'],
            'Raw null data' => [null],
            'Encoded null data' => ['null'],
        ];
    }

    private function get_simple_weka_doc(string $content): array {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $content,
                        ],
                    ],
                ],
            ],
        ];
    }

}