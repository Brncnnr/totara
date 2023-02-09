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

use mod_facetoface\output\seminarevent_actionbar;

/**
 * A builder class for seminarevent_actionbar.
 */
class seminarevent_actionbar_builder {
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $class = '';

    /**
     * @var string
     */
    private $align = '';

    /**
     * Array<string, mixed>
     *
     * @var array
     */
    private $commandlinks = [];

    /**
     * @var string
     */
    private $label = '';

    /**
     * @var boolean
     */
    private $group = false;

    /**
     * @param string $id    part of element id
     */
    public function __construct(string $id) {
        $this->id = $id;
    }

    /**
     * Add a button.
     *
     * @param string             $name
     * @param string|\moodle_url $url
     * @param string|\pix_icon   $textoricon label text or icon
     * @param bool               $primary    true to accent the element
     * @param bool               $disabled   true to disable a button
     *
     * @return seminarevent_actionbar_builder
     */
    public function add_commandlink(string $name, $url, $textoricon, bool $primary = false, bool $disabled = false): seminarevent_actionbar_builder {
        global $OUTPUT;
        if ($url instanceof \moodle_url) {
            $url = $url->out(false);
        }
        $data = [
            'name' => $name,
            'href' => $url,
            'primary' => $primary,
            'disabled' => $disabled,
        ];
        if ($textoricon instanceof \pix_icon) {
            $iconattr = array(
                'template' => $textoricon->get_template(),
                'context' => $textoricon->export_for_template($OUTPUT),
            );
            $data['icon'] = $iconattr;
        } else {
            $data['text'] = (string)$textoricon;
        }
        $this->commandlinks[$name] = $data;
        return $this;
    }

    /**
     * Set the class of this bar.
     *
     * @param string $class
     *
     * @return seminarevent_actionbar_builder
     */
    public function set_class(string $class): seminarevent_actionbar_builder {
        $this->class = $class;
        return $this;
    }

    /**
     * Set the alignment of buttons.
     *
     * @param string $align     one of far, near or center
     *
     * @return seminarevent_actionbar_builder
     */
    public function set_align(string $align): seminarevent_actionbar_builder {
        $this->align = $align;
        return $this;
    }

    /**
     * Set the label text for accessibility.
     *
     * @param string $label
     * @return seminarevent_actionbar_builder
     */
    public function set_label(string $label): seminarevent_actionbar_builder {
        $this->label = $label;
        return $this;
    }

    /**
     * Set the appearance of buttons.
     *
     * @param boolean $group Set true to group buttons together.
     * @return seminarevent_actionbar_builder
     */
    public function set_buttongroup(bool $group): seminarevent_actionbar_builder {
        $this->group = $group;
        return $this;
    }

    /**
     * Create a seminarevent_actionbar object.
     *
     * @return seminarevent_actionbar
     */
    public function build(): seminarevent_actionbar {
        return new seminarevent_actionbar(
            [
                'id' => $this->id,
                'class' => $this->class,
                'align' => $this->align ?: 'near',
                'commandlinks' => array_values($this->commandlinks),
                'label' => $this->label,
                'group' => $this->group,
            ]
        );
    }
}
