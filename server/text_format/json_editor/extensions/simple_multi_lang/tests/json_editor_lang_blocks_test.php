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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package jsoneditor_simple_multi_lang
 */

use core\json_editor\formatter\default_formatter;
use core\json_editor\node\paragraph;
use core_phpunit\testcase;
use jsoneditor_simple_multi_lang\json_editor\node\lang_block;
use jsoneditor_simple_multi_lang\json_editor\node\lang_blocks;

class json_editor_lang_blocks_testcase extends testcase {
    public function test_render_single_lang_block_as_html(): void {
        $formatter = new default_formatter();

        $raw_node = lang_blocks::create_raw_node([
            [
                'type' => lang_block::get_type(),
                'attrs' => ['lang' => 'en', 'siblings_count' => 1],
                'content' => [paragraph::create_json_node_from_text('hello')]
            ],
        ]);
        $html = lang_blocks::from_node($raw_node)->to_html($formatter);
        self::assertEquals('<p>hello</p>', $html);
    }

    public function test_render_multiple_lang_blocks_as_html(): void {
        $formatter = new default_formatter();

        $raw_node = lang_blocks::create_raw_node([
            [
                'type' => lang_block::get_type(),
                'attrs' => ['lang' => 'en', 'siblings_count' => 2],
                'content' => [paragraph::create_json_node_from_text('Can I please buy some matches?')]
            ],
            [
                'type' => lang_block::get_type(),
                'attrs' => ['lang' => 'de', 'siblings_count' => 2],
                'content' => [paragraph::create_json_node_from_text('Mein Luftkissenfahrzeug ist voll von Aale')]
            ],
        ]);
        $html = lang_blocks::from_node($raw_node)->to_html($formatter);
        self::assertEquals(
            /** @lang text */
            '<span class="multilang" lang="en"><p>Can I please buy some matches?</p></span>' .
                '<span class="multilang" lang="de"><p>Mein Luftkissenfahrzeug ist voll von Aale</p></span>',
            $html
        );
    }

    public function test_render_multiple_lang_blocks_as_html_without_siblings_count(): void {
        $formatter = new default_formatter();

        $raw_node = lang_blocks::create_raw_node([
            [
                'type' => lang_block::get_type(),
                'attrs' => ['lang' => 'en'],
                'content' => [paragraph::create_json_node_from_text('Can I please buy some matches?')]
            ],
            [
                'type' => lang_block::get_type(),
                'attrs' => ['lang' => 'de'],
                'content' => [paragraph::create_json_node_from_text('Mein Luftkissenfahrzeug ist voll von Aale')]
            ],
        ]);
        $html = lang_blocks::from_node($raw_node)->to_html($formatter);
        self::assertEquals(
            /** @lang text */
            '<span class="multilang" lang="en"><p>Can I please buy some matches?</p></span>' .
                '<span class="multilang" lang="de"><p>Mein Luftkissenfahrzeug ist voll von Aale</p></span>',
            $html
        );
    }

    public function test_render_multiple_lang_blocks_as_html_with_incorrect_siblings_count(): void {
        $formatter = new default_formatter();

        $raw_node = lang_blocks::create_raw_node([
            [
                'type' => lang_block::get_type(),
                'attrs' => ['lang' => 'en', 'siblings_count' => 1],
                'content' => [paragraph::create_json_node_from_text('Can I please buy some matches?')]
            ],
            [
                'type' => lang_block::get_type(),
                'attrs' => ['lang' => 'de', 'siblings_count' => 1],
                'content' => [paragraph::create_json_node_from_text('Mein Luftkissenfahrzeug ist voll von Aale')]
            ],
        ]);
        $html = lang_blocks::from_node($raw_node)->to_html($formatter);
        self::assertEquals(
            /** @lang text */
            '<span class="multilang" lang="en"><p>Can I please buy some matches?</p></span>' .
                '<span class="multilang" lang="de"><p>Mein Luftkissenfahrzeug ist voll von Aale</p></span>',
            $html
        );
    }

    public function test_render_lang_blocks_as_html_with_some_missing_values(): void {
        $formatter = new default_formatter();

        $raw_node = lang_blocks::create_raw_node([
            ['type' => lang_block::get_type()],
            [
                'type' => lang_block::get_type(),
                'attrs' => ['lang' => 'en'],
                'content' => [paragraph::create_json_node_from_text('hello')]
            ],
        ]);
        $html = lang_blocks::from_node($raw_node)->to_html($formatter);
        self::assertEquals('<p>hello</p>', $html);
    }

    public function test_render_lang_blocks_as_html_with_empty_blocks(): void {
        $formatter = new default_formatter();

        $raw_node = lang_blocks::create_raw_node([
            ['type' => lang_block::get_type()],
            ['type' => lang_block::get_type()],
        ]);
        $html = lang_blocks::from_node($raw_node)->to_html($formatter);
        self::assertEquals('', $html);
    }
}
