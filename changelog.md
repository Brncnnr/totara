Release 17.3 (30th January 2023):
=================================

Security issues
---------------

  TL-36038 Fixed prototype pollution issue in tui/util
  TL-36117 Prevent blind SSRF in external tool activity

    While configuring an external tool activity, it was possible to initiate
    server-side requests to user-defined URLs. The responses were not visible to the
    user. The risk associated with this has been mitigated by only allowing these
    requests to go to public IP addresses. If private or restricted IP addresses
    need to be used while configuring this tool, addresses can be added to the
    $CFG->link_parser_allowed_hosts config setting in the config.php file.

  TL-36118 Sesskey no longer exposed in the request URL when editing a course announcement
  TL-36119 Fixed permission check when requesting to join workspaces

    Previously, privileged roles with specific configuration could request to join
    private hidden workspaces by modifying API requests. Permission checks have now
    been fixed to prevent this.

  TL-36245 Restricted sending emails to admins from the default Moodle 404 error page.

    Currently, any user, even a guest account, can submit the default Moodle 404
    error page form, and there is no capability set up to restrict the behaviour.
    This patch adds a capability wrapper so the admin can control the behaviour.

New features
------------

  TL-28559 Added site administration tool to test outgoing email settings

    Settings > Server > Email > Test outgoing email settings allows the admin to
    send an email from Totara, with SMTP debugging information displayed.

  TL-33784 Scheduled user actions

    You can schedule actions to occur on users, using filters. This initial version
    allows you to delete users who are suspended for a certain amount of time, and
    optionally restrict by audience. In conjunction with configured purge types,
    this is designed to assist organisations in automating the deletion of user data
    in line with their own data retention policy and relevant GDPR requirements.

  TL-34972 Added a new show and hide input for client secrets

    Client secrets can now be viewed by clicking on the new 'Show/Hide' button on
    the API clients and OAuth2 provider details pages

  TL-35991 Created new core component InputGroup to handle multiple use cases for password input field

Performance improvements
------------------------

  TL-36192 Updated user data query to convert username to lowercase prior to execution

    The user data query was querying with the username in a case-insensitive way,
    this change converts the username to lowercase prior to execution which should
    improve the query's performance.

  TL-36204 Removed a needless check for new notifications happening at the start of each page
  TL-36206 Improved the performance of the Global Search API

    This patch improves the performance of the Global Search API in how it finds
    searchable areas.

  TL-36236 Optimised the query which removes orphaned competency assignment user records

    In some circumstances, this function was taking a long time to run and timing
    out. The sql in the function was optimised.

Improvements
------------

  TL-34302 Added a scheduled task for deleting expired oauth2 access tokens from the database
  TL-34919 Disallowed api user to update locked fields without valid capability if fields are locked by auth plugin
  TL-35168 Added new after_require_login and after_config hooks

    There is a new hook at the end of the require_login() function
    "after_require_login" that can be used to customise the function.

    There is also a new hook at the end of setup.php "after_config" allowing
    customisations to be run as soon as possible after the config has been loaded.

  TL-35223 Added support for tenant isolation=on to user API
  TL-35315 Added custom field type property to core_user.custom_fields object
  TL-35483 Allow admin approve requests that require role approval
  TL-35947 Added job assignments to core_user type for the external API
  TL-36134 Added support for tenant isolation to job assignments external API
  TL-36137 Added a description to the 'Enable comments'  setting under 'Shared services settings'
  TL-36154 Added HTTP HEAD request support for files
  TL-36179 Improved error handling on tenant participant report page when opening the report without the required paramater
  TL-36187 Added additional languages support to LinkedIn Learning
  TL-36196 Cherry-picked MDL-64454 : Admin screen should show warning if cron does not run frequently

    A new config option has been added to specify a maximum time elapsed since last
    cron run before showing the warning about running the cron on the admin pages.
    Previously it would show after 24 hours, this allows for more regular checks.
    Default setting is 200 seconds.

  TL-36208 Added CLI script that allows plugins to be programatically uninstalled

    A new CLI script has been added at server/admin/cli/uninstall_plugins.php
    It can be used to programmatically run the uninstall routines for plugins.
    It also can list plugins, including missing plugins which have been previously
    installed, but which are no longer have code in place.

  TL-36210 Redis cache store can now compress data before storage

    The Redis cache store now has a compression setting that allows a site to
    configure a Redis cache store to compress data before it is sent for storage.
    The options available are no compression, gzip compression, and zstandard
    compression (providing zstandard is available).

  TL-36217 Added a new event that is triggered when an admin uses the database search and replace tool

    Port of MDL-68193 / MDL-68276 to provide audit trail when values are replaced in
    the database.

