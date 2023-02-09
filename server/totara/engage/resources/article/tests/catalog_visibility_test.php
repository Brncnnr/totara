<?php
/**
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package engage_article
 */

defined('MOODLE_INTERNAL') || die();

use totara_catalog\provider_handler;
use totara_engage\access\access;
use engage_article\totara_engage\resource\article;
use engage_article\event\article_updated;

class engage_article_catalog_visibility_testcase extends advanced_testcase {

    private $users = null;
    private $articles = null;

    public function setUp(): void {
        $generator = $this->getDataGenerator();
        $articlegen = $generator->get_plugin_generator('engage_article');

        // Create some test users.
        $this->users = [];
        for ($i = 1; $i <= 4; $i++) {
            $this->users[$i] = $generator->create_user();
        }

        // Create a couple of Private articles as controls.
        $this->articles = [];
        $params = [
            'name' => 'Private Content 2',
            'content' => 'wordswords',
            'userid' => $this->users[2]->id,
            'access' => access::PRIVATE
        ];
        $this->articles[2] = $articlegen->create_article($params);

        $params = [
            'name' => 'Private Content 3',
            'content' => 'wordswordswords',
            'userid' => $this->users[3]->id,
            'access' => access::PRIVATE
        ];
        $this->articles[3] = $articlegen->create_article($params);
    }

    public function tearDown(): void {
        $this->users = null;
        $this->articles = null;
    }

    private function setup_object($id) {
        $object = new stdClass();
        $object->objectid = $id;
        return [$object];
    }

    /**
     * @return void
     */
    public function test_article_cache_all_visibility(): void {
        // Set up an article with open access to everyone.
        $generator = $this->getDataGenerator();
        $articlegen = $generator->get_plugin_generator('engage_article');

        $params = [
            'name' => 'Public Content 1',
            'content' => 'wordswordswords',
            'userid' => $this->users[1]->id,
            'access' => access::PUBLIC
        ];
        $article = $articlegen->create_article($params);

        // First get the catalog provider for engage articles.
        $providerhandler = provider_handler::instance();
        $provider = $providerhandler->get_provider('engage_article');

        // Next prime the caches to make sure the data is there.
        $this->setUser($this->users[1]->id);
        $provider->prime_provider_cache();

        // Finally run through the can_see() function to check expected results.
        $u1data = [
            $article->get_articleid() => true,
            $this->articles[2]->get_articleid() => false,
            $this->articles[3]->get_articleid() => false,
        ];

        foreach ($u1data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }

        // Cache is now set per user.
        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();

        $u2data = [
            $article->get_articleid() => true,
            $this->articles[2]->get_articleid() => true,
            $this->articles[3]->get_articleid() => false,
        ];

        foreach ($u2data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }

        $this->setUser($this->users[3]->id);
        $provider->prime_provider_cache();

        $u3data = [
            $article->get_articleid() => true,
            $this->articles[2]->get_articleid() => false,
            $this->articles[3]->get_articleid() => true,
        ];

        foreach ($u3data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }
    }

    public function test_article_object_update(): void {
        global $DB;

        // Set up an article with open access to everyone.
        $generator = $this->getDataGenerator();
        $articlegen = $generator->get_plugin_generator('engage_article');

        $params = [
            'name' => 'Public Content 1',
            'content' => 'words',
            'userid' => $this->users[1]->id,
            'access' => access::PUBLIC
        ];
        $article = $articlegen->create_article($params);

        // First check we have the expected number of items and the test item has the expected data.
        $items = $DB->get_records('catalog', ['objecttype' => 'engage_article']);
        $this->assertCount(3, $items);
        foreach ($items as $item) {
            if ($item->objectid == $article->get_articleid()) {
                $this->assertSame($params['name'], $item->ftshigh);
            }
        }

        // Now manually update the item
        $DB->set_field('engage_resource', 'name', 'Updated Content 1', ['id' => $article->get_id()]);

        // Check that it hasn't updated the catalog record
        $items = $DB->get_records('catalog', ['objecttype' => 'engage_article']);
        $this->assertCount(3, $items);
        foreach ($items as $item) {
            if ($item->objectid == $article->get_articleid()) {
                $this->assertSame($params['name'], $item->ftshigh);
            }
        }

        // Trigger the update event
        $a1 = article::from_resource_id($article->get_id());
        $e1 = article_updated::from_article($a1);
        $e1->trigger();

        // Finally check that the catalog record has been updated
        $items = $DB->get_records('catalog', ['objecttype' => 'engage_article']);
        $this->assertCount(3, $items);
        foreach ($items as $item) {
            if ($item->objectid == $article->get_articleid()) {
                $this->assertSame('Updated Content 1', $item->ftshigh);
            }
        }
    }

