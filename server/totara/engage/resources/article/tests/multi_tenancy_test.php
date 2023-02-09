<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use core_user\totara_engage\share\recipient\user;
use totara_engage\query\query as card_query;
use totara_engage\card\card_loader;

class engage_article_multi_tenancy_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_user_cannot_see_shared_article_from_ex_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        $topic_ids = [];
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 5; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        // Log in as user two and start creating articles that are shared with user one.
        $this->setUser($user_two);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $recipient = new user($user_one->id);

        for ($i = 0; $i < 5; $i++) {
            $article = $article_generator->create_article([
                'access' => access::RESTRICTED,
                'topics' => $topic_ids
            ]);

            $article_generator->share_article($article, [$recipient]);
        }

        // Log in as user one and check if the user is able to see the articles or not.
        $this->setUser($user_one);

        $query = new card_query();
        $query->set_userid($user_one->id);
        $query->set_area('shared');

        $loader = new card_loader($query);
        $old_paginator = $loader->fetch();

        $this->assertCount(5, $old_paginator->get_items()->all());
        $this->assertEquals(5, $old_paginator->get_total());

        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        $new_paginator = $loader->fetch();
        $this->assertEmpty($new_paginator->get_items()->all());
        $this->assertEquals(0, $new_paginator->get_total());
    }
}