Bug fixes
---------

  TL-33781 Fixed single quotation marks in notifications subject line
  TL-35175 Fixed PHP warnings for Moodle forms
  TL-35213 Fixed missing validation of custom field unique values in user upload
  TL-35306 Modifed the order for Seminar attendance tracking in sessions dropdown
  TL-35342 Fixed gender specific language in lang strings

    Replaced various instances of the word "his" with "their" when referring to the
    user

  TL-35343 HTML emails will use the plain text content if no dedicated HTML content was provided
  TL-35382 Fixed the Content-Disposition header for file download
  TL-35401 Fixed 'Trainer sessions details changed' seminar notification not being sent
  TL-35415 Sanitised sort order parameter in search_users() and get_users_listing()

    These two functions are not used in core, however they may be used by third
    party plugins or customisations.
    The functions have been deprecated at the same time.

  TL-35498 Prevented sending booking start and end date notifications when legacy notifications enabled

    The booking start date and booking end date centralised notifications were not
    observing the seminar legacy notification setting, causing these notifications
    to be sent even if the seminar was configured to use legacy notifications. Now,
    these notifications will only be sent if either the individual seminar or the
    seminar site setting is set to use centralised notifications.

  TL-35761 Fixed alias PDF file forece download when using filesystem repository
  TL-35780 Removed adhoc task to close subject instances when activity has been deleted
  TL-35802 Fixed tenant theme favicons prior to login
  TL-35817 Fixed an undefined "forceview" variable in the external tool activity type
  TL-35851 Fixed memory issue on embedded evidence type report
  TL-35860 Changed icon in popup notifications related to forum module
  TL-35894 Removed user-to-user message settings dependency from the notification

    The core_message::mark_notification_read function is no longer interrupted by
    user-to-user messaging settings to allow it to work for the notifications sent
    via the 'site notification' delivery method.

  TL-35913 Cherry-picked MDL-67695: Use correct return structure for get_tool_proxies
  TL-35944 Fixed issue with program message paragraph formatting not maintained during the upgrade

    When the message contains line-breaks, it must be considered a separate
    paragraph. Otherwise, the line-break won't work with the weka editor after the
    migration.

  TL-35956 Fixed a crash when exporting reports in excel format.

    Previous fields in reports that are numbers ending in a new line character would
    export in PHP 8 and in non-xlsx formats, but crash with the xlsx format. With
    this fix the behaviour is the same, and these reports can be exported as xlsx
    files.

  TL-35988 Fixed undefined variable in Record of Learning: Previous Certifications report
  TL-36054 Fixed incorrect capability checks occuring when a user resets their own course completion

    Prior to this fix, if a course contained an assignment and the user who held the
    totara/core:archivemycompletion capability, then when they reset their course
    completion they would get an error about missing mod/assign:grade capability.
    This has been fixed. The mod/assign:grade capability is not required when a user
    who has the totara/core:archivemycompletion capability is resetting their course
    completion.

  TL-36098 Fixed display of the your workspaces interface when the user has lost access to the last workspace they entered
  TL-36099 Added clarification to the help text on the 'upload course records' page around evidence records
  TL-36116 Fixed missing captions in report builder tables to improve screen reader compatibility
  TL-36121 Fixed by when displaying users enrolled in a course without any roles
  TL-36123 Improved the styling of the 'your progress' element on the course page to prevent it being hidden behind topic headers
  TL-36126 Fixed add attendees capability when seminar event is over

    When seminar event is over and a user does not have the "Ability to signup
    people on past events" capability then "Add users", "Add users via file upload"
    and "Add users via list of IDs" options will be removed from "Add attendees"
    actions.

  TL-36128 Resolved response_debug parameter resetting if no value is provided
  TL-36136 Prevent tenant user with system capability from editing users in a different tenant

    Tenant users should not be able to edit users from a different tenancy, but
    prior to this patch a tenant user with 'moodle/user:update' capability in the
    system context could do so in some situations. This has been fixed.

  TL-36138 Fixed that adding text area custom course field in notification adds html tags to notification
  TL-36155 Added a check for existing record in recommender interactions table before inserting a new one
  TL-36185 Removed usages of get_magic_quotes_gpc() function

    The PHP function get_magic_quotes_gpc() is deprecated since PHP 7.4 and got
    removed in PHP 8.0. The related setting magic_quotes_gpc got removed with PHP
    version 5.4 and the get_magic_quotes_gpc() function always returns false.

    To avoid debugging messages in PHP 7.4 or fatal errors in PHP 8 the usages of
    this function has been removed.

  TL-36335 Fixed incorrectly formatted SQL ORDER BY in job assignments API
  TL-36336 Fixed crash with invalid timezones on PHP 8.1.14

    In previous versions of PHP it was possible to set user timezones to
    non-standard values such as Etc/GMT+2 via HR Import. These timezones did work,
    but PHP has warned they are unreliable. In PHP 8.1.14 these timezones stopped
    working, and any user with that style timezone set will crash upon logging in.

    With this patch if you are running PHP 8.1.14 these invalid timezones will be
    substituted for the equivalent location timezone to allow users to log in. In
    all cases you can check if users have invalid timezones set by accessing the
    Site administration > Localisation > User timezone check page and updating them
    in bulk there.

  TL-36337 Fixed accessibility of Show/Hide button in OAuth 2 provider details
  TL-36348 Fixed bug resulting in full site cache being purged when a course is deleted
  TL-36374 Fixed slow query that counts the number of unread conversations
  TL-36418 Fixed reporting of Throwable errors in scheduled tasks
  TL-36422 Fixed the issue where an external respondent viewed a page for a performance activity, and the subject user profileimagealt field had a null value
  TL-36428 Updated 'display_string_params' field type from 'notification_event_log' table to 'text'

    The field type of 'display_string_params' in table 'notification_event_log' was
    causing failures when the value was too long. The 'display_string_params' field
    type has been updated to 'text'.

Database upgrades
-----------------

  TL-36237 Introduced index for suspended column on user table

Tui front end framework
-----------------------

  TL-36239 Fixed audience adder being cut off in Safari 13.1
  TL-36251 Fixed issue where buttons in dropdowns would not get separators applied

Contributions
-------------

  * Carlos Jurado - Kineo UK - TL-36374
  * Wajdi Bshara - Xtractor - TL-36335



Release 17.2 (14th December 2022):
==================================

Security issues
---------------

  TL-35199 Fixed an issue allowing an external API client with limited capability to access hidden user profile fields

    The `moodle/user:viewalldetails` capability is now required for an external API
    service account to query or mutate any custom user profile fields with
    visibility set to 'Not visible' or 'Restricted visibility'. This brings the API
    service in line with behaviour of the web interface for updating user profiles.

Improvements
------------

  TL-34846 Extended create/update user services to offer more password-related options

    Boolean fields have been added to the create and update user mutations for
    'force_password_change' and 'generate_password'.

  TL-35898 API client can now delete an existing user custom profile field value

    This improvement adds a 'delete' flag to the 'custom_fields' per-field input for
    the 'core_user_update_user' mutation, allowing an external API client to remove
    custom user profile field values when updating a target user's profile.

  TL-35942 Added ability to change external API debug level setting per API client

    Each API client can now have its own debug level setting.

    This introduces a breaking change in the pre- and post-request hooks that were
    released with Totara 17.0. Hook instantiation now requires the
    \totara_webapi\server instance to be passed as a third parameter.

Bug fixes
---------

  TL-35203 Added the location details to seminar calendar export ical

    The location was not included as a field in the external calendar, and it won't
    show in the iCal after export. As a part of this patch, location detail has been
    added to the export iCal.

  TL-35432 Fixed folder downloading for cloud storage

    When downloading a folder the system will now attempt to retrieve the file from
    cloud storage if the files / subdirectories are missing prior to downloading the
    zip of the folder.

  TL-35497 Fixed unique notification_event_log entries to include the event_data

    Previously, it could happen that for the same type of event only one event_log
    entry got created even if the event data is different. This has now been fixed.

  TL-35852 Fixed a bug in LinkedIn Learning Classification sync scheduled task that caused unused classifications to remain in the database
  TL-35952 Fixed the report builder Tui display component for the 'Performance Activity response reporting: Subject users' embedded report
  TL-36040 Fixed an error in the delete_workspace_task adhoc task when permissions for the user have changed since it got scheduled
  TL-36053 Fixed notification queues breaking on Errors

    Previously the notification queues caught thrown Exceptions and continued to
    send the rest of the messages in the queue. However an Error or other Throwable
    would still break the queue. These have been updated to catch all Throwables, to
    allow as many valid notifications to be sent as possible while logging all
    invalid ones.

  TL-36086 Fixed being able to set a blank password in create/update user GraphQL mutations



