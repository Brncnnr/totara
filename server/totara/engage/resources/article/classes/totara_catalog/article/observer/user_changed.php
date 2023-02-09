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
namespace engage_article\totara_catalog\article\observer;

use totara_catalog\observer\object_update_observer;
use engage_article\totara_engage\resource\article as resource_article;

final class user_changed extends object_update_observer {
    /**
     * @return array
     */
    public function get_observer_events(): array {
        return [
            '\core\event\user_deleted',
        ];
    }

    /**
     * @return void
     */
    protected function init_change_objects(): void {
        global $DB;
        $user_id = $this->event->objectid;

        // Fetch all the articles to be deleted from catalog.
        $sql = '
            SELECT ea.id FROM "ttr_engage_article" ea 
            INNER JOIN "ttr_engage_resource" er ON er.instanceid = ea.id 
            AND er.resourcetype = :article
            WHERE er.userid = :user_id
        ';

        $article_ids = $DB->get_fieldset_sql(
            $sql,
            [
                'article' => resource_article::get_resource_type(),
                'user_id' => $user_id
            ]
        );

        foreach ($article_ids as $article_id) {
            $this->register_for_delete($article_id);
        }
    }
}