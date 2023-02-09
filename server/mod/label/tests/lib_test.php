<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package mod_label
 */

/**
 * Test for functions from lib.php
 */
class mod_label_lib_testcase extends advanced_testcase {
    public function test_get_label_name() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/label/lib.php');

        $label = new stdClass();
        $label->name = 'xyz';
        $label->introformat = FORMAT_HTML;

        $label->intro = "Some random text\nwith line breaks";
        $this->assertSame('Some random text with line breaks', get_label_name($label));

        $label->intro = "\n<h1>Me &amp; 'I'</h1>\n";
        $this->assertSame('ME &#38; &#39;I&#39;', get_label_name($label));

        $label->intro = str_repeat('x', LABEL_MAX_NAME_LENGTH - 4) . ' Hokus Pokus';
        $this->assertSame(str_repeat('x', LABEL_MAX_NAME_LENGTH - 4) . ' ...', get_label_name($label));

        $label->intro = "Some <bold>bold</bold> text";
        $this->assertSame('Some bold text', get_label_name($label));

        $label->intro = "Some <a href='https://www.example.com'>linked</a> text";
        $this->assertSame('Some linked text', get_label_name($label));

        $label->intro = "Some <!--This is a comment.--> text";
        $this->assertSame('Some text', get_label_name($label));

        $label->intro = <<<st
<!--*|IF:MC_PREVIEW_TEXT|*-->
		<!--[if !gte mso 9]><!----><span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;">*|MC_PREVIEW_TEXT|*</span><!--<![endif]-->
		<!--*|END:IF|*-->
        <center>
            <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                <tbody><tr>
                    <td align="center" valign="top" id="bodyCell">
                        <!-- BEGIN TEMPLATE // -->
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody><tr>
								<td align="center" valign="top" id="templatePreheader">
									<!--[if (gte mso 9)|(IE)]>
									<table align="center" border="0" cellspacing="0" cellpadding="0" width="600" style="width:600px;">
									<tr>
									<td align="center" valign="top" width="600" style="width:600px;">
									<![endif]-->
									<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" class="templateContainer">
										<tbody><tr>
                                			<td valign="top" class="preheaderContainer"><table class="mcnTextBlock" style="min-width:100%;" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td class="mcnTextBlockInner" style="padding-top:9px;" valign="top">
              	<!--[if mso]>
				<table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;">
				<tr>
				<![endif]-->

				<!--[if mso]>
				<td valign="top" width="600" style="width:600px;">
				<![endif]-->
                <table style="max-width:100%; min-width:100%;" class="mcnTextContentContainer" width="100%" cellspacing="0" cellpadding="0" border="0" align="left">
                    <tbody><tr>
                        <td class="mcnTextContent" style="padding: 0px 18px 9px; text-align: left;" valign="top"><h3><b>

Verijdel het stelen van je wachtwoord.
                        </b></h3></td>
                    </tr>
                </tbody></table>
				<!--[if mso]>
				</td>
				<![endif]-->
                
				<!--[if mso]>
				</tr>
				</table>
				<![endif]-->
            </td>
        </tr>
    </tbody>
</table></td>
st;
        $this->assertSame('VERIJDEL HET STELEN VAN JE WACHTWOORD.', get_label_name($label));


        $label->introformat = FORMAT_PLAIN;
        $label->intro = "Some <!--This is a comment.--> <bold>bold</bold> text";
        $this->assertSame('Some &#60;!--This is a comment.--&#62; &#60;bold&#62;bold&#60;/bold&#62; text', get_label_name($label));

        unset($label->introformat);
        $label->intro = "Some <!--This is a comment.--> text";
        $this->assertSame('Some text', get_label_name($label));
    }
}
