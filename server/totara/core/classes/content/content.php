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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */
namespace totara_core\content;

/**
 * A class that contains metadata about specific content.
 */
final class content {
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $content;

    /**
     * @var int
     */
    private $contentformat;

    /**
     * @var null
     */
    private $instanceid;

    /**
     * @var null|int
     */
    private $contextid;

    /**
     * @var string
     */
    private $component;

    /**
     * @var null
     */
    private $area;

    /**
     * A url to go the instance view. This will be helpful for notification only
     * @var null|\moodle_url
     */
    private $contexturl;

    /**
     * This is represent the actor id who is responsible to create this very content.
     * If null is set, then the function will respect global $USER.
     *
     * Note that global $USER will not sometimes be the same as the actor, as actor can be passed thru the
     * to the function as an argument.
     *
     * @var int|null
     */
    private $user_id;

    /**
     * content_info constructor.
     *
     * @param string $title
     * @param string $content
     * @param int    $contentformat
     * @param int    $instanceid
     * @param string $component
     * @param string $area
     */
    public function __construct(string $title, string $content, int $contentformat, int $instanceid,
                                string $component, string $area) {
        $this->title = $title;
        $this->content = $content;
        $this->contentformat = $contentformat;
        $this->component = $component;

        $this->contextid = null;
        $this->instanceid = $instanceid;
        $this->area = $area;

        $this->contexturl = null;
        $this->user_id = null;
    }

    /**
     * Set the actor's id, the user that is responsible to create this pretty content.
     *
     * @param int $user_id
     * @return void
     */
    public function set_user_id(int $user_id): void {
        $this->user_id = $user_id;
    }

    /**
     * @param string|\moodle_url $url
     * @return void
     */
    public function set_contexturl($url): void {
        $this->contexturl = new \moodle_url($url);
    }

    /**
     * @return \moodle_url|null
     */
    public function get_contexturl(): ?\moodle_url {
        return $this->contexturl;
    }

    /**
     * @param string                    $title
     * @param string                    $content
     * @param int                       $contentformat
     * @param int|null                  $instanceid
     * @param string                    $component
     * @param string|null               $area
     * @param int|null                  $contextid
     * @param \moodle_url|string|null   $contexturl     The url that will lead recipient to a proper page.
     *
     * @return content
     */
    public static function create(string $title, string $content, int $contentformat, int $instanceid,
                                  string $component, string $area, ?int $contextid = null,
                                  $contexturl = null): content {
        $item = new static($title, $content, $contentformat, $instanceid, $component, $area);

        if (null != $contextid) {
            $item->set_contextid($contextid);
        }

        if (null !== $contexturl) {
            $item->set_contexturl($contexturl);
        }

        return $item;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_contextid(int $value): void {
        $this->contextid = $value;
    }

    /**
     * @return int
     */
    public function get_instanceid(): int {
        return $this->instanceid;
    }

    /**
     * @return string
     */
    public function get_area(): string {
        return $this->area;
    }

    /**
     * @return int|null
     */
    public function get_contextid(): int {
        if (null == $this->contextid) {
            // No context id had been set yet. Just system context returned.s
            return \context_system::instance()->id;
        }

        return $this->contextid;
    }

    /**
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * @return string
     */
    public function get_content(): string {
        return $this->content;
    }

    /**
     * @return int
     */
    public function get_contentformat(): int {
        return $this->contentformat;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->component;
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        global $USER;
        if (empty($this->user_id)) {
            // This check will include invalid $user_id Zero.
            return $USER->id;
        }

        return $this->user_id;
    }
}