Release 17.1 (29th November 2022):
==================================

Improvements
------------

  TL-34814 Added a warning to seminar adhoc messages when legacy notifications are disabled
  TL-34991 Added totara_job_job_assignments query to the external API

    A new query 'totara_job_job_assignments' is added to the external API to fetch
    all job_assignments, providing options for sorting and paginating results.

  TL-35074 Ensure sort order for seminar sessions is consistent
  TL-35156 Allows users to access their notification logs

    This adds a new capability 'totara/notification:auditownnotifications'. This
    will allow the user to view their own notification logs. This is not added to
    any role by default.

  TL-35230 Added the 'Section title' multi-select filter to the performance activities response data report
  TL-35278 Added default filters to the performance activity response data reports for 'Element type', 'Response data text' and 'Review type'
  TL-35410 Added default error response_debug site setting

    Added a response_debug setting to the API site-level settings to configure the
    amount of error information returned in API responses

  TL-35628 Improved documentation for API client settings token expiration form field
  TL-35887 Deprecated the setting to use the legacy program interface

    In Totara 12.8, a new program assignments interface was added, which was more
    scalable for large sites, and a setting was introduced for users wanting to
    continue using the old interface. The setting has now been deprecated and the
    old program assignment interface will be removed in TXP 18.

  TL-35895 Added support for privacy-aware page titles to $PAGE object

    It is now possible for a page to optionally set a 'privacy-aware' page title as
    well as the normal title. This title is stored in the $PAGE object and can be
    retrieved when a page title that does not contain personally identifiable
    information is needed.

    We have modified all known pages that contain the users' names to specify a
    second title that excludes the name.

    A use case for this is to avoid sending Personal Identifiable Information (PII)
    to a third-party analytics service.

Bug fixes
---------

  TL-34893 Fixed cloning of perform elements not cloning related files after draft files are cleaned up
  TL-35112 Fixed allowing selection of resources when the user is not allowed to share a resource

    A user that is not allowed to share a resource can no longer select resources to
    share.

  TL-35149 Fixed not being able to complete a quiz when only the required grade is checked
  TL-35222 Fixed search for users via email when choosing a manager for a job assignment
  TL-35381 Fixed 'event under minimum bookings' centralised notification being sent when centralised notifications are disabled
  TL-35426 Fixed error being triggered in LinkedIn cron task due to unwanted records left in 'totara_contentmarketplace_course_module_source' table
  TL-35463 Fixed the accessibility of the Tui FormRow component when help icon is present
  TL-35486 Fixed multi-lang filter not direct filtering json_editor content unless it contained a placeholder
  TL-35520 Fixed validation for recipients when creating centralised notifications
  TL-35526 Fixed resource card width on dashboard sidebar blocks
  TL-35532 Fixed GraphQL mutation to enable notification without passing the field 'is_enabled'
  TL-35606 Updated the help string for the service account selector in the UI
  TL-35664 Fixed typo in the days to keep notification logs settings description
  TL-35665 Fixed typo in context help for notification delivery preferences
  TL-35792 Fixed Perform notification placeholder 'subject_instance:created_at' displaying an unformatted date
  TL-35841 Fixed an error being triggered when clicking on the comment submit button while reply textarea is still loading

    The submit button on a comment reply form is now disabled while the component is
    loading.

  TL-35862 Added debugging messages for deprecated notification preference functions

    The following deprecated functions have had debugging messages added:
    * notification_preference_builder::set_recipient()
    * notification_preference_value::get_recipient()
    * notification_preference::get_recipient()
    * create_notification_preference::resolve()
    * update_notification_preference::resolve()

  TL-35876 Fixed 'All participants' column in the Course completion report source returning more than one record
  TL-35891 Added a MariaDB optimizer hint for search depth to fix an issue where some versions of MariaDB could get stuck on Perform response reporting

    MariaDB versions 10.5 and earlier have an issue with the default setting for
    optimizer_search_depth where some complex queries can cause the optimizer to
    take a long time to generate a query plan. This can be fixed by adjusting the
    optimizer_search_depth setting. This patch introduces the ability to add an
    optimizer hint for this purpose on a per-query basis. It makes use of the
    existing moodle_database::get_optimizer_hint() functionality.

    Such an adjustment has been applied to the performance activity response data
    reporting, where this problem occurred. This particular adjustment can still be
    overridden by setting a value for
    $CFG->mariadb_search_depth_perform_response_reporting in config.php.

Technical changes
-----------------

  TL-35277 Remove unneeded JS checks for core component



Release 17.0 (08th November 2022):
==================================

Important
---------

  TL-35320 Updated system requirements

    * Increased the minimum required PHP version to 7.4.3
    * Added support for PHP 8.1
    * Added support for MariaDB 10.7.2+
    * Added support for MariaDB 10.8.3+
    * Increased minimum required Python version for Machine Learning to 3.7

    Information on our recommended system environments can be found on the help
    site:
    https://help.totaralearning.com/display/TPD/Recommended+hosting+environments

