<?php
/**
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
namespace totara_topic\usage;

/**
 * A Plain old PHP object - popo, to hold the neccessary data for the usage of other processing. For example,
 * this is being used to render the email output notify user owner.
 */
final class item {
    /**
     * The owner's id, of whoever will be a target of all action.
     * @var int $userid
     */
    private $userid;

    /**
     * The item name, should be the resource's name
     * @var string
     */
    private $label;

    /**
     * The context url, should be the resource's url.
     * @var \moodle_url
     */
    private $url;

    /**
     * @var string
     */
    private $component;

    /**
     * item constructor.
     *
     * @param int                   $userid
     * @param string                $label
     * @param string|\moodle_url    $url
     */
    public function __construct(int $userid, string $label, $url) {
        $this->userid = $userid;
        $this->label = $label;

        if (is_string($url)) {
            $this->url = new \moodle_url($url);
        } else if (!($url instanceof \moodle_url)) {
            throw new \coding_exception(
                "Invalid parameter \$url being passed, expecting either string or instance of " . \moodle_url::class
            );
        } else {
            $this->url = $url;
        }

        $this->component = null;
    }

    /**
     * @param string $component
     * @return void
     */
    public function set_component(string $component): void {
        if (null !== $this->component && $component !== $this->component) {
            debugging(
                "The component had already been set, and being reset again with a different value",
                DEBUG_DEVELOPER
            );
        }

        $this->component = $component;
    }

    /**
     * @return string|null
     */
    public function get_component(): ?string {
        return $this->component;
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * @return string
     */
    public function get_label(): string {
        return $this->label;
    }

    /**
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return $this->url;
    }
}