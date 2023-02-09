<?php
/**
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
 * @author  Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_msteams
 */

defined('MOODLE_INTERNAL') || die();

$string['alert:opennew'] = 'This page is not fully compatible with Microsoft Teams. Click \'Open in browser\'.';

// Bot framework
$string['botfw:cmd_help'] = 'help';
$string['botfw:cmd_signin'] = 'signin';
$string['botfw:cmd_signout'] = 'signout';
$string['botfw:error_auth_invalid'] = 'Authentication request was not valid.';
$string['botfw:error_auth_timeout'] = 'Authentication timed out.';
$string['botfw:friendly_username1'] = '{$a->alternatename}';
$string['botfw:friendly_username2'] = '{$a->firstname}';
$string['botfw:generic_failure'] = 'Something went wrong. Please try again.';
$string['botfw:help_help'] = 'View help on how to use the app';
$string['botfw:help_signin'] = 'Sign in to receive notifications';
$string['botfw:help_signout'] = 'Sign out to disable notifications';
$string['botfw:help_command'] = 'Run "{$a}" command';
$string['botfw:msg_canthearyou'] = 'Sorry, I\'m unsure what you\'re asking. Please contact your site administrator or type \'Help\' to find out how you can use the app.';
$string['botfw:msg_help_button'] = 'Go to help';
$string['botfw:msg_help_title'] = 'Need help?';
$string['botfw:msg_help_body'] = 'This chatbot allows you to sign in to receive Totara notifications within Microsoft Teams. 
Choose one of the options below to sign in to the bot, sign out of the bot or go to the Help tab to learn more about how you can use Totara with Microsoft Teams.';

$string['botfw:msg_private'] = 'Hi, I can\'t respond to that here. Please start a one-on-one chat instead.';
$string['botfw:msg_private_name'] = 'Hi {$a}, I can\'t respond to that here. Please start a one-on-one chat instead.';
$string['botfw:msg_signin'] = 'Sign in to get started.';
$string['botfw:msg_signin_already'] = 'Hi {$a}, you\'re already signed in.';
$string['botfw:msg_signin_button'] = 'Sign in';
$string['botfw:msg_signin_done'] = 'Great, {$a}. You\'re all set up to receive notifications.';
$string['botfw:msg_signout_button'] = 'Sign out';
$string['botfw:msg_signout_already'] = 'Hi, you\'re already signed out.';
$string['botfw:msg_signout_button'] = 'Sign out';
$string['botfw:msg_signout_done'] = 'Thanks {$a}, you are now signed out. Please sign in again to receive notifications.';
$string['botfw:msg_subscribe_already'] = 'Hi {$a}, you\'re already set up to receive notifications.';
$string['botfw:msg_welcome'] = 'Welcome! Totara connects your learning to day-to-day work. Totara supports you to access learning, share and collaborate on resources and receive Totara notifications all within Microsoft Teams.<br><br>This bot will let you receive all your notifications right here. You\'ll need to sign in to your account to get this started.';
$string['botfw:mx_command_search'] = 'Search catalogue';
$string['botfw:mx_command_search_desc'] = 'Search catalogue';
$string['botfw:mx_command_search_term'] = 'Search';
$string['botfw:mx_command_search_term_desc'] = 'Search';
$string['botfw:mx_initialrun'] = 'Browse the Totara catalogue to share learning content.';
$string['botfw:mx_nomatches'] = 'We couldn\'t find any matches.';
$string['botfw:mx_signin'] = 'Sign in';
$string['botfw:mx_signinretry'] = 'Something went wrong. Please try again.';
$string['botfw:none'] = 'None';
$string['botfw:output_title'] = 'Signing in';

