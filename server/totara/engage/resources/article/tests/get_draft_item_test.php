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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use engage_article\totara_engage\resource\article;
use core\json_editor\node\image;
use core\webapi\execution_context;
use totara_engage\access\access;
use totara_engage\timeview\time_view;
use totara_webapi\graphql;
use core\json_editor\document;
use editor_weka\local\file_helper;

class engage_article_get_draft_item_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_draft_item_with_image(): void {
        global $CFG, $USER;
        $this->setAdminUser();

        $doc = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' =>  'This is an article'
                        ]
                    ],
                ]
            ]
        ];

        $article = article::create(
            [
                'format' => FORMAT_JSON_EDITOR,
                'content' => json_encode($doc),
                'timeview' => time_view::LESS_THAN_FIVE,
                'draft_id' => 25,
                'name' => 'Is this random enuf ?'
            ],
            $USER->id
        );

        // Creating a draft file.
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();
        $record = new \stdClass();
        $record->contextid = $article->get_context()->id;
        $record->component = 'user';
        $record->filearea = 'draft';
        $record->itemid = 42;
        $record->filename = 'admin.png';
        $record->userid = $USER->id;
        $record->filepath = '/';

        $file = $fs->create_file_from_string($record, 'hello world');
        $doc['content'][] = image::create_raw_node_from_image($file);

        $article->update([
            'content' => json_encode($doc),
            'draft_id' => 42,
            'access' => access::PRIVATE
        ]);

        $ec = execution_context::create('ajax', 'engage_article_draft_item');
        $result = graphql::execute_operation($ec, ['resourceid' => $article->get_id()]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('draft', $result->data);
        $draft = $result->data['draft'];

        $document = document::create($draft['content']);
        $rawnodes = $document->find_raw_nodes(image::get_type());

        // From this point, the file should be moved into draft.
        $this->assertCount(1, $rawnodes);
        $rawnode = reset($rawnodes);

        $this->assertArrayHasKey('attrs', $rawnode);

        $this->assertArrayNotHasKey('draftid', $rawnode['attrs']);
        $this->assertArrayHasKey('filename', $rawnode['attrs']);

        $this->assertArrayHasKey('file_item_id', $draft);
        $draft_item_id = $draft['file_item_id'];

        $draftfile = $fs->get_file(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $draft_item_id,
            '/',
            $rawnode['attrs']['filename']
        );

        $this->assertNotFalse($draftfile);
        $this->assertInstanceOf(\stored_file::class, $draftfile);
    }
}