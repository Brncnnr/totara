<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\node\text;
use totara_tui\json_editor\formatter\formatter;

class core_json_editor_text_testcase extends advanced_testcase {

    /**
     * @return void
     */
    public function test_to_text_with_marks(): void {
        $formatter = new formatter();

        // Bold
        $node = [
            'type' => text::get_type(),
            'text' => 'This text is bold!',
            'marks' => [
                [
                    'type' => 'strong'
                ]
            ]
        ];
        $text = text::from_node($node);
        $this->assertEquals('**' . $node['text'] . '**', $text->to_text($formatter));

        // Italics
        $node = [
            'type' => text::get_type(),
            'text' => 'This text is italic!',
            'marks' => [
                [
                    'type' => 'em'
                ]
            ]
        ];
        $text = text::from_node($node);
        $this->assertEquals('_' . $node['text'] . '_', $text->to_text($formatter));

        // Underline
        $node = [
            'type' => text::get_type(),
            'text' => 'This text is underlined!',
            'marks' => [
                [
                    'type' => 'underline'
                ]
            ]
        ];
        $text = text::from_node($node);
        $this->assertEquals('__' . $node['text'] . '__', $text->to_text($formatter));
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            text::validate_schema([
                'type' => text::get_type(),
                'text' => 'something else'
            ])
        );

        $this->assertTrue(
            text::validate_schema([
                'type' => text::get_type(),
                'text' => 'something else',
                'marks' => [
                    [
                        'type' => 'strong'
                    ]
                ]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_invalid_marks(): void {
        $this->assertFalse(
            text::validate_schema([
                'type' => text::get_type(),
                'text' => 'woho',
                'marks' => 'ddews',
            ])
        );

        $this->assertFalse(
            text::validate_schema([
                'type' => text::get_type(),
                'text' => 'ddd',
                'marks' => [1, 2, 3, 4]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_extra_keys(): void {
        $this->assertFalse(
            text::validate_schema([
                'type' => text::get_type(),
                'text' => 'whooh',
                'ddeiijiokwo' => 'ddqw',
            ])
        );

        $this->assertFalse(
            text::validate_schema([
                'type' => text::get_type(),
                'text' => 'dddeww',
                'marks' => [
                    [
                        'type' => 'strong',
                        'dame' => ''
                    ]
                ]
            ])
        );

        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @return void
     */
    public function test_clean_raw_node(): void {
        $data = [
            'type' => text::get_type(),
            'text' => 'Something special',
            'marks' => [
                ['type' => 'strong']
            ]
        ];

        $cleaned = text::clean_raw_node($data);
        $this->assertSame($data, $cleaned);
    }


    /**
     * @dataProvider test_clean_raw_node_link_marks_provider
     * @param string $href
     * @param bool $expect_allowed
     * @throws coding_exception
     */
    public function test_clean_raw_node_with_link_mark(string $href, bool $expect_allowed): void {
        $data = [
            'type' => text::get_type(),
            'text' => 'Something special',
            'marks' => [
                [
                    'type' => 'link',
                    'attrs' => [
                        'href' => $href,
                    ],
                ],
            ],
        ];

        $cleaned = text::clean_raw_node($data);

        if ($expect_allowed) {
            $this->assertSame($data, $cleaned);
        } else {
            $this->assertEquals('', $cleaned['marks'][0]['attrs']['href']);
        }
    }

    public function test_clean_raw_node_link_marks_provider(): array
    {
        return [
            'http' => ['http://example.com', true],
            'https' => ['https://example.com', true],
            'partial' =>['example.com', true],
            'mailto' => ['mailto:jaron.steenson@totaralearning.com', true],
            'mailto with slashes' => ['mailto://jaron.steenson@totaralearning.com', true], // Not actually a valid mailto url.
            'mailto with query strings' => ['mailto:jaron.steenson@totaralearning.com?subject=Hello', true],
            'mailto with no address' => ['mailto:?to=&subject=mailto%20with%20examples&body=https%3A%2F%2Fen.wikipedia.org%2Fwiki%2FMailto', true],
            'mailto multiple address' => ['mailto:someone@example.com,someoneelse@example.com', true],
            'javascript' => ['javascript:alert(1)', false],
            'javascript with slashes' => ['javascript://alert(1)', false], // Won't actually run js in most browsers.
        ];
    }

    /**
     * @return void
     */
    public function test_clean_raw_node_with_invalid_marks_structure(): void {
        $data = [
            'type' => text::get_type(),
            'text' => 'DUDUDU',
            'marks' => [
                'type' => 'strong'
            ]
        ];

        $cleaned = text::clean_raw_node($data);
        $this->assertNotSame($data, $cleaned);

        $this->assertArrayHasKey('marks', $cleaned);
        $this->assertEmpty($cleaned['marks']);

        $this->assertDebuggingCalled();
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_link(): void {
        $this->assertTrue(
            text::validate_schema([
                'type' => text::get_type(),
                'text' => 'This is the text',
                'marks' => [
                    [
                        'type' => 'link',
                        'attrs' => [
                            'href' => 'http://example.com'
                        ]
                    ]
                ]
            ])
        );

        $this->assertTrue(
            text::validate_schema([
                'type' => text::get_type(),
                'text' => 'This is the text',
                'marks' => [
                    [
                        'type' => 'link',
                        'attrs' => [
                            'href' => 'http://example.com',
                            'opensInNewWindow' => true,
                        ]
                    ]
                ]
            ])
        );

        $this->assertFalse(
            text::validate_schema([
                'type' => text::get_type(),
                'text' => 'This is the text',
                'marks' => [
                    [
                        'type' => 'link',
                        'attrs' => 'dde'
                    ]
                ]
            ])
        );
    }

}
