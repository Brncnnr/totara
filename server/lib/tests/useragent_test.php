<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests the user agent class.
 *
 * @package    core
 * @copyright  2013 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * User agent test suite.
 *
 * @package    core
 * @copyright  2013 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_useragent_testcase extends advanced_testcase {

    /**
     * Restores the user agent to the default one.
     */
    protected function tearDown(): void {
        core_useragent::instance(true);
        parent::tearDown();
    }

    public function user_agents_providers() {
        // Note: When adding new entries to this list, please ensure that any new browser versions are added to the corresponding list.
        // This ensures that regression tests are applied to all known user agents.
        return array(
            // Microsoft Edge 12 for Windows 10 Desktop.
            array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.10136',
                array(
                    'is_edge'                       => true,
                    'check_edge_version'            => array(
                        '12'                        => true,
                    ),

                    // Edge pretends to be WebKit.
                    'is_webkit'                     => true,

                    // Edge pretends to be Chrome.
                    // Note: Because Edge pretends to be Chrome, it will not be picked up as a Safari browser.
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                    ),

                    'versionclasses'                => array(
                        'msedge',
                    ),

                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // Microsoft Edge 16 for Windows 10 Desktop.
            array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 Edge/16.16257',
                array(
                    'is_edge'                       => true,
                    'check_edge_version'            => array(
                        '12'                        => true,
                        '16'                        => true,
                        '17'                        => false,
                    ),

                    // Edge pretends to be WebKit.
                    'is_webkit'                     => true,

                    // Edge pretends to be Chrome.
                    // Note: Because Edge pretends to be Chrome, it will not be picked up as a Safari browser.
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                        '58'                        => true,
                        '59'                        => false,
                    ),

                    'versionclasses'                => array(
                        'msedge',
                    ),

                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // Microsoft Lumia 950.
            array(
                'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/13.10586',
                array(
                    'is_edge'                       => true,
                    'check_edge_version'              => array(
                        '12'                        => true,
                    ),

                    // Edge pretends to be WebKit.
                    'is_webkit'                     => true,

                    // Mobile Edge pretends to be Android.
                    'is_webkit_android'             => true,
                    'check_webkit_android_version'  => array(
                        '525'                       => true,
                        '527'                       => true,
                    ),

                    // Edge pretends to be Chrome.
                    // Note: Because Edge pretends to be Chrome, it will not be picked up as a Safari browser.
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                    ),

                    'versionclasses'                => array(
                        'msedge',
                        'msmobile',
                    ),

                    'devicetype'                    => 'mobile',

                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // Windows 98; Internet Explorer 5.0.
            array(
                'Mozilla/4.0 (compatible; MSIE 5.00; Windows 98)',
                array(
                    // MSIE 5.0 is not considered a browser at all: known false results.
                    'is_ie'                         => false,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                    ),
                    'versionclasses'                => array(
                        // IE 5.0 is not considered a browser.
                    ),

                    // IE 5.0 is a legacy browser.
                    'devicetype'                    => 'legacy',

                    'supports_svg'                  => false,
                    'supports_json_contenttype'     => false,
                ),
            ),

            // Windows 2000; Internet Explorer 5.5.
            array(
                'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                    ),

                    // IE 6.0 is a legacy browser.
                    'devicetype'                    => 'legacy',

                    'supports_svg'                  => false,
                    'supports_json_contenttype'     => false,
                ),
            ),

            // Windows XP SP2; Internet Explorer 6.0.
            array(
                'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie6',
                    ),

                    // IE 7.0 is a legacy browser.
                    'devicetype'                    => 'legacy',

                    'supports_svg'                  => false,
                    'supports_json_contenttype'     => false,
                ),
            ),

            // Windows XP SP2; Internet Explorer 7.0.
            array(
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; YPC 3.0.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie7',
                    ),

                    'supports_svg'                  => false,
                    'supports_json_contenttype'     => false,
                ),
            ),

            // Windows XP SP2; Internet Explorer 7.0; Meridio extension.
            array(
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Meridio for Excel 5.0.251; Meridio for PowerPoint 5.0.251; Meridio for Word 5.0.251; Meridio Protocol; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; .NET CLR 3.0.04506.648; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie7',
                    ),

                    'supports_svg'                  => false,
                    'supports_json_contenttype'     => false,
                ),
            ),

            // Windows Vista; Internet Explorer 8.0.
            array(
                'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET CLR 3.0.04506.30; .NET CLR 3.0.04506.648)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie8',
                    ),

                    'supports_svg'                  => false,
                ),
            ),

            // Windows 7; Internet Explorer 9.0.
            array(
                'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                        '9.0'                       => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie9',
                    ),
                ),
            ),

            // Windows 7; Internet Explorer 9.0i.
            array(
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                        '9.0'                       => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie9',
                    ),
                    'iecompatibility'               => true,

                    // IE 9 in Compatiblity mode does not support SVG.
                    'supports_svg'                  => false,

                    // IE in Compatiblity mode does not support JSON ContentType.
                    'supports_json_contenttype'     => false,
                ),
            ),

            // Windows 8; Internet Explorer 10.0.
            array(
                'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0; Touch)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                        '9.0'                       => true,
                        '10'                        => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie10',
                    ),
                ),
            ),

            // Windows 8; Internet Explorer 10.0i.
            array(
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.2; Trident/6.0; Touch; .NET4.0E; .NET4.0C; Tablet PC 2.0)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                        '9.0'                       => true,
                        '10'                        => true,
                    ),
                    'iecompatibility'               => true,
                    'versionclasses'                => array(
                        'ie',
                        'ie10',
                    ),

                    // IE in Compatiblity mode does not support JSON ContentType.
                    'supports_json_contenttype'     => false,
                ),
            ),

            // Windows 8.1; Internet Explorer 11.0.
            array(
                'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                        '9.0'                       => true,
                        '10'                        => true,
                        '11'                        => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie11',
                    ),

                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // Windows 8.1; Internet Explorer 11.0i.
            array(
                ' Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                        '9.0'                       => true,
                        '10'                        => true,
                        '11'                        => true,
                    ),
                    'iecompatibility'               => true,
                    'versionclasses'                => array(
                        'ie',
                        'ie11',
                    ),

                    // IE in Compatiblity mode does not support JSON ContentType.
                    'supports_json_contenttype'     => false,
                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // Windows XP; Firefox 1.0.6.
            array(
                'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.10) Gecko/20050716 Firefox/1.0.6',
                array(
                    'is_firefox'                    => true,

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                        'gecko17',
                    ),
                ),
            ),

            // Windows XP; Firefox 1.0.6.
            array(
                'Mozilla/5.0 (Windows; U; Windows NT 5.1; nl; rv:1.8) Gecko/20051107 Firefox/1.5',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                        'gecko18',
                    ),
                ),
            ),

            // Windows XP; Firefox 1.5.0.1.
            array(
                'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.8.0.1) Gecko/20060111 Firefox/1.5.0.1',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                        'gecko18',
                    ),
                ),
            ),

            // Windows XP; Firefox 2.0.
            array(
                'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                        'gecko18',
                    ),
                ),
            ),

            // Ubuntu Linux amd64; Firefox 2.0.
            array(
                'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.8.1) Gecko/20060601 Firefox/2.0 (Ubuntu-edgy)',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                        'gecko18',
                    ),
                ),
            ),

            // SUSE; Firefox 3.0.6.
            array(
                'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.0.6) Gecko/2009012700 SUSE/3.0.6-1.4 Firefox/3.0.6',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                        'gecko19',
                    ),
                ),
            ),

            // Linux i686; Firefox 3.6.
            array(
                'Mozilla/5.0 (X11; Linux i686; rv:2.0) Gecko/20100101 Firefox/3.6',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '3.6'                       => true,
                        '20100101'                  => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                        'gecko20',
                    ),
                ),
            ),

            // Windows; Firefox 11.0.
            array(
                'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko Firefox/11.0',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                        '4'                         => true,
                        '10'                        => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '20100101'                  => true,
                        '3.6'                       => true,
                        '4.0'                       => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                    ),
                ),
            ),

            // Windows; Firefox 15.0a2.
            array(
                'Mozilla/5.0 (Windows NT 6.1; rv:15.0) Gecko/20120716 Firefox/15.0a2',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                        '4'                         => true,
                        '10'                        => true,
                        '15'                        => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '20100101'                  => true,
                        '3.6'                       => true,
                        '4.0'                       => true,
                        '15.0'                      => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                    ),
                ),
            ),

            // Firefox 18; Mac OS X 10.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:18.0) Gecko/18.0 Firefox/18.0',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                        '4'                         => true,
                        '10'                        => true,
                        '15'                        => true,
                        '18'                        => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '3.6'                       => true,
                        '4.0'                       => true,
                        '15.0'                      => true,
                        '18.0'                      => true,
                        '20100101'                  => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                    ),
                ),
            ),

            // Firefox 33; Mac OS X 10.10.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:33.0) Gecko/20100101 Firefox/33.0',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                        '4'                         => true,
                        '10'                        => true,
                        '15'                        => true,
                        '18'                        => true,
                        '19'                        => true,
                        '33'                        => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '3.6'                       => true,
                        '4.0'                       => true,
                        '15.0'                      => true,
                        '18.0'                      => true,
                        '19.0'                      => true,
                        '20100101'                  => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                    ),
                ),
            ),

            // Firefox 55; Mac OS X 10.13.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:55.0) Gecko/20100101 Firefox/55.0',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                        '4'                         => true,
                        '10'                        => true,
                        '15'                        => true,
                        '18'                        => true,
                        '19'                        => true,
                        '33'                        => true,
                        '55'                        => true,
                        '56'                        => false,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '3.6'                       => true,
                        '4.0'                       => true,
                        '15.0'                      => true,
                        '18.0'                      => true,
                        '19.0'                      => true,
                        '20100101'                  => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                    ),
                ),
            ),

            // Firefox 97; Windows 10.
            array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                        '4'                         => true,
                        '10'                        => true,
                        '15'                        => true,
                        '18'                        => true,
                        '19'                        => true,
                        '33'                        => true,
                        '55'                        => true,
                        '56'                        => true,
                        '97'                        => true,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '3.6'                       => true,
                        '4.0'                       => true,
                        '15.0'                      => true,
                        '18.0'                      => true,
                        '19.0'                      => true,
                        '20100101'                  => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                    ),
                ),
            ),

            // Firefox 100; Windows 10.
            array(
                'Mozilla/5.0 (Windows NT 10.0; rv:100.0) Gecko/20100101 Firefox/100.0',
                array(
                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                        '4'                         => true,
                        '10'                        => true,
                        '15'                        => true,
                        '18'                        => true,
                        '19'                        => true,
                        '33'                        => true,
                        '55'                        => true,
                        '56'                        => true,
                        '97'                        => true,
                        '100'                       => true,
                        '101'                       => false,
                    ),

                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '3.6'                       => true,
                        '4.0'                       => true,
                        '15.0'                      => true,
                        '18.0'                      => true,
                        '19.0'                      => true,
                        '20100101'                  => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                    ),

                    'is_totara_legacy_browser'      => false,
                ),
            ),

            // SeaMonkey 2.0; Windows.
            array(
                'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1b3pre) Gecko/20081208 SeaMonkey/2.0',
                array(
                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051106'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                        'gecko19',
                    ),
                ),
            ),

            // SeaMonkey 2.1; Linux.
            array(
                'Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20110609 Firefox/4.0.1 SeaMonkey/2.1',
                array(
                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '20100101'                  => true,
                        '3.6'                       => true,
                        '4.0'                       => true,
                    ),

                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                        '4'                         => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                        'gecko20',
                    ),
                ),
            ),

            // SeaMonkey 2.3; FreeBSD.
            array(
                'Mozilla/5.0 (X11; FreeBSD amd64; rv:6.0) Gecko/20110818 Firefox/6.0 SeaMonkey/2.3',
                array(
                    'is_gecko'                      => true,
                    'check_gecko_version'           => array(
                        '1'                         => true,
                        '2'                         => true,
                        '20030516'                  => true,
                        '20051116'                  => true,
                        '2006010100'                => true,
                        '20100101'                  => true,
                        '3.6'                       => true,
                        '4.0'                       => true,
                    ),

                    'is_firefox'                    => true,
                    'check_firefox_version'         => array(
                        '1.5'                       => true,
                        '3.0'                       => true,
                        '4'                         => true,
                    ),

                    'versionclasses'                => array(
                        'gecko',
                    ),
                ),
            ),

            // Windows 7; MS Word 2010.
            array(
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C; .NET4.0E; ms-office)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                    ),
                    'iecompatibility'               => true,
                    'versionclasses'                => array(
                        'ie',
                        'ie8',
                    ),

                    'is_msword'                     => true,

                    'supports_svg'                  => false,
                    'supports_json_contenttype'     => false,
                ),
            ),

            // Windows 7; MS Outlook 2010.
            array(
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.3; .NET4.0C; .NET4.0E; Microsoft Outlook 14.0.7113; ms-office; MSOffice 14)',
                array(
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                    ),
                    'iecompatibility'               => true,
                    'versionclasses'                => array(
                        'ie',
                        'ie8',
                    ),

                    // Note: Outlook is deliberately not considered to be MS Word.
                    'is_msword'                     => false,

                    'supports_svg'                  => false,
                    'supports_json_contenttype'     => false,
                ),
            ),

            // Mac OS X; MS Word 14.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X) Word/14.38.0',
                array(
                    'versionclasses'                => array(
                    ),

                    'is_msword'                     => true,
                ),
            ),

            // Safari 312; Max OS X.
            array(
                'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-us) AppleWebKit/312.1 (KHTML, like Gecko) Safari/312',
                array(
                    'is_safari'                     => true,
                    'check_safari_version'          => array(
                        '1'                         => true,
                        '312'                       => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                    ),
                ),
            ),

            // Safari 412; Max OS X.
            array(
                'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/412 (KHTML, like Gecko) Safari/412',
                array(
                    'is_safari'                     => true,
                    'check_safari_version'          => array(
                        '1'                         => true,
                        '312'                       => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                    ),
                ),
            ),

            // Safari 2.0; Max OS X.
            array(
                'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/412 (KHTML, like Gecko) Safari/412',
                array(
                    'is_safari'                     => true,
                    'check_safari_version'          => array(
                        '1'                         => true,
                        '312'                       => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                    ),
                ),
            ),

            // Safari 3.0; Mac OS X.
            array(
                'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en) AppleWebKit/522.11 (KHTML, like Gecko) Version/3.0.2 Safari/522.12',
                array(
                    'is_safari'                     => true,
                    'check_safari_version'          => array(
                        '1'                         => true,
                        '312'                       => true,
                        '500'                       => true,
                        '522'                       => true,
                    ),
                    'check_safari_browser_version'  => array(
                        '1'                         => true,
                        '3'                         => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                    ),

                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // Safari 8.0; Mac OS X.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/600.8.9 (KHTML, like Gecko) Version/8.0.8 Safari/600.8.9',
                array(
                    'is_safari'                     => true,
                    'check_safari_version'          => array(
                        '1'                         => true,
                        '312'                       => true,
                        '500'                       => true,
                        '600'                       => true,
                    ),
                    'check_safari_browser_version'  => array(
                        '3'                         => true,
                        '8'                         => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                    ),

                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // Safari 11.1; macOS.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1.2 Safari/605.1.15',
                array(
                    'is_safari'                     => true,
                    'check_safari_version'          => array(
                        '1'                         => true,
                        '312'                       => true,
                        '500'                       => true,
                        '600'                       => true,
                        '605'                       => true,
                    ),
                    'check_safari_browser_version'  => array(
                        '3'                         => true,
                        '8'                         => true,
                        '11'                        => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                    ),

                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // Safari 13.1; macOS.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_16_0 AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.2 Safari/604.1',
                array(
                    'is_safari'                     => true,
                    'check_safari_version'          => array(
                        '1'                         => true,
                        '312'                       => true,
                        '500'                       => true,
                        '600'                       => true,
                        '605'                       => true,
                    ),
                    'check_safari_browser_version'  => array(
                        '3'                         => true,
                        '8'                         => true,
                        '11'                        => true,
                        '13'                        => true,
                        '13.1'                      => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                    ),

                    'is_totara_legacy_browser'      => false,
                ),
            ),

            // Safari 15.3; macOS.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.3 Safari/605.1.15',
                array(
                    'is_safari'                     => true,
                    'check_safari_version'          => array(
                        '1'                         => true,
                        '312'                       => true,
                        '500'                       => true,
                        '600'                       => true,
                        '605'                       => true,
                    ),
                    'check_safari_browser_version'  => array(
                        '3'                         => true,
                        '8'                         => true,
                        '11'                        => true,
                        '13'                        => true,
                        '13.1'                      => true,
                        '15'                        => true,
                        '15.3'                      => true,
                        '15.4'                      => false,
                    ),

                    'is_webkit'                     => true,
                    'is_chrome'                     => false,
                    'is_safari_ios'                 => false,

                    'versionclasses'                => array(
                        'safari',
                    ),

                    'is_totara_legacy_browser'      => false,
                ),
            ),

            // iOS WebKit (Safari) 4.0; iPhone OS 3.1.
            array(
                'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_1_2 like Mac OS X; cs-cz) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7D11 Safari/528.16',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'mobile',
                    'is_totara_legacy_browser'      => true,
               ),
            ),

            // Safari; iPhone 6 Plus; iOS 8.1; Build 12B411.
            array(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 8_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B411 Safari/600.1.4',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                        '590'                       => true,
                        '600'                       => true,
                        '601'                       => false,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                        '8'                         => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'mobile',
                    'is_totara_legacy_browser'      => true,
               ),
            ),

            // iOS WebKit(Safari 10); iPhone 7 Plus; iOS 11.
            array(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_3 like Mac OS X) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.0 Mobile/14G60 Safari/602.1',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                        '590'                       => true,
                        '602'                       => true,
                        '603'                       => true,
                        '604'                       => false,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'mobile',
                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // iOS WebKit (Chrome); iPhone 7 Plus; iOS 11.
            array(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_3 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) CriOS/61.0.3163.73 Mobile/14G60 Safari/602.1',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                        '590'                       => true,
                        '602'                       => true,
                        '603'                       => true,
                        '604'                       => false,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'mobile',
                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // iOS WebKit (Safari 13.0); iOS 13.1.
            array(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 13_1_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.1 Mobile/15E148 Safari/604.1',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '12'                        => true,
                        '13'                        => true,
                        '13.4'                      => false,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'mobile',
                    'is_totara_legacy_browser'      => true,
                ),
            ),

            // iOS WebKit (Safari 13.1); iOS 13.4.
            array(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 13_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1 Mobile/15E148 Safari/604.1',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '12'                        => true,
                        '13'                        => true,
                        '13.4'                      => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'mobile',
                    'is_totara_legacy_browser'      => false,
               ),
            ),

            // iOS WebKit (Safari 15.2); iOS 15.3.
            array(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 15_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.2 Mobile/15E148 Safari/604.1',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '12'                        => true,
                        '13'                        => true,
                        '15'                        => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'mobile',
                    'is_totara_legacy_browser'      => false,
               ),
            ),

            // iOS WebKit (Chrome); iOS 15.
            array(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 15_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/99.0.4844.47 Mobile/15E148 Safari/604.1',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '12'                        => true,
                        '13'                        => true,
                        '15'                        => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'mobile',
                    'is_totara_legacy_browser'      => false,
               ),
            ),

            // iOS WebKit (Safari 5.0.2); iPad OS 4.
            array(
                'Mozilla/5.0 (iPad; U; CPU OS 4_2_1 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148 Safari/6533.18.5',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'tablet',
                    'is_totara_legacy_browser'      => true,
               ),
            ),

            // iOS WebKit (Safari 5.0.2); iPad OS 4.
            array(
                'Mozilla/5.0 (iPad; U; CPU OS 4_2_1 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148 Safari/6533.18.5',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'tablet',
                    'is_totara_legacy_browser'      => true,
               ),
            ),

            // iOS WebKit (Safari); iPad OS 12.
            array(
                'Mozilla/5.0 (iPad; CPU OS 12_4_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.2 Mobile/15E148 Safari/604.1',
                array(
                    // Note: We do *not* identify mobile Safari as Safari.
                    'is_safari_ios'                 => true,
                    'is_webkit_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '12'                        => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'tablet',
                    'is_totara_legacy_browser'      => true,
               ),
            ),

            // iOS WebKit (Facebook in-app browser); iPad OS 15.
            array(
                'Mozilla/5.0 (iPad; CPU OS 15_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/19D52 [FBAN/FBIOS;FBDV/iPad11,6;FBMD/iPad;FBSN/iPadOS;FBSV/15.3.1;FBSS/2;FBID/tablet;FBLC/it_IT;FBOP/5]',
                array(
                    'is_webkit_ios'                 => true,
                    
                    'is_ios'                        => true,
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '12'                        => true,
                        '13'                        => true,
                        '15'                        => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'tablet',
                    'is_totara_legacy_browser'      => false,
               ),
            ),

            // Android WebKit 525; G1 Phone.
            array(
                'Mozilla/5.0 (Linux; U; Android 1.1; en-gb; dream) AppleWebKit/525.10+ (KHTML, like Gecko) Version/3.0.4 Mobile Safari/523.12.2 – G1 Phone',
                array(
                    'is_webkit_android'             => true,
                    'check_webkit_android_version'  => array(
                        '525'                       => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'android',
                        'safari',
                    ),

                    'devicetype'                    => 'mobile',

                    'supports_svg'                  => false,
               ),
            ),

            // Android WebKit 530; Nexus.
            array(
                'Mozilla/5.0 (Linux; U; Android 2.1; en-us; Nexus One Build/ERD62) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17 –Nexus',
                array(
                    'is_webkit_android'             => true,
                    'check_webkit_android_version'  => array(
                        '525'                       => true,
                        '527'                       => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'android',
                        'safari',
                    ),

                    'devicetype'                    => 'mobile',

                    'supports_svg'                  => false,
               ),
            ),

            // Android WebKit 537; Samsung GT-9505.
            array(
                'Mozilla/5.0 (Linux; Android 4.3; it-it; SAMSUNG GT-I9505/I9505XXUEMJ7 Build/JSS15J) AppleWebKit/537.36 (KHTML, like Gecko) Version/1.5 Chrome/28.0.1500.94 Mobile Safari/537.36',
                array(
                    'is_webkit_android'             => true,
                    'check_webkit_android_version'  => array(
                        '525'                       => true,
                        '527'                       => true,
                    ),

                    'is_webkit'                     => true,

                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                    ),

                    'versionclasses'                => array(
                        'chrome',
                        'android',
                    ),

                    'devicetype'                    => 'mobile',
                ),
            ),

            // Android WebKit 537; Nexus 5.
            array(
                'Mozilla/5.0 (Linux; Android 5.0; Nexus 5 Build/LPX13D) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.102 Mobile Safari/537.36',
                array(
                    'is_webkit_android'             => true,
                    'check_webkit_android_version'  => array(
                        '525'                       => true,
                        '527'                       => true,
                    ),

                    'is_webkit'                     => true,

                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                    ),

                    'versionclasses'                => array(
                        'chrome',
                        'android',
                    ),

                    'devicetype'                    => 'mobile',
                ),
            ),

            // Chrome 8; Mac OS X.
            array(
                'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_5; en-US) AppleWebKit/534.10 (KHTML, like Gecko) Chrome/8.0.552.215 Safari/534.10',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Chrome 39; Mac OS X.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Chrome 61; Mac OS X.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.91 Safari/537.36',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                        '61'                        => true,
                        '62'                        => false,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Chrome 61; Windows 10.
            array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.91 Safari/537.36',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                        '61'                        => true,
                        '62'                        => false,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Chrome 100; Windows 10.
            array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4758.102 Safari/537.36',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                        '61'                        => true,
                        '62'                        => true,
                        '100'                       => true,
                        '101'                       => false,
                    ),

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Chrome based MS Edge Beta; OS X Catalina.
            array(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.70 Safari/537.36 Edg/78.0.276.20',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                        '78'                        => true,
                        '79'                        => false,
                    ),

                    'is_edge'                       => false,

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Chrome based MS Edge Beta; Windows.
            array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.70 Safari/537.36 Edg/44.18362.387.0',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                        '78'                        => true,
                        '79'                        => false,
                    ),

                    'is_edge'                       => false,

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Chrome based MS Edge 98; Windows 10.
            array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36 Edg/98.0.1108.55',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                        '78'                        => true,
                        '79'                        => true,
                        '98'                        => true,
                        '100'                       => false,
                        '101'                       => false,
                    ),

                    'is_edge'                       => false,

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Chrome based MS Edge 98.100; Windows 10.
            array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.100.1108.55 Safari/537.36 Edg/98.0.1108.55',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                        '78'                        => true,
                        '79'                        => true,
                        '98'                        => true,
                    ),

                    'is_edge'                       => false,

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Chrome based MS Edge 100; Windows 10.
            array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.1108.55 Safari/537.36 Edg/100.0.1108.55',
                array(
                    'is_chrome'                     => true,
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                        '78'                        => true,
                        '79'                        => true,
                        '98'                        => true,
                        '100'                       => true,
                        '101'                       => false,
                    ),

                    'is_edge'                       => false,

                    'is_webkit'                     => true,

                    'versionclasses'                => array(
                        'chrome',
                    ),
                ),
            ),

            // Opera 8.51; Windows XP.
            array(
                'Opera/8.51 (Windows NT 5.1; U; en)',
                array(
                    'is_opera'                      => true,
                    'check_opera_version'           => array(
                        '8.0'                       => true,
                    ),

                    'versionclasses'                => array(
                        'opera',
                    ),

                    'supports_svg'                  => false,
               ),
            ),

            // Opera 9.0; Windows XP.
            array(
                'Opera/9.0 (Windows NT 5.1; U; en)',
                array(
                    'is_opera'                      => true,
                    'check_opera_version'           => array(
                        '8.0'                       => true,
                        '9.0'                       => true,
                    ),

                    'versionclasses'                => array(
                        'opera',
                    ),

                    'supports_svg'                  => false,
               ),
            ),

            // Opera 12.15 (Build 1748); Mac OS X.
            array(
                'Opera/9.80 (Macintosh; Intel Mac OS X 10.10.0; Edition MAS) Presto/2.12.388 Version/12.15',
                array(
                    'is_opera'                      => true,
                    'check_opera_version'           => array(
                        '8.0'                       => true,
                        '9.0'                       => true,
                        '10.0'                      => true,
                        '12.15'                     => true,
                    ),

                    'versionclasses'                => array(
                        'opera',
                    ),

                    'supports_svg'                  => false,
               ),
            ),

            // Opera 9.0; Debian Linux.
            array(
                'Opera/9.01 (X11; Linux i686; U; en)',
                array(
                    'is_opera'                      => true,
                    'check_opera_version'           => array(
                        '8.0'                       => true,
                        '9.0'                       => true,
                    ),

                    'versionclasses'                => array(
                        'opera',
                    ),

                    'supports_svg'                  => false,
               ),
            ),

            // Google web crawlers.
            array(
                'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
            array(
                'Googlebot/2.1 (+http://www.googlebot.com/bot.html)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
            array(
                'Googlebot-Image/1.0',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),

            // Yahoo crawlers.
            // See https://help.yahoo.com/kb/slurp-crawling-page-sln22600.html.
            array(
                'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),

            // Bing / MSN / AdIdx crawlers.
            // See http://www.bing.com/webmaster/help/which-crawlers-does-bing-use-8c184ec0.
            array(
                'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
            array(
                'Mozilla/5.0 (compatible; bingbot/2.0 +http://www.bing.com/bingbot.htm)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
            array(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
                array(
                    'is_web_crawler'                => true,
                    'is_webkit'                     => true,
                    'is_webkit_ios'                 => true,
                    'is_safari_ios'                 => true,
                    'is_ios'                        => true,
                    'check_safari_ios_version'      => array(
                        '527'                       => true,
                    ),
                    'check_webkit_ios_platform_version' => array(
                        '3'                         => true,
                        '4'                         => true,
                    ),

                    'versionclasses'                => array(
                        'safari',
                        'ios',
                    ),

                    'devicetype'                    => 'mobile',
               ),
            ),
            array(
                'Mozilla/5.0 (Windows Phone 8.1; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; NOKIA; Lumia 530) like Gecko (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
                array(
                    'is_web_crawler'                => true,
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                        '9.0'                       => true,
                        '10'                        => true,
                        '11'                        => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie11',
                    ),
                    'devicetype'                    => 'mobile',
               ),
            ),

            array(
                'msnbot/2.0b (+http://search.msn.com/msnbot.htm)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
            array(
                'msnbot/2.1',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
            array(
                'msnbot-media/1.1 (+http://search.msn.com/msnbot.htm)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
            array(
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534+ (KHTML, like Gecko) BingPreview/1.0b',
                array(
                    'is_web_crawler'                => true,
                    'is_webkit'                     => true,
                    'is_safari'                     => true,
                    'check_safari_version'          => array(
                        '1'                         => true,
                        '312'                       => true,
                        '500'                       => true,
                    ),

                    'versionclasses'                => array(
                        'safari',
                    ),
               ),
            ),
            array(
                'Mozilla/5.0 (Windows Phone 8.1; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; NOKIA; Lumia 530) like Gecko BingPreview/1.0b',
                array(
                    'is_web_crawler'                => true,
                    'is_ie'                         => true,
                    'check_ie_version'              => array(
                        '0'                         => true,
                        '5.0'                       => true,
                        '5.5'                       => true,
                        '6.0'                       => true,
                        '7.0'                       => true,
                        '8.0'                       => true,
                        '9.0'                       => true,
                        '10'                        => true,
                        '11'                        => true,
                    ),
                    'versionclasses'                => array(
                        'ie',
                        'ie11',
                    ),
                    'devicetype'                    => 'mobile',
               ),
            ),
            array(
                'Mozilla/5.0 (Linux; Android 9; SM-G955U1 Build/PPR1.180610.011; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/99.0.4844.58 Mobile Safari/537.36 TotaraMobileApp',
                array(
                    'is_webkit_android'             => true,
                    'is_webkit'                     => true,
                    'check_webkit_android_version'  => array(
                        '525'                       => true,
                        '527'                       => true,
                    ),
                    'check_chrome_version'          => array(
                        '7'                         => true,
                        '8'                         => true,
                        '10'                        => true,
                        '39'                        => true,
                    ),
                    'versionclasses'                => array(
                        'android',
                        'chrome',
                    ),
                    'is_chrome'                     => true,
                    'is_app'                        => true,
                    'devicetype'                    => 'mobile',
               ),
            ),


            // Google image proxy, not a real browser and does not support SVG
            array(
                'Mozilla/5.0 (Windows NT 5.1; rv:11.0) Gecko Firefox/11.0 (via ggpht.com GoogleImageProxy)',
                array(
                    'is_google_image_proxy'         => true,
                    'supports_svg'                  => false,
                    'versionclasses'                => array(
                    ),
                ),
            ),
            array(
                'Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.0.7) Gecko/2009021910 Firefox/3.0.7 (via ggpht.com GoogleImageProxy)',
                array(
                    'is_google_image_proxy'         => true,
                    'supports_svg'                  => false,
                    'versionclasses'                => array(
                    ),
                ),
            ),
            array(
                'Mozilla/5.0 (Windows NT 9000; rv:9000.0) Gecko Firefox/9000.0 (via ggpht.com googleimageproxy)',
                array(
                    'is_google_image_proxy'         => true,
                    'supports_svg'                  => false,
                    'versionclasses'                => array(
                    ),
                ),
            ),

            // Yandex.
            // See http://help.yandex.com/search/robots/agent.xml.
            array(
                'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
            array(
                'Mozilla/5.0 (compatible; YandexImages/3.0; +http://yandex.com/bots)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),

            // AltaVista.
            array(
                'AltaVista V2.0B crawler@evreka.com',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),

            // ZoomSpider.
            array(
                'ZoomSpider - wrensoft.com [ZSEBOT]',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),

            // Baidu.
            array(
                'Baiduspider+(+http://www.baidu.com/search/spider_jp.html)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
            array(
                'Baiduspider+(+http://www.baidu.com/search/spider.htm)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),

            // Ask.com.
            array(
                'User-Agent: Mozilla/2.0 (compatible; Ask Jeeves/Teoma)',
                array(
                    'is_web_crawler'                => true,
                    'versionclasses'                => array(
                    ),
               ),
            ),
        );
    }

    /**
     * Test instance generation.
     */
    public function test_instance() {
        $this->assertInstanceOf('core_useragent', core_useragent::instance());
        $this->assertInstanceOf('core_useragent', core_useragent::instance(true));
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_edge($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        // Edge Tests.
        if (isset($tests['is_edge']) && $tests['is_edge']) {
            $this->assertTrue(core_useragent::is_edge());
        } else {
            $this->assertFalse(core_useragent::is_edge());
        }

        $versions = array(
            // New versions of should be added here.
            '12'   => false,
        );

        if (isset($tests['check_edge_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_edge_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_edge_version($version),
                "Version incorrectly determined for Edge version '{$version}'");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_is_mobile($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['devicetype'])
            && ($tests['devicetype'] === 'mobile' || $tests['devicetype'] === 'tablet')
        ) {
            $this->assertTrue(core_useragent::is_mobile());
        } else {
            $this->assertFalse(core_useragent::is_mobile());
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_is_mobile_app($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_app']) && $tests['is_app'] === true) {
            $this->assertTrue(core_useragent::is_mobile_app());
        } else {
            $this->assertFalse(core_useragent::is_mobile_app());
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_ie($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        // IE Tests.
        if (isset($tests['is_ie']) && $tests['is_ie']) {
            $this->assertTrue(core_useragent::is_ie());
        } else {
            $this->assertFalse(core_useragent::is_ie());
        }

        $versions = array(
            // New versions of should be added here.
            '0'    => false,
            '5.0'  => false,
            '5.5'  => false,
            '6.0'  => false,
            '7.0'  => false,
            '8.0'  => false,
            '9.0'  => false,
            '10'   => false,
            '11'   => false,
            '12'   => false,
            '13'   => false,
            '14'   => false,
        );

        if (isset($tests['check_ie_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_ie_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_ie_version($version),
                "Version incorrectly determined for IE version '{$version}'");
        }

        // IE Compatibility mode.
        if (isset($tests['iecompatibility']) && $tests['iecompatibility']) {
            $this->assertTrue(core_useragent::check_ie_compatibility_view(), "IE Compability false negative");
        } else {
            $this->assertFalse(core_useragent::check_ie_compatibility_view(), "IE Compability false positive");
        }

    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_msword($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        // MSWord Tests.
        if (isset($tests['is_msword']) && $tests['is_msword']) {
            $this->assertTrue(core_useragent::is_msword());
        } else {
            $this->assertFalse(core_useragent::is_msword());
        }
    }


    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_supports($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        // Supports SVG.
        if (!isset($tests['supports_svg']) || $tests['supports_svg']) {
            $this->assertTrue(core_useragent::supports_svg(),
                "SVG Support was not reported (and should have been)");
        } else {
            $this->assertFalse(core_useragent::supports_svg(),
                "SVG Support was reported (and should not have been)");
        }

        // Supports JSON ContentType.
        if (!isset($tests['supports_json_contenttype']) || $tests['supports_json_contenttype']) {
            $this->assertTrue(core_useragent::supports_json_contenttype(),
                "JSON ContentType Support was not reported (and should have been)");
        } else {
            $this->assertFalse(core_useragent::supports_json_contenttype(),
                "JSON ContentType Support was reported (and should not have been)");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_webkit($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_webkit']) && $tests['is_webkit']) {
            $this->assertTrue(core_useragent::is_webkit(),
                "Browser was not identified as a webkit browser");
            $this->assertTrue(core_useragent::check_webkit_version());
        } else {
            $this->assertFalse(core_useragent::is_webkit(),
                "Browser was incorrectly identified as a webkit browser");
            $this->assertFalse(core_useragent::check_webkit_version());
        }

        $versions = array(
            // New versions should be added here.
        );

        if (isset($tests['check_webkit_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_webkit_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_webkit_version($version),
                "Version incorrectly determined for Webkit version '{$version}'");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_webkit_android($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_webkit_android']) && $tests['is_webkit_android']) {
            $this->assertTrue(core_useragent::is_webkit_android(),
                "Browser was not identified as an Android webkit browser");
            $this->assertTrue(core_useragent::check_webkit_android_version());
        } else {
            $this->assertFalse(core_useragent::is_webkit_android(),
                "Browser was incorrectly identified as an Android webkit browser");
            $this->assertFalse(core_useragent::check_webkit_android_version());
        }

        $versions = array(
            // New versions should be added here.
            '525'       => false,
            '527'       => false,
            '590'       => false,
        );

        if (isset($tests['check_webkit_android_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_webkit_android_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_webkit_android_version($version),
                "Version incorrectly determined for Android webkit version '{$version}'");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_chrome($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_chrome']) && $tests['is_chrome']) {
            $this->assertTrue(core_useragent::is_chrome(),
                "Browser was not identified as a chrome browser");
            $this->assertTrue(core_useragent::check_chrome_version());
        } else {
            $this->assertFalse(core_useragent::is_chrome(),
                "Browser was incorrectly identified as a chrome browser");
            $this->assertFalse(core_useragent::check_chrome_version());
        }

        $versions = array(
            // New versions should be added here.
            '7'         => false,
            '8'         => false,
            '10'        => false,
            '39'        => false,
            '100'       => false,
            '101'       => false,
        );

        if (isset($tests['check_chrome_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_chrome_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_chrome_version($version),
                "Version incorrectly determined for Chrome version '{$version}'");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_safari($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_safari']) && $tests['is_safari']) {
            $this->assertTrue(core_useragent::is_safari(),
                "Browser was not identified as a safari browser");
            $this->assertTrue(core_useragent::check_safari_version());
        } else {
            $this->assertFalse(core_useragent::is_safari(),
                "Browser was incorrectly identified as a safari browser");
            $this->assertFalse(core_useragent::check_safari_version());
            $this->assertFalse(core_useragent::check_safari_browser_version());
        }

        // Check Safari (generic). WebKit version.
        $versions = array(
            // New versions should be added here.
            '1'         => false,
            '312'       => false,
            '500'       => false,
            '600'       => false,
            '605'       => false,
        );

        if (isset($tests['check_safari_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_safari_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_safari_version($version),
                "Version incorrectly determined for Safari (generic) version '{$version}'");
        }

        // Check Safari (browser) version.
        $browser_versions = array(
            '3'   => false,
            '8'   => false,
            '11'  => false,
            '13'  => false,
            '15'  => false,
            '345' => false,
        );

        if (isset($tests['check_safari_browser_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $browser_versions = $tests['check_safari_browser_version'] + $browser_versions;
        }

        foreach ($browser_versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_safari_browser_version($version),
                "Version incorrectly determined for Safari (browser) version '{$version}'");
        }

    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_ios_safari($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_safari_ios']) && $tests['is_safari_ios']) {
            $this->assertTrue(core_useragent::is_safari_ios(),
                "Browser was not identified as an iOS safari browser");
            $this->assertTrue(core_useragent::check_safari_ios_version());
        } else {
            $this->assertFalse(core_useragent::is_safari_ios(),
                "Browser was incorrectly identified as an iOS safari browser");
            $this->assertFalse(core_useragent::check_safari_ios_version());
        }

        // Check iOS Safari.
        $versions = array(
            // New versions should be added here.
            '630'       => false,
            '650'       => false,
        );

        if (isset($tests['check_safari_ios_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_safari_ios_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_safari_ios_version($version),
                "Version incorrectly determined for iOS Safari version '{$version}'");
        }
    }


    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_webkit_ios($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_webkit_ios']) && $tests['is_webkit_ios']) {
            $this->assertTrue(core_useragent::is_webkit_ios(),
                "Browser was not identified as an iOS WebKit browser");
            $this->assertTrue(core_useragent::check_webkit_ios_platform_version());
        } else {
            $this->assertFalse(core_useragent::is_webkit_ios(),
                "Browser was incorrectly identified as an iOS WebKit browser");
            $this->assertFalse(core_useragent::check_webkit_ios_platform_version());
        }

        // Check iOS WebKit.
        $versions = array(
            // New versions should be added here.
            '3'  => false,
            '4'  => false,
            '8'  => false,
            '10' => false,
            '12' => false,
            '13' => false,
            '15' => false,
        );

        if (isset($tests['check_webkit_ios_platform_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_webkit_ios_platform_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_webkit_ios_platform_version($version),
                "Version incorrectly determined for WebKit iOS platform version '{$version}'");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_ios($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_ios']) && $tests['is_ios']) {
            $this->assertTrue(core_useragent::is_ios(),
                "Browser was not identified as an iOS device browser");
        } else {
            $this->assertFalse(core_useragent::is_ios(),
                "Browser was incorrectly identified as an iOS device browser");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_gecko($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_gecko']) && $tests['is_gecko']) {
            $this->assertTrue(core_useragent::is_gecko(),
                "Browser was not identified as a gecko browser");
            $this->assertTrue(core_useragent::check_gecko_version());
        } else {
            $this->assertFalse(core_useragent::is_gecko(),
                "Browser was incorrectly identified as a gecko browser");
            $this->assertFalse(core_useragent::check_gecko_version());
        }

        $versions = array(
            // New versions should be added here.
            '1'             => false,
            '2'             => false,
            '3.6'           => false,
            '4.0'           => false,
            '20030516'      => false,
            '20051116'      => false,
            '2006010100'    => false,
            '20100101'      => false,
            '15.0'          => false,
            '18.0'          => false,
            '19.0'          => false,
        );

        if (isset($tests['check_gecko_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_gecko_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_gecko_version($version),
                "Version incorrectly determined for Gecko version '{$version}'");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_firefox($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_firefox']) && $tests['is_firefox']) {
            $this->assertTrue(core_useragent::is_firefox(),
                "Browser was not identified as a firefox browser");
            $this->assertTrue(core_useragent::check_firefox_version());
        } else {
            $this->assertFalse(core_useragent::is_firefox(),
                "Browser was incorrectly identified as a firefox browser");
            $this->assertFalse(core_useragent::check_firefox_version());
        }

        $versions = array(
            // New versions should be added here.
            '1.5'       => false,
            '3.0'       => false,
            '4'         => false,
            '10'        => false,
            '15'        => false,
            '18'        => false,
            '19'        => false,
            '33'        => false,
            '97'        => false,
            '100'       => false,
        );

        if (isset($tests['check_firefox_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_firefox_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_firefox_version($version),
                "Version incorrectly determined for Firefox version '{$version}'");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_opera($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_opera']) && $tests['is_opera']) {
            $this->assertTrue(core_useragent::is_opera(),
                "Browser was not identified as a opera browser");
            $this->assertTrue(core_useragent::check_opera_version());
        } else {
            $this->assertFalse(core_useragent::is_opera(),
                "Browser was incorrectly identified as a opera browser");
            $this->assertFalse(core_useragent::check_opera_version());
        }

        $versions = array(
            // New versions should be added here.
            '8.0'       => false,
            '9.0'       => false,
            '10.0'      => false,
            '12.15'     => false,
        );

        if (isset($tests['check_opera_version'])) {
            // The test provider has overwritten some of the above checks.
            // Must use the '+' operator, because array_merge will incorrectly rewrite the array keys for integer-based indexes.
            $versions = $tests['check_opera_version'] + $versions;
        }

        foreach ($versions as $version => $result) {
            $this->assertEquals($result, core_useragent::check_opera_version($version),
                "Version incorrectly determined for Opera version '{$version}'");
        }
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_get_device_type($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        $expected = 'default';
        if (isset($tests['devicetype'])) {
            $expected = $tests['devicetype'];
        }

        $this->assertEquals($expected, core_useragent::get_device_type(),
            "Device Type was not correctly identified");
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_get_browser_version_classes($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        $actual = core_useragent::get_browser_version_classes();
        foreach ($tests['versionclasses'] as $expectedclass) {
            $this->assertContains($expectedclass, $actual);
        }
        $this->assertCount(count($tests['versionclasses']), $actual);
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_useragent_web_crawler($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        $expectation = isset($tests['is_web_crawler']) ? $tests['is_web_crawler'] : false;
        $this->assertSame($expectation, core_useragent::is_web_crawler());
    }

    /**
     * @dataProvider user_agents_providers
     */
    public function test_is_totara_legacy_browser($useragent, $tests) {
        // Setup the core_useragent instance.
        core_useragent::instance(true, $useragent);

        if (isset($tests['is_totara_legacy_browser'])) {
            $this->assertEquals(
                $tests['is_totara_legacy_browser'],
                core_useragent::is_totara_legacy_browser(),
                "Legacy browser was not correctly identified"
            );
        }

    }
}