New features
------------

  TL-29075 Approval workflows platform with XState

    The approval workflows feature is designed to give Totara developers and admins
    a system for modelling business processes that require a user to submit
    information and/or get approval from one or more other users before something is
    allowed to happen. It consists of a workflow manager (for admins) and an
    applications dashboard (for applicants and approvers).

    It includes the XState front-end framework for managing the complexity of
    single-page Tui applications in a systematic way.

    This is a developer preview release, which means:
    * There is a single, simple approvalform plugin, and no integration with other
      parts of Totara TXP.
    * Application form definitions must be created by a developer, and embedded in
      an approvalform subplugin.
    * Simple workflows can be created by admins, but complex workflows must be
      scripted by a developer.
    * A custom report source and table is required to produce an application report
      with columns that reference submitted form data.
    * Approval workflows is disabled and off-menu by default.

  TL-31318 Competency achievement paths can now be copied to other competencies in bulk

    To improve the efficiency of managing competency achievement paths we've
    introduced the ability to copy an existing achievement path onto many other
    competencies.

    As the achievement path may be dependent upon the competency's scale, the admin
    can only copy an achievement path to competencies in the same framework.

    The copied achievement path will overwrite any pre-existing achievement path on
    the copy to competencies, the action is not reversible.

    The copy action does not copy courses, or child competencies associated with an
    achievement path.

    The copy action does copy courses linked from a learning plan.

  TL-34139  External GraphQL API

    Added a new external API framework to provide a modern, flexible and extensible
    API to allow third-party services to access and modify Totara functionality and
    data.

    Features include:
    * GraphQL endpoint with OAuth 2.0 for authentication.
    * Administration interface for managing multiple individual API clients.
    * Each API client uses a service user account to allow access control to be
      managed via roles and capabilities.
    * New preconfigured 'API user' role.
    * Site-wide and per-client configuration options to control if client is
      enabled, API usage (such as rate limiting), token expiry times and debug message
      levels.
    * API events logged in site logs, filterable via 'API' source option.
    * Multitenancy aware, allowing for the creation and management of tenant-level
      API clients by tenant members, with appropriate access limitations.
    * In-product reference documentation, providing up-to-date reference
      documentation for the API based on the site version, including any third-party
      API customisations.

    The new API is disabled by default, but can be enabled for Enterprise customers
    via the 'Configure features' administration menu option.

    See the developer documentation for more information about Totara APIs:

    https://help.totaralearning.com/display/DEV/API+documentation

  TL-34707 Auditing for centralised notifications

    After a site has been upgraded to Totara 17, the centralised notifications
    system will start recording logs of which notifiable events have been triggered,
    and which notifications are sent out as a result. These logs can be viewed by
    users with the permission totara/notification:auditnotifications, by following
    the 'View notification logs' link on the notification management page, or user
    profile.

    These logs are displayed via embedded reports, as such there are three new
    report sources:
    * Notification event log - Displays logs for all the events that trigger a
      notification
    * Notification log - Displays logs for all the notifications triggered by an
      event
    * Notification delivery log - Displays logs for all messages sent by a
      notification

    For more information see
    https://totara.help/17/docs/auditing-centralised-notifications

  TL-34796 Added API services to support creating, updating, deleting and viewing users and job assignments

    Added API services to the new external API framework (see TL-34139) to allow
    external access to core HR services. This includes:
    * Creating users
    * Updating users
    * Deleting users
    * Suspending or unsuspending users
    * Fetching a list of users and their profile data
    * Creating job assignments
    * Updating job assignments
    * Deleting job assignments

    User services include support for fetching and updating user profile fields, but
    not for creating or modifying the fields themselves via the API.

    Job assignment services include support for setting organisations and positions,
    though not for managing the position and organisation frameworks themselves via
    the API.

    For more information on HR services syntax and supported options, see the
    in-product API reference documentation.

  TL-35158 Layout support in Weka

    As a result of user research, support for multi-column layout has been added to
    the Weka editor. This allows users and content authors more flexibility in how
    they choose to present content.

    There are options for one-, two- and three-column layouts, with varying column
    sizes.

    Weka layouts are available in Engage resources, Perform static content elements,
    and in Totara Learn when using Weka as the editor.

    In Totara Mobile, content added to Weka layouts will not be visible as it does
    not yet support layouts.

Security issues
---------------

  TL-35316 Removed database migration tool web interface

    The experimental database migration tool web interface located under
    'Development > Experimental > Database migration' has been removed. Please use
    the command line version of this tool instead.

    Also available in 16.7 and later releases.

Performance improvements
------------------------

  TL-33272 Regrading of large courses is now offset to cron

    When a course has more than 100 enrolments or 100 grade items, any regrading
    necessary (such as adding a new activity or changing grade settings) will be
    done on the next cron run rather than blocking page load. When this happens, a
    message is displayed to the user to let them know that grades are being
    recalculated.

    For smaller courses, the regrading is done in real time.

    This is a follow-up to an earlier patch (TL-31570), which introduced background
    regrading, but only when adding a new activity.

    Also available in 16.2 and later releases.

  TL-33362 Improved the loading time of the course enrolled users page

    Also available in 16.1 and later releases.

  TL-33363 Deleting an enrolment instance has been shifted to a background task

    Previously when deleting an enrolment instance from a course, users would be
    unenrolled immediately and then the instance would be deleted. If the number of
    enrolled users was large, the page may take a long time to respond.

    With this patch, the deletion is shifted into a background task run on the next
    cron run.

    Also available in 16.2 and later releases.

  TL-34063 Improved the performance of the user activity page

    Also available in 16.1 and later releases.

  TL-34361 Improved the performance when a user signs up for a seminar event

    When a user books for a seminar event, the system verifies whether this will
    result in a booking conflict. Previously, this check was done multiple times
    during the signup process. This patch now caches the result for one minute,
    allowing the reduction of repeating the same query multiple times during this
    time.

    Also available in 16.6 and later releases.

  TL-34382 Improved performance for the user search when selecting performance activity participants

    Also available in 16.2 and later releases.

  TL-34400 Fixed GraphQL performance regression from latest graphql-php library update

    The latest version of the webonyx/graphql-php library added schema validation
    that is unnecessarily repeated for each call by default. This patch switches the
    unnecessary validation off, improving performance of all GraphQL operations.

    Also available in 16.2 and later releases.

  TL-35218 Improved the performance of the current learning block and the GraphQL query returning the current learning items for Mobile

    Also available in 16.5 and later releases.

  TL-35721 Improved the performance of Manager's manager relationship with MariaDB

    This was most noticeable while activating a performance activity using the
    Manager's manager relation, where multiple relations needed to be fetched.

    Also available in 16.6 and later releases.

  TL-35726 Fixed overzealous user loading while viewing a users performance activities

    Previously while loading the subject instances for a participant in a
    performance activity, a lot of unnecessary users were loaded into memory to
    generate the relations. This has been fixed to only load relevant users.

