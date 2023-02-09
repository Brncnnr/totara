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

use coding_exception;
use totara_msteams\botfw\card\action;
use totara_msteams\botfw\card\signin_card;

/**
 * A builder class for a sign-in card.
 */
class signin_card_builder {
    /** @var signin_card */
    private $card;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->card = new signin_card();
        $this->card->contentType = 'application/vnd.microsoft.card.signin';
        $this->card->content = (object)[
            'text' => '',
            'buttons' => [],
        ];
    }

    /**
     * @return signin_card
     */
    public function build(): signin_card {
        return $this->card;
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
     * @param action $action
     * @return self
     */
    public function add_button(action $action): self {
        if (empty($action->type) || $action->type !== 'signin') {
            throw new coding_exception('$action->type must be \'signin\' at the moment');
        }
        $this->card->content->buttons[] = $action->to_object();
        return $this;
    }
}
