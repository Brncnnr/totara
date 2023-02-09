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

use core\webapi\resolver\payload;
use core\webapi\execution_context;
use core\json_editor\node\paragraph;
use core\webapi\resolver\result;
use core\webapi\middleware\clean_editor_content;
use core\json_editor\helper\document_helper;

class core_webapi_middleware_clean_editor_content_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_clean_editor_content(): void {
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text(
                    /** @lang text */
                    "Wohoo this is json content <script>alert('hello world');</script>"
                )
            ]
        ]);

        $ec = execution_context::create('dev');
        $payload = payload::create(
            [
                'content' => $document,
                'content_format' => FORMAT_JSON_EDITOR
            ],
            $ec
        );

        $next = Closure::fromCallable(
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $middleware = new clean_editor_content('content', 'content_format');
        $result = $middleware->handle($payload, $next);

        $result_data = $result->get_data();

        $this->assertArrayHasKey('content', $result_data);
        $this->assertArrayHasKey('content_format', $result_data);

        $this->assertEquals(
            document_helper::clean_json_document($document),
            $result_data['content']
        );
    }

    /**
     * @return void
     */
    public function test_clean_editor_content_missing_required_key(): void {
        $ec = execution_context::create('dev');
        $payload = payload::create([], $ec);

        $next = Closure::fromCallable(
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot find the content variable at key 'content'");

        $middleware = new clean_editor_content('content', 'content_format');
        $middleware->handle($payload, $next);
    }

    /**
     * @return void
     */
    public function test_clean_editor_content_missing_key_without_yielding_error(): void {
        $ec = execution_context::create('dev');
        $payload = payload::create([], $ec);

        $next = Closure::fromCallable(
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $middleware = new clean_editor_content('content', 'content_format', false);
        $result = $middleware->handle($payload, $next);

        $this->assertEmpty($result->get_data());
    }

    /**
     * @return void
     */
    public function test_clean_non_json_editor_content(): void {
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text(
                /** @lang text */
                    "Wohoo this is json content <script>alert('hello world');</script>"
                )
            ]
        ]);

        $ec = execution_context::create('dev');
        $payload = payload::create(['content' => $document], $ec);

        $middleware = new clean_editor_content('content', 'content_format');
        $result = $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $this->assertNotEmpty($result->get_data());
        $data = $result->get_data();

        $this->assertArrayHasKey('content', $data);

        // No content format - meaning that it should not do anything.
        $this->assertEquals($document, $data['content']);
    }

    /**
     * @return void
     */
    public function test_clean_invalid_json_content(): void {
        $ec = execution_context::create('dev');
        $payload = payload::create(
            [
                'content' =>  "*Italic*, **bold**, and `monospace`",
                'content_format' => FORMAT_JSON_EDITOR
            ],
            $ec
        );

        $middleware = new clean_editor_content('content', 'content_format');

        $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $messages = [
            'There was an error on parsing json content: Syntax error',
            'JSON document is invalid'
        ];

        $this->assertDebuggingCalledCount(2, $messages);
    }
}