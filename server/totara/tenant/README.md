# Technical description of support for multiple tenants in Totara

This document is intended for developers (and advanced sysadmins) as an overview
of general concepts, architecture and API changes related to support for multiple
tenants in Totara.

Lists of future ideas are not intended as a roadmap for future versions.


## How to start

1. install or upgrade from Evergreen
2. enable "Enable multitenancy support" in Configure features page
3. optionally enable "Enable tenant isolation" in Experimental settings
4. as admin go to "Tenants" via quick access menu (first Core Platform column)
5. press "Add new tenant" and fill in tenant name and idnumber
6. click on the participants list and add new tenant accounts
7. go to Site administration / Manage users and try to migrate non-admin users to tenant users
8. go to tenant course category page and explore the Tenant items in navigation box
9. assign _Tenant user manager_ to some tenant account in tenant context
10. assign _Tenant domain manager_ role to some user in tenant category


## New tenant context level and context class

There is a new CONTEXT_TENANT context level and matching context_tenant class.
The main purpose is to allow delegation of management of tenant user accounts.

The context_tenant is inserted between system and user contexts of tenant members,
the new tenant context level is not used if tenant support is disabled.

There is a new role archetype and default role _Tenant user manager_ assignable
in tenant contexts.


## New top level tenant category

Together with each new tenant a dedicated top level course category is created.
This category cannot be moved around, all subcategories and their content belong to
that particular tenant.

There is a new role archetype and default role _Tenant domain manager_ assignable
in course categories, courses and activities. 


## New tenant participants audience

New audience is automatically created for each tenant in its top level tenant course category.
This audience is automatically populated with all tenant members and other non-tenant users.

The purpose of this audience is to maintain a list of all tenant participants.

New roles can be assigned in tenant related contexts to participants only, existing
role assignments are not removed when user is removed from the list of tenant participants. 

Note that due to tenant separation rules members of tenants cannot be added as participants
in other tenants.


## Tenant separation modes and effects on permission evaluation and user availability

There two two available tenant separation modes that can be selected in site settings.


### Tenant isolation disabled

When tenant isolation is disabled then tenant members can access everything except
categories and contexts of other tenants. They are able to see other tenant members
in non-tenant areas.

The permission evaluation rules are following.

When the user is a guest user or not-logged-in:
 * If the context belongs to any tenant then the user cannot have any capability.
 * If the context does not belong to any tenant then normal checking applies.

When the user is a member of a tenant:
 * If the context belongs to their tenant then normal checking applies.
 * If the context belongs to any other tenant then the user cannot have any capability.
 * If the context does not belong to any tenant then normal checking applies.

When the user is not a member of a tenant:
 * Normal checking applies in all situations.

The same logic applies to access to courses and enrolments.


### Tenant isolation enabled

When tenant isolation is enabled then tenant members are restricted to their tenant
category and contexts only. They should not be able to see other tenant members anywhere
unless users were previously migrated from one tenant into another.  

The permission evaluation rules are following.

When the user is a guest user or not-logged-in:
 * If the context belongs to any tenant then the user cannot have any capability.
 * If the context does not belong to any tenant then normal checking applies.

When the user is a member of a tenant:
 * If the context belongs to their tenant then normal checking applies.
 * If the context belongs to any other tenant then the user cannot have any capability.
 * If the context does not belong to any tenant then the user cannot have any capability.

When the user is not a member of a tenant:
 * Normal checking applies in all situations.

The same logic applies to access to courses and enrolments.


## New tenantid property in each context instance

Each context instance has a new nullable tenantid property which is used to improve
performance of tenant membership lookups. For example tenantid in context_user tells
you if user is member of any tenant, in case of context_course it tells you if
course belongs into a regular top category or a tenant top category. 


## Tenant management UI

Access to tenant management UI is controlled via 'totara/tenant:view' and 'totara/tenant:config'
capabilities.

Users with 'moodle/user:viewalldetails' capability in tenant context or 'moodle/user:viewhiddendetails'
capability in tenant category context may view embedded report of tenant participants.

If tenant is suspended then all tenant member accounts are considered to be suspended too.
Please note that suspended account only means that user cannot login and they do not receive
any messages, nothing else should be implied. 

Future ideas:
 * add possibility to delegate some tenant customisations via new capability in tenant context


## Site administration settings and quick access menu

