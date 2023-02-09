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
 * @package totara_playlist
 * @category totara_catalog
 */

namespace totara_playlist\totara_catalog\playlist\dataholder_factory;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataholder_factory;
use totara_catalog\dataholder;
use totara_catalog\dataformatter\formatter;
use totara_catalog\dataformatter\fts;
use totara_catalog\dataformatter\ordered_list;


class topics extends dataholder_factory {

    /**
     * Override the parent dataholder completely so we can rename tags => topics.
     *
     * @return array
     */
    public static function get_dataholders(): array {
        global $CFG, $DB;

        if (empty($CFG->usetags)) {
            return [];
        }

        $collectionid = \core_tag_area::get_collection('totara_playlist', 'playlist');
        $coll = $DB->get_record('tag_coll', ['id' => $collectionid], '*', MUST_EXIST);
        $displayname = \core_tag_collection::display_name($coll);

        if (!\core_tag_area::is_enabled('totara_playlist', 'playlist')) {
            return [];
        }

        return [
            new dataholder(
                'ftstags',
                $displayname,
                [formatter::TYPE_FTS => new fts('ftstags.tags')],
                [
                    'ftstags' =>
                        "LEFT JOIN (SELECT ti.itemid, {$DB->sql_group_concat('t.name',',')} AS tags
                                      FROM {tag_instance} ti
                                      JOIN {tag} t ON t.id = ti.tagid
                                     WHERE ti.itemtype = 'playlist'
                                     GROUP BY ti.itemid) ftstags
                           ON ftstags.itemid = base.id"
                ]
            ),
            new dataholder(
                'tags',
                $displayname,
                [
                    formatter::TYPE_PLACEHOLDER_TEXT => new ordered_list('tags.tags'),
                ],
                [
                    'tags' =>
                        "LEFT JOIN (SELECT ti.itemid, {$DB->sql_group_concat('t.rawname',',')} AS tags
                                      FROM {tag_instance} ti
                                      JOIN {tag} t ON t.id = ti.tagid
                                     WHERE ti.itemtype = 'playlist'
                                     GROUP BY ti.itemid) tags
                           ON tags.itemid = base.id"
                ]
            ),
        ];
    }
}
