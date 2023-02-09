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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
namespace core\link;

final class metadata_info {
    /**
    * @var string|null
    */
    private $title;

    /**
    * @var string|null
    */
    private $description;

    /**
    * @var \moodle_url|null
    */
    private $url;

    /**
    * @var \moodle_url|null
    */
    private $image;

    /**
     * @var string|null
     */
    private $videoheight;

    /**
     * @var string|null
     */
    private $videowidth;

    /**
     * metadata_info constructor.
     */
    public function __construct() {
        $this->description = null;
        $this->title = null;
        $this->url = null;
        $this->image = null;
        $this->videoheight = null;
        $this->videowidth = null;
    }

    /**
     * @return string|null
     */
    public function get_title(): ?string {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function get_description(): ?string {
        return $this->description;
    }

    /**
     * @return \moodle_url|null
     */
    public function get_url(): ?\moodle_url {
        if (empty($this->url)) {
            return null;
        }

        return new \moodle_url($this->url);
    }

    /**
     * @return \moodle_url|null
     */
    public function get_image(): ?\moodle_url {
        if (empty($this->image)) {
            return null;
        }

        return new \moodle_url($this->image);
    }

    /**
     * @return int|null
     */
    public function get_video_height(): ?int {
        return $this->videoheight;
    }

    /**
     * @return int|null
     */
    public function get_video_width(): ?int {
        return $this->videowidth;
    }

    /**
     * @param array $info
     * @return metadata_info
     */
    public static function create_instance(array $info): metadata_info {
        $instance = new static();

        if (isset($info['image'])) {
            $instance->image = clean_param($info['image'], PARAM_URL);
        }

        if (isset($info['url'])) {
            $instance->url = clean_param($info['url'], PARAM_URL);
        }

        if (isset($info['video:height'])) {
            $instance->videoheight = clean_param($info['video:height'], PARAM_INT);
        }

        if (isset($info['video:width'])) {
            $instance->videowidth = clean_param($info['video:width'], PARAM_INT);
        }

        if (isset($info['title'])) {
            $instance->title = clean_param($info['title'], PARAM_TEXT);
        }

        if (isset($info['description'])) {
            $instance->description = clean_param($info['description'], PARAM_TEXT);
        }

        return $instance;
    }
}