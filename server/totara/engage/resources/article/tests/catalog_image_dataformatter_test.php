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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package engage_article
 */

defined('MOODLE_INTERNAL') || die();

use engage_article\totara_catalog\article\dataholder_factory\image;
use totara_catalog\dataformatter\formatter;
use totara_engage\access\access;

class engage_article_catalog_image_dataformatter_testcase extends advanced_testcase {

    public function test_article_default_image(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $topic_ids = [];

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'access' => access::PUBLIC,
            'topics' => $topic_ids,
            'userid' => $user_one->id,
        ]);

        $context = context_system::instance();
        $data_holders = image::get_dataholders();
        $this->assertCount(1, $data_holders);
        $data_holder = current($data_holders);

        $result = $data_holder->get_formatted_value(
            formatter::TYPE_PLACEHOLDER_IMAGE,
            [
                'resourceid' => $article->get_id(),
                'owner' => $user_one->id,
                'alt' => 'Alt Text'
            ],
            $context
        );

        $theme_revision = theme_get_revision();

        $this->assertIsObject($result);
        $this->assertIsString($result->url);
        $this->assertStringEndsWith("engage_article/{$theme_revision}/default", $result->url);
    }
}
