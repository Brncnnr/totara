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

class totara_msteams_botfw_builder_hero_card_testcase extends advanced_testcase {
    public function test_build() {
        $card = builder::hero_card()
            ->title('Test hero')
            ->subtitle('For heroes')
            ->text('Lorem ipsum')
            ->add_image('http://example.com/hero.jpg')
            ->add_button(builder::action()
                ->url('Click me', 'http://example.com/test')
                ->build())
            ->build();
        $this->assertEquals('application/vnd.microsoft.card.hero', $card->contentType);
        $this->assertEquals('Test hero', $card->content->title);
        $this->assertEquals('For heroes', $card->content->subtitle);
        $this->assertEquals('Lorem ipsum', $card->content->text);
        $this->assertEquals('http://example.com/hero.jpg', $card->content->images[0]->url);
        $this->assertEquals('openUrl', $card->content->buttons[0]->type);
        $this->assertEquals('Click me', $card->content->buttons[0]->title);
        $this->assertEquals('Click me', $card->content->buttons[0]->text);
        $this->assertEquals('http://example.com/test', $card->content->buttons[0]->value);

        $card = builder::hero_card()
            ->title('Test hero')
            ->subtitle('For heroes')
            ->text('Lorem ipsum')
            ->add_image('http://example.com/hero.jpg')
            ->tap(builder::action()
                ->url('Click me', 'http://example.com/test')
                ->build())
            ->build();
        $this->assertEquals('application/vnd.microsoft.card.hero', $card->contentType);
        $this->assertEquals('Test hero', $card->content->title);
        $this->assertEquals('For heroes', $card->content->subtitle);
        $this->assertEquals('Lorem ipsum', $card->content->text);
        $this->assertEquals('http://example.com/hero.jpg', $card->content->images[0]->url);
        $this->assertEquals('openUrl', $card->content->tap->type);
        $this->assertEquals('Click me', $card->content->tap->title);
        $this->assertEquals('Click me', $card->content->tap->text);
        $this->assertEquals('http://example.com/test', $card->content->tap->value);
    }
}
