<?php

/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\output\builder;

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\output\seminarevent_dashboard_action;
use moodle_url;
use pix_icon;

/**
 * A builder class for seminarevent_dashboard_action.
 */
final class seminarevent_dashboard_action_builder {
    /** @var array button info */
    private $button = array();

    /** @var array of action icons */
    private $icons = array();

    /** @var array of action links */
    private $links = array();

    /** @var array of action menu items */
    private $menus = array();

    /** @var string */
    private $nonactionable = '';

    /** @var string */
    private $menubutton = array();

    /**
     * Set the text and the url of a button.
     *
     * @param string $text button label
     * @param string|\moodle_url $url
     * @return self
     */
    public function set_button(string $text, $url): self {
        if ($text === '') {
            $this->button = [];
            return $this;
        }
        if ($url instanceof \moodle_url) {
            $url = $url->out(false);
        }
        $this->button = [
            'text' => $text,
            'link' => $url,
        ];
        return $this;
    }

    /**
     * Add a menu item.
     *
     * @param string $text
     * @param string|\moodle_url $url
     * @param \pix_icon|null $icon
     * @return self
     */
    public function add_menu(string $text, $url, ?\pix_icon $icon = null): self {
        if ($url instanceof \moodle_url) {
            $url = $url->out(false);
        }
        $this->menus[] = [
            'text' => $text,
            'link' => $url,
            'icon' => $icon,
        ];
        return $this;
    }

    /**
     * Add a separator menu item.
     *
     * @return self
     */
    public function add_menu_separator(): self {
        $this->menus[] = [
            'separator' => true,
        ];
        return $this;
    }

    /**
     * Set the text and the icon of the drop-down button.
     *
     * @param string $text
     * @param \pix_icon|null $icon
     * @return self
     */
    public function set_menu_button(string $text, ?\pix_icon $icon = null): self {
        $this->menubutton = [
            'text' => $text,
            'icon' => $icon,
        ];
        return $this;
    }

    /**
     * Add an action icon.
     *
     * @param \pix_icon          $icon
     * @param string|\moodle_url $url
     * @return self
     */
    public function add_action_icon(\pix_icon $icon, $url): self {
        if ($url instanceof \moodle_url) {
            $url = $url->out(false);
        }
        $this->icons[] = [
            'icon' => $icon,
            'link' => $url,
        ];
        return $this;
    }

    /**
     * Add an action link.
     *
     * @param string             $text link label
     * @param string|\moodle_url $url
     * @return self
     */
    public function add_action_link(string $text, $url): self {
        if ($url instanceof \moodle_url) {
            $url = $url->out(false);
        }
        $this->links[] = [
            'text' => $text,
            'link' => $url,
        ];
        return $this;
    }

    /**
     * Set the text that is visible if no buttons and no links are visible.
     *
     * @param string $text
     * @return self
     */
    public function set_non_actionable(string $text): self {
        $this->nonactionable = $text;
        return $this;
    }

    /**
     * Templatise pix_icon.
     *
     * @param \pix_icon|null $icon
     * @return array|null
     */
    private function icon_output(?\pix_icon $icon): ?array {
        global $OUTPUT;
        if ($icon) {
            return [
                'template' => $icon->get_template(),
                'context' => $icon->export_for_template($OUTPUT),
            ];
        }
        return null;
    }

    /**
     * Create an seminarevent_dashboard_action object.
     *
     * @return seminarevent_dashboard_action
     */
    public function build(): seminarevent_dashboard_action {
        $data = [];
        $data['noactions'] = empty($this->button) && empty($this->icons) && empty($this->links) && empty($this->menus);
        $data['nonactionable'] = $this->nonactionable;
        $data['hasbuttons'] = !empty($this->button) || !empty($this->menus);
        $data['button'] = $this->button;
        $data['menuid'] = 'mod_facetoface__actionmenu-'.mt_rand();
        $data['hasmenus'] = !empty($this->menus);
        $data['menutext'] = $this->menubutton['text'] ?? '';
        $data['menuicon'] = self::icon_output($this->menubutton['icon'] ?? null);
        $data['menus'] = array_map(function ($menu) {
            $out = $menu;
            $out['icon'] = self::icon_output($menu['icon'] ?? null);
            return $out;
        }, $this->menus);
        $data['hasicons'] = !empty($this->icons);
        $data['icons'] = array_map(function ($icon) {
            $out = $icon;
            $out['icon'] = self::icon_output($icon['icon']);
            return $out;
        }, $this->icons);
        $data['haslinks'] = !empty($this->links);
        $data['links'] = $this->links;
        return new seminarevent_dashboard_action($data);
    }
}
