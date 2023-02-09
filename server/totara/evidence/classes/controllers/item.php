<?php
/**
 * This file is part of Totara Learn
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\controllers;

use context;
use context_user;
use core\entity\user;
use moodle_url;
use totara_core\advanced_feature;
use totara_evidence\models;
use totara_evidence\models\helpers\evidence_item_capability_helper;
use totara_evidence\totara\menu\my_evidence;
use totara_mvc\controller;

abstract class item extends controller {

    protected const RETURN_VIEW  = 'view';
    protected const RETURN_INDEX = 'index';

    /**
     * @var user
     */
    protected $user;

    /**
     * @var models\evidence_item
     */
    protected $item;

    protected function setup_context(): context {
        return context_user::instance($this->user->id);
    }

    public function __construct() {
        require_login();

        if ($item_id = $this->get_optional_param('id', null, PARAM_INT)) {
            $this->item = models\evidence_item::load_by_id($item_id);
            $this->user = $this->item->user;
        } else {
            $user_id = $this->get_optional_param('user_id', user::logged_in()->id, PARAM_INT);
            $this->user = new user($user_id);
        }

        parent::__construct();
    }

    protected function authorize(): void {
        parent::authorize();

        advanced_feature::require('evidence');

        if (isset($this->item)) {
            $this->check_capability(evidence_item_capability_helper::for_item($this->item));
        } else {
            $this->check_capability(evidence_item_capability_helper::for_user($this->user->id));
        }
    }

    /**
     * Check the relevant capability via the capability helper.
     *
     * @param evidence_item_capability_helper $capability_helper
     * @return void
     */
    abstract protected function check_capability(evidence_item_capability_helper $capability_helper);

    public function action() {
        if ($this->is_for_another_user()) {
            $page_title = get_string('evidence_bank_for_x', 'totara_evidence', $this->user->fullname);
        } else {
            $page_title = get_string('evidence_bank', 'totara_evidence');
            $this->get_page()->set_totara_menu_selected(my_evidence::class);
        }
        $this->get_page()->navigation->extend_for_user((object) $this->user->to_array());
        $this->get_page()->navbar->add($page_title, new moodle_url('/totara/evidence/index.php', ['user_id' => $this->user->id]));
    }

    /**
     * Is this for another user?
     *
     * @return bool
     */
    protected function is_for_another_user(): bool {
        $is_for = $this->get_optional_param('for', null, PARAM_ALPHA);
        if ($is_for === 'other') {
            return true;
        }

        return !$this->user->is_logged_in();
    }

    /**
     * Get what page to return to from current parameters
     *
     * @param models\evidence_item $item
     * @param string $param_name
     * @return moodle_url
     */
    public static function get_return_url(models\evidence_item $item, string $param_name = 'return_to'): moodle_url {
        $param = optional_param($param_name, self::RETURN_INDEX, PARAM_URL);
        switch ($param) {
            case self::RETURN_VIEW:
                return new moodle_url('/totara/evidence/view.php', ['id' => $item->get_id()]);
            case self::RETURN_INDEX:
                return new moodle_url('/totara/evidence/index.php', ['user_id' => $item->user_id]);
            default:
                return new moodle_url($param);
        }
    }

    /**
     * Adds the current page's return url parameter to a url
     *
     * @param moodle_url $url
     * @return moodle_url
     */
    public static function apply_return_url(moodle_url $url): moodle_url {
        $param = optional_param('return_to', false, PARAM_URL);
        if ($param) {
            $url->param('return_to', $param);
        }
        return $url;
    }

}
