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

use mod_facetoface\output\seminarevent_detail_section;

/**
 * A builder class for seminarevent_detail_section.
 */
final class seminarevent_detail_section_builder {
    /**
     * @var string
     */
    private $class = '';

    /**
     * @var string
     */
    private $title = '';

    /**
     * @var string
     */
    private $id = '';

    /**
     * @var string
     */
    private $intro = '';

    /**
     * @var boolean
     */
    private $divider = true;

    /**
     * @var array
     */
    private $details = [];

    /**
     * @param string $class
     */
    public function __construct(string $class = '') {
        $this->class = $class;
    }

    /**
     * Add a detail.
     *
     * @param string    $label
     * @param string    $description in text
     * @param string    $class only for backward compatibility; do not use!!
     * @return self
     */
    public function add_detail(string $label, string $description, string $class = ''): self {
        return $this->add_detail_unsafe($label, clean_string($description), $class);
    }

    /**
     * Add a detail.
     *
     * @param string    $label
     * @param string    $description in HTML; beware of XSS
     * @param string    $class only for backward compatibility; do not use!!
     * @return self
     */
    public function add_detail_unsafe(string $label, string $description, string $class = ''): self {
        $this->details[] = [
            'label' => $label,
            'description' => $description,
            'class' => $class,
        ];
        return $this;
    }

    /**
     * Set the class of this section.
     *
     * @param string $class
     * @return self
     */
    public function set_class(string $class): self {
        $this->class = $class;
        return $this;
    }

    /**
     * Set the title text of this section.
     *
     * @param string $title
     * @return self
     */
    public function set_title(string $title): self {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the summary text of this section.
     *
     * @param string $intro
     * @return self
     */
    public function set_summary(string $intro): self {
        $this->intro = $intro;
        return $this;
    }

    /**
     * Set the id text of this section.
     *
     * @param string $id
     * @return self
     */
    public function set_id(string $id): self {
        $this->id = $id;
        return $this;
    }

    /**
     * Display the divider.
     *
     * @param boolean $show
     * @return self
     */
    public function show_divider(bool $show): self {
        $this->divider = $show;
        return $this;
    }

    /**
     * Create a seminarevent_detail_section object.
     *
     * @return seminarevent_detail_section
     */
    public function build(): seminarevent_detail_section {
        return new seminarevent_detail_section(
            [
                'class' => $this->class,
                'title' => $this->title,
                'intro' => $this->intro,
                'id' => $this->id,
                'nodivider' => !$this->divider,
                'details' => $this->details,
            ]
        );
    }
}
