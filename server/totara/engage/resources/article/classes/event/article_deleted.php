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
namespace engage_article\event;

final class article_deleted extends base_article_event {
    /**
     * @return void
     */
    protected function init(): void {
        parent::init();
        $this->data['crud'] = 'd';
    }

    /**
     * @return string
     */
    public static function get_name() {
        return get_string('articledeleted', 'engage_article');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' deleted the article with id '$this->objectid'" .
            "and the name of article is '{$this->other['name']}'.";
    }

    /**
     * @return string
     */
    public function get_interaction_type(): string {
        return 'delete';
    }
}