Improvements
------------

  TL-20269 Added a setting and scheduled task to delete old records from the course completion log

    The course completion log table stores transaction history for the completion
    editor, and can grow very large on sites with a lot of activity. A new 'Delete
    course completion logs after' setting allows admins to automatically cull the
    oldest records from the log. Once those records are deleted, they will no longer
    appear in the completion editor as history.

    The default is to never delete old logs, and no data is purged on upgrade until
    the setting is changed.

    Also available in 16.1 and later releases.

  TL-22579 Added new alignment setting for the featured links block

    Added the following options:
    * Left align
    * Centre align
    * Right align
    * Justified

    The justified option will position the links with even spacing across the block.

  TL-25521 Implemented visibility options for site policies

    Site policy visibility can now be set to all users (the default), authenticated
    users only, or guest users only.

    Also available in 16.1 and later releases.

  TL-29549 Added displaying manual rating comments in the competency activity log

    Comments that were added when manually rating a user's competency will now be
    displayed in the user's activity log for that competency.

    Also available in 16.2 and later releases.

  TL-30485 Updated strings on the Engage access form when creating a resource and added an info icon button to the topic selector

    Also available in 16.4 and later releases.

  TL-31660 Improved the help text for seminar third-party email setting

    Also available in 16.1 and later releases.

  TL-32119 Added the missing event trigger for suspended users

    Also available in 16.2 and later releases.

  TL-32731 Clicking on an Engage article no longer triggers editing mode

    Users who have permission to edit an Engage article are no longer put into edit
    mode when they click on it. This allows those users to follow links and interact
    with the article as users normally would. A new edit icon is added to the
    article for those who have permission to edit it that enables them to toggle in
    and out of editing mode.

    Also available in 16.7 and later releases.

  TL-33052 Added a seminar 'Attendance status' report builder column and filter

    Also available in 16.2 and later releases.

  TL-33261 Improved the visual display of information within the Course completion status block

  TL-33365 Changed 'Course compatible in-app' setting to 'Mobile-friendly course' and updated the help text

    When the Totara Mobile app is enabled, courses that are marked as
    'Mobile-friendly' will open in the app; those that are not will be opened in the
    mobile web browser instead. The behaviour of this setting has not changed, only
    the label and help text explaining it.

    Also available in 16.1 and later releases.

  TL-33439 Improved the help text regarding the use of event roles in seminar activities

    Also available in 16.1 and later releases.

  TL-33491 Started recording any changed HR Import settings within the config log database table

    Also available in 16.2 and later releases.

  TL-33498 Fixed missing legacy 'Session date/time changed' message when removing the last session of a seminar

    When the last session of a seminar event is removed, all appropriate users will
    now receive a 'Session date/time changed' message with an ical attachment to
    allow the removal of the calendar entry from their calendars.

    Also available in 16.1 and later releases.

  TL-33661 Replaced the 'Close' button with 'Cancel' button in the 'Manage participation' modal for performance activities

  TL-33738 Improved wording when setting course completion date

    Also available in 16.1 and later releases.

  TL-33790 Keyboard shortcut (ctrl + k) added to open the Weka link dialog

    Also available in 16.2 and later releases.

  TL-33851 Ensured all roles names consistently use title case

  TL-34051 Added spacing on delete topic confirmation modal body text

    Also available in 16.1 and later releases.

  TL-34135 Pass OAuth 2.0 request data to xAPI statement created event

    Upon a successful Oauth 2.0 authentication during an xAPI statement request, the
    system now stores the client_id in the event metadata, allowing event listeners
    to identify the specific source of the statement.

  TL-34145 Improved the select/deselect all functionality when looking at the question bank

    Also available in 16.1 and later releases.

  TL-34166 Improved wording in content visibility settings of resources and playlists

    Also available in 16.4 and later releases.

  TL-34228 Removed the separation of evidence shown in Record of Learning and the Evidence bank

    There is no longer any separation of evidence items based on the type of the
    evidence item. The same evidence type can now be used when uploading evidence
    from csv files or when adding evidence items in the Evidence bank, and all items
    can now be shown in both the Record of Learning and Evidence bank reports.

    By default the Record of Learning report will be filtered to only show evidence
    that was uploaded (i.e. their source is 'Completion history import'). Similarly,
    the Evidence bank reports will by default be filtered to only show evidence
    items that were 'Manually created'. As this is a normal report filter, users can
    change / clear the filter to show both uploaded and/or manually created items in
    any one of these reports.

    Also available in 16.2 and later releases.

  TL-34296 Added client side 'alphanumeric' validation and help text for custom field short names to improve user experience

    Also available in 16.3 and later releases.

  TL-34300 Removed broken sorting functionality from the Progress column on the Course completion report

    Also available in 16.1 and later releases.

  TL-34408 Removed webapi index page

    Removed the webapi index page as the information on it belongs in user
    documentation rather than in product. With the addition of the new external API
    this information was out of date and may have caused confusion.

  TL-34482 Added core_string_format and core_text_format to GraphQL schema to fix introspection for fields using these formatters

  TL-34570 Updated the environments checks page to support PHP 7.4.3 as the minimum version

    Added the new Totara 17 server requirements to the environment checks page
    (Quick-access menu > Server > Environment). Totara 17 requires a minimum PHP
    version of 7.4.3.

    Also available in 16.3 and later releases.

  TL-34613 Replaced the side panel on the multi-section performance activity participant view with the ProgressTrackerNav component

    In the participant view for multi-section performance activities, the previous
    side panel has been replaced with the progress tracker component, which includes
    state icons for each section, such as 'view-only'. The page layout breakpoints
    were also amended to better support the progress tracker content.

  TL-34641 Added a notification to Perform that is triggered by participant instance submission

    This gives a Performance Activity Manager the ability to configure notifications
    to be sent when responding participants submit a completed participant instance.
    This has been implemented using the centralised notifications framework, so it
    can be configured at the system level, or for individual performance activities.
    It is disabled by default.

  TL-34647 Improved warnings around making changes to facetoface_displaysessiontimezones

    Also available in 16.2 and later releases.

  TL-34703 Weka improvements

    We have implemented a number of improvements to the baseline Weka functionality.
    * Image size can be set to large, medium, and small, in addition to the existing
      original size option
    * Captions can be added to images
    * Text can be underlined
    * Text (paragraphs and headings) can be aligned to the left/centre/right

    These additions are not currently supported in Totara Mobile.

    Additionally, there are several quality-of-life improvements to the editor
    itself:
    * The Weka toolbar will 'stick' to the top of your screen and remain visible
      when scrolling down on long content
    * Weka now has an integral loading spinner to avoid page layout jumps
    * The menu on image blocks has been redesigned

  TL-34767 Files larger than 5gb can now be uploaded when using cloud file storage with AWS S3

    Also available in 16.3 and later releases.

  TL-34839 Performance activity reporting improvements

    Performance activity response data can now be exported to Excel or viewed in
    Totara. We've also added more filtering options for the response data.

    We have added messaging to Performance activities and reporting prefiltering
    pages regarding the visibility of data.

    We have added a button to the Performance activities page to increase this
    feature's visibility and usage.

    We have also moved the performance activity response data report actions into a
    dropdown menu for each row.

    The single-select 'Element type' filter is now a multi-select filter.

    The single-select 'Relationship name' filter is now a multi-select filter.

    We have added a multi-select filter for 'Review type'.

    We have added text filters for 'Review item name' and 'Parent element'.

  TL-34844 Cherry-picked MDL-46542 to allow restricting duration units menu to a subset of the available units

    Also available in 16.3 and later releases.

  TL-34864 Improved UI behaviour for Tenant default values field when the 'Override with file and defaults' value for existing user details field is selected

    When 'Override with file and defaults' value is selected then 'Tenant default
    values' field will be disabled.

    Also available in 16.4 and later releases.

  TL-34888 Allow escaping hyphens with a backslash in OAuth 2.0 field mapping

    The hyphen character is used as an object nesting divider, i.e.
    'Country-region-city' field will look up $country->region->city in the userinfo
    data source. This prevents using the hyphen character as a regular character. We
    added an ability to use a backslash character before the hyphen to treat it as a
    regular hyphen.

    Also available in 16.4 and later releases.

  TL-34897 Migrated performance activity notifications to centralised notifications

    Previously, performance activities used a custom notifications engine. These
    have now been migrated to the centralised notifications system. This will allow
    custom notifications on an activity level.

  TL-35004 User experience improvements to goals functionality

    Improvements to the goals adder in a goal review:

    To improve the goal selection experience we've changed the current goals adder
    sort order for both personal and company goals.
    * Sort goals in the goals adder by 'Target date', with the latest date being
      first.
    * If multiple goals have the same target date they should then be sorted
      alphabetically (A-Z).
    * Sort goals without a target date after all those with a target date, and then
      sorted alphabetically (A-Z).

    Goal type has been added to the adder content.

    To improve the selection of goals we've added 'framework' and 'type' filters
    to the existing company goal adder and a 'type' filter for personal goals.

    Improvements to the goals user experience:

    When creating a new goal you have the option to select a 'type', which can be
    used to add custom fields to goals. Previously there was no method to refresh
    the page so that custom fields related to the selected type would be available
    to edit.
    To address this we've added a new 'Save and continue editing' button.
    The save action returns the user to the edit form. The custom fields from the
    selected type are included in the form.

    We've added a 'Goal type' column and filters for both company and personal
    goals to allow goals to be sorted and filtered by type.

  TL-35008 Improvements to the performance activities overview participant experience

    To improve the performance activity participant experience, we have made changes
    to the participant activities overview UI, moving away from a 'tables within
    tables' layout, improving the visual information hierarchy, and replacing the
    expanding detail panel with a modal dialogue component.

    We have added heading content to the activity details modal. The heading
    includes Avatar, Username, Type, Creation date, Title and Status. The heading
    will always be displayed, even when there is a large amount of content.

    The 'Print activity' option has moved to the meatball menu on the activities
    summary page.

    We've separated the activity list data table into two different spaces: the
    summary table and a view details modal.

    We've replaced the existing table to include columns for Type, Creation date,
    Due date, Role, Title and Status. There are now visual cues for complete,
    overdue and view-only activities.

  TL-35019 Added the ability to set multiple recipients in centralised notifications

    Previously it was only possible to add one recipient for a notification in the
    centralised notifications system. When creating or editing a notification you
    can now select multiple recipients.

  TL-35052 Increased size of the Totara Menu URL field to allow for a url up to 1333 characters in length

    Also available in 16.4 and later releases.

  TL-35777 Modified user visibility permissions to allow users who can manage API clients to see profile fields of available service account users.

