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

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\exception\resource_exception;

class engage_article_webapi_update_article_validation_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_update_article_with_empty_content(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'format' => FORMAT_JSON_EDITOR,
            'content' => 'Dope'
        ]);

        // Update article with content as empty document
        $this->expectException(resource_exception::class);
        $this->expectExceptionMessage(get_string('error:update', 'engage_article'));

        $this->resolve_graphql_mutation(
            'engage_article_update',
            [
                'resourceid' => $article->get_id(),
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => []
                ]),
            ]
        );
    }
}