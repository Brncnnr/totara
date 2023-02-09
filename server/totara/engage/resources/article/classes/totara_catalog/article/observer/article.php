<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package engage_article
 * @category totara_catalog
 */

namespace engage_article\totara_catalog\article\observer;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\observer\object_update_observer;
use core_user\totara_engage\share\recipient\user;

/**
 * update catalog data based on update or create article id
 */
class article extends object_update_observer {

    public function get_observer_events(): array {
        return [
            '\engage_article\event\article_created',
            '\engage_article\event\article_updated',
        ];
    }

    /**
     * Adds or updates an items visibility cache
     */
    private function refresh_item_cache(): void {
        global $DB;

        $sql = "SELECT er.instanceid as id, er.access, er.userid, {$DB->sql_group_concat('esr.instanceid',',')} AS accessors
                  FROM {engage_resource} er
             LEFT JOIN {engage_share} es
                    ON er.id = es.itemid
                   AND er.resourcetype = es.component
             LEFT JOIN {engage_share_recipient} esr
                    ON es.id = esr.shareid
                   AND esr.area = :area
                   and esr.component = :component
                 WHERE er.resourcetype = :type
                   AND er.instanceid = :instanceid
              GROUP BY er.instanceid, er.access, er.userid";

        $params = [
            'instanceid' => $this->event->objectid,
            'area' => user::AREA,
            'component' => 'core_user',
            'type' => \engage_article\totara_engage\resource\article::get_resource_type()
        ];

        $accessitem = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
        if ($accessitem) {
            $cache = \cache::make('engage_article', 'catalog_visibility');
            $cache->set($accessitem->id, $accessitem);
        }
    }

    /**
     * init article update object for created or updated article
     */
    protected function init_change_objects(): void {
        $this->refresh_item_cache();

        $data = new \stdClass();
        $data->objectid = $this->event->objectid;
        $data->contextid = $this->event->contextid;
        $this->register_for_update($data);
    }
}
