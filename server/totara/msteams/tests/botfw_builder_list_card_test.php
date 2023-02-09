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

use totara_msteams\botfw\builder;

class totara_msteams_botfw_builder_list_card_testcase extends advanced_testcase {
    public function test_build() {
        $card = builder::list_card()
            ->title('Test list 1st')
            ->add_button(builder::action()
                ->url('Click me', 'http://example.com/test')
                ->build())
            ->build();
        $this->assertEquals('application/vnd.microsoft.teams.card.list', $card->contentType);
        $this->assertEquals('Test list 1st', $card->content->title);
        $this->assertEquals('openUrl', $card->content->buttons[0]->type);
        $this->assertEquals('Click me', $card->content->buttons[0]->title);
        $this->assertEquals('Click me', $card->content->buttons[0]->text);
        $this->assertEquals('http://example.com/test', $card->content->buttons[0]->value);

        $card = builder::list_card()
            ->title('Test list 2nd')
            ->add_item(builder::list_item()
                ->type('resultItem')
                ->title('Test result')
                ->subtitle('Lorem ipsum dolor sit amet')
                ->icon('http://example.com/lorem.png')
                ->tap(builder::action()
                    ->url('Tap me', 'http://example.com/tap')
                    ->build())
                ->build())
            ->add_item(builder::list_item()
                ->type('person')
                ->id('JohnDoe@example.com')
                ->title('John Doe')
                ->subtitle('Manager')
                ->tap(builder::action()
                    ->im_back('whois JohnDoe@example.com')
                    ->build())
                ->build())
            ->build();
        $this->assertEquals('application/vnd.microsoft.teams.card.list', $card->contentType);
        $this->assertEquals('resultItem', $card->content->items[0]->type);
        $this->assertEquals('Test result', $card->content->items[0]->title);
        $this->assertEquals('Lorem ipsum dolor sit amet', $card->content->items[0]->subtitle);
        $this->assertEquals('http://example.com/lorem.png', $card->content->items[0]->icon);
        $this->assertEquals('openUrl', $card->content->items[0]->tap->type);
        $this->assertEquals('Tap me', $card->content->items[0]->tap->title);
        $this->assertEquals('Tap me', $card->content->items[0]->tap->text);
        $this->assertEquals('http://example.com/tap', $card->content->items[0]->tap->value);
        $this->assertEquals('person', $card->content->items[1]->type);
        $this->assertEquals('JohnDoe@example.com', $card->content->items[1]->id);
        $this->assertEquals('John Doe', $card->content->items[1]->title);
        $this->assertEquals('Manager', $card->content->items[1]->subtitle);
        $this->assertEquals('imBack', $card->content->items[1]->tap->type);
        $this->assertEquals('whois JohnDoe@example.com', $card->content->items[1]->tap->value);
    }
}
