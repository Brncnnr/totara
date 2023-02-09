<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Russell England <russell.england@totaralms.com>
 * @package totara
 * @subpackage plan
 */

/**
 * Devplan linked evidence specific evidence dialog generator
 */

use core\entity\user;
use core\orm\entity\repository;
use totara_evidence\entity\evidence_item;
use totara_evidence\models\helpers\evidence_item_capability_helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content.class.php');

class totara_dialog_linked_evidence_content_evidence extends totara_dialog_content {

    /**
     * PHP file to use for search tab content
     *
     * @access  public
     * @var     string
     */
    public $searchtype = 'evidence_item';

    /**
     * Loads the selected evidence for this component
     *
     * @global type $DB
     * @param type $planid - plan id
     * @param type $component - component name
     * @param type $itemid - component id
     */
    public function load_selected($planid, $component, $itemid) {
        global $DB;

        $planid = (int) $planid;
        $component = (string) $component;
        $itemid = (int) $itemid;

        $sql = "
            SELECT e.id AS id, e.name AS fullname
            FROM {dp_plan_evidence_relation} er
            JOIN {totara_evidence_item} e ON e.id = er.evidenceid
            WHERE er.planid = ?
            AND er.component = ?
            AND er.itemid = ?
            ORDER BY e.name";
        $params = array($planid, $component, $itemid);

        $this->selected_items = $DB->get_records_sql($sql, $params);
    }

    /**
     * Loads all the evidence available for this user
     */
    public function load_evidence($userid) {
        $can_view_own_items_only = evidence_item_capability_helper::for_user($userid)->can_view_own_items_only();

        $this->items = evidence_item::repository()
            ->select(['id', 'name AS fullname'])
            ->where('user_id', $userid)
            ->when($can_view_own_items_only, function (repository $repository) {
                $repository->where('created_by', user::logged_in()->id);
            })
            ->order_by('name')
            ->get()
            ->all();
    }
}
