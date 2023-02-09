<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

defined('MOODLE_INTERNAL') || die();

$string['number'] = 'Number';
$string['pluginname'] = 'Totara TUI frontend framework';
$string['samples'] = 'TUI Samples';
$string['setting_cache_scss'] = 'Cache SCSS';
$string['setting_cache_scss_desc'] = 'When enabled Tui front end framework SCSS will be cached on the server and will not be regenerated each time it is requested.
Disabling this will delay page load times as processing SCSS takes several seconds. It is only useful when developing styles for the product.
It should never be disabled on production instances.';
$string['setting_cache_js'] = 'Cache JS';
$string['setting_cache_js_desc'] = 'When enabled Tui front end framework JavaScript will be cached on the server and will not be regenerated each time it is requested.';
$string['setting_development_mode'] = 'Development mode';
$string['setting_development_mode_desc'] = 'When enabled development versions of the Tui JavaScript and SCSS will be served to pages requiring the Tui components.
This is useful when developing components or debugging front end code at runtime.';
$string['setting_pre_php74_allow_unperformant_css'] = 'Allow unperformant CSS';
$string['setting_pre_php74_allow_unperformant_css_desc'] = "Versions of PHP before PHP 7.4 have a bug that results in poor build performance and potential hangs if CSS or SCSS files contain non-ASCII characters. Totara will refuse to compile these files to avoid hanging. Enable this setting to try and compile them anyway. This setting is ignored when running on PHP 7.4 or newer.\n\nWe recommend leaving this setting at its default value of off.";

// Capability strings.
$string['tui:themesettings'] = 'Manage theme settings';

// Theme settings.
$string['select_tenant'] = 'Tenants';
$string['theme_settings'] = 'Settings';

// Theme settings - saving.
$string['settings_error_save'] = 'An error occurred while saving, changes have not been applied.';
$string['settings_success_save'] = 'Changes have successfully been applied.';

// Theme settings - Tenant settings.
$string['formtenant_label_tenant'] = 'Custom tenant branding';
$string['formtenant_details_tenant'] = 'If enabled, you can override the site brand for this tenant.';

// Theme settings - Tabs.
$string['tabbrand'] = 'Brand';
$string['tabcolours'] = 'Colours';
$string['tabimages'] = 'Images';
$string['tabcustom'] = 'Custom';

$string['form_details_default'] = 'Default:';
$string['defaultimage'] = '{$a} default image';

// Theme settings - Branding tab.
$string['formbrand_label_logo'] = 'Logo';
$string['formbrand_details_logo'] = 'Upload an image to be used as the site’s logo. Acceptable image formats: PNG, JPG, GIF, SVG.';
$string['formbrand_label_logoalttext'] = 'Logo alternative text';
$string['formbrand_details_logoalttext'] = 'Add the name of the site, to function as a text alternative to the logo image. There\'s no need to include "logo" in this field.';
$string['formbrand_label_favicon'] = 'Favicon';
$string['formbrand_details_favicon'] = 'Upload your browser tab icon here and it will appear next to your site address in the browser. The format of the icon file should be in a .ico, .png or .svg format. (Suggested dimension: 16 x 16px)';
$string['formbrand_group_notifications'] = 'Email notifications';
$string['formbrand_details_notificationshtmlheader'] = 'Add custom HTML (for example, social media links and icons) in HTML email notifications.';
$string['formbrand_details_notificationshtmlfooter'] = 'Add custom HTML (for example, social media links and icons) in HTML email notifications.';
$string['formbrand_details_notificationstextfooter'] = 'Add information (for example, contact details and legal disclaimers) in plain-text email notifications.';
$string['formbrand_label_notificationshtmlheader'] = 'HTML header';
$string['formbrand_label_notificationshtmlfooter'] = 'HTML footer';
$string['formbrand_label_notificationstextfooter'] = 'Plain-text footer';
$string['settings_email_send_error'] = 'Error sending notification email';
$string['settings_email_send_success'] = 'Successfully sent email notification. Please check your email inbox.';

