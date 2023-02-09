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
use totara_engage\access\access;
use totara_reaction\exception\reaction_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_user\totara_engage\share\recipient\user;

class engage_article_webapi_fetch_reactions_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_reactions_of_private_article_by_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'userid' => $user_one->id,
            'access' => access::PRIVATE
        ]);

        // Log in as second user and fetch the reaction.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(reaction_exception::class);
        $this->expectExceptionMessage(get_string('error:view', 'totara_reaction'));

        $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'area' => article::REACTION_AREA
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_restricted_article_by_non_shared_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'userid' => $user_one->id,
            'access' => access::RESTRICTED
        ]);

        // Log in as user two and fetch for the reaction.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(reaction_exception::class);
        $this->expectExceptionMessage(get_string('error:view', 'totara_reaction'));

        $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'area' => article::REACTION_AREA
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_public_article_by_viewer(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'userid' => $user_one->id,
            'access' => access::PUBLIC
        ]);

        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $reactions = $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'area' => article::REACTION_AREA
            ]
        );

        self::assertIsArray($reactions);
        self::assertEmpty($reactions);
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_restricted_article_by_shared_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['access' => access::RESTRICTED]);

        // Share article to user two.
        $user_two = $generator->create_user();
        $article_generator->share_article($article, [new user($user_two->id)]);

        // Log in as user two and fetch for the reactions.
        $this->setUser($user_two);
        $reactions = $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $article->get_id(),
                'component' => article::get_resource_type(),
                'area' => article::REACTION_AREA
            ]
        );

        self::assertIsArray($reactions);
        self::assertEmpty($reactions);
    }
}