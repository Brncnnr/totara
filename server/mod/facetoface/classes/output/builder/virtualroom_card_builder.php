<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\output\builder;

use coding_exception;
use core\output\flex_icon;
use mod_facetoface\output\seminarevent_detail_section;
use mod_facetoface\output\virtualroom_card;

defined('MOODLE_INTERNAL') || die();

/**
 * A builder class for virtualroom_card.
 */
class virtualroom_card_builder {
    /** @var string */
    private $heading;

    /** @var string */
    private $subtitle = '';

    /** @var boolean */
    private $active = false;

    /** @var seminarevent_detail_section|null */
    private $details = null;

    /** @var string */
    private $instruction = '';

    /** @var string */
    private $preview = '';

    /** @var array */
    private $buttons = [];

    /** @var string */
    private $copylink = '';

    /** @var string */
    private $copylinklabel = '';

    /**
     * @param string $heading heading text
     */
    public function __construct(string $heading) {
        if ($heading === '') {
            throw new coding_exception('heading text must be provided');
        }
        $this->heading = $heading;
    }

    /**
     * @param string $heading heading text
     * @return self
     */
    public function heading(string $heading): self {
        $this->heading = $heading;
        return $this;
    }

    /**
     * @param string $subtitle subtitle text
     * @return self
     */
    public function subtitle(string $subtitle): self {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * @param boolean $active
     * @return self
     */
    public function active(bool $active = true): self {
        $this->active = $active;
        return $this;
    }

    /**
     * @param string $text
     * @return virtualroom_card_button_builder
     */
    public function button(string $text): virtualroom_card_button_builder {
        return new virtualroom_card_button_builder($this, $text);
    }

    /**
     * @param string $text button label
     * @param string $link url
     * @param string $style primary or default
     * @param string $hint accessibility label
     * @param string $copylinklabel display button to copy the url of this button
     * @return self
     */
    public function add_button(string $text, string $link, string $style, string $hint, string $copylinklabel = ''): self {
        if ($text === '') {
            throw new coding_exception('text cannot be empty');
        }
        if ($link === '') {
            throw new coding_exception('link cannot be empty');
        }
        if (!in_array($style, ['', 'primary', 'default'], true)) {
            $style = '';
        }
        $data = [
            'text' => $text,
            'url' => $link,
        ];
        if ($style !== '') {
            $data['style'] = $style;
        }
        if ($hint !== '') {
            $data['hint'] = $hint;
        }
        if ($copylinklabel !== '') {
            $this->copylink = $link;
            $this->copylinklabel = $copylinklabel;
        }
        $this->buttons[] = $data;
        return $this;
    }

    /**
     * @param seminarevent_detail_section|null $details
     * @return self
     */
    public function details(?seminarevent_detail_section $details): self {
        $this->details = $details;
        return $this;
    }

    /**
     * @param string $instruction instruction contents
     * @return self
     */
    public function instruction(string $instruction): self {
        $this->instruction = $instruction;
        return $this;
    }

    /**
     * @param string $preview preview content in html
     * @return self
     */
    public function preview(string $preview): self {
        $this->preview = $preview;
        return $this;
    }

    /**
     * @return virtualroom_card
     */
    public function build(): virtualroom_card {
        global $OUTPUT;
        $data = [
            'heading' => $this->heading,
            'simple' => !$this->details,
            'inactive' => !$this->active,
        ];
        if ($this->subtitle !== '') {
            $data['subtitle'] = $this->subtitle;
        }
        if ($this->details !== null) {
            $data['detailsection'] = $this->details->get_template_data();
        }
        if ($this->instruction !== '') {
            $data['instruction'] = $this->instruction;
        }
        if ($this->preview !== '') {
            $data['preview'] = $this->preview;
        }
        if ($this->copylink !== '') {
            $icon = new flex_icon('mod_facetoface|copy_virtual_room_link');
            $data['copy'] = [
                'text' => $this->copylinklabel,
                'icon' => [
                    'template' => $icon->get_template(),
                    'context' => $icon->export_for_template($OUTPUT),
                ],
                'url' => $this->copylink,
            ];
        }
        if (!empty($this->buttons)) {
            $data['has_buttons'] = true;
            $data['buttons'] = $this->buttons;
        }
        return new virtualroom_card($data);
    }
}
