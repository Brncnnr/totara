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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\card\builder;

use moodle_url;
use totara_msteams\botfw\card\action;
use totara_msteams\botfw\card\list_item;

/**
 * A builder class for an item in a list card.
 */
class list_item_builder {
    /** @var list_item */
    private $item;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->item = new list_item();
        $this->item->type = 'section';
    }

    /**
     * @return list_item
     */
    public function build(): list_item {
        return $this->item;
    }

    /**
     * @param string $type
     * @return self
     */
    public function type(string $type): self {
        $this->item->type = $type;
        return $this;
    }

    /**
     * @param string $id
     * @return self
     */
    public function id(string $id): self {
        $this->item->id = $id;
        return $this;
    }

    /**
     * @param string $title
     * @return self
     */
    public function title(string $title): self {
        $this->item->title = $title;
        return $this;
    }

    /**
     * @param string $subtitle
     * @return self
     */
    public function subtitle(string $subtitle): self {
        $this->item->subtitle = $subtitle;
        return $this;
    }

    /**
     * @param string|moodle_url $url
     * @return self
     */
    public function icon($url): self {
        if ($url instanceof moodle_url) {
            $url = $url->out(false);
        } else {
            $url = (string)$url;
        }
        $this->item->icon = $url;
        return $this;
    }

    /**
     * @param action $action
     * @return self
     */
    public function tap(action $action): self {
        $this->item->tap = $action->to_object();
        return $this;
    }
}
