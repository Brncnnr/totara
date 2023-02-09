<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package filter_multilang
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\list_item;
use core\json_editor\node\ordered_list;
use core\json_editor\node\paragraph;
use jsoneditor_simple_multi_lang\json_editor\node\lang_block;
use jsoneditor_simple_multi_lang\json_editor\node\lang_blocks;

defined('MOODLE_INTERNAL') || die();

/**
 * Class filter_multilang_filter_testcase
 */
class filter_multilang_filter_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/filter/multilang/filter.php');
        parent::setUpBeforeClass();
    }

    public function test_filter() {

        // Prep the required structure.
        $learner = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($learner->id, $course->id);
        $coursecontext = context_course::instance($course->id);

        // Prep some data.
        $text = '<span lang="en" class="multilang">English</span><span lang="xx" class="multilang">Klingon</span>';
        $filtered = 'English';

        // Access it like we're the learner.
        $this->setUser($learner);

        // Check it filters at the course context level.
        $filter = new filter_multilang($coursecontext, []);
        $this->assertSame($filtered, $filter->filter($text));

        // Check it filters at the system level.
        $filter = new filter_multilang(\context_system::instance(), []);
        $this->assertSame($filtered, $filter->filter($text));

        // Confirm that this filter is indeed compatible with clean_text.
        $this->assertSame($filtered, clean_text($filtered, FORMAT_HTML));
    }

    public function test_is_compatible_with_clean_text() {

        $method = new ReflectionMethod('filter_multilang', 'is_compatible_with_clean_text');
        $method->setAccessible(true);
        self::assertTrue($method->invoke(null));

    }

    public function test_json_filter(): void {
        if (!class_exists('jsoneditor_simple_multi_lang\\json_editor\\node\\lang_blocks')) {
            $this->markTestSkipped('jsoneditor_simple_multi_lang plugin not installed');
        }

        $filter = new filter_multilang(\context_system::instance(), []);

        // Normal usage
        $input = document_helper::create_document_from_content_nodes([
            paragraph::create_json_node_from_text('Good afternoon'),
            lang_blocks::create_raw_node([
                [
                    'type' => lang_block::get_type(),
                    'attrs' => ['lang' => 'de'],
                    'content' => [paragraph::create_json_node_from_text('Mein Luftkissenfahrzeug ist voll von Aalen')]
                ],
                [
                    'type' => lang_block::get_type(),
                    'attrs' => ['lang' => 'en'],
                    'content' => [paragraph::create_json_node_from_text('Can I please buy some matches?')]
                ],
            ])
        ]);
        $filtered = document_helper::create_document_from_content_nodes([
            paragraph::create_json_node_from_text('Good afternoon'),
            paragraph::create_json_node_from_text('Can I please buy some matches?')
        ]);
        
        self::assertSame(json_encode($filtered), $filter->filter_json(json_encode($input)));

        // No matching lang block -- default to first
        $input = document_helper::create_document_from_content_nodes([
            lang_blocks::create_raw_node([
                [
                    'type' => lang_block::get_type(),
                    'attrs' => ['lang' => 'de'],
                    'content' => [paragraph::create_json_node_from_text('Mein Luftkissenfahrzeug ist voll von Aalen')]
                ],
                [
                    'type' => lang_block::get_type(),
                    'attrs' => ['lang' => 'ja'],
                    'content' => [paragraph::create_json_node_from_text('わたくしのホバークラフトはウナギでいっぱいです')]
                ],
            ])
        ]);
        $filtered = document_helper::create_document_from_text('Mein Luftkissenfahrzeug ist voll von Aalen');
        
        self::assertSame(json_encode($filtered), $filter->filter_json(json_encode($input)));

        // Lang block with multiple nodes
        $paragraph1 = paragraph::create_json_node_from_text('foobar');
        $paragraph2 = paragraph::create_json_node_from_text('baz');
        $input = document_helper::create_document_from_content_nodes([
            lang_blocks::create_raw_node([
                [
                    'type' => lang_block::get_type(),
                    'attrs' => ['lang' => 'en'],
                    'content' => [$paragraph1, $paragraph2]
                ],
            ])
        ]);
        $filtered = document_helper::create_document_from_content_nodes([$paragraph1, $paragraph2]);

        self::assertSame(json_encode($filtered), $filter->filter_json(json_encode($input)));

        // lang_blocks with no lang_block
        $input = document_helper::create_document_from_content_nodes([
            lang_blocks::create_raw_node([])
        ]);
        $filtered = document_helper::create_document_from_content_nodes([]);

        self::assertSame(json_encode($filtered), $filter->filter_json(json_encode($input)));

        // Lang blocks nested inside other nodes
        $input = document_helper::create_document_from_content_nodes([
            [
                'type' => ordered_list::get_type(),
                'content' => [
                    [
                        'type' => list_item::get_type(),
                        'content' => [
                            lang_blocks::create_raw_node([
                                [
                                    'type' => lang_block::get_type(),
                                    'attrs' => ['lang' => 'en'],
                                    'content' => [paragraph::create_json_node_from_text('Can I please buy some matches?')],
                                ],
                            ])
                        ]
                    ]
                ]
            ]
        ]);
        $filtered = document_helper::create_document_from_content_nodes([
            [
                'type' => ordered_list::get_type(),
                'content' => [
                    [
                        'type' => list_item::get_type(),
                        'content' => [
                            paragraph::create_json_node_from_text('Can I please buy some matches?'),
                        ]
                    ]
                ]
            ]
        ]);

        self::assertSame(json_encode($filtered), $filter->filter_json(json_encode($input)));
    }

}