# Totara TXP 17 Technical Release Notes

A change management guide for Totara developers and implementers.

For a description of new features, improvements, and bugs fixed in Totara 17, please
start with our [change logs](changelog.md).

<a name="toc" />

## Table of contents

* [System requirements](#requirements)
* [Breaking code changes](#breaking)
* [GraphQL API](#gql)
* [Capabilities](#capabilities)
* [Scheduled tasks](#tasks)
* [Notifications](#notifications)
* [Site administration](#settings)
* [Hooks](#hooks)
* [Events](#events)
* [Plugins](#plugins)
* [Tui components](#tui)
* [Deprecations](#deprecated)

<a name="requirements" />

## System requirements

- **PHP**   
Added PHP 8.1 support  
PHP 7.3 is no longer supported.  
Note that max_input_vars minimum has increased in recent releases to 5000.

- **MariaDB**  
Added MariaDB 10.7 and 10.8 support.

- **MySQL** - No change

- **PostgreSQL** - No change

- **MSSQL** - No change

- **Python** (for Machine Learning Service)  
Python 3.6 is no longer supported

- **Node.js**  
Node 12 is no longer supported

- **Web browsers**  
IE11 is no longer supported

[Table of contents](#toc)

<a name="breaking" />

## Breaking code changes

### GraphQL resolver classes
The GraphQL resolver interfaces have been changed to abstract classes which implement rate-limiting and middleware definitions.  

All custom GraphQL resolver classes must be updated:  

> Change `implements mutation_resolver` to `extends mutation_resolver`  
> Change `implements query_resolver` to `extends query_resolver`  
> Change `implements type_resolver` to `extends type_resolver`  

Consider in each case whether to override the default methods in the abstract class.

The final keyword has been removed from all core resolver classes that included it, to allow more flexibility
for partners wanting to extend existing GraphQL services.

### Perform front end  
Moved `mod_perform/src/js/redirect.js` to `tui/src/js/dom/form.js`, any existing reference will need their paths updated.

In components replace `mod_perform/redirect` with `tui/dom/form`.

### Notifications front end  
Split NotificationPage.vue into two components so that the notification UI can be used in different page layouts.
- NotificationPage.vue now just handles the page layout and Notifications.vue contains the logic previously contained in NotificationPage.vue. 
- Ensure any customisations previously made to NotificationPage.vue is correctly split between these two components.

### Course container
Moved two PHP entity classes to core, with renames.
- Moved `server/container/classes/entity/module.php` to `server/lib/classes/entity/course_module.php`
- Moved `server/container/classes/entity/section.php` to `server/lib/classes/entity/course_section.php`

### Competency
Moved these `server/totara/competency/classes/entity` files to `server/totara/hierarchy/classes/entity`
- scale_assignment.php
- scale_value.php
- scale.php
- competency.php
- competency_repository.php
- competency_type.php
- competency_framework
- competency_framework_repository
- assignment_availability

Moved and renamed `totara_competency\entity\competency_scale_assignment` to `totara_hierarchy\entity\scale_assignment`

Moved these `server/totara/competency/classes/entity` files to `server/lib/classes/entity`:
- course_repository.php
- course_categories.php
- course.php

[Table of contents](#toc)

<a name="gql" />

## GraphQL API
Totara 17 provides three separate-but-related GraphQL APIs: external, AJAX, and Mobile.

### Changes to external API

For full documentation of the Totara 17.0 external API, see https://graphql-schema.totara.com/docs/totara-17.0.html

#### New queries

- **core_user_users**  
  Return a paginated list of users in the system.

- **totara_webapi_status**  
  Simple query returning "ok" to test that you are able to successfully execute GraphQL queries.

#### New mutations

- **core_user_create_user**  
Create a new user.

- **core_user_update_user**  
Update the specified target user with new properties.

- **core_user_delete_user**  
Delete the target user.

- **totara_job_create_job_assignment**  
Creates a new job assignment.

- **totara_job_update_job_assignment**  
Mutation to delete a job assignment.

- **totara_job_delete_job_assignment**  
Updates a specific target job assignment with new properties.

### Important changes to AJAX / Mobile APIs

- `Query.core` was removed - this was a placeholder query that didn't do anything
- `Mutation.todo` was removed - this was a placeholder mutation that didn't do anything
- `core_user.interests` changed type from Boolean to String

Additionally, the following AJAX schema changes have been made to support multiple recipients in notification configurations:

**Mutation deprecations**
- `totara_notification_create_custom_notification_preference` in favour of `totara_notification_create_custom_notification_preference_v2`
- `totara_notification_toggle_notifiable_event` in favour of `totara_notification_toggle_notifiable_event_v2`
- `totara_notification_create_notification_preference` in favour of `totara_notification_create_notification_preference_v2`
- `totara_notification_update_notification_preference` in favour of `totara_notification_update_notification_preference_v2`
- `totara_notification_override_notification_preference` in favour of `totara_notification_override_notification_preference_v2`

**Query deprecations**
- `totara_notification_event_resolvers` in favour of `totara_notification_event_resolvers_v2`
- `totara_notification_notification_preference` in favour of `totara_notification_notification_preference_v2`

[Table of contents](#toc)

<a name="capabilities" />

## New capabilities

### Hierarchy capabilities

- **Assign company goal** renamed to **Manage goal assignments**  
`totara/hierarchy:managegoalassignments`  
This is an admin capability which let a user manage any goal assignment in the system.

- **Manage manager assigned goals**  
`totara/hierarchy:managemanagerassignedgoal`  
Allows the user to manage a manager's assigned goals.

### Notifications capabilities

- **Audit course notifications**  
`moodle/course:auditcoursenotifications`  
Allows the user to view notification log reports for course notifications.

- **Audit performance activity notifications**  
`mod/perform:audit_notifications`  
Allows the user to view notification log reports for performance activity notifications.

- **Audit program messages**  
`totara/program:auditmessages`  
Allows the user to view notification log reports for program notifications.

- **Audit notifications**  
`totara/notification:auditnotifications`  
Blanket capability allowing the user to view notification log reports for all notifications.

### External API capabilities

- **Manage API clients**  
`totara/api:manageclients`  
Allows the user to create, edit, and delete external API clients.

- **Manage API client settings**  
`totara/api:managesettings`  
Allows the user to manage settings of existing external API clients.

- **View API documentation**  
`totara/api:viewdocumentation`  
Allows the user to view the in-product external API documentation.

### Approval workflows capabilities

#### Application capabilities

Note that most application capabilities have three or four versions, depending on whether they should apply to anyone (`_any`),
the owner/creator of the application (`_owner`), the applicant/subject of the application (`_applicant`), or someone with
a role in the user context of the applicant (`_user`) such as their manager.

- **Create applications**  
`mod/approval:create_application_any`  
`mod/approval:create_application_applicant`  
`mod/approval:create_application_user`  
Allows the user to create applications for themselves (`_applicant`) or on behalf of other users.

- **View draft applications in the dashboard**    
`mod/approval:view_draft_in_dashboard_application_any`  
`mod/approval:view_draft_in_dashboard_application_applicant`  
`mod/approval:view_draft_in_dashboard_application_user`  
Allows the user to see draft application in the applications dashboard.

- **View draft applications**  
`mod/approval:view_draft_application_any`  
`mod/approval:view_draft_application_owner`  
`mod/approval:view_draft_application_applicant`  
`mod/approval:view_draft_application_user`  
Allows the user to view draft applications.

- **Edit draft applications**  
`mod/approval:edit_draft_application_any`  
`mod/approval:edit_draft_application_owner`  
`mod/approval:edit_draft_application_applicant`  
`mod/approval:edit_draft_application_user`  
Allows the user to edit draft applications.

- **Delete draft applications**  
`mod/approval:delete_draft_application_any`  
`mod/approval:delete_draft_application_owner`  
`mod/approval:delete_draft_application_applicant`  
`mod/approval:delete_draft_application_user`  
Allows the user to delete draft applications.

- **View applications in the dashboard**  
`mod/approval:view_in_dashboard_application_any`  
`mod/approval:view_in_dashboard_application_applicant`  
`mod/approval:view_in_dashboard_application_user`  
Allows the user to see non-draft applications in the applications dashboard.

- **View pending applications in the dashboard**  
`mod/approval:view_in_dashboard_pending_application_any`  
`mod/approval:view_in_dashboard_pending_application_user`  
Allows the user to see applications which are awaiting their approval in the applications dashboard.

- **View applications**  
`mod/approval:view_application_any`  
`mod/approval:view_application_owner`  
`mod/approval:view_application_applicant`  
`mod/approval:view_application_user`  
Allows the user to view non-draft applications.

- **View pending applications**  
`mod/approval:view_pending_application_any`  
`mod/approval:view_pending_application_user`  
Allows the user to view applications which are awaiting their approval.

- **Edit unsubmitted applications**  
`mod/approval:edit_unsubmitted_application_any`  
`mod/approval:edit_unsubmitted_application_owner`  
`mod/approval:edit_unsubmitted_application_applicant`  
`mod/approval:edit_unsubmitted_application_user`  
Allows the user to edit non-draft applications at a form stage.

- **Edit in-approvals applications**  
`mod/approval:edit_in_approvals_application_any`  
`mod/approval:edit_in_approvals_application_owner`  
`mod/approval:edit_in_approvals_application_applicant`  
`mod/approval:edit_in_approvals_application_user`  
Allows the user to edit applications that are at an approval stage.

- **Edit pending submitted applications**  
`mod/approval:edit_in_approvals_pending_application_any`  
`mod/approval:edit_in_approvals_pending_application_user`  
Allows the user to edit applications which are awaiting their approval.

- **Edit first approval level applications**  
`mod/approval:edit_first_approval_level_application_any`  
`mod/approval:edit_first_approval_level_application_owner`  
`mod/approval:edit_first_approval_level_application_applicant`  
`mod/approval:edit_first_approval_level_application_user`  
Allows the user to edit applications that are at an approval stage, but have not been approved by anyone yet.

- **Edit first approval level pending applications**  
`mod/approval:edit_first_approval_level_pending_application_any`  
`mod/approval:edit_first_approval_level_pending_application_user`  
Allows the user to edit applications that are awaiting their approval, and have not been approved by anyone else yet.

- **Edit applications without invalidating exist approvals**  
`mod/approval:edit_without_invalidating_approvals_any`  
`mod/approval:edit_without_invalidating_approvals_owner`  
`mod/approval:edit_without_invalidating_approvals_applicant`  
`mod/approval:edit_without_invalidating_approvals_user`  
Allows the user to edit applications that have already been approved, without invalidating those approvals.

- **Edit full form on applications**  
`mod/approval:edit_full_application_any`  
`mod/approval:edit_full_application_owner`  
`mod/approval:edit_full_application_applicant`  
`mod/approval:edit_full_application_user`  
Allows the user to edit all form fields in the application to date, rather than just the ones defined at the current stage.

- **Approve applications**  
`mod/approval:approve_application_any`  
`mod/approval:approve_application_owner`  
`mod/approval:approve_application_applicant`  
`mod/approval:approve_application_user`  
Allows the user to approve applications that are at an approval stage.

- **Approve pending applications**  
`mod/approval:approve_pending_application_any`  
`mod/approval:approve_pending_application_owner`  
`mod/approval:approve_pending_application_applicant`  
`mod/approval:approve_pending_application_user`  
Allows the user to approve applications that are awaiting their approval.

- **Upload files to applications**  
`mod/approval:attach_file_to_application_any`  
`mod/approval:attach_file_to_application_owner`  
`mod/approval:attach_file_to_application_applicant`  
`mod/approval:attach_file_to_application_user`  
Allows the user to upload files to rich text fields when editing applications.

- **View comments on applications**  
`mod/approval:view_comment_on_application_any`  
`mod/approval:view_comment_on_application_owner`  
`mod/approval:view_comment_on_application_applicant`  
`mod/approval:view_comment_on_application_user`  
Allows the user to view comments associated with applications.

- **Post comments on applications**  
`mod/approval:post_comment_on_application_any`  
`mod/approval:post_comment_on_application_owner`  
`mod/approval:post_comment_on_application_applicant`  
`mod/approval:post_comment_on_application_user`  
Allows the user to post comments on applications.

- **Post comments on pending applications**  
`mod/approval:post_comment_on_pending_application_any`  
`mod/approval:post_comment_on_pending_application_user`  
Allows the user to post comments on applications that are awaiting their approval.

- **Withdraw unsubmitted applications**  
`mod/approval:withdraw_unsubmitted_application_any`  
`mod/approval:withdraw_unsubmitted_application_owner`  
`mod/approval:withdraw_unsubmitted_application_applicant`  
`mod/approval:withdraw_unsubmitted_application_user`  
Allows the user to withdraw submitted applications that are at a form stage.

- **Withdraw in-approvals applications**  
`mod/approval:withdraw_in_approvals_application_any`  
`mod/approval:withdraw_in_approvals_application_owner`  
`mod/approval:withdraw_in_approvals_application_applicant`  
`mod/approval:withdraw_in_approvals_application_user`  
Allows the user to withdraw submitted applications that are at an approval stage.

- **Backdate applications**  
`mod/approval:backdate_application_any`  
`mod/approval:backdate_application_owner`  
`mod/approval:backdate_application_applicant`  
`mod/approval:backdate_application_user`  
Allows the user to set application date fields earlier than today's date.

#### Workflow management capabilities

- **Manage workflows**  
`mod/approval:manage_workflows`  
Allows the user to access the workflow management interface. This capability is required for all of the others below.

- **Create workflows from templates**  
`mod/approval:create_workflow_from_template`  
Allows the user to create workflows from an existing template.

- **Create workflows**  
`mod/approval:create_workflow`  
Allows the user to create new workflows.

- **Clone existing workflows**  
`mod/approval:clone_workflow`  
Allows the user to clone existing workflows.

- **Edit draft workflows**  
`mod/approval:edit_draft_workflow`  
Allows the user to manage draft workflows.

- **Edit active workflows**  
`mod/approval:edit_active_workflow`  
Allows the user to manage active workflows.

- **Publish/activate draft workflows**  
`mod/approval:activate_workflow`  
Allows the user to activate draft workflows, so they can be used by applicants.

- **Archive active workflows**  
`mod/approval:archive_workflow`  
Allows the user to archive active workflows, so that they no longer accept new applications.

- **Save workflows as templates**  
`mod/approval:create_workflow_template`  
Allows the user to save workflows as workflow templates.

- **Edit templates**  
`mod/approval:edit_workflow_template`  
Allows the user to manage workflow templates.

- **Manage stages (create, edit, reorder, clone, delete)**  
`mod/approval:manage_workflow_stages`  
Allows the user to configure stages on a (draft) workflow.

- **Manage formviews**  
`mod/approval:manage_workflow_form_view`  
Allows the user to manage form views on a (draft) workflow's stages.

- **Add approval levels**  
`mod/approval:add_workflow_approval_level`  
Allows the user to add approval levels to a (draft) workflow's approval stages.

- **Change approval level order**  
`mod/approval:reorder_workflow_approval_level`  
Allows the uset to change the order of approval levels on a (draft) workflow's approval stages.

- **Manage individual approvers**  
`mod/approval:manage_individual_workflow_approvers`  
Allows the user to manage individual approvers on a workflow.

- **Manage relationship approvers**  
`mod/approval:manage_relationship_workflow_approvers`  
Allows the user to manage relationship approvers (aka manager) on a workflow.

- **Manage assignment overrides**  
`mod/approval:manage_workflow_assignment_overrides`  
Allows the user to manage assignment approver overrides (alternate approvers) on a workflow.

- **Manage transitions**  
`mod/approval:manage_workflow_transitions`  
Allows the user to manage (draft) workflow interactions, controlling how applications move from stage to stage.

- **Manage notifications**  
`mod/approval:manage_workflow_notifications`  
Allows the user to manage per-stage workflow notifications.

- **Move applications to a different workflows**  
`mod/approval:move_application_between_workflows`  
Allows the user to move applications from one workflow to another.

- **Delete approval level**  
`mod/approval:delete_workflow_approval_level`  
Allows the user to delete an approval level from a (draft) workflow's approval stage.

- **Manage form plugin lookup tables**  
`mod/approval:manage_lookup_tables`  
Allows the user to manage custom tables used by approvalform plugins.

- **View workflow's applications report**  
`mod/approval:view_workflow_applications_report`  
Allows the user to view a workflow's application response data in report format.

[Table of contents](#toc)

<a name="tasks" />

## New scheduled tasks

Added **Delete notification logs** task as part of centralised notifications.

Disabled deprecated **Periodically check notification trigger conditions** task for performance activities

Added **Regenerate role capability maps** task to optimise application dashboard loading as part of approval workflows

[Table of contents](#toc)

<a name="notifications" />

## New notifications

### Perform notifications

Performance activity custom notifications are converted on upgrade to centralised notifications. 

The following default notifications have been added, to match the old default behaviour of performance activities. They are disabled by default unless indicated.

- Participant due date reminder - due today (for subject)
- Participant due date reminder - due today (for other participants)
- Participant due date reminder - overdue (for subject)
- Participant due date reminder - overdue (for other participants)
- Participant due date reminder - due in 2 days (for subject)
- Participant due date reminder - due in 2 days (for other participants)
- Completion of subject instance (for subject)
- Completion of subject instance (for other participants)
- Participant instance completion by direct report (for subject)
- Participant instance completion by direct report (for manager)
- Participant instance completion by external respondent (for subject)
- Participant instance completion by external respondent (for manager)
- Participant instance completion by manager (for subject)
- Participant instance completion by manager's manager (for subject)
- Participant instance completion by manager's manager (for manager)
- Participant instance completion by mentor (for subject)
- Participant instance completion by mentor (for manager)
- Participant instance completion by peer (for subject)
- Participant instance completion by peer (for manager)
- Participant instance completion by reviewer (for subject)
- Participant instance completion by reviewer (for manager)
- Participant instance completion by subject (for manager)
- Participant instance created (for subject) (**enabled**)
- Participant instance created (for other participants) (**enabled**)
- Participant instance created - reminder 1 day later (for subject)
- Participant instance created - reminder 1 day later (for other participants)
- Reopened activity (for subject)
- Reopened activity (for other participants)
- Participant selection (for subject) (**enabled**)
- Participant selection (for manager, manager's manager, and appraiser) (**enabled**)

### Approval workflows notifications

These notifications are all disabled by default.

- Application approved (at Level)
- Application denied (at Level)
- Application awaiting approval (at Level) - for applicant
- Application awaiting approval (at Level) - for approvers
- Application has been fully approved
- Application has completed (workflow stage)
- Application has entered (workflow stage)
- Application has been submitted
- Application has been withdrawn

[Table of contents](#toc)

<a name="settings" />

## Site administration

### Shared services settings

- **Enable API** - enableapi  
Allow external systems to connect to your Totara site, to access data and perform operations.  
(default No)

### Messaging and notification settings

- **Enable notification logs** - notificationlogs  
When enabled, notification logs are aggregated for auditing and troubleshooting purposes.  
(default Yes)

- **Days to keep notification logs** - totara_notification_log_days_to_keep  
(default 30)

### API settings

- **Site rate limit** - totara_api | site_rate_limit  
Maximum query complexity cost allowed per minute on this site.  
(default 500,000)

- **Client rate limit** - totara_api | client_rate_limit    
Maximum query complexity cost allowed per minute for an individual client on this site.  
(default 250,000)

- **Maximum query complexity** - totara_api | max_query_complexity   
Maximum complexity allowed for an individual query.  
(default 6,000)

- **Maximum query depth** - totara_api | max_query_depth   
Maximum depth allowed for an individual query.  
(default 15)

- **Default token expiration** - totara_api | default_token_expiration   
Length of time that a token will be valid, before expiration.  
(default 24 hours)

- **Enable GraphQL introspection** - totara_api | enable_introspection   
Allow clients to ask for information about the GraphQL schema. This includes data like types, fields, queries and mutations.  
(default No)

### Experimental settings

- **Enable Approval Workflows** - enableapproval_workflows   
When enabled, Approval Workflows features will be accessible.  
(default No)

[Table of contents](#toc)

<a name="hooks" />

## New hooks

Added three hooks to support the external API:

- **totara_webapi\hook\api_hook**  
Allows defining middleware based on endpoint_type, component, or resolver

- **totara_webapi\hook\handle_request_pre_hook**  
Allows third parties to intercept API requests prior to processing and reject or modify the request based on their own criteria.

- **totara_webapi\hook\handle_request_post_hook**  
Allows third parties to intercept API requests after processing and reject or modify the request based on their own criteria.

[Table of contents](#toc)

<a name="events" />

## New events

### Competencies events

- **Pathways copied**  
`totara_competency\event\pathways_copied_bulk`  
Triggered when competency pathways are copied in bulk from one competency to others.

### Approval workflows events

#### Application events

- **Application completed**  
`mod_approval\event\application_completed`  
Triggered when an application reaches an end stage.

- **Existing approvals invalidated due to rejection or withdrawal**  
`mod_approval\event\approvals_invalidated`  
Triggered when approvals are invalidated because an application has been rejected or withdrawn during an approval stage.

- **Application approved**  
`mod_approval\event\level_approved`  
Triggered when an application is approved at an approval level.

- **Application rejected**  
`mod_approval\event\level_rejected`  
Triggered when an application is rejected at an approval level.

- **Application entered new level**  
`mod_approval\event\level_started`  
Triggered when an application enters an approval stage, or advances to the next approval level.

- **Application fully-approved at stage**  
`mod_approval\event\stage_all_approved`  
Triggered when an application has been approved at all levels in an approval stage.

- **Application ended current stage**  
`mod_approval\event\stage_ended`  
Triggered when an application leaves a workflow stage.

- **Application entered new stage**  
`mod_approval\event\stage_started`  
Triggered when an application enters a workflow stage.

- **Application submitted**  
`mod_approval\event\stage_submitted`  
Triggered when an application form is submitted.

- **Application withdrawn**  
`mod_approval\event\stage_withdrawn`  
Triggered when an application is withdrawn.

#### Workflow administration events

- **Form version updated**  
`mod_approval\event\form_version_updated`  
Triggered when an approval form is updated (status change or plugin schema refresh). 

- **Workflow assignment archived**  
`mod_approval\event\workflow_assignment_archived`  
Triggered when an assignment override is archived.

- **Workflow assignment created**  
`mod_approval\event\workflow_assignment_created`  
Triggered when an assignment override is created.

- **Workflow assignment deleted**  
`mod_approval\event\workflow_assignment_deleted`  
Triggered when an assignment override is deleted.

- **Workflow cloned**  
`mod_approval\event\workflow_cloned`  
Triggered when an existing workflow is cloned.

- **Workflow created**  
`mod_approval\event\workflow_created`  
Triggered when a new workflow is created.

- **Workflow deleted**  
`mod_approval\event\workflow_deleted`  
Triggered when a workflow is deleted.

- **Workflow details edited**  
`mod_approval\event\workflow_edited`  
Triggered when a workflow's details (name, description, ID number) are changed.

- **Workflow stage approval level created**  
`mod_approval\event\workflow_stage_approval_level_created`  
Triggered when a new approval level is added to an approval workflow stage.

- **Workflow stage approval level deleted**  
`mod_approval\event\workflow_stage_approval_level_deleted`  
Triggered when an approval level is deleted from an approval workflow stage.

- **Workflow stage approval levels reordered**  
`mod_approval\event\workflow_stage_approval_levels_reordered`  
Triggered when the order of approval levels has changed.

- **Approvers on assignment changed**  
`mod_approval\event\workflow_stage_assignment_approvers_for_level_changed`  
Triggered whenever there is a change to how approvers are defined for a workflow's default assignment, or an assignment override.

- **Workflow stage created**  
`mod_approval\event\workflow_stage_created`  
Triggered when a new stage is added to a workflow.

- **Workflow stage deleted**  
`mod_approval\event\workflow_stage_deleted`  
Triggered when a stage is deleted from a workflow.

- **Workflow stage name changed**  
`mod_approval\event\workflow_stage_edited`  
Triggered when the name of a workflow stage changes.

- **Workflow stage formviews updated**  
`mod_approval\event\workflow_stage_form_views_updated`  
Triggered when the form views (which form fields are in use) on a stage changes.

- **Workflow archived**  
`mod_approval\event\workflow_version_archived`  
Triggered when a workflow is archived.

- **Workflow version published**  
`mod_approval\event\workflow_version_published`  
Triggered when a draft workflow is published.

- **Workflow version unarchived**  
`mod_approval\event\workflow_version_unarchived`  
Triggered when an archived workflow is unarchived.

[Table of contents](#toc)

<a name="plugins" />

## New plugins

### Totara plugins

- **External API** - totara_api  
`sever/totara/api`  
Implements the external GraphQL API and API client management.  
Depends on `totara_tenant` and `totara_oauth2`.

### Course containers

- **Approval Workflow** - container_approval  
`server/container/type/approval`  
Workflow container, holds workflow configuration and workflow assignments.

### Activity modules  

- **Approval workflow** - mod_approval  
`sever/mod/approval`  
(not a standard course activity module)  
Implements approval workflows, holds workflow assignment information.  
Depends on `container_approval`, `editor_weka`, and `totara_comment`.

### Approval Workflow Form sub-plugins (new)  

Approval workflow form plugins define the form schema for a specific type of workflow, and implement advanced 
functionality such as custom field validation and scheduled tasks.

- **Simple Request Form** - approvalform_simple  
`server/mod/approval/form/simple`  
Implements a simple request form to demonstrate approval workflows.

### JSON Editor plugins (new)  

JSON editor plugins implement node definitions for FORMAT_JSON_EDITOR editors. This controls the structure of each node,
as well as how it is rendered.

- **Simple multi lang** - jsoneditor_simple_multi_lang
`server/text_format/json_editor/extensions/simple_multi_lang`  
Format plugin implementing internationalisation support for centralised notifications. This was previously part of `weka_simple_multi_lang`, but was split out as part of the separation of the JSON Editor format and Weka plugins.
Depends on `filter_multi_lang`


[Table of contents](#toc)

<a name="tui" />

## New Tui components

- **buttons/ButtonAria**  
The ButtonAria component is a way to render a button with no built-in styling.

- **form/FormTagList**  
TagList component wrapped for Uniform.

- **form/InputCurrency**  
A uniform input for currency values.

[Table of contents](#toc)

<a name="deprecated" />

## Deprecations

### Perform

Deprecated Tui components to support legacy performance activity notifications:
- `RedirectWithPost` JavaScript function  
- NotificationSection.vue  
- RecipientsTable.vue  
- TriggersTable.vue  

Deprecated Tui components for displaying performance activity actions, replaced by a single `RowActions` component:
- ElementActions.vue
- ExportRowAction.vue
- SubjectInstanceActions.vue
- SubjectUserActions.vue

Changed name of renderer method in `mod_perform\controllers\reporting\performance` namespace:
- deprecated `renders_performance_reports::get_rendered_action_card()`
- replacement `renders_performance_reports::get_action_card()`

Legacy performance activity notification classes and methods deprecated in `mod_perform\notification` namespace:
- `dealer::class`
- `dealer_participant_selection::class`
- `factory::create_dealer_on_subject_instance()`
- `factory::create_dealer_on_subject_instances_for_manual_participants()`
- `factory::create_dealer_on_participant_instances()`
- `factory::create_mailer_on_notification()`
- `mailer::class`

Deprecated other performance activity notification classes:
- `mod_perform\task\check_notification_trigger_task::class` (the associated scheduled task has been disabled)
- `mod_perform\webapi\resolver\query\notifications::class`

Deprecated a linked review element response report class:
- `hierarchy_goal\performelement_linked_review\response_report::class`
- use `\hierarchy_goal\performelement_linked_review\personal_goal_response_report` or `\hierarchy_goal\performelement_linked_review\company_goal_response_report` instead.

### Machine learning

ML Recommender has been deprecated in favour of the ML Service.

### Report builder

#### Course

Audience visibility columns have been replaced by audience visibility joins.

- namespace `\coure_course\rb\traits`  
deprecated `required_columns::add_audiencevisibility_columns()`  
replacement `required_joins::add_audiencevisibility_joins()`

#### Seminar

Audience visibility columns have been replaced by audience visibility joins.

- namespace `\mod_facetoface\rb\traits`  
deprecated `required_columns::add_audiencevisibility_columns()`  
replacement `required_joins::add_audiencevisibility_joins()`

#### Feedback activity

The `session_value` and `sessiontrainer` joins in `server\mod\feedback\rb_sources\rb_source_feedback_summary.php::define_joinlist()`
have been deprecated.

### GraphQL API

The `has_middleware` interface is deprecated; the new abstract resolver classes have `has_middleware()` methods (see [Breaking changes](#breaking)).

### Notifications

Deprecated notification_preference methods that only allowed one recipient:

- class `\totara_notification\builder\notification_preference_builder`  
deprecated `set_recipient()`  
use `set_recipients()`

- class `\totara_notification\model\notification_preference`  
deprecated `get_recipient()`  
use `get_recipients()`

- class `\totara_notification\model\notification_preference_value`  
deprecated `get_recipient()`  
use `get_recipients()`

Deprecated resolvers for deprecated notification_preference GraphQL mutations:  

- namespace `\totara_notification\webapi\resolver\mutation\`  
deprecated `create_notification_preference::class`  
use `create_notification_preference_v2::class`

- namespace `\totara_notification\webapi\resolver\mutation\`  
deprecated `toggle_notifiable_event::class`  
use `toggle_notifiable_event_v2::class`

- namespace `\totara_notification\webapi\resolver\mutation\`
deprecated `update_notification_preference::class`  
use `update_notification_preference_v2::class`

Deprecated resolvers for deprecated notification_preference GraphQL queries:

- namespace `\totara_notification\webapi\resolver\query\`  
deprecated `event_resolvers::class`  
use `event_resolvers_v2::class`

- namespace `\totara_notification\webapi\resolver\query\`  
deprecated `notification_preference::class`  
use `notification_preference_v2:class`

- namespace `\totara_notification\webapi\resolver\query\`  
deprecated `notification_preferences::class`  
use `notification_preferences_v2::class`

Deprecated resolvers for deprecated notification_preference GraphQL types:

- namespace `\totara_notification\webapi\resolver\type\`  
deprecated `event_resolver::class`  
use `event_resolver_v2:class`

- namespace `\totara_notification\webapi\resolver\type\`  
deprecated `notification_preference::class`  
use `notification_preference_v2::class`

Split single notification capability check into separate manage and audit capability checks:

- class `\totara_notification\factory\capability_factory`  
deprecated `get_capabilities()`  
use `get_manage_capabilities()`  
or use `get_audit_capabilities()`

### Weka

Deprecated passing "usage-identifier" prop to Weka.vue without also passing "variant".

Loading JSON_EDITOR node definitions from weka plugins has been deprecated, please create a "jsoneditor" plugin to contain the node definitions instead.

All area-specific variants (identifiable by containing a "-") have been deprecated and replaced with a small set of core variants.

The weka_simple_multi_lang\json_editor\node\lang_block(s) classes were renamed to jsoneditor_simple_multi_lang\json_editor\node\lang_block(s) as the node definitions were moved to a "jsoneditor" plugin.

### Core

The `generate_uuid()` function has been deprecated in favour of `\core\uuid::generate()`  

The `workaround_max_input_vars()` function has been deprecated with no replacement due to warnings in PHP 8+; there has been a required increase in the minimum max_input_vars PHP setting to compensate (some course activities use a large number of input variables)

[Table of contents](#toc)