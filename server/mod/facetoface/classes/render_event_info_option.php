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

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * A set of render options that is passed to mod_facetoface_renderer::render_seminar_event_details()
 */
final class render_event_info_option {
    /** @var boolean */
    private $calendaroutput = false;

    /** @var boolean */
    private $displaycapacity = true;

    /** @var boolean */
    private $displaysignupinfo = false;

    /** @var boolean */
    private $displayrooms = false;

    /** @var boolean */
    private $displayassets = false;

    /** @var boolean */
    private $displayassetsinsessions = false;

    /** @var boolean */
    private $displayediteventlink = false;

    /** @var boolean */
    private $backtoallsessions = false;

    /** @var boolean */
    private $backtoeventinfo = false;

    /** @var string */
    private $heading = '';

    /** @var string|null */
    private $backurl = null;

    /** @var string|null */
    private $pageurl = null;

    /** @var int */
    private $singlesession = 0;

    /**
     * @return boolean
     */
    public function get_calendaroutput(): bool {
        return $this->calendaroutput;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function set_calendaroutput(bool $value): self {
        $this->calendaroutput = $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function get_displaycapacity(): bool {
        return $this->displaycapacity;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function set_displaycapacity(bool $value): self {
        $this->displaycapacity = $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function get_displaysignupinfo(): bool {
        return $this->displaysignupinfo;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function set_displaysignupinfo(bool $value): self {
        $this->displaysignupinfo = $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function get_displayrooms(): bool {
        return $this->displayrooms;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function set_displayrooms(bool $value): self {
        $this->displayrooms = $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function get_displayassets(): bool {
        return $this->displayassets;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function set_displayassets(bool $value): self {
        $this->displayassets = $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function get_displayassetsinsessions(): bool {
        return $this->displayassetsinsessions;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function set_displayassetsinsessions(bool $value): self {
        $this->displayassetsinsessions = $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function get_displayediteventlink(): bool {
        return $this->displayediteventlink;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function set_displayediteventlink(bool $value): self {
        $this->displayediteventlink = $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function get_backtoallsessions(): bool {
        return $this->backtoallsessions;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function set_backtoallsessions(bool $value): self {
        $this->backtoallsessions = $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function get_backtoeventinfo(): bool {
        return $this->backtoeventinfo;
    }

    /**
     * @param boolean $value
     * @return self
     */
    public function set_backtoeventinfo(bool $value): self {
        $this->backtoeventinfo = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function get_heading(): string {
        return $this->heading;
    }

    /**
     * @param string $value
     * @return self
     */
    public function set_heading(string $value): self {
        $this->heading = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function get_backurl(): string {
        global $PAGE;
        if ($this->backurl === null) {
            return $PAGE->has_set_url() ? $PAGE->url : '';
        }
        return $this->backurl;
    }

    /**
     * @param string|null $value
     * @return self
     */
    public function set_backurl(?string $value): self {
        $this->backurl = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function get_pageurl(): string {
        return $this->pageurl ?? '';
    }

    /**
     * @param string|null $value
     * @return self
     */
    public function set_pageurl(?string $value): self {
        $this->pageurl = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function get_current_url(): string {
        return $this->get_pageurl() ?: $this->get_backurl();
    }

    /**
     * @return int
     */
    public function get_singlesession(): int {
        return $this->singlesession;
    }

    /**
     * @param int $value
     * @return self
     */
    public function set_singlesession(int $value): self {
        $this->singlesession = $value;
        return $this;
    }
}
