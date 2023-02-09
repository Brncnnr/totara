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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_article
 */
namespace engage_article\totara_engage\modal;

use context_user;
use core\entity\user;
use engage_article\totara_engage\resource\article;
use totara_engage\modal\modal;
use totara_tui\output\component;

/**
 * A modal medata for the front-end component.
 */
final class article_modal extends modal {
    /**
     * @return component
     */
    public function get_vue_component(): component {
        return new component('engage_article/components/CreateArticle');
    }

    /**
     * @return string
     */
    public function get_label(): string {
        return get_string('resource', 'engage_article');
    }

    /**
     * @return bool
     */
    public function is_expandable(): bool {
        return true;
    }

    /**
     * @return int
     */
    public function get_order(): int {
        return 2;
    }

    /**
     * @return bool
     */
    public function show_modal(): bool {
        global $USER;

        return article::can_create($USER->id);
    }

    /**
     * @return bool
     */
    public function can_share(): bool {
        return has_capability('engage/article:share', context_user::instance(user::logged_in()->id));
    }
}