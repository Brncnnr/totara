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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package theme_msteams
 */

namespace theme_msteams\hook;

use context;
use moodle_url;
use totara_core\hook\base;

defined('MOODLE_INTERNAL') || die();

class get_page_navigation_hook extends base {
    /** @var string|null */
    private $tabname;

    /** @var moodle_url|null */
    private $taburl;

    /** @var boolean */
    private $customtab;

    /** @var moodle_url */
    private $pageurl;

    /** @var context */
    private $context;

    /**
     * array of additional links as ['icon' => pix_icon|flex_icon, 'text' => string, 'href' => string, 'attributes' => [name => value, ...]]
     * set false to completely hide the navigation bar.
     *
     * @var array|false
     */
    public $navigation = false;

    /**
     * Set a string text to display as alert.
     *
     * @var string|false
     */
    public $alert = false;

    /**
     * Check adding sign out link or not
     *
     * @var bool
     */
    public $has_sign_out;

    /**
     * @param context $context
     * @param moodle_url $pageurl
     * @param array $tabdata array of [name, url, redirectUrl]
     * @param moodle_url|null $taburl
     */
    public function __construct(context $context, moodle_url $pageurl, array $tabdata) {
        $this->context = $context;
        $this->pageurl = clone $pageurl;
        $this->tabname = $tabdata['name'] ?? null;
        $this->taburl = isset($tabdata['redirectUrl']) ? new moodle_url($tabdata['redirectUrl']) : null;
        $this->customtab = $tabdata['customTab'] ?? false;
        $this->has_sign_out = false;
    }

    /**
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }

    /**
     * @return string
     */
    public function get_tab_name(): ?string {
        return $this->tabname;
    }

    /**
     * @return moodle_url
     */
    public function get_tab_url(): ?moodle_url {
        return $this->taburl;
    }

    /**
     * @return moodle_url
     */
    public function get_page_url(): moodle_url {
        return $this->pageurl;
    }

    /**
     * @return boolean
     */
    public function is_custom_tab(): bool {
        return $this->customtab;
    }
}
