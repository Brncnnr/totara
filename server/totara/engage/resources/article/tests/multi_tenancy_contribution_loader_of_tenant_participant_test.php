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

use core_user\totara_engage\share\recipient\user;
use engage_article\totara_engage\card\article_card;
use totara_engage\card\card_loader;
use totara_engage\query\query;

class engage_article_multi_tenancy_contribution_loader_of_tenant_participant_testcase extends advanced_testcase {
    /***
     * @var stdClass|null
     */
    private $system_user;

    /**
     * @var stdClass|null
     */
    private $tenant_user;

    /**
     * The main actor of this test case.
     * @var stdClass|null
     */
    private $tenant_participant;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $this->tenant_user = $generator->create_user([
            'firstname' => 'tenant_user',
            'lastname' => 'tenant_user',
            'tenantid' => $tenant->id,
        ]);

        $this->system_user = $generator->create_user([
            'firstname' => 'system_user',
            'lastname' => 'system_user',
        ]);

        $this->tenant_participant = $generator->create_user([
            'firstname' => 'tenant_participant',
            'lastname' => 'tenant_participant',
        ]);

        $tenant_generator->set_user_participation($this->tenant_participant->id, [$tenant->id]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->system_user = null;
        $this->tenant_user = null;
        $this->tenant_participant = null;
    }

    /**
     * @return \engage_article\testing\generator
     */
    private function get_article_generator(): \engage_article\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        return $article_generator;
    }

    /**
     * @return void
     */
    public function test_loading_shared_resources_by_system_user_without_isolation(): void {
        $article_generator = $this->get_article_generator();
        $public_article = $article_generator->create_public_article(['userid' => $this->system_user->id]);

        $article_generator->share_article($public_article, [new user($this->tenant_participant->id)]);

        $query = new query();
        $query->set_component('totara_engage');
        $query->set_area('shared');

        $this->setUser($this->tenant_participant);
        $loader = new card_loader($query);
        $result = $loader->fetch();

        self::assertEquals(1, $result->get_total());
        $cards = $result->get_items();

        self::assertEquals(1, $cards->count());

        /** @var article_card $first_card */
        $first_card = $cards->first();

        self::assertInstanceOf(article_card::class, $first_card);
        self::assertEquals($first_card->get_instanceid(), $public_article->get_id());
    }

    /**
     * @return void
     */
    public function test_loading_shared_resources_by_system_user_with_isolation(): void {
        $article_generator = $this->get_article_generator();
        $public_article = $article_generator->create_public_article(['userid' => $this->system_user->id]);

        $article_generator->share_article($public_article, [new user($this->tenant_participant->id)]);
        $this->setUser($this->tenant_participant);

        set_config('tenantsisolated', 1);

        $query = new query();
        $query->set_component('totara_engage');
        $query->set_area('shared');

        $loader = new card_loader($query);
        $result = $loader->fetch();

        self::assertEquals(1, $result->get_total());
        $cards = $result->get_items();

        self::assertEquals(1, $cards->count());

        /** @var article_card $first_card */
        $first_card = $cards->first();

        self::assertInstanceOf(article_card::class, $first_card);
        self::assertEquals($first_card->get_instanceid(), $public_article->get_id());
    }

    /**
     * @return void
     */
    public function test_loading_shared_resources_by_tenant_member_without_isolation(): void {
        $article_generator = $this->get_article_generator();
        $public_article = $article_generator->create_public_article(['userid' => $this->tenant_user->id]);

        $article_generator->share_article($public_article, [new user($this->tenant_participant->id)]);
        $this->setUser($this->tenant_participant);

        $query = new query();
        $query->set_component('totara_engage');
        $query->set_area('shared');

        $loader = new card_loader($query);
        $result = $loader->fetch();

        self::assertEquals(1, $result->get_total());
        $cards = $result->get_items();

        self::assertEquals(1, $cards->count());

        /** @var article_card $first_card */
        $first_card = $cards->first();
        self::assertEquals($public_article->get_id(), $first_card->get_instanceid());
    }

    /**
     * @return void
     */
    public function test_loading_shared_resource_by_tenant_member_with_isolation(): void {
        $article_generator = $this->get_article_generator();
        $public_article = $article_generator->create_public_article(['userid' => $this->tenant_user->id]);

        $article_generator->share_article($public_article, [new user($this->tenant_participant->id)]);
        $this->setUser($this->tenant_participant);

        set_config('tenantsisolated', 1);

        $query = new query();
        $query->set_component('totara_engage');
        $query->set_area('shared');

        $loader = new card_loader($query);
        $result = $loader->fetch();

        self::assertEquals(1, $result->get_total());
        $cards = $result->get_items();

        self::assertEquals(1, $cards->count());

        /** @var article_card $first_card */
        $first_card = $cards->first();
        self::assertEquals($public_article->get_id(), $first_card->get_instanceid());
    }
}