Bug fixes
---------

  TL-32849 Fixed the workspace Transfer ownership modal to provide indication if the current owner account was suspended or deleted. 

  TL-34758 Ensured course forum searches using tags that contain spaces works correctly

    Also available in 16.7 and later releases.

  TL-35053 Fixed code which fetches a list of files to ensure reliable sort order

    Some utility code which was being used to build the graphql schema was obtaining
    a list of files in a way which didn't guarantee the order in which the files
    were returned. This could lead to the schema file being built differently on
    different environments.

    This was fixed so the utility method always returns files in alphabetical order.

  TL-35064 Fixed a bug which prevented deleting a section in a performance activity that includes a review question

    Also available in 16.7 and later releases.

  TL-35138 Blockquotes are no longer removed when saving content in the JSON_EDITOR format

  TL-35154 Added a default sort order based on the 'target date' for goal adders

    Previously the 'cursor_paginator' used by goal adders would throw an error if a
    default sort order was not defined. This default sort order has been added. And
    now when the get_direction() method is used, the system will check that it
    exists in the instance before it is called. If it does not exist then an empty
    string will be returned.

  TL-35170 Fixed email casing when matching users for seminar attendance import 

    Also available in 16.7 and later releases.

  TL-35206 Fixed an error on the course page for certain assignment settings that was displayed when debugging was turned on

    Also available in 16.7 and later releases.

  TL-35506 Added further validation around forced delivery channels when creating / updating notification preferences

    Also available in 16.7 and later releases.

  TL-35516 Fixed wrong grouping when using multi-select custom field

    Also available in 16.7 and later releases.

  TL-35536 Fixed recommending workspaces/resources/courses that are not visible to the user.

    Also available in 16.7 and later releases.

  TL-35548 Fixed duplicate due date program and certification notifications

    Duplicate program and certification due date and course set due date
    notifications were being sent if a user was assigned by more than one method.

    Also available in 16.7 and later releases.

  TL-35572 Removed max-height on responsive images in StaticContentAdminView.vue to fix a bug where the image would stretch when the browser is resized

  TL-35631 Fixed sorting by target dates in goal adder

    The targetdate columns in the mdl_goal and mdl_goal_personal table are nullable
    but existing goal code always regarded nulls as zero. A zero (whether a real
    zero or null) indicated the target date was not set; the goal code implicitly
    assumed a target date would always come from the UI and therefore it would
    always be a non zero date.

    TL-35054 changed the goal adder to sort by target dates by default. However, the
    goal adder currently uses a cursor paginator that cannot handle nullable
    columns. This patch changes the targetdate columns to be non nullable and
    defaulting to 0. This allows the goal adder to paginate by target dates to work
    and makes the implicit 'zero = not set' assumption explicit.

  TL-35644 Prevented transformation of some right to left CSS styles

    In some locations, there were styles for right to left languages that were being
    transformed when they should not have been. These have been fixed so that those
    styles are no longer transformed

    Also available in 16.7 and later releases.

  TL-35654 Updated the Comment component to scroll smoothly to the reply box only if it is outside of the scroll view

    Also available in 16.7 and later releases.

  TL-35666 Allow the placeholder to return an empty string. 

    These changes allow the placeholder to return an empty string which hides the
    whole sentence from the result. If the placeholder returns null, it returns <no
    data available $key>.

  TL-35715 Reduced duplicate queries on report builder when fetching result count

  TL-35736 Fixed a bug where the focus outline was being applied to the wrong element on Weka video and audio blocks

    Also available in 16.7 and later releases.

  TL-35739 Fixed the permission check for logged in users to view user field 'descriptionformat'

  TL-35769 Fixed competency progress tracker not showing values higher than the minimum proficient value as proficient

  TL-35791 Fixed managers full name link notification placeholder so that it renders correctly

    Also available in 16.7 and later releases.

  TL-35810 Fixed a notice that can appear after upgrading from Totara 14 if no recommendations options have been saved.

    Also available in 16.7 and later releases.

  TL-35884 N/A

Database upgrades
-----------------

  TL-35033 Centralised notifications updated to support multiple recipients

    The 'recipient' field has been deprecated from centralised notifications in
    favour of a new 'recipients' field which allows for multiple recipients to be
    added to a single notification.