    /**
     * @return void
     */
    public function test_article_cache_update(): void {
        global $DB;

        // Set up an article with open access to everyone.
        $generator = $this->getDataGenerator();
        $articlegen = $generator->get_plugin_generator('engage_article');

        $params = [
            'name' => 'Public Content 1',
            'content' => 'words',
            'userid' => $this->users[1]->id,
            'access' => access::PUBLIC
        ];
        $article = $articlegen->create_article($params);
        $article2 = $this->articles[2];

        // First get the catalog provider for engage articles.
        $providerhandler = provider_handler::instance();
        $provider = $providerhandler->get_provider('engage_article');

        // Next prime the caches to make sure the data is there.
        $this->setUser($this->users[1]->id);
        $provider->prime_provider_cache();

        // Check the initial visibilty.
        $u1data = [
            $article->get_articleid() => true,
            $article2->get_articleid() => false,
            $this->articles[3]->get_articleid() => false,
        ];

        foreach ($u1data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }

        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();
        $u2data = [
            $article->get_articleid() => true,
            $article2->get_articleid() => true,
            $this->articles[3]->get_articleid() => false,
        ];

        foreach ($u2data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }


        // Update visibility and trigger events
        $DB->set_field('engage_resource', 'access', access::PRIVATE, ['id' => $article->get_id()]);
        $a1 = article::from_resource_id($article->get_id());
        $e1 = article_updated::from_article($a1);
        $e1->trigger();

        $DB->set_field('engage_resource', 'access', access::PUBLIC, ['id' => $article2->get_id()]);
        $a2 = article::from_resource_id($article2->get_id());
        $e2 = article_updated::from_article($a2);
        $e2->trigger();

        $this->setUser($this->users[1]->id);
        $provider->prime_provider_cache();

        $cansee = $provider->can_see($this->setup_object($article->get_articleid()));
        $this->assertSame($cansee[$article->get_articleid()], true);

        $cansee = $provider->can_see($this->setup_object($article2->get_articleid()));
        $this->assertSame($cansee[$article2->get_articleid()], true);

        // Cache is per user.
        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();

        $cansee = $provider->can_see($this->setup_object($article->get_articleid()));
        $this->assertSame($cansee[$article->get_articleid()], false);

        $cansee = $provider->can_see($this->setup_object($article2->get_articleid()));
        $this->assertSame($cansee[$article2->get_articleid()], true);
    }

    /**
     * @return void
     */
    public function test_article_cache_no_visibility(): void {
        // Set up an article with open access to everyone.
        $generator = $this->getDataGenerator();
        $articlegen = $generator->get_plugin_generator('engage_article');

        $params = [
            'name' => 'Private Content 1',
            'content' => 'wordswordswords',
            'userid' => $this->users[1]->id,
            'access' => access::PRIVATE
        ];
        $article = $articlegen->create_article($params);

        // First get the catalog provider for engage articles.
        $providerhandler = provider_handler::instance();
        $provider = $providerhandler->get_provider('engage_article');

        // Next prime the caches to make sure the data is there.
        $this->setUser($this->users[1]->id);
        $provider->prime_provider_cache();

        // Finally run through the can_see() function to check expected results.
        $u1data = [
            $article->get_articleid() => true,
            $this->articles[2]->get_articleid() => false,
            $this->articles[3]->get_articleid() => false,
        ];

        foreach ($u1data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }

        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();

        $u2data = [
            $article->get_articleid() => false,
            $this->articles[2]->get_articleid() => true,
            $this->articles[3]->get_articleid() => false,
        ];

        foreach ($u2data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }

        $this->setUser($this->users[3]->id);
        $provider->prime_provider_cache();

        $u3data = [
            $article->get_articleid() => false,
            $this->articles[2]->get_articleid() => false,
            $this->articles[3]->get_articleid() => true,
        ];

        foreach ($u3data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }
    }

    /**
     * @return void
     */
    public function test_article_cache_restricted_visibility(): void {
        global $DB;

        // Set up an article with open access to everyone.
        $generator = $this->getDataGenerator();
        $articlegen = $generator->get_plugin_generator('engage_article');

        $params = [
            'name' => 'Public Content 1',
            'content' => 'wordswordswords',
            'userid' => $this->users[1]->id,
            'access' => access::RESTRICTED
        ];
        $article = $articlegen->create_article($params);

        // Create recipients.
        $recipients = $articlegen->create_user_recipients([$this->users[2], $this->users[4]]);

        // Share articles.
        $this->setUser($this->users[1]);
        $articlegen->share_article($article, $recipients);

        // First get the catalog provider for engage articles.
        $providerhandler = provider_handler::instance();
        $provider = $providerhandler->get_provider('engage_article');

        // Next prime the caches to make sure the data is there.
        $provider->prime_provider_cache();

        // Finally run through the can_see() function to check expected results.
        $this->setUser($this->users[1]->id);
        $u1data = [
            $article->get_articleid() => true,
            $this->articles[2]->get_articleid() => false,
            $this->articles[3]->get_articleid() => false,
        ];

        foreach ($u1data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }

        $this->setUser($this->users[2]->id);
        $provider->prime_provider_cache();

        $u2data = [
            $article->get_articleid() => true,
            $this->articles[2]->get_articleid() => true,
            $this->articles[3]->get_articleid() => false,
        ];

        foreach ($u2data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }

        $this->setUser($this->users[3]->id);
        $provider->prime_provider_cache();
        $u3data = [
            $article->get_articleid() => false,
            $this->articles[2]->get_articleid() => false,
            $this->articles[3]->get_articleid() => true,
        ];

        foreach ($u3data as $articleid => $expectation) {
            $cansee = $provider->can_see($this->setup_object($articleid));
            $this->assertSame($expectation, $cansee[$articleid]);
        }
    }
}