$string['check:appid_invalid'] = 'The application ID \'{$a}\' is not in a valid format for Microsoft services.';
$string['check:appid_notset'] = 'The application ID is not set.';
$string['check:bot_appid'] = 'Bot app ID';
$string['check:bot_disabled'] = 'Both bot feature and messaging extension feature are disabled.';
$string['check:bot_id_invalid'] = 'The bot app ID \'{$a}\' is not in a valid format for Microsoft services.';
$string['check:bot_id_notset'] = 'The bot app ID is not set.';
$string['check:bot_secret'] = 'Bot client secret';
$string['check:bot_secret_notset'] = 'The client secret is not set.';
$string['check:mf_appid'] = 'Manifest app ID';
$string['check:mf_appversion'] = 'Manifest app version';
$string['check:mf_appversion_notset'] = 'The app version is not set.';
$string['check:mf_desc'] = 'Manifest short description';
$string['check:mf_desc_notset'] = 'The short description is not set.';
$string['check:mf_desc_toolong'] = 'The short description is too long. It must not exceed {$a} characters.';
$string['check:mf_descfull'] = 'Manifest full description';
$string['check:mf_descfull_notset'] = 'The full description is not set.';
$string['check:mf_descfull_toolong'] = 'The full description is too long. It must not exceed {$a} characters.';
$string['check:mf_name'] = 'Manifest short name';
$string['check:mf_name_notset'] = 'The short name is not set.';
$string['check:mf_name_toolong'] = 'The short name is too long. It must not exceed {$a} characters.';
$string['check:mf_namefull'] = 'Manifest full name';
$string['check:mf_namefull_toolong'] = 'The full name is too long. It must not exceed {$a} characters.';
$string['check:mf_package'] = 'Manifest package name';
$string['check:mf_package_nodefault'] = 'The package name must not be default.';
$string['check:mf_package_notset'] = 'The package name is not set.';
$string['check:pub_mpnid_toolong'] = 'The Microsoft Partner Network ID is too long. It must not exceed {$a} characters.';
$string['check:pub_name_notset'] = 'The publisher\'s name is not set.';
$string['check:pub_name_toolong'] = 'The publisher\'s name is too long. It must not exceed {$a} characters.';
$string['check:pub_url_empty'] = 'The site URL is used by default.';
$string['check:pub_website_notset'] = 'The publisher\'s website is not set.';
$string['check:pub_website_insecure'] = 'The publisher\'s website is not an https URL.';
$string['check:pub_website_toolong'] = 'The publisher\'s website is too long. It must not exceed {$a} characters.';
$string['check:site_privacy_notset'] = 'The privacy policy is not set.';
$string['check:site_privacy_insecure'] = 'The privacy policy is not an https URL.';
$string['check:site_privacy_toolong'] = 'The privacy policy is a too long URL. It must not exceed {$a} characters.';
$string['check:site_terms_notset'] = 'The terms of use is not set.';
$string['check:site_terms_insecure'] = 'The terms of use is not an https URL.';
$string['check:site_terms_toolong'] = 'The terms of use is a too long URL. It must not exceed {$a} characters.';
$string['check:wwwroot'] = 'Site is https';
$string['check:wwwroot_insecure'] = 'Your site needs to use HTTPS.';
$string['check:grid_skip'] = 'If disabled, users will not be able to see catalogue images in a card and the messaging extension.';
$string['check:notticked'] = '\'{$a}\' is not enabled.';
$string['check:notequals'] = '\'{$a->name}\' is not set to \'{$a->value}\'.';
$string['check:pass'] = '<span aria-hidden="true">-</span>';
$string['check:sso_auth'] = 'SSO OAuth2 service';
$string['check:sso_disabled'] = 'Single sign-on is disabled.';
$string['check:sso_id'] = 'SSO app ID';
$string['check:sso_scope'] = 'SSO resource scope';
$string['check:sso_scope_invalid'] = 'The resource scope \'{$a}\' is not in a valid format.';
$string['check:sso_scope_notset'] = 'The resource scope is not set.';
$string['customtab_list_label'] = 'Select item';
$string['customtab_name_error'] = 'Name is required';
$string['customtab_name_label'] = 'Tab name';
$string['customtab_name_placeholder'] = 'Name your tab';
$string['customtab_search_label'] = 'Search the catalog and select an item to be added in a new tab';
$string['customtab_search_placeholder'] = 'Search';
$string['customtab_title'] = 'Create a tab';
$string['domain_name'] = 'Domain name';
$string['enable_msteams'] = 'Microsoft Teams integration';
$string['enable_msteams_description'] = 'When enabled this will allow users to view and interact with their Library of resources/playlists, current learning, notifications and Find learning catalogue through Microsoft Teams. If disabled, the Microsoft Teams API will not be accessible.';
$string['env:missing_gateway_private_key'] = "Missing the setting for the private key to sign the gateway request";
$string['env:no_openssl_extension'] = "PHP OpenSSL extension must be enabled in order to allow Totara MsTeams plugin connect with Totara Gateway";
$string['env:openssl_custom_check'] = "PHP OpenSSL extension check";
$string['error:catalognotavailable'] = 'Find learning is not available. For more information, talk to your administrator.';
$string['error:domain_name'] = 'The site could not be connected. Please check your domain name again.';
$string['error:gateway_register'] = 'Cannot register with Totara Gateway';
$string['error:manifest_createzip'] = 'Can not create a zip archive.';
$string['error:mylearningnotavailable'] = 'Current learning is not available. For more information, talk to your administrator.';
$string['error:nodirectaccess'] = 'Direct access to the page is prohibited.';
$string['error:no_openssl_extension'] = 'PHP OpenSSL extension is disabled. Please enable the extension, in order to make the request work.';
$string['error:no_private_key'] = 'MsTeams Gateway private key is unset, please check your configuration';
$string['error:oauth2_disabled'] = 'OAuth2 authentication plugin is not enabled.';
$string['error:oauth2_issuerdisabled'] = 'OAuth2 service \'{$a}\' is not enabled.';
$string['error:oauth2_issuerinvalid'] = 'OAuth2 service \'{$a}\' is not properly configured. Make sure the client ID and the client secret are correctly set.';
$string['error:oauth2_missingendpoint'] = 'The {$a->type}_endpoint is not found for the OAuth2 service \'{$a->issuer}\'.';
$string['error:oauth2_noissuer'] = 'OAuth2 service is not set.';
$string['error:sso_failure_desc'] = 'There is an error in the configuration and set up of your organisation\'s Totara app. Please contact your site administrator.';
$string['error:sso_failure_title'] = 'Single sign-on authentication failure';
$string['help_info'] = 'The domain will connect your site to your organisation\'s Microsoft Teams. Your domain name is 
likely to be found in the email addressed used to log on to Microsoft Teams e.g. sarah@globallearning.com means that the 
domain name is "globallearning.com". Alternatively, you can find the domain name by logging into your Azure portal 
under the <b>Azure Active Directory</b> \'Overview\' page.';
$string['help_page_about'] = 'About';
$string['help_page_about_info'] = 'Here you can find out more about the Totara app, such as the publisher, version number, and a
description. This tab may also include links to the publisher’s website and important policies.';
$string['help_page_accessing_app'] = 'Accessing the app';
$string['help_page_accessing_app_adding_app'] = 'Click the app and select <strong>Add</strong> to access it.';
$string['help_page_accessing_app_built_for_organisation'] = 'Find the \'Totara\' by Totara Learning app in the catalogue.';
$string['help_page_accessing_app_more_app'] = 'Click on the icon of three dots in the side panel and select <strong>More apps.</strong> ';
$string['help_page_accessing_app_signin'] = 'Sign in to Microsoft Teams.';
$string['help_page_accessing_app_steps'] = 'Follow these steps to access the Totara app:';
$string['help_page_accessing_app_uninstall'] = 'You can also uninstall the app by right-clicking it (if pinned to the left-hand navigation bar) 
and selecting <strong>Uninstall.</strong>';
$string['help_page_catalog_content'] = 'Simply click on resources or playlists to open them in Microsoft Teams. Now you can read, comment on and 
like resources as you would on Totara. For courses, programs and certifications you may need to first enrol. Once you have opened your content 
you can select the Microsoft Teams ‘globe’ icon for opening in the browser at any time, if you would prefer to complete some of the activities in a web browser instead.';
$string['help_page_catalog_items'] = 'In this tab you can access the Totara catalogue, where you can browse a list of courses, resources and playlists.';
$string['help_page_catalog_search'] = 'You can search for content or use the filters on the left-hand side to find the content you’re looking for.';
$string['help_page_chat'] = 'Chat';
$string['help_page_chat_commands'] = 'There are three commands for the chat bot:';
$string['help_page_chat_help'] = '<strong>Help:</strong> Use this command to access further information on how to use the app';
$string['help_page_chat_notification'] = 'By signing in on this tab you can receive Totara notifications. You can configure which Totara notifications you want to be sent to Microsoft 
Teams under the Notifications preferences when accessing Totara from your browser.';
$string['help_page_chat_signin'] = '<strong>Sign in:</strong> You need to sign in to the bot in order to enable notifications';
$string['help_page_chat_signout'] = '<strong>Sign out:</strong> You can use this to sign out and disable notifications from being sent to Microsoft Teams';
$string['help_page_config'] = 'Configurable tabs';
$string['help_page_config_add'] = 'Click <strong>Add</strong>.';
$string['help_page_config_click'] = 'Click the + icon in the top panel within a Team.';
$string['help_page_config_search'] = 'You will now be able to search the catalogue to find and select the item you would like to add in a tab. It is also possible to provide a name for this 
new tab. Once it has been added, it will display to all users within that tab. You can also rename or remove the tab at any time by clicking the down arrow next to the tab name.';
$string['help_page_config_select'] = 'Find and select the Totara app under the listed available apps.';
$string['help_page_config_tab'] = 'In the Totara app you can also add configurable tabs to a Team, allowing you to share content in a different way. Microsoft Teams allows for the 
tabs in Team channels to be added or removed by its members. In the Totara app users can add learning content within these tabs to \'pin\' content to certain channels. This could be 
a course administrator or facilitator who wants to flag pre-course material for a team by adding this course as a new tab, or a manager who wants to pin a playlist with useful resources for their team to use. To add a configurable tab, follow these steps:';
$string['help_page_current_learning'] = 'Current learning';
$string['help_page_current_learning_course'] = 'Select a course to launch it within Microsoft Teams. You can then work through the course and its activities 
as usual. All Totara Learn activities are fully functional in the Microsoft Teams app with a few exceptions. Due to the dynamic nature of the <strong>wiki</strong>
and <strong>external tool</strong> activities, it is recommended that these activities are opened in the browser. This ensures that all functionality is available. The Microsoft 
Teams ‘globe’ icon in the top panel allows you to open any page in a browser window at any time.';
$string['help_page_current_learning_tab'] = 'On the <strong>Current learning tab</strong> you can view and access any of your assigned learning, such as courses, programs and certifications you are 
enrolled on. If you are not enrolled on any learning, this tab will be empty.';
$string['help_page_extension'] = 'Messaging extension';
$string['help_page_extension_desc'] = 'With the messaging extension feature you can find and share any content from the catalogue <strong>(Find learning)</strong> 
into your chats with other users.';
$string['help_page_extension_instruction'] = 'As with any other MS Teams messaging extension, you can find the Totara messaging extension in the chat toolbar. 
Click the Totara icon to share content, such as courses or resources, with other users. The recipient can then click the link to view the content in the Totara app.';
$string['help_page_extension_pinning'] = 'You can also pin the messaging extension app to the side panel by right-clicking it and selecting <strong>Pin.</strong>';
$string['help_page_library_playlist'] = '<strong>Your playlists:</strong> Any public or private playlists you have created';
$string['help_page_library_resource'] = '<strong>Your resources:</strong> Any public or private resources you have created';
$string['help_page_library_shared_with_you'] = '<strong>Shared with you:</strong> Any public resources that have been shared with you by other users';
$string['help_page_library_saved_playlist'] = '<strong>Saved playlists:</strong>  View all of the playlists you have bookmarked';
$string['help_page_library_saved_resource'] = '<strong>Saved resources:</strong> View all of the resources you have bookmarked';
$string['help_page_library_createtab'] = 'Select the plus icon next to <strong>Your resources</strong> to create a new resource or survey, or the icon next to <strong>Your playlists</strong> 
to create a new playlist.';
$string['help_page_library_tab'] = 'If your site uses Totara Engage you will also have access to the <strong>Library</strong> tab. Using the left-hand navigation bar you can access the following:';
$string['help_page_pinning_app'] = 'Pinning the app';
$string['help_page_pinning_app_adding'] = 'On the left-hand navigation bar, click the ellipsis icon and search for the Totara app using the search bar. Once you have found the app, 
right-click it and select Pin. This will ensure the Totara app is always available in the side-bar.';
$string['help_page_pinning_app_viewing'] = 'You may find that the app has already been pinned to your navigation bar by an administrator.';
$string['help_page_product_doc'] = 'Totara’s product documentation.';
$string['help_page_title'] = 'Totara Help';
$string['help_page_using_app'] = 'Using the app';
$string['help_page_using_app_course'] = 'From within the application you can access a range of Totara content such as courses, 
programs, certifications, resources and playlists. You can also share content with other users via the messaging extension, 
and receive Totara notifications through Microsoft Teams.';
$string['help_page_using_app_more_info'] = 'For more information on configuration visit ';
$string['help_page_using_app_tabs'] = 'Here you can find out the basics of using the Totara app in Microsoft Teams. This includes 
tabs for <strong> Find learning, Current learning, Your library</strong> and Totara notifications including tasks and alerts.';
$string['howtouploadapp'] = '';
$string['info:badsettings'] = 'One or more settings are not correctly set.';
$string['info:goodsettings'] = 'All settings have been verified, you can download the manifest file.';
$string['manifest_downloadbutton'] = 'Download manifest file';
$string['pluginname'] = 'Microsoft Teams';
$string['report:config'] = 'Information';
$string['report:report'] = 'Report';
$string['report:status'] = 'Status';
$string['report:status_failed'] = 'Failed';
$string['report:status_pass'] = 'OK';
$string['report:status_skipped'] = 'Skipped';
$string['report:summary'] = 'Verification of settings';
$string['settings:bot_app_id'] = 'Bot app ID';
$string['settings:bot_app_id_help'] = '';
$string['settings:bot_app_secret'] = 'Client secret';
$string['settings:bot_app_secret_help'] = '';
$string['settings:bot_feature_enabled'] = 'Bot feature enabled';
$string['settings:bot_feature_enabled_help'] = '';
$string['settings:header_app'] = 'Set up the Totara app';
$string['settings:header_app_help'] = '';
$string['settings:header_bot'] = 'Set up the conversational bot';
$string['settings:header_bot_help'] = '';
$string['settings:header_branding'] = 'Customise the Totara app';
$string['settings:header_branding_help'] = '';
$string['settings:header_publisher'] = 'Publisher information';
$string['settings:header_publisher_help'] = '';
$string['settings:header_sso'] = 'Set up single sign-on';
$string['settings:header_sso_help'] = '';
$string['settings:install_msteams_app'] = 'Install your Totara app on Microsoft Teams';
$string['settings:manifest_accent_colour'] = 'Accent colour';
$string['settings:manifest_accent_colour_default'] = '#FFFFFF';
$string['settings:manifest_accent_colour_help'] = 'Select the background colour of an app icon.';
$string['settings:manifest_app_desc'] = 'Short description';
$string['settings:manifest_app_desc_default'] = 'Access your Totara learning content within Microsoft Teams.';
$string['settings:manifest_app_desc_help'] = 'A short description for the app is required (80 characters or less). This is used when space is limited.';
$string['settings:manifest_app_full_name'] = 'Full name';
$string['settings:manifest_app_full_name_help'] = 'A full name for the app is optional and only needed if your preferred name exceeds 30 characters. The full name must be 100 characters or less.';
$string['settings:manifest_app_fulldesc'] = 'Full description';
$string['settings:manifest_app_fulldesc_default'] = 'The Totara app for Microsoft Teams allows you to access your learning and collaborate with others within Teams. You can access current learning, explore learning content, create and interact with playlists and resources.';
$string['settings:manifest_app_fulldesc_help'] = 'A full description for the app is required. Your short description must not be repeated within the long description.';
$string['settings:manifest_app_icon_colour'] = 'Full colour icon';
$string['settings:manifest_app_icon_colour_help'] = 'Upload a full-colour icon in png format.';
$string['settings:manifest_app_icon_outline'] = 'Outline icon';
$string['settings:manifest_app_icon_outline_help'] = 'Upload a transparent outline icon in png format.';
$string['settings:manifest_app_id'] = 'Manifest app ID';
$string['settings:manifest_app_id_help'] = 'A unique identifier for this app. It must be a registered GUID.';
$string['settings:manifest_app_name'] = 'Short name';
$string['settings:manifest_app_name_default'] = 'Totara';
$string['settings:manifest_app_name_help'] = 'A short name for the app is required (30 characters or less).';
$string['settings:manifest_app_version'] = 'App version';
$string['settings:manifest_app_version_default'] = '1.0.0';
$string['settings:manifest_app_version_help'] = 'If you update any settings for Teams integration, the version must be incremented and it must follow the <a href="{$a->semverurl}" target="_blank">semver</a> standard.';
$string['settings:manifest_package_name'] = 'Package name';
$string['settings:manifest_package_name_help'] = 'A unique identifier for this app in reverse domain notation.';
$string['settings:messaging_extension_enabled'] = 'Messaging extension feature enabled';
$string['settings:messaging_extension_enabled_help'] = '';
$string['settings:oauth2_issuer'] = 'OAuth2 service';
$string['settings:oauth2_issuer_help'] = 'The <a href="{$a->authurl}">OAuth2 authentication plugin</a> must be enabled for single sign-on. The <a href="{$a->issuerurl}">OAuth2 service</a> must use Microsoft\'s identity provider.';
$string['settings:oauth2_issuer_none'] = '(None)';
$string['settings:page_setup'] = 'Microsoft Teams integration';
$string['settings:page_totara_app'] = 'Totara app installation';
$string['settings:publisher_mpnid'] = 'Microsoft Partner Network ID';
$string['settings:publisher_mpnid_help'] = 'Leave this field as blank unless your organisation has joined the <a href="https://partner.microsoft.com/" target="_blank">Microsoft Partner Network</a>.';
$string['settings:publisher_name_help'] = 'The name of the organisation publishing the app (32 characters or less).';
$string['settings:publisher_privacypolicy_help'] = 'The https URL to the organisation or publisher\'s privacy policy.';
$string['settings:publisher_termsofuse_help'] = 'The https URL to the organisation or publisher\'s terms of use.';
$string['settings:publisher_website_help'] = 'The https URL to the publisher\'s website.';
$string['settings:sso_app_id'] = 'SSO app ID';
$string['settings:sso_app_id_help'] = '';
$string['settings:sso_scope'] = 'Resource scope';
$string['settings:sso_scope_help'] = 'Format: api://<fully-qualified-your-domain-name.com\>/<app_id_for_sso\>';
$string['spinner_loading'] = "Loading\u{2026}";
$string['spinner_signingin'] = "Signing in\u{2026}";
$string['tab:catalog'] = 'Find learning';
$string['tab:help'] = 'Help';
$string['tab:library'] = 'Library';
$string['tab:mylearning'] = 'Current learning';
$string['userdataitemuser'] = 'MS Teams user records';
$string['userdataitemusersetting'] = 'MS Teams user settings';
$string['userdataitemuserstate'] = 'MS Teams user authentication state';

// Deprecated since Totara 13.3
$string['botfw:msg_help'] = 'To find out what you can do with this app please contact your site administrator.';
$string['settings:manifest_package_name_default'] = '';