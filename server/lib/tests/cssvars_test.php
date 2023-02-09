<?php
/*
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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

class core_cssvars_testcase extends basic_testcase {
    public function test_values_replaced() {
        $cssvars = new \core\cssvars();
        $css = ':root { --val: 5px; } a { width: var(--val); }';
        $transformed = $cssvars->transform($css);
        $this->assertStringContainsString('a { width: 5px; }', $transformed);
    }

    public function test_var_references_resolved() {
        $cssvars = new \core\cssvars();
        // try referencing vars in various orders
        $css = ":root { --w: 5px; --w-2: var(--w); --h-2: var(--h); --h: 10px; /* --h: 20px; */ " .
            "--m: 1px; --m-2: var(--m); --m: 2px; } a { width: var(--w-2); height: var(--h-2); margin: var(--m-2); }";
        $transformed = $cssvars->transform($css);
        $this->assertStringContainsString('a { width: 5px; height: 10px; margin: 2px; }', $transformed);

        // test multiline
        $css = ":root\n{\n--bg:\n#06c\n;\n}\n\n#foo\n{\nbackground-color:\nvar(--bg)\n;\n}\n";
        $transformed = $cssvars->transform($css);
        $this->assertStringContainsString("#foo\n{\nbackground-color:\n#06c\n;\n}\n", $transformed);
    }

    public function test_var_compat() {
        $cssvars = new \core\cssvars();

        // test single var transform and ignoring of commented var
        $css = ':root{--bg:#06c;/*--bg:red;*/}';
        $transformed = $cssvars->transform($css);
        $this->assertEquals('#cssVarCompatRoot{-var--bg:#06c;--bg:#06c;}', $transformed);

        // test multiline
        $css = ":root\n{\n--bg:\n#06c\n;\n}";
        $transformed = $cssvars->transform($css);
        $this->assertEquals("#cssVarCompatRoot{-var--bg:\n#06c;--bg:\n#06c;}", $transformed);

        // test repeat transform
        $css = ':root{--bg:#06c;-var--bg:red;}';
        $transformed = $cssvars->transform($css);
        $this->assertEquals('#cssVarCompatRoot{-var--bg:#06c;--bg:#06c;}:root{-var--bg:red;}', $transformed);
    }

    public function test_value_fallback() {
        $cssvars = new \core\cssvars();

        $css = 'a { width: var(--val, 20px); }';
        $transformed = $cssvars->transform($css);
        $this->assertStringContainsString('a { width: 20px; }', $transformed);

        $css = ':root { --val: 30px; } a { width: var(--val, 20px); }';
        $transformed = $cssvars->transform($css);
        $this->assertStringContainsString('a { width: 30px; }', $transformed);
    }

    public function test_nested_calc_replacing() {
        $cssvars = new \core\cssvars();

        // basic
        $css = '.foo { width: calc(100px - calc(50px / 2)); }';
        $transformed = $cssvars->transform($css);
        $this->assertEquals('.foo { width: calc(100px - (50px / 2)); }', $transformed);

        // content before and after
        $css = '/* hello */ .foo { width: calc(100px - calc(50px / 2)); } /* goodbye */';
        $transformed = $cssvars->transform($css);
        $this->assertEquals('/* hello */ .foo { width: calc(100px - (50px / 2)); } /* goodbye */', $transformed);

        // via vars
        $css = ':root { --fsz: 16px; --h: calc(var(--fsz) * 1.333); } .el { height: calc(var(--h) + 2px) }';
        $transformed = $cssvars->transform($css);
        $this->assertStringContainsString('.el { height: calc((16px * 1.333) + 2px) }', $transformed);
    }

    public function test_nested_calc_throws_on_unbalanced_parens() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Unbalanced parentheses at index 14: calc(100px - calc(50px / 2); a: exp());');
        $cssvars = new \core\cssvars();
        $css = '.foo { width: calc(100px - calc(50px / 2); a: exp()); }';
        $cssvars->transform($css);
    }

    public function test_nested_calc_throws_on_unbalanced_parens_2() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Unbalanced parentheses at index 14: calc(100px - calc(50px / 2); }');
        $cssvars = new \core\cssvars();
        $css = '.foo { width: calc(100px - calc(50px / 2); }';
        $cssvars->transform($css);
    }
}
