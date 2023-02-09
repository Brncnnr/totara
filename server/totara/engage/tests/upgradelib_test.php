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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;

class totara_engage_upgradelib_testcase extends testcase {
    /**
     * @return void
     */
    public function test_set_context_id_for_resource(): void {
        global $CFG, $DB;

        self::setAdminUser();
        $gen = self::getDataGenerator();
        /** @var engage_article_generator $article_gen */
        $article_gen = $gen->get_plugin_generator('engage_article');

        $article1 = $article_gen->create_article();
        $DB->execute('UPDATE {engage_resource} SET contextid = NULL WHERE id = :id', ['id' => $article1->get_id()]);
        $article1->refresh();
        $article2 = $article_gen->create_article();
        $DB->execute('UPDATE {engage_resource} SET contextid = NULL WHERE id = :id', ['id' => $article2->get_id()]);
        $article2->refresh();
        $article3 = $article_gen->create_article();

        self::assertNull($DB->get_field('engage_resource', 'contextid', ['id' => $article1->get_id()]));
        self::assertNull($DB->get_field('engage_resource', 'contextid', ['id' => $article2->get_id()]));
        self::assertNotNull($article3->get_context_id());

        require_once($CFG->dirroot.'/totara/engage/db/upgradelib.php');
        totara_engage_set_context_id_for_resource();

        self::assertNotNull($DB->get_field('engage_resource', 'contextid', ['id' => $article1->get_id()]));
        self::assertNotNull($DB->get_field('engage_resource', 'contextid', ['id' => $article2->get_id()]));
        self::assertNotNull($DB->get_field('engage_resource', 'contextid', ['id' => $article3->get_id()]));
    }
}