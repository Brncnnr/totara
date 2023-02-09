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

use coding_exception;
use core\output\template;
use mod_facetoface\output\seminarevent_detail;
use mod_facetoface\output\seminarevent_actionbar;
use mod_facetoface\output\seminarevent_detail_section;

/**
 * A builder class for seminarevent_detail.
 */
final class seminarevent_detail_builder {
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
     * @var boolean
     */
    private $collapsible = false;

    /**
     * @var string
     */
    private $sectionsid = '';

    /**
     * @var array
     */
    private $sections = [];

    /**
     * @var array|null
     */
    private $action = null;

    /**
     * @param string $class
     */
    public function __construct(string $class) {
        $this->class = $class;
    }

    /**
     * Add a section.
     *
     * @param seminarevent_detail_section|seminarevent_detail_section_builder $section
     * @return self
     */
    public function add_section($section): self {
        if ($section instanceof seminarevent_detail_section_builder) {
            $data = $section->build()->get_template_data();
        } else if ($section instanceof seminarevent_detail_section) {
            $data = $section->get_template_data();
        } else {
            throw new coding_exception('$section must be seminarevent_detail_section or seminarevent_detail_section_builder');
        }
        $this->sections[] = [
            'template' => 'mod_facetoface/seminarevent_detail_section',
            'context' => $data
        ];
        return $this;
    }

    /**
     * Add a section from any template instance.
     *
     * @param template $template
     * @return self
     */
    public function add_section_template(template $template): self {
        $this->sections[] = [
            'template' => $template->get_template_name(),
            'context' => $template->get_template_data()
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
     * Set the collapsible option.
     *
     * @param boolean $collapsible
     * @param string $sectionsid
     * @return self
     */
    public function set_collapsible(bool $collapsible, string $sectionsid): self {
        if ($collapsible && $sectionsid === '') {
            throw new coding_exception('$sectionsid is mandatory if $collapsible is set to true');
        }
        $this->collapsible = $collapsible;
        $this->sectionsid = $collapsible ? $sectionsid : '';
        return $this;
    }

    /**
     * Attach an action bar.
     *
     * @param seminarevent_actionbar|null $actionbar
     * @return self
     */
    public function set_actionbar(?seminarevent_actionbar $actionbar): self {
        if ($actionbar !== null) {
            $this->action = $actionbar->get_template_data();
        } else {
            $this->action = null;
        }
        return $this;
    }

    /**
     * Create a seminarevent_detail object.
     *
     * @return seminarevent_detail
     */
    public function build(): seminarevent_detail {
        return new seminarevent_detail(
            [
                'class' => $this->class,
                'title' => $this->title,
                'id' => $this->id,
                'collapsible' => $this->collapsible,
                'sectionsid' => $this->sectionsid,
                'sections' => $this->sections,
                'action' => $this->action,
            ]
        );
    }
}
