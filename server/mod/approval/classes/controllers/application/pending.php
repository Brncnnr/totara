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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers\application;

use container_approval\approval as approval_container;
use context;
use totara_mvc\tui_view;

/**
 * The application dashboard.
 */
class pending extends base {

    protected $layout = 'noblocks';

    /**
    * @inheritDoc
    */
    public function setup_context(): context {
        return approval_container::get_default_category_context();
    }

    /**
    * @inheritDoc
    */
    public function process(string $action = '') {
        parent::process($action);
    }

    /**
    * @return tui_view
    */
    public function action(): tui_view {
        $this->set_url(self::get_url());
        $this->get_page()->set_title(get_string('applications_awaiting_response', 'mod_approval'));

        return parent::create_tui_view('mod_approval/pages/Pending');
    }

    /**
    * @inheritDoc
    */
    public static function get_base_url(): string {
        return '/mod/approval/application/pending.php';
    }

    /**
     * @inheritDoc
     */
    public static function get_url_for(int $application_id): string {
        return self::get_url();
    }
}