Technical changes
-----------------

  TL-32667 Implemented a check for JavaScript errors in behat runs

  TL-32931 Updated behat to support PHP 8.0

    Also available in 16.2 and later releases.

  TL-33278 Avoid using required column to allow visibility checks in report builder

    Previously, in order to perform visibility checks in reports, we obtained the
    data needed by defining required columns which were columns that, although not
    visible, were present in the report. However, it was noted they were interfering
    with aggregation, giving unexpected results.

    Now, 'required joins' have been added in order to perform this task. The
    information to perform the visibility check is still present, but should not
    interfere with aggregation.

    All applicable report sources have been updated to use the new
    define_requiredjoins function.

    Please note that custom report sources that use the old way of requiring columns
    shouldn't be affected by this change, but we recommend that they are updated to
    use define_requiredjoins to get the correct result when using aggregation.

    Also available in 16.2 and later releases.

  TL-33368 Moved core entity classes in totara/competency to totara/hierarchy

  TL-33369 Moved course-related entity classes to correct core locations

  TL-34133 The generate_uuid() function has been deprecated

    Please use \core\uuid::generate() instead. If the PECL UUID extension is not
    installed, this new function will use random_bytes() instead of mt_rand(), which
    is more secure.

    Also available in 16.1 and later releases.

  TL-34180 Converted GraphQL endpoint types from constants to classes

    Implemented new abstract base endpoint type class. Existing endpoints (mobile,
    ajax, dev) were converted to use endpoint classes and the new external API
    endpoint type was added.

    Endpoint classes define the properties of the specific endpoint and allow core
    code to be free from endpoint implementation details.

    For more information see our developer documentation:

    https://help.totaralearning.com/display/DEV/Extending+GraphQL+APIs

  TL-34181 Split GraphQL schema by endpoint type

    Previously all GraphQL endpoints shared a single schema. Now the schema has been
    split so that individual endpoint types have their own schema. This allows the
    schema to only include services that are relevant to it.

    For more information see this page in the public developer documentation:

    https://help.totaralearning.com/display/DEV/GraphQL+schema+file+changes+in+Totara+17

  TL-34258 Added support for 'internal' OAuth 2.0 providers

    Added support for 'internal' OAuth 2.0 providers which can be managed by other
    components without showing up in the OAuth 2.0 providers interface.

    This change has no impact on the behaviour of OAuth 2.0 providers via the UI.

  TL-34261 Added support for per-endpoint global GraphQL middleware

    Added support for global middleware that is automatically included for all
    query, mutation and type resolvers of the specified endpoint type (rather than
    needing to be specified in each resolver).

    For more information see the developer documentation:

    https://help.totaralearning.com/display/DEV/Implementing+GraphQL+Middleware

  TL-34263 Relocated GraphQL schema files to appropriate endpoint subfolder

    Moved GraphQL schema files so they appear in the appropriate GraphQL schema. See
    TL-34181 for more details on this change.

  TL-34272 Add low-level support for disabling OAuth 2.0 providers

    Added support for disabling OAuth 2.0 providers in code, allowing other
    components to specify if a provider is active. Inactive OAuth 2.0 providers will
    temporarily reject all access tokens until re-enabled.

    This change does not include the ability to disable OAuth 2.0 providers via the
    UI.

  TL-34328 Added support for customisation of global middleware via a hook

    Add support for a hook that can be used to modify the global middleware that is
    applied to GraphQL resolvers.

    This allows for third-party customisation of the global middleware chain.

    For more information see the API documentation:

    https://help.totaralearning.com/display/DEV/Extending+GraphQL+APIs

  TL-34380 GraphQL endpoints converted to use API controller

    A new base api controller class was implemented and the existing GraphQL
    endpoints (ajax, mobile, dev) were converted to make use of it.

    This allows common functionality to be abstracted and improves the testability
    of GraphQL endpoints.

  TL-34426 Replaced calls to deprecated strftime function for PHP 8.1 support

    In PHP 8.1 the strftime function is deprecated. This is used in various places
    in Totara, for things such as handling date/time formats defined in language
    strings. To continue supporting these language strings as they are, the calls
    have been been replaced with \totara_core\strftime::format which can be swapped
    in.

    All existing placeholders are supported, however the %x, %X and %c placeholders
    may give slightly different results as they are based on your locale and server
    setup.

  TL-34780 Introduced a 'jsoneditor' plugin type for JSON_EDITOR schema extensions

  TL-34783 GraphQL type, query and mutation resolvers are now abstract classes instead of interfaces

    Previously we had interfaces for type_resolver, query_resolver and
    mutation_resolver which were implemented by GraphQL resolver classes.

    Now those interfaces have been converted to abstract classes so that default
    methods can be included. This requires any existing resolver classes to be
    updated - instead of:

    class your_resolver implements type_resolver, has_middleware \{ }

    it should now be:

    class your_resolver extends type_resolver \{ }

    (the same for query_resolver and mutation_resolver).

    The has_middleware interface has been deprecated since the get_middleware()
    method is now included in the base resolver classes, so it is no longer
    necessary to include ' implements has_middleware' in your resolvers.

    Additionally, the final keyword has been removed from any core resolver classes
    that set it, to allow partners more flexibility in extending core resolvers.

  TL-34833 Allowed notification Tui components to be reusable in existing page layouts

  TL-34875 Editor variants have been standardised

    'Variants' are an editor concept, currently only used by Weka, that allow
    developers to select a set of editor features to enable. In Weka, this means
    which 'extensions' (editor plugins) are enabled, which implement things like
    links and bulleted lists.

    Previously, there were a handful of core variants (standard, description, and
    simple).

    In addition to those three, we have added two additional core variants, 'full',
    containing all editor features and intended for use cases such as article
    content, and 'basic', which contains a limited feature set suitable for things
    like adding comments.

    The 'basic' and 'simple' variants are include-based, meaning they do not
    automatically include Weka extensions added through plugins. Weka extensions
    must instead declare when they wish to be added to these variants.

    'mention' and 'hashtag' extensions have also been removed from the standardised
    variants, and must be manually enabled at each editor usage as they only work
    correctly when the corresponding backend support is in place.

    Please see the Weka documentation for more information:
    https://help.totaralearning.com/display/DEV/Weka+editor

  TL-34899 Fixed an issue where isset on an entity returns false if the relationship exists but is not loaded

    Also available in 16.4 and later releases.

  TL-35113 Added GraphQL schema diff tool

    Added schema diff tool to allow developers to identify API schema changes
    between two versions.

    The tool can identify both non-breaking and breaking changes (changes that will
    impact existing usage of the API).

    For more information see our developer documentation:

    https://help.totaralearning.com/display/DEV/Changes+to+the+GraphQL+APIs#ChangestotheGraphQLAPIs-ToolingtosupporttrackingAPIchanges

  TL-35482 Updated webapi README to reflect changes to core APIs

