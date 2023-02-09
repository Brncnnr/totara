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

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * A builder class to build a button of virtualroom_card.
 */
class virtualroom_card_button_builder {
    /** @var virtualroom_card_builder */
    private $owner;

    /** @var string */
    private $text;

    /** @var string */
    private $link = '#';

    /** @var string */
    private $hint = '';

    /** @var string */
    private $style = '';

    /** @var string */
    private $copyable = '';

    /**
     * @param virtualroom_card_builder $owner
     * @param string $text button label
     * @internal called from virtualroom_card_builder
     */
    public function __construct(virtualroom_card_builder $owner, string $text) {
        $this->owner = $owner;
        $this->text = $text;
    }

    /**
     * @param string|moodle_url $link uri
     * @return self
     */
    public function link($link): self {
        if ($link instanceof moodle_url) {
            $link = $link->out(false);
        }
        $this->link = (string)$link;
        return $this;
    }

    /**
     * @param string $hint accessibility label
     * @return self
     */
    public function hint(string $hint): self {
        $this->hint = $hint;
        return $this;
    }

    /**
     * @param string $style primary or default
     * @return self
     */
    public function style(string $style): self {
        $this->style = $style;
        return $this;
    }

    /**
     * @param string $text label of copy button
     * @return self
     */
    public function copyable(string $text): self {
        $this->copyable = $text;
        return $this;
    }

    /**
     * @return self
     */
    public function primary(): self {
        return $this->style('primary');
    }

    /**
     * @return virtualroom_card_builder
     */
    public function done(): virtualroom_card_builder {
        return $this->owner->add_button($this->text, $this->link, $this->style, $this->hint, $this->copyable);
    }
}
