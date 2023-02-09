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
use totara_msteams\botfw\card\hero_card;

class hero_card_builder {
    /** @var hero_card */
    private $card;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->card = new hero_card();
        $this->card->contentType = 'application/vnd.microsoft.card.hero';
        $this->card->content = (object)[
            'title' => '',
            'subtitle' => '',
            'text' => '',
            'images' => [],
            'buttons' => [],
        ];
    }

    /**
     * @return hero_card
     */
    public function build(): hero_card {
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
     * @param string $subtitle
     * @return self
     */
    public function subtitle(string $subtitle): self {
        $this->card->content->subtitle = $subtitle;
        return $this;
    }

    /**
     * @param string $text
     * @return self
     */
    public function text(string $text): self {
        $this->card->content->text = $text;
        return $this;
    }

    /**
     * @param string|moodle_url $url
     * @param string $alt
     * @return self
     */
    public function add_image($url, string $alt = ''): self {
        if ($url instanceof moodle_url) {
            $url = $url->out(false);
        } else {
            $url = (string)$url;
        }
        $this->card->content->images[] = (object)[
            'url' => $url,
            'alt' => $alt,
        ];
        return $this;
    }

    /**
     * @param action $action
     * @return self
     */
    public function add_button(action $action): self {
        $this->card->content->buttons[] = $action->to_object();
        return $this;
    }

    /**
     * @param action $action
     * @return self
     */
    public function tap(action $action): self {
        $this->card->content->tap = $action->to_object();
        return $this;
    }
}