Tenant members cannot be admins due to tenant separation rules. When isolation mode is enabled
tenant members do not have access to any site level settings due to context based permission restrictions. 

Quick access menu was designed for system level administration only, there are some workarounds
in tenant settings that create fake external admin pages so that tenant members have access
to basic tenant users, course and categories management.

The intention here is to make it look like a normal site for tenant members with Tenant
manager roles. 


## Effects of multiple tenants on core subsystems and plugins

### Course category access and visibility

Standard tenant separation logic is enforced, which means that members of one tenant do not see
categories of other tenants. If tenant separation is enabled then tenant members do not see any
categories outside their tenant.


### User profile

Changes:
 * moodle/user:viewalldetails is for user and system context only,
   it can be used for parents accessing data of their children or staff managers
 * moodle/user:viewdetails is for course context and user context too


### Courses and enrolments

Future ideas:
 * implement support for course requests and approvals in course categories


### User management

Changes:
 * 'moodle/user:viewalldetails' is now used to control access to all system users and tenant member embedded reports

Future ideas:
 * add new UI and add capability for changing of passwords of other users


### Dashboards

Tenant dashboards are fully supported, standard tenant separation rules apply.

For tenant members the list of available dashboards always starts with dashboards for their tenant,
this guarantees that they get a tenant dashboard by default without affecting other tenant participants
that are not members.

Note that dashboards must be enabled in strict separation mode because tenant members cannot access the front page
when tenant isolation is enabled.

Future ideas:
 * Instead of using Clone dashboard add a new copy blocks from option to create dashboard form
 * We could also add option to reset dashboard blocks using current blocks from other dashboard 


### Audiences

Standard category audiences work fine in tenants. Only tenant participants are presented as candidates
for tenant audience assignments.

New dynamic audience rule for _Tenant members_ was added.


### Report builder

RB caching is not compatible with multitenancy and is automatically disabled if tenant support is enabled.

_Show records by audience_ content restriction may be used to restrict what user data is included
in user reports. 

Also existing global restrictions may be used to exclude tenant specific data from reports.

Future idea:
 * create more tenant related content restrictions and use them in existing report sources 


### Person-to-person messaging

When isolation is disabled user to user messaging is not restricted.

When tenant isolation is enabled then tenant members may communicate only with participants in their tenants
and non-tenant users may contact tenant members only if they participate in their tenant.

Note that sending and receiving of user-to-user messages by administrators is not affected by tenant isolation.


### Themes

When tenant member user is logged in following CSS classes are automatically added to body element of all pages:
 * tenant-user
 * tenant-user-XX where XX is id of tenant
 * tenant-user-YYY where YYY is tenant identifier (idnumber) 

When page context belongs to a tenant the following CSS classes are automatically added to body element:
 * tenant-context
 * tenant-context-XX where XX is id of tenant
 * tenant-context-YYY where YYY is tenant identifier (idnumber)

Other options for theme customisation are to set user theme for each tenant member account
or to user course and course category themes.

Future ideas:
 * pre-login theme could be customised via URL parameters 


### Curse backup and restore

Course backup and restore ignore tenant membership, for security reasons tenant domain managers should not be given
'moodle/restore:createuser' permission.


### User account upload via CSV  

Tenant field is not supported in CSV file, but admins may select tenant for all new users in one CSV upload.


### Blocks

When tenant isolation is enabled system level blocks with the exception of Administration block are not propagated
into tenant courses and categories.


### auth_approved

Requested accounts cannot be approved by tenant user managers, but it is possible to select tenant when approving user.


## Areas and plugins that do not have support for tenants yet

 * Calendars
 * Content marketplace
 * File repositories
 * Seminar
 * Programs
 * Certifications
 * Course requests
 * Jobs
 * LTI plugins
 * OAuth authentication
 * many others


## Areas and plugins that will not have support for tenants

 * Login-as is not allowed in tenant contexts and it cannot be used by tenant members.
 * Role switching
 * Dynamic audiences creation - there is no way to restrict tenant managers from including rules that contain non-member accounts
 * hierarchy and goals
 * Appraisals
 * Feedback 360
 * enrol_category - not recommended to be used at all
 * entol_imsenterprise
 * enrol_meta - not recommended to be used at all
 * enrol_paypal
 * custom tenant URL - Totara requires one fixed URL which cannot change

 