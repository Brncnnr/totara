<?php
/**
 * This file is part of Totara TXP
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Aaron Machin <aaron.machin@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\formatter\default_formatter;
use core\json_editor\node\attachment;
use core\json_editor\node\blockquote;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use core_phpunit\testcase;

class core_json_editor_blockquote_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            blockquote::validate_schema([
                'type' => blockquote::get_type(),
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        'content' => [],
                    ],
                ],
            ])
        );

        $this->assertTrue(
            blockquote::validate_schema([
                'type' => blockquote::get_type(),
                'content' => [],
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_block_node(): void {
        $this->assertFalse(
            blockquote::validate_schema([
                'type' => blockquote::get_type(),
                'content' => [
                    [
                        'type' => attachment::get_type(),
                        'attrs' => [
                            'filename' => 'somefile.mp3',
                            'url' => 'http://example.com'
                        ]
                    ]
                ]
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_invalid_content_node(): void {
        $this->assertFalse(
            blockquote::validate_schema([
                'type' => blockquote::get_type(),
                'content' => ['12', '2', '3']
            ])
        );

        $this->assertFalse(
            blockquote::validate_schema([
                'type' => blockquote::get_type(),
                'content' => [
                    [
                        'type' => text::get_type(),
                        'text' => 'dewq',
                        'deew' => 'de'
                    ]
                ]
            ])
        );

        $this->assertDebuggingCalled();
    }

    /**
     * @return void
     */
    public function test_clean_raw_node(): void {
        $data = [
            'type' => blockquote::get_type(),
            'content' => null
        ];

        $cleaned = blockquote::clean_raw_node($data);
        $this->assertNotSame($data, $cleaned);

        $this->assertArrayHasKey('type', $cleaned);
        $this->assertArrayHasKey('content', $cleaned);
        $this->assertNotNull($cleaned['content']);
    }

    /**
     * @return void
     */
    public function test_clean_raw_node_with_valid_data(): void {
        $data = [
            'type' => blockquote::get_type(),
            'content' => [
                'ddd' => [
                    'type' => blockquote::get_type(),
                    'text' => 'gelllo world'
                ]
            ]
        ];

        $cleaned = blockquote::clean_raw_node($data);
        $this->assertNotSame($data, $cleaned);

        $this->assertArrayHasKey('type', $cleaned);
        $this->assertArrayHasKey('content', $cleaned);
        $this->assertArrayNotHasKey('ddd', $cleaned['content']);

        $this->assertNotEmpty($cleaned['content']);
        $text = reset($cleaned['content']);

        $this->assertArrayHasKey('type', $text);
        $this->assertArrayHasKey('text', $text);
        $this->assertSame('gelllo world', $text['text']);
    }

    /**
     * @return void
     */
    public function test_to_html(): void {
        $formatter = new default_formatter();

        $data = [
            'type' => blockquote::get_type(),
            'content' => [
                [
                    'type' => paragraph::get_type(),
                    'content' => [],
                ],
            ],
        ];

        $node = blockquote::from_node($data);

        $this->assertEquals('<blockquote><p><br /></p></blockquote>', $node->to_html($formatter));

        //Testing nested blockquote
        $data = [
            'type' => blockquote::get_type(),
            'content' => [
                [
                    'type' => paragraph::get_type(),
                    'content' => [
                        [
                            'type' => text::get_type(),
                            'text' => 'Crazy!',
                        ]
                    ],
                ],
                [
                    'type' => blockquote::get_type(),
                    'content' => [
                        [
                            'type' => paragraph::get_type(),
                            'content' => [
                                [
                                    'type' => text::get_type(),
                                    'text' => 'Hello',
                                ]
                            ],
                        ],
                    ],
                ]
            ],
        ];

        $node = blockquote::from_node($data);

        $this->assertEquals('<blockquote><p>Crazy!</p><blockquote><p>Hello</p></blockquote></blockquote>', $node->to_html($formatter));
    }

    /**
     * @return void
     */
    public function test_to_text(): void {
        $formatter = new default_formatter();

        $data = [
            'type' => blockquote::get_type(),
            'content' => [
                [
                    'type' => paragraph::get_type(),
                    'content' => [
                        [
                            'type' => text::get_type(),
                            'text' => 'Crazy!',
                        ]
                    ],
                ],
            ],
        ];

        $node = blockquote::from_node($data);

        $this->assertEquals('> Crazy!' . "\n\n", $node->to_text($formatter));

        //Testing nested blockquote
        $data = [
            'type' => blockquote::get_type(),
            'content' => [
                [
                    'type' => paragraph::get_type(),
                    'content' => [
                        [
                            'type' => text::get_type(),
                            'text' => 'Crazy!',
                        ]
                    ],
                ],
                [
                    'type' => blockquote::get_type(),
                    'content' => [
                        [
                            'type' => paragraph::get_type(),
                            'content' => [
                                [
                                    'type' => text::get_type(),
                                    'text' => 'Hello',
                                ]
                            ],
                        ],
                    ],
                ]
            ],
        ];

        $node = blockquote::from_node($data);

        $this->assertEquals('> Crazy!' . "\n\n" . '> > Hello' . "\n\n", $node->to_text($formatter));
    }
}