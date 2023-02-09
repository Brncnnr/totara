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
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_comment\exception\comment_exception;

class totara_comment_webapi_create_comment_validation_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_create_comment_with_content_format_different_from_json_editor(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");

        $this->resolve_graphql_mutation(
            'totara_comment_create_comment',
            [
                'instanceid' => 42,
                'component' => 'totara_comment',
                'area' => 'comment_view',
                'content' => 'hello world',
                'format' => FORMAT_PLAIN
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_comment_with_content_as_empty_document(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/comment/tests/fixtures/totara_comment_default_resolver.php");

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        // Reset the fixture class to different context rather than system context.
        totara_comment_default_resolver::add_callback(
            'get_context_id',
            function () use ($user_one): int {
                $context = \context_user::instance($user_one->id);
                return $context->id;
            }
        );

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage(get_string('error:create', 'totara_comment'));

        $this->resolve_graphql_mutation(
            'totara_comment_create_comment',
            [
                'instanceid' => 42,
                'component' => 'totara_comment',
                'area' => 'comment_view',
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => []
                ])
            ]
        );
    }
}