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

class totara_msteams_botfw_builder_thumbnail_card_testcase extends advanced_testcase {
    public function test_build() {
        global $CFG;
        $card = builder::thumbnail_card()
            ->title('Test thumbnail')
            ->subtitle('Thumbnail')
            ->text('Lorem ipsum')
            ->add_image('http://example.com/pukeko.jpg')
            ->add_image('http://example.com/moa.jpg')
            ->add_button(builder::action()
                ->url('Click me', 'http://example.com/uno')
                ->build())
            ->add_button(builder::action()
                ->url('Hit me', 'http://example.com/dos')
                ->build())
            ->build();
        $this->assertEquals('application/vnd.microsoft.card.thumbnail', $card->contentType);
        $this->assertEquals('Test thumbnail', $card->content->title);
        $this->assertEquals('Thumbnail', $card->content->subtitle);
        $this->assertEquals('Lorem ipsum', $card->content->text);
        $this->assertEquals('http://example.com/pukeko.jpg', $card->content->images[0]->url);
        $this->assertEquals('http://example.com/moa.jpg', $card->content->images[1]->url);
        $this->assertEquals('openUrl', $card->content->buttons[0]->type);
        $this->assertEquals('Click me', $card->content->buttons[0]->title);
        $this->assertEquals('Click me', $card->content->buttons[0]->text);
        $this->assertEquals('http://example.com/uno', $card->content->buttons[0]->value);
        $this->assertEquals('openUrl', $card->content->buttons[1]->type);
        $this->assertEquals('Hit me', $card->content->buttons[1]->title);
        $this->assertEquals('Hit me', $card->content->buttons[1]->text);
        $this->assertEquals('http://example.com/dos', $card->content->buttons[1]->value);

        $card = builder::thumbnail_card()
            ->title('Another test thumbnail')
            ->add_image(new moodle_url('/kiwi.jpg'))
            ->tap(builder::action()
                ->im_back('tapped')
                ->build())
            ->build();
        $this->assertEquals('application/vnd.microsoft.card.thumbnail', $card->contentType);
        $this->assertEquals('Another test thumbnail', $card->content->title);
        $this->assertEquals($CFG->wwwroot . '/kiwi.jpg', $card->content->images[0]->url);
        $this->assertEquals('imBack', $card->content->tap->type);
        $this->assertEquals('tapped', $card->content->tap->value);
    }
}
