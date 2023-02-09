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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package core
 */

use core\json_editor\helper\text_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * @coversDefaultClass core\json_editor\helper\text_helper
 */
class core_json_editor_text_helper_testcase extends advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        require_once(__DIR__ . '/fixtures/json_editor/sample_documents.php');
    }

    /**
     * @covers ::append_paragraph
     */
    public function test_append_paragraph(): void {
        $this->assertFalse(text_helper::append_paragraph('<p>kia ora te ao</p>', 'New!'));
        $this->resetDebugging();
        $this->assertFalse(text_helper::append_paragraph('{}', 'New!'));
        $this->assertEquals('{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"New!","marks":[]}]}]}', text_helper::append_paragraph('{"type":"doc","content":[]}', 'New!'));
        $doc = text_helper::append_paragraph(core_json_editor_sample_documents::minimal(false), 'New parapara graph');
        $this->assertNotFalse($doc);
        $json = json_decode($doc);
        $this->assertNotEmpty($json);
        $this->assertCount(3, $json->content);
        $this->assertStringContainsString('{"type":"paragraph","content":[{"type":"text","text":"New parapara graph","marks":[]}]}', $doc);
        $doc = text_helper::append_paragraph(core_json_editor_sample_documents::minimal(true), [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'kia'],
                ['type' => 'text', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://totara.example.com/ora.jsp']]], 'text' => 'ora'],
                ['type' => 'text', 'text' => 'koutou'],
                ['type' => 'emoji', 'attrs' => ['shortcode' => '1F4A6']]
            ]
        ]);
        $json = json_decode($doc);
        $this->assertNotEmpty($json);
        $this->assertCount(3, $json->content);
        $this->assertCount(4, $json->content[2]->content);
        $this->assertEquals('link', $json->content[2]->content[1]->marks[0]->type);
        $this->assertEquals('https://totara.example.com/ora.jsp', $json->content[2]->content[1]->marks[0]->attrs->href);
        $this->assertEquals('emoji', $json->content[2]->content[3]->type);
        $this->assertEquals('1F4A6', $json->content[2]->content[3]->attrs->shortcode);
    }

    /**
     * @covers ::append_formatted_paragraph_with_link
     */
    public function test_append_formatted_paragraph_with_link(): void {
        $callback = function (string $encoded_link) {
            return "kia ora {$encoded_link} koutou katoa!";
        };
        $this->assertFalse(text_helper::append_formatted_paragraph_with_link('<p>kia ora te ao</p>', 'New!', 'https://totara.example.com/new.jsp', $callback));
        $this->resetDebugging();
        $this->assertFalse(text_helper::append_formatted_paragraph_with_link('{}', 'New!', 'https://totara.example.com/new.jsp', $callback));
        $doc = text_helper::append_formatted_paragraph_with_link(core_json_editor_sample_documents::minimal(false), 'New!', 'https://totara.example.com/new.jsp', $callback);
        $this->assertNotFalse($doc);
        $json = json_decode($doc);
        $this->assertNotEmpty($json);
        $this->assertCount(3, $json->content);
        $this->assertEquals('paragraph', $json->content[2]->type);
        $this->assertCount(3, $json->content[2]->content);
        $this->assertEmpty($json->content[2]->content[0]->marks);
        $this->assertCount(1, $json->content[2]->content[1]->marks);
        $this->assertEquals('link', $json->content[2]->content[1]->marks[0]->type);
        $this->assertEmpty($json->content[2]->content[2]->marks);

        $callback = function (string $encoded_link) {
            return 'ma te wa';
        };
        $doc = text_helper::append_formatted_paragraph_with_link(core_json_editor_sample_documents::minimal(false), 'New!', 'https://totara.example.com/new.jsp', $callback);
        $this->assertNotFalse($doc);
        $json = json_decode($doc);
        $this->assertNotEmpty($json);
        $this->assertCount(3, $json->content);
        $this->assertEquals('paragraph', $json->content[2]->type);
        $this->assertCount(1, $json->content[2]->content);
        $this->assertTrue(empty($json->content[2]->content[0]->marks));
        $this->assertEquals('ma te wa', $json->content[2]->content[0]->text);
    }
}