// Theme settings - Colours tab.
$string['formcolours_label_primary'] = 'Primary brand colour';
$string['formcolours_details_primary'] = 'Choose your primary brand colour to set the colour for interactive elements.';
$string['formcolours_label_useoverrides'] = 'Use overriding colours';
$string['formcolours_details_useoverrides'] = 'You can override the default colour for the following individual elements.';
$string['formcolours_label_accent'] = 'Accent colour';
$string['formcolours_details_accent'] = 'Choose an additional accent colour to set the highlight colour of non-interactive elements.';
$string['formcolours_label_primarybuttons'] = 'Primary button colour';
$string['formcolours_details_primarybuttons'] = 'This sets the colour of all primary buttons.';
$string['formcolours_label_secondarybuttons'] = 'Secondary button colour';
$string['formcolours_details_secondarybuttons'] = 'This sets the border and text colour of all secondary buttons.';
$string['formcolours_label_links'] = 'Link colour';
$string['formcolours_details_links'] = 'This sets the colour of all links.';
$string['formcolours_moresettings'] = 'More colours';
$string['formcolours_label_headerbg'] = 'Header background colour';
$string['formcolours_details_headerbg'] = 'This sets the background colour of the main navigation.';
$string['formcolours_label_headertext'] = 'Header text colour';
$string['formcolours_details_headertext'] = 'This sets the text colour of the main navigation.';
$string['formcolours_label_pagetext'] = 'Page text colour';
$string['formcolours_details_pagetext'] = 'This sets the main text colour of the site.';
$string['formcolours_label_footerbg'] = 'Footer background colour';
$string['formcolours_details_footerbg'] = 'This sets the background colour of the footer.';
$string['formcolours_label_footertext'] = 'Footer text colour';
$string['formcolours_details_footertext'] = 'This sets the text colour of the footer.';

// Theme settings - Images tab.
$string['formimages_group_core'] = 'Core';
$string['formimages_label_login'] = 'Login page';
$string['formimages_details_login'] = 'Upload an image to change the login page default image.';
$string['formimages_label_displaylogin'] = 'Display login page image';
$string['formimages_label_loginalttext'] = 'Login alternative text';
$string['formimages_details_loginalttext'] = 'Add a text alternative that conveys the content and function of the login image. If the image is purely presentational, leave this field empty.';
$string['formimages_group_learn'] = 'Learn';
$string['formimages_label_course'] = 'Course';
$string['formimages_details_course'] = 'Upload an image to change the course default images.';
$string['formimages_label_program'] = 'Program';
$string['formimages_details_program'] = 'Upload an image to change the program default images.';
$string['formimages_label_cert'] = 'Certification';
$string['formimages_details_cert'] = 'Upload an image to change the certification default images.';
$string['formimages_group_engage'] = 'Engage';
$string['formimages_label_resource'] = 'Resource';
$string['formimages_details_resource'] = 'Upload an image to change the resource default images.';
$string['formimages_label_workspace'] = 'Workspace';
$string['formimages_details_workspace'] = 'Upload an image to change the workspace default images.';

// Theme settings - Custom tab.
$string['formcustom_label_customfooter'] = 'Custom footer';
$string['formcustom_details_customfooter'] = 'The text entered here will be visible in the footer for this Theme.';
$string['formcustom_label_customcss'] = 'Custom CSS';
$string['formcustom_details_customcss'] = 'Warning: Any CSS you enter here will be added after all other styles on every page.';

// Theme settings - Tenants page.
$string['branding'] = 'Branding';
$string['custom'] = 'Custom';
$string['editsitebranding'] = 'Edit site brand';
$string['edittenantsetting'] = 'Edit settings for {$a}';
$string['site'] = 'Site';
$string['sitebranding'] = 'Site branding';
$string['sitebrandinginformation'] = 'Control the visual appearance of your site brand such as logos, colours, default images etc.';
$string['tenantbranding'] = 'Tenant branding';

// Default component completion strings
$string['completionstatus_pending'] = 'Pending';
$string['completionstatus_target'] = 'Target';
$string['completionstatus_complete'] = 'Complete';
$string['completionstatus_achieved'] = 'Achieved';
$string['completionstatus_current'] = 'Current';
$string['completionstatus_ready'] = 'Ready';
$string['completionstatus_done'] = 'Done';
$string['completionstatus_selected'] = 'Selected';
$string['completionstatus_locked'] = 'Locked';
$string['completionstatus_optional'] = 'Optional';
$string['completionstatus_invalid'] = 'Invalid';
$string['completionstatus_not_complete'] = 'Not complete';
$string['completionstatus_view_only'] = 'View only';
$string['a11y_progresstracker_action'] = 'Select for more information about this item\'s status.';
