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

use core\json_editor\node\link_media;

class core_json_editor_link_media_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            link_media::validate_schema([
                'type' => link_media::get_type(),
                'attrs' => [
                    'url' => 'http://example.com',
                    'title' => 'some title',
                    'description' => 'woops',
                    'image' => 'http://example.com',
                    'resolution' => [
                        'width' => 15,
                        'height' => 20
                    ]
                ]
            ])
        );

        $this->assertTrue(
            link_media::validate_schema([
                'type' => link_media::get_type(),
                'attrs' => [
                    'url' => 'http://example.com'
                ]
            ])
        );

        // Youtube data.
        $this->assertTrue(
            link_media::validate_schema([
                'type' => link_media::get_type(),
                'attrs' => [
                    'url' => "https://www.youtube.com/watch?v=lkofeZW6T1o",
                    'loading' => false,
                    'image' => 'https://i.ytimg.com/vi/lkofeZW6T1o/maxresdefault.jpg',
                    'title' => 'OG vs LIQUID - Decider Match - OMEGA League DOTA 2',
                    'description' => 'Tournaments',
                    'resolution' => [
                        'width' => 1280,
                        'height' => 720
                    ]
                ]
            ])
        );
    }

    /**
     * Clean json data for youtube link media.
     * @return void
     */
    public function test_clean_json_data_youtube(): void {
        $result = link_media::clean_raw_node([
            'type' => link_media::get_type(),
            'attrs' => [
                'url' => "https://www.youtube.com/watch?v=lkofeZW6T1o",
                'loading' => false,
                'image' => 'https://i.ytimg.com/vi/lkofeZW6T1o/maxresdefault.jpg',
                'title' => 'OG vs LIQUID - Decider Match - OMEGA League DOTA 2',
                'description' => 'Tournaments',
                'resolution' => [
                    'width' => 1280,
                    'height' => 720
                ]
            ]
        ]);

        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('attrs', $result);

        $this->assertEquals(link_media::get_type(), $result['type']);
        $attrs = $result['attrs'];

        $this->assertArrayHasKey('url', $attrs);
        $this->assertArrayHasKey('title', $attrs);
        $this->assertArrayHasKey('image', $attrs);
        $this->assertArrayHasKey('description', $attrs);
        $this->assertArrayHasKey('resolution', $attrs);

        $this->assertEquals('https://www.youtube.com/watch?v=lkofeZW6T1o', $attrs['url']);
        $this->assertEquals('https://i.ytimg.com/vi/lkofeZW6T1o/maxresdefault.jpg', $attrs['image']);
        $this->assertEquals('OG vs LIQUID - Decider Match - OMEGA League DOTA 2', $attrs['title']);
        $this->assertEquals('Tournaments', $attrs['description']);

        $this->assertArrayNotHasKey('loading', $attrs);
    }
}