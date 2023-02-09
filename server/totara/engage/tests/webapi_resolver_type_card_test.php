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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die();

use engage_article\totara_engage\resource\article;
use totara_engage\card\card_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the card graphql type
 */
class totara_engage_webapi_resolver_type_card_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_card_image(): void {
        global $CFG;

        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var engage_article_generator $article_gen */
        $article_gen = $gen->get_plugin_generator('engage_article');
        $article = $article_gen->create_article()->to_array();
        $article['component'] = article::get_resource_type();
        $card = card_resolver::create_card(article::get_resource_type(), $article);

        // Test with theme.
        $this->resolve_graphql_type(
            'totara_engage_card',
            'image',
            $card,
            ['theme' => 'ventura']
        );
        $this->assertDebuggingNotCalled();

        // Test without theme.
        $this->resolve_graphql_type(
            'totara_engage_card',
            'image',
            $card
        );
        $this->assertDebuggingCalled(
            "'theme' parameter not set. Falling back on {$CFG->theme}. The resolved assets "
            . "will be associated with {$CFG->theme}, which might not be the expected result."
        );
    }
}