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

use totara_msteams\botfw\card\action;
use totara_msteams\botfw\card\list_card;
use totara_msteams\botfw\card\list_item;

/**
 * A builder class for a list card.
 */
class list_card_builder {
    /** @var list_card */
    private $card;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->card = new list_card();
        $this->card->contentType = 'application/vnd.microsoft.teams.card.list';
        $this->card->content = (object)[
            'title' => '',
            'items' => [],
            'buttons' => [],
        ];
    }

    /**
     * @return list_card
     */
    public function build(): list_card {
        return $this->card;
    }

    /**
     * @param string $title
     * @return self
     */
    public function title(string $title): self {
        $this->card->content->title = $title;
        return $this;
    }

    /**
     * Add a list item.
     *
     * @param list_item $item
     * @return self
     */
    public function add_item(list_item $item): self {
        $this->card->content->items[] = $item->to_object();
        return $this;
    }

    /**
     * Add a button.
     * WARNING: Buttons are not shown on mobile.
     *
     * @param action $action
     * @return self
     */
    public function add_button(action $action): self {
        $this->card->content->buttons[] = $action->to_object();
        return $this;
    }
}