Tui front end framework
-----------------------

  TL-26199 Refactored the CSS in the Tui Range component

  TL-26667 An error is now thrown for invalid Tui CSS imports, eliminating the confusing in-browser error messages

    Also available in 16.2 and later releases.

  TL-29586 Added 'toHaveNoViolations()' to jest's expect() result

    When using JavaScript unit tests (as provided by jest), a 'toHaveNoViolations'
    function has been added to the 'expect()' return value. This allows the test to
    check the accessibility of the given component and avoids importing the function
    from jest-axe package 'toHaveNoViolations()'.

    Also available in 16.4 and later releases.

  TL-31494 Added an optional @reset event and showReset prop to display a filter reset button in the FilterBar component

  TL-32798 Changed Delete bootstrap icon from Trash fill to Trash outline

    Also available in 16.1 and later releases.

  TL-34032 Updated layout of adders to work better on mobile devices

    Also available in 16.1 and later releases.

  TL-34086 Updated webpack and other packages to support Node 18

    If you have previously customised webpack builds using the hooks
    in {{build.config.js}} or by modifying the core webpack configuration, you may
    have to update these to be compatible with webpack 5. If you have not made any
    customisations to the webpack builds, you shouldn't need to take any action
    here.

    Also available in 16.3 and later releases.

  TL-34151 Fixed keyboard navigation in nested Tui modals

    Also available in 16.1 and later releases.

  TL-34385 Updated the computeError method in FormField.vue to only return the error as a string to prevent an 'Invalid Prop' Vue warning.

    Also available in 16.2 and later releases.

  TL-34481 Fixed keyboard accessibility of the Dropdown vue component

    Also available in 16.2 and later releases.

  TL-34555 Fixed the function dom/position/getBox to be functional in IE11

    Also available in 16.4 and later releases.

  TL-34963 Added a core component for implementing buttons with custom UI

    It's now possible to implement buttons with completely custom UI using the core
    ButtonAria component. See the samples page for ButtonAria for more details.

    Implementing custom buttons using the native button element is difficult, as
    theme styles typically set a lot of properties directly on the button element.

  TL-35202 Added Uniform wrapper for TagList component

  TL-35490 Stopped disabled DropDownItem being focusable via keyboard navigation

  TL-35525 Updated focus style of dropdown to meet accessibility requirements

    Also available in 16.6 and later releases.

Recommendations engine
----------------------

  TL-35129 The legacy Recommendations Engine is deprecated

    The Recommendations Engine (also called Recommenders) introduced with Totara 13,
    is now deprecated. It will be removed from Totara 18 onwards. It has been
    replaced by the Machine Learning Service.  The Machine Learning Service was
    introduced in Totara 15.

    The Machine Learning Service brings several improvements over the
    Recommendations Engine: better performance at scale combined with real-time
    generation of recommendation data. The legacy version will work normally in
    Totara 17, but will display a warning message.

    Instructions on migrating to the Machine Learning Service can be found here:
    https://help.totaralearning.com/display/DEV/Machine+Learning+Service

Library updates
---------------

  TL-28252 Upgraded library Video.js to 7.18.1

  TL-32213 Upgraded GraphQL-tag library to version 2.12.4

  TL-33146 Upgraded development library "stylelint" from 12.0 to 14.6

    Also available in 16.4 and later releases.

  TL-33904 Upgraded library dompdf to version 1.2.2

    We now use a modified version of this library on Github which contains
    Totara-specific changes.

  TL-34192 Updated npm dependencies

    Dependencies:

    * @babel/runtime: 7.13.10 -> 7.18.9
    * date-fns: 2.24.0 -> 2.29.1
    * graphql: 15.5.0 -> 15.8.0
    * graphql-tag: 2.12.4 -> 2.12.6
    * prosemirror-commands: 1.1.12 -> 1.3.0
    * prosemirror-dropcursor: 1.3.5 -> 1.5.0
    * prosemirror-gapcursor: 1.2.0 -> 1.3.1
    * prosemirror-history: 1.2.0 -> 1.3.0
    * prosemirror-inputrules: 1.1.3 -> 1.2.0
    * prosemirror-keymap: 1.1.5 -> 1.2.0
    * prosemirror-model: 1.15.0 -> 1.18.1
    * prosemirror-state: 1.3.4 -> 1.4.1
    * prosemirror-transform: 1.3.3 -> 1.6.0
    * prosemirror-view: 1.21.0 -> 1.27.
    * vue-apollo: 3.0.7 -> 3.1.0

    Also available in 16.4 and later releases.

  TL-34372 Upgraded library adodb/adodb-php to 5.22.2

  TL-34375 Updated the SVGGraph library to improve support for PHP 8.1

    Also available in 16.3 and later releases.

  TL-34383 Updated the SCSSSPHP library to improve support for PHP 8.1

    Also available in 16.3 and later releases.

  TL-34399 Upgraded library nyholm/psr7 to 1.5.1

  TL-34417 Upgraded library box/spout to a 3.3.0 forked version

  TL-34449 Upgraded symfony/polyfill-php libraries

    * Upgraded library symfony/polyfill-php74 to version 1.25.0
    * Upgraded library symfony/polyfill-php80 to version 1.25.0
    * Installed library symfony/polyfill-php81 version 1.25.0

  TL-34450 Upgraded library phpmailer/phpmailer to 6.5.4 

  TL-34454 Upgraded library simplepie/simplepie to 1.6.0

  TL-34457 Upgraded library sabberworm/php-css-parser to 8.4.0

  TL-34459 Upgraded library ops/json-schema to version 2.3.0

  TL-34460 Upgraded library michelf/php-markdown to 1.9.1

  TL-34463 Upgraded library league/oauth2-server to 8.3.5 for PHP 8.1 support

  TL-34470 Upgraded library mathiasmullie/minify to 1.3.68

  TL-34489 Upgraded library wikimedia/less.php to support php 8.1

  TL-34528 Upgraded library phpseclib/phpseclib to 2.0.37

  TL-34534 Upgraded library ezyang/htmlpurifer to a 4.15.0 forked version

  TL-34535 Upgraded library PHPOffice/PhpSpreadsheet to 1.23.0

  TL-34942 Upgraded optional library microsoft/azure-storage-blob to 1.5.4

  TL-35130 Updated Python libraries

    Also available in 16.3 and later releases.

  TL-35369 Upgraded optional library aws/aws-sdk-php to 3.235.7
