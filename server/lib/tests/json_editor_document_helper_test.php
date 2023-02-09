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

use core\json_editor\helper\document_helper;
use core\json_editor\node\attachment;
use core\json_editor\node\attachments;
use core\json_editor\node\audio;
use core\json_editor\node\bullet_list;
use core\json_editor\node\image;
use core\json_editor\node\mention;
use core\json_editor\node\ordered_list;
use core\json_editor\node\paragraph;
use core\json_editor\node\ruler;
use core\json_editor\node\text;
use core\json_editor\node\video;

/**
 * @coversDefaultClass core\json_editor\helper\document_helper
 */
class core_json_editor_document_helper_testcase extends advanced_testcase {
    /**
     * @covers ::is_valid_document
     */
    public function test_validate_document(): void {
        $this->assertFalse(
            document_helper::is_valid_document(['type' => 'something else', 'xxx' => 'd'])
        );

        $this->assertFalse(
            document_helper::is_valid_document(['type' => 'doc', 'content' => null])
        );

        $this->assertTrue(
            document_helper::is_valid_document(['type' => 'doc', 'content' => []])
        );

        $proper_document = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => paragraph::get_type(),
                    'content' => [
                        [
                            'type' => text::get_type(),
                            'text' => 'this is value ',
                            'marks' => [
                                ['type' => 'strong']
                            ]
                        ],
                        [
                            'type' => mention::get_type(),
                            'attrs' => [
                                'display' => "<script>alert('hello world');</script>",
                                'id' => '15'
                            ]
                        ]
                    ]
                ],
                [
                    'type' => attachments::get_type(),
                    'content' => [
                        [
                            'type' => attachment::get_type(),
                            'attrs' => [
                                'filename' => 'some_file.png',
                                'url' => 'http://example.com',
                                'size' => 1920
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertTrue(document_helper::is_valid_document($proper_document));
        $this->assertTrue(document_helper::is_valid_json_document(json_encode($proper_document)));

        $document = '{somecontent}some content{somecontent}';
        $this->assertFalse(document_helper::is_valid_json_document($document));
        $this->assertFalse(document_helper::is_valid_json_document(json_encode($document)));
    }

    /**
     * @covers ::clean_json_document
     * @covers ::clean_json
     * @covers ::do_clean_raw_nodes
     */
    public function test_clean_raw_node(): void {
        $proper_document = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => paragraph::get_type(),
                    'content' => [
                        [
                            'type' => text::get_type(),
                            'text' => 'this is value ',
                            'marks' => [
                                ['type' => 'strong']
                            ]
                        ],
                        [
                            'type' => mention::get_type(),
                            'attrs' => [
                                'display' => "<script>alert('hello world');</script>",
                                'id' => '15'
                            ]
                        ]
                    ]
                ],
                [
                    'type' => attachments::get_type(),
                    'content' => [
                        [
                            'type' => attachment::get_type(),
                            'attrs' => [
                                'filename' => 'some_file.png',
                                'url' => 'http://example.com',
                                'size' => 1920
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $json_document = json_encode($proper_document);
        $cleaned = document_helper::clean_json_document($json_document);
        $this->assertNotSame($json_document, $cleaned);

        $cleaned_document = document_helper::parse_document($cleaned);

        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertIsArray($cleaned_document);
        $this->assertArrayHasKey('content', $cleaned_document);

        // Paragraph
        $paragraph_node = reset($cleaned_document['content']);
        $this->assertArrayHasKey('type', $paragraph_node);
        $this->assertEquals(paragraph::get_type(), $paragraph_node['type']);

        $this->assertArrayHasKey('content', $paragraph_node);
        $this->assertNotEmpty($paragraph_node['content']);

        // Text node
        $text_node = reset($paragraph_node['content']);
        $this->assertArrayHasKey('type', $text_node);
        $this->assertArrayHasKey('text', $text_node);

        $this->assertEquals(text::get_type(), $text_node['type']);
        $this->assertEquals("this is value ", $text_node['text']);

        // Mention node
        $mention_node = end($paragraph_node['content']);
        $this->assertArrayHasKey('type', $mention_node);
        $this->assertArrayHasKey('attrs', $mention_node);
        $this->assertIsArray($mention_node['attrs']);
        $this->assertNotEmpty($mention_node['attrs']);

        $this->assertEquals(mention::get_type(), $mention_node['type']);
        $this->assertArrayHasKey('display', $mention_node['attrs']);
        $this->assertArrayHasKey('id', $mention_node['attrs']);

        $this->assertSame(15, $mention_node['attrs']['id']);
        // this is text, not html, so it should not be cleaned
        $this->assertSame("<script>alert('hello world');</script>", $mention_node['attrs']['display']);
    }

    /**
     * @covers ::parse_document
     */
    public function test_parse_document(): void {
        $this->assertEmpty(document_helper::parse_document(null));
        $this->assertEmpty(document_helper::parse_document(false));
        $this->assertSame(
            ['type' => 'doc'],
            document_helper::parse_document(['type' => 'doc'])
        );

        $this->assertSame(
            [
                'type' => 'doc',
                'content' => null
            ],
            document_helper::parse_document(json_encode(['type' => 'doc', 'content' => null]))
        );
    }

    /**
     * @covers ::parse_document
     */
    public function test_parse_invalid_document(): void {
        $document = '{somecontent}some content{somecontent}';
        // Suppress debugging messages
        $this->assertEmpty(document_helper::parse_document($document, false));

        $document = '{somecontent}some content{somecontent}';
        $this->assertEmpty(document_helper::parse_document($document, true));
        $this->assertDebuggingCalled('There was an error on parsing json content: Syntax error');

        $document = '{somecontent}some content{somecontent}';
        $this->assertEmpty(document_helper::parse_document($document));
        $this->assertDebuggingCalled('There was an error on parsing json content: Syntax error');

        $document = 'John says "Hello!"';
        $this->assertEmpty(document_helper::parse_document($document));
        $this->assertDebuggingCalled('There was an error on parsing json content: Syntax error');

        $document = '{"name": "Joe", , "age": null}';
        $this->assertEmpty(document_helper::parse_document($document));
        $this->assertDebuggingCalled('There was an error on parsing json content: Syntax error');
    }

    /**
     * @covers ::is_valid_document
     */
    public function test_validate_document_content_nodes(): void {
        global $CFG, $USER;
        $this->setAdminUser();

        $this->assertFalse(
            document_helper::is_valid_document([
                'type' => 'doc',
                'content' => [
                    mention::create_raw_node($USER->id),
                    text::create_json_node_from_text('This is some text')
                ]
            ])
        );

        $this->assertTrue(
            document_helper::is_valid_document([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text('This is some text')
                ]
            ])
        );

        // Create stored files so that we can have more nodes to test
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();
        $context_id = \context_user::instance($USER->id)->id;

        $image_record = new stdClass();
        $image_record->itemid = file_get_unused_draft_itemid();
        $image_record->contextid = \context_user::instance($USER->id)->id;
        $image_record->filename = uniqid() . ".png";
        $image_record->filepath = '/';
        $image_record->component = 'user';
        $image_record->filearea = 'draft';

        $video_record = new stdClass();
        $video_record->itemid = file_get_unused_draft_itemid();
        $video_record->contextid = $context_id;
        $video_record->filename = uniqid() . ".mp4";
        $video_record->filepath = '/';
        $video_record->component = 'user';
        $video_record->filearea = 'draft';

        $audio_record = new stdClass();
        $audio_record->itemid = file_get_unused_draft_itemid();
        $audio_record->contextid = $context_id;
        $audio_record->filename = uniqid() . ".mp3";
        $audio_record->filepath = '/';
        $audio_record->component = 'user';
        $audio_record->filearea = 'draft';


        $image_file = $fs->create_file_from_string($image_record, "Wop owp ;d-;[wd pfel");
        $video_file = $fs->create_file_from_string($video_record, "dokkok oblplpef ldpelp");
        $audio_file = $fs->create_file_from_string($audio_record, "kokoko ;[de;[e ;w");

        $this->assertTrue(
            document_helper::is_valid_document([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text('woop wopow oiokvoce oe'),
                    image::create_raw_node_from_image($image_file),
                    attachments::create_raw_node_from_list([$image_file, $audio_file, $video_file]),
                    video::create_raw_node($video_file),
                    audio::create_raw_node($audio_file),
                    bullet_list::create_raw_node_from_texts(['wow', 'do', 'ddu', 'hit', 'dajiwe', 'kokoe']),
                    ruler::create_raw_node(),
                    ordered_list::create_raw_node_from_texts(['dota', '2', 'me', 'pudge', 'that'])
                ]
            ])
        );
    }

    /**
     * @covers ::sanitize_json
     * @covers core\json_editor\helper\node_helper::sanitize_raw_nodes
     */
    public function test_sanitize_document(): void {
        $html = /** @lang text */'<img src="x" onerror="alert(\'This file failed\')"/>';
        $result = document_helper::sanitize_json([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text($html)
            ],
        ]);

        $this->assertDebuggingCalled([
            'The method \core\json_editor\helper\document_helper::sanitize_json() is deprecated, there is no replacement.',
            'The method \core\json_editor\helper\node_helper::sanitize_raw_nodes() is deprecated, there is no replacement.',
            'The method \core\json_editor\node\node::sanitize_raw_node() is deprecated, there is no replacement.',
            'The method \core\json_editor\helper\node_helper::sanitize_raw_nodes() is deprecated, there is no replacement.',
            'The method \core\json_editor\node\node::sanitize_raw_node() is deprecated, there is no replacement.'
        ]);

        $this->assertIsArray($result);

        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('content', $result);

        $this->assertSame('doc', $result['type']);
        $this->assertCount(1, $result['content']);

        $paragraph = reset($result['content']);
        $this->assertArrayHasKey('type', $paragraph);
        $this->assertArrayHasKey('content', $paragraph);

        $this->assertEquals(paragraph::get_type(), $paragraph['type']);
        $this->assertIsArray($paragraph['content']);
        $this->assertCount(1, $paragraph['content']);

        $text = reset($paragraph['content']);
        $this->assertArrayHasKey('type', $text);
        $this->assertArrayHasKey('text', $text);

        $this->assertEquals(text::get_type(), $text['type']);
        $this->assertSame(
            $html,
            $text['text']
        );
    }

    /**
     * @covers ::is_valid_json_document
     * @covers ::is_valid_document_header
     */
    public function test_is_valid_json_document(): void {
        $tests = [
            null => false,
            '' => false,
            'no' => false,
            '{"doc": true}' => false,
            '{"type":"doc"}' => false,
            '{type:"doc",content:[]}' => false,
            '{"type":"doc","content":""}' => false,
            '{"type":"doc","content":[]}' => true,
            '{"type":"doc","content":[{"cat": "dog"}]}' => false,
            '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"."}]}]}' => true,
            '{"type": "doc", "content": [{"type": "paragraph", "content": [{"type": "text", "text": "."}]}]} ' => true,
            '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"."}]}]}}' => false,
            ' {"type":"doc","content":[]} ' => true,
            '{"type":"doc","content":{}}' => true,
            '{"content":[],"type":"doc"}' => true,
        ];

        foreach ($tests as $test => $expected) {
            $this->assertEquals($expected, document_helper::is_valid_json_document($test), $test);
            // Invalid JSON triggers a debugging message in document_helper::parse_document() that we don't care about here.
            $this->resetDebugging();
        }
    }

    /**
     * @covers ::json_encode_document
     */
    public function test_json_encode_document(): void {
        $tests = [
            // slashes are preserved
            '{"9":"3/4"}' => [9 => '3/4'],
            // fractions are preserved
            '{"f":2.0}' => ['f' => 2.00],
            // multi-byte characters are preserved
            "[\"\u{263A}\u{1F60D}\"]" => ["\u{263A}\u{1F60D}"],
        ];
        foreach ($tests as $expected => $input) {
            $this->assertEquals($expected, document_helper::json_encode_document($input));
        }
    }

    /**
     * @covers ::looks_like_json
     */
    public function test_looks_like_json(): void {
        $tests = [
            '<div><p>HTML is your<br>friend!</p></div>' => false,
            'I wonder <a href="#doc">what I am</a>?' => false,
            'Too many cooks spoils the broth.' => false,
            '{}' => true,
            ' {"space": "is hard"} ' => true,
            '{"content":[],"type":"doc"}' => true,
            '' => false,
            false => false,
        ];
        foreach ($tests as $test => $expected) {
            $this->assertEquals($expected, document_helper::looks_like_json($test), $test);
        }
        // Test null (should be same as '')
        $this->assertEquals(false, document_helper::looks_like_json(null), $test);

        // Test moar
        require_once(__DIR__ . '/fixtures/json_editor/sample_documents.php');
        $tests = [
            '{}' => false,
            ' {"space": "is hard"} ' => false,
            '{"type":"doc"}' => true,
            '{"content":{}}' => true,
            '{"content":[],"type":"doc"}' => true,
        ];
        $tests[json_encode(core_json_editor_sample_documents::sample(false), JSON_PRETTY_PRINT)] = true;
        foreach ($tests as $test => $expected) {
            $this->assertEquals($expected, document_helper::looks_like_json($test, true), $test);
        }
    }

    /**
     * @covers ::is_document_empty
     */
    public function test_is_document_empty() {
        require_once(__DIR__ . '/fixtures/json_editor/sample_documents.php');

        $tests = [
            '' => 'String is not a json content string',
            '<strong>kia kaha</strong>' => 'String is not a json content string',
            'null' => 'String is not a json content string',
            'false' => 'String is not a json content string',
            'true' => 'String is not a json content string',
            '42' => 'String is not a json content string',
            '42.195' => 'String is not a json content string',
            '"oioi"' => 'String is not a json content string',
            '{}' => 'String is not a json content string',
            '{"kia":"ora"}' => 'String is not a json content string',
            '{"type":"Doc"}' => 'Invalid document schema',
            '{"content":[]}' => 'Invalid document schema',
        ];
        foreach ($tests as $test => $expected) {
            try {
                document_helper::is_document_empty($test);
                $this->fail('coding_exception expected: ' . $test);
            } catch (coding_exception $ex) {
                $this->assertStringContainsString($expected, $ex->getMessage(), $test);
            }
        }

        $tests = [
            '{"type":"doc"}' => true,
            '{"type":"doc","content":[]}' => true,
            '{"type":"doc","content":[{"type":"paragraph"}]}' => true,
            '{"type":"doc","content":[{"type":"paragraph","content":[]}]}' => true,
            '{"type":"doc","content":[{},{}]}' => false,
        ];
        $tests[core_json_editor_sample_documents::minimal(true)] = false;
        $tests[core_json_editor_sample_documents::sample(true)] = false;
        foreach ($tests as $test => $expected) {
            $this->assertEquals($expected, document_helper::is_document_empty($test));
        }
    }

    /**
     * @return void
     */
    public function test_create_json_string_document_from_text_string(): void {
        self::assertEquals(
            json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Hello world',
                                'marks' => []
                            ]
                        ]
                    ]
                ]
            ]),
            document_helper::create_json_string_document_from_text('Hello world')
        );
    }

    /**
     * @return void
     */
    public function test_create_json_string_document_from_json_text_string(): void {
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Some text'
                        ]
                    ]
                ]
            ]
        ]);

        self::assertEquals(
            $document,
            document_helper::create_json_string_document_from_text($document)
        );
    }

    /**
     * @return void
     */
    public function test_create_json_string_document_from_text_string_with_multiple_line(): void {
        $text = "Hello world\nBoom";
        $document = document_helper::create_json_string_document_from_text($text);

        self::assertEquals(
            json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text('Hello world'),
                    paragraph::create_json_node_from_text('Boom')
                ]
            ]),
            $document
        );
    }

    /**
     * @return void
     */
    public function test_create_json_string_document_from_text_string_with_multiple_line_as_html(): void {
        $text = /** @lang text */"<p>Hello world</p><p>Boom</p>";
        $document = document_helper::create_json_string_document_from_text($text, true);

        self::assertEquals(
            json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text('Hello world'),
                    paragraph::create_json_node_from_text('Boom')
                ]
            ]),
            $document
        );
    }

    /**
     * @return void
     */
    public function test_create_json_string_document_from_text_string_with_multiple_line_as_html_with_br(): void {
        $text = /** @lang text */"Hello world<br/>Boom";
        $document = document_helper::create_json_string_document_from_text($text, true);

        self::assertEquals(
            json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text('Hello world'),
                    paragraph::create_json_node_from_text('Boom')
                ]
            ]),
            $document
        );
    }

    /**
     * @return void
     */
    public function test_create_document_with_a_json_string(): void {
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Some text'
                        ]
                    ]
                ]
            ]
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The text is already in a json editor document format');

        document_helper::create_document_from_text($document);
    }
}