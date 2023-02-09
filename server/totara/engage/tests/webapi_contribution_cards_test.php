<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Qingyang liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */

use engage_article\totara_engage\resource\article;
use totara_engage\answer\answer_type;

defined('MOODLE_INTERNAL') || die();

class totara_engage_webapi_contribution_cards_testcase extends advanced_testcase {
    use \totara_webapi\phpunit\webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_contribution_cards_with_wrong_area(): void {
        $user = $this->setup_user();
        $this->create_survey($user->id);
        $this->create_article($user->id);
        $this->create_playlist($user->id);

        $this->expectException("coding_exception");
        $this->expectExceptionMessage("The area oowwnned is not supported.");
        $this->execute_query([
            'component' => "totara_engage",
            "area" => "oowwnned",
            "filter" => [
            ]
        ]);
    }

    /**
     * @return void
     */
    public function test_contribution_cards_with_wrong_component(): void {
        $user = $this->setup_user();
        $this->create_survey($user->id);
        $this->create_article($user->id);
        $this->create_playlist($user->id);

        $this->expectException("coding_exception");
        $this->expectExceptionMessage("The component engage_article is not supported.");
        $this->execute_query([
            'component' => "engage_article",
            "area" => "owned",
            "filter" => [
            ]
        ]);
    }

    /**
     * @return void
     */
    public function test_contribution_cards_for_your_resources(): void {
        // Other's resource
        $user = $this->getDataGenerator()->create_user();
        $this->create_survey($user->id);

        $user = $this->setup_user();
        $survey = $this->create_survey($user->id);
        $article1 = $this->create_article($user->id);
        $article2 = $this->create_article($user->id);
        $this->create_playlist($user->id);


        $result = $this->execute_query([
            'component' => "totara_engage",
            "area" => "owned",
            "filter" => []
        ]);

        // Playlist and other's resources has not be included.
        $this->assertIsArray($result);
        $this->assertCount(3, $result['cards']);
        $ids = array_map(
            function ($resource): int {
                return $resource->get_instanceid();
            },
            $result['cards']
        );

        $this->assertContains($survey->get_id(), $ids);
        $this->assertContains($article1->get_id(), $ids);
        $this->assertContains($article2->get_id(), $ids);
    }

    /**
     * @return void
     */
    public function test_contribution_cards_for_search_page(): void {
        $user = $this->setup_user();
        $this->create_survey($user->id, "bbb");
        $this->create_article($user->id, "rrr");
        $playlist = $this->create_playlist($user->id, "aaa");

        $result = $this->execute_query([
            'component' => "totara_engage",
            "area" => "search",
            "filter" => [
                "search" => "aaa"
            ]
        ]);

        $ids = array_map(
            function ($resource): int {
                return $resource->get_instanceid();
            },
            $result['cards']
        );

        $this->assertIsArray($result);
        $this->assertCount(1, $result['cards']);
        $this->assertContainsEquals($playlist->get_id(), $ids);
    }

    /**
     * @param array $args
     * @return mixed|null
     */
    private function execute_query(array $args) {
        return $this->resolve_graphql_query('totara_engage_contributions', $args);
    }

    /**
     * @return array|stdClass|null
     */
    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        return $user;
    }

    /**
     * @param int $user_id
     * @param string|null $name
     * @return article
     */
    private function create_article(int $user_id, ?string $name = null): article {
        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');

        $params = ['userid' => $user_id];
        if (isset($name)) {
            $params['name'] = $name;
        }
        return $generator->create_article($params);
    }

    /**
     * @param int $user_id
     * @param string|null $question
     * @return \engage_survey\totara_engage\resource\survey
     */
    private function create_survey(int $user_id, ?string $question = null): \engage_survey\totara_engage\resource\survey {
        /** @var \engage_survey\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_survey');
        return $generator->create_survey($question, [], answer_type::MULTI_CHOICE, ['userid' => $user_id]);
    }

    /**
     * @param int $user_id
     * @param string|null $name
     * @return \totara_playlist\playlist
     */
    private function create_playlist(int $user_id, ?string $name = null): \totara_playlist\playlist {
        /** @var \totara_playlist\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_playlist');

        $params = ['userid' => $user_id];
        if (isset($name)) {
            $params['name'] = $name;
        }
        return $generator->create_playlist($params);
    }

}