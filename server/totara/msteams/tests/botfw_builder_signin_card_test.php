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

class totara_msteams_botfw_builder_signin_card_testcase extends advanced_testcase {
    public function test_build_success() {
        $card = builder::signin_card()
            ->text('Test sign-in')
            ->add_button(builder::action()
                ->signin('Click me', 'http://example.com/test')
                ->build())
            ->build();
        $this->assertEquals('application/vnd.microsoft.card.signin', $card->contentType);
        $this->assertEquals('Test sign-in', $card->content->text);
        $this->assertEquals('signin', $card->content->buttons[0]->type);
        $this->assertEquals('Click me', $card->content->buttons[0]->title);
        $this->assertEquals('Click me', $card->content->buttons[0]->text);
        $this->assertEquals('http://example.com/test', $card->content->buttons[0]->value);
    }

    public function test_build_failure() {
        $this->expectException(\coding_exception::class);
        builder::signin_card()
            ->text('Test sign-in')
            ->add_button(builder::action()
                ->url('Click me', 'http://example.com/test')
                ->build())
            ->build();
    }
}
