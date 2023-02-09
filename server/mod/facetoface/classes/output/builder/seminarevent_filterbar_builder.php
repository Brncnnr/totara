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

use mod_facetoface\output\seminarevent_filterbar;

/**
 * A builder class for seminarevent_filterbar.
 */
class seminarevent_filterbar_builder {
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string[]
     */
    private $togglelabel;

    /**
     * @var \pix_icon|null
     */
    private $icon = null;

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var array
     */
    private $links = [];

    /**
     * @param string $id     part of form id
     * @param string $method get or post
     */
    public function __construct(string $id, string $method = 'get') {
        $this->id = $id;
        $this->method = $method;
    }

    /**
     * Set an icon.
     *
     * @param pix_icon|null $icon
     *
     * @return seminarevent_filterbar_builder
     */
    public function set_icon(?\pix_icon $icon): seminarevent_filterbar_builder {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Set the text of the toggle button.
     *
     * @param string $hiddentext the text displayed when the filter bar is closed
     * @param string $showntext the text displayed when the filter bar is open
     * @return seminarevent_filterbar_builder
     */
    public function set_toggle_button(string $hiddentext, string $showntext): seminarevent_filterbar_builder {
        $this->togglelabel = [
            'hidden' => $hiddentext,
            'shown' => $showntext
        ];
        return $this;
    }

    /**
     * Add a query parameter.
     *
     * @param string         $name      The name of the query parameter
     * @param string|integer $value     The value of the query parameter
     *
     * @return seminarevent_filterbar_builder
     */
    public function add_param(string $name, $value): seminarevent_filterbar_builder {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Add a select menu.
     *
     * @param string  $name     the name attribute of an element
     * @param array   $options  key/value array of options
     * @param string  $class    part of css class
     * @param string  $label    label text
     * @param boolean $disabled true to disable/hide an element
     * @param boolean $tooltips true to show tooltips on an element
     *
     * @return seminarevent_filterbar_builder
     */
    public function add_filter(string $name, array $options, string $class = '',
                               string $label = '', bool $disabled = false, bool $tooltips = false): seminarevent_filterbar_builder {
        $this->filters[$name] = [
            'options' => $options,
            'class' => $class,
            'label' => $label,
            'disabled' => $disabled,
            'tooltips' => $tooltips,
        ];
        return $this;
    }

    /**
     * Append an arbitrary link at the end of filter.
     *
     * @param string                $label  label
     * @param string|\moodle_url    $url    url
     * @return seminarevent_filterbar_builder
     */
    public function add_link(string $label, $url): seminarevent_filterbar_builder {
        if ($url instanceof \moodle_url) {
            $url = $url->out(false);
        }
        $this->links[] = [
            'label' => $label,
            'url' => $url,
        ];
        return $this;
    }

    /**
     * Create a seminarevent_filterbar object.
     *
     * @return seminarevent_filterbar
     */
    public function build(): seminarevent_filterbar {
        global $OUTPUT;
        $params = [];
        foreach ($this->params as $name => $value) {
            if (array_key_exists($name, $this->filters)) {
                continue;
            }
            $params[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        $filters = [];
        foreach ($this->filters as $name => $opts) {
            $selectedvalue = $this->params[$name] ?? null;
            $options = [];
            foreach ($opts['options'] as $optvalue => $optname) {
                $options[] = [
                    'name' => $optname,
                    'value' => $optvalue,
                    'selected' => $optvalue === $selectedvalue,
                ];
            }
            $filters[] = [
                'id' => '',
                'name' => $name,
                'class' => $opts['class'],
                'label' => $opts['label'],
                'disabled' => $opts['disabled'],
                'webkit_init_value' => $selectedvalue,
                'show_tooltips' => $opts['tooltips'],
                'options' => $options,
            ];
        }

        $data = [
            'formid' => $this->id,
            'method' => $this->method,
            'togglelabel' => $this->togglelabel,
            'params' => $params,
            'filters' => $filters,
            'links' => $this->links,
        ];

        if ($this->icon) {
            $iconattr = array(
                'template' => $this->icon->get_template(),
                'context' => $this->icon->export_for_template($OUTPUT),
            );
            $data['icon'] = $iconattr;
        } else {
            $data['icon'] = null;
        }

        return new seminarevent_filterbar($data);
    }
}
