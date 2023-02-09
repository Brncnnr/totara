<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    /////////////////////////////////////////////////////////////////////
    // CX00a View draft application in dashboard
    // This capability should be granted in every role where there is a
    // need to see the draft application in the dashboard in order to access
    // the granted capability. E.g. to view, edit, or delete.
    // Note that we don't have CW00a because this is a built-in right of all owners.
    /////////////////////////////////////////////////////////////////////
    // CP00a
    // Allows the applicant to view their non-published applications,
    // where the application exists within the given context.
    'mod/approval:view_draft_in_dashboard_application_applicant' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU00a
    // Allows a user to view the non-published applications of applicants,
    // usually their staff.
    'mod/approval:view_draft_in_dashboard_application_user' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA00a
    // Allows a user to view the non-published applications of all users,
    // where the application exists within the given context.
    'mod/approval:view_draft_in_dashboard_application_any' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX01 Draft / unpublished application view
    /////////////////////////////////////////////////////////////////////
    // CW01
    // Allows the owner to view their non-published applications,
    // where the application exists within the given context.
    'mod/approval:view_draft_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CP01
    // Allows the applicant to view their non-published applications,
    // where the application exists within the given context.
    'mod/approval:view_draft_application_applicant' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU01
    // Allows a user to view the non-published applications of applicants,
    // usually their staff.
    'mod/approval:view_draft_application_user' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA01
    // Allows a user to view the non-published applications of all users,
    // where the application exists within the given context.
    'mod/approval:view_draft_application_any' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX02 Draft / unpublished application edit and submit
    /////////////////////////////////////////////////////////////////////
    // CW02
    // Allows the owner to edit and submit their non-published applications,
    // where the application exists within the given context.
    'mod/approval:edit_draft_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CP02
    // Allows the applicant to edit and submit their non-published applications,
    // where the application exists within the given context.
    'mod/approval:edit_draft_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU02
    // Allows a user to edit and submit the non-published applications of applicants,
    // usually their staff.
    'mod/approval:edit_draft_application_user' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA02
    // Allows a user to edit and submit the non-published applications of all users,
    // where the application exists within the given context.
    'mod/approval:edit_draft_application_any' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX03 Draft / unpublished application delete
    /////////////////////////////////////////////////////////////////////
    // CW03
    // Allows the owner to delete their non-published applications,
    // where the application exists within the given context.
    'mod/approval:delete_draft_application_owner' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CP03
    // Allows the applicant to delete their non-published applications,
    // where the application exists within the given context.
    'mod/approval:delete_draft_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU03
    // Allows a user to delete the non-published applications of applicants,
    // usually their staff.
    'mod/approval:delete_draft_application_user' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA03
    // Allows a user to delete the non-published applications of all users,
    // where the application exists within the given context.
    'mod/approval:delete_draft_application_any' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX00b View non-draft application in dashboard
    // This capability should be granted in every role where there is a
    // need to see the application in the dashboard in order to access
    // the granted capability. E.g. to view, edit, approve or delete.
    // Note that we don't have CW00b because this is a built-in right of all owners.
    /////////////////////////////////////////////////////////////////////
    // CP00b
    // Allows the applicant to view their non-published applications,
    // where the application exists within the given context.
    'mod/approval:view_in_dashboard_application_applicant' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CU00b
    // Allows a user to view the non-published applications of applicants,
    // usually their staff.
    'mod/approval:view_in_dashboard_application_user' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'staffmanager' => CAP_ALLOW,
        ]
    ],
    // CA00b
    // Allows a user to view the non-published applications of all users,
    // where the application exists within the given context.
    'mod/approval:view_in_dashboard_application_any' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowapprover' => CAP_ALLOW,
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX00c Application dashboard view specifically when pending for the viewer
    // Pending indicates that the user is an approver and they can currently approve
    // or reject the application.
    /////////////////////////////////////////////////////////////////////
    // CU00c
    // Allows a user to view only pending applications of applicants on the dashboard,
    // usually their staff.
    'mod/approval:view_in_dashboard_pending_application_user' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA00c
    // Allows a user to view only pending applications of all users on the dashboard,
    // where the application exists within the given context.
    'mod/approval:view_in_dashboard_pending_application_any' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX04 Application view (except when draft)
    /////////////////////////////////////////////////////////////////////
    // CW04
    // Allows the owner to view their non-draft, published applications,
    // where the application exists within the given context.
    'mod/approval:view_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CP04
    // Allows the applicant to view their non-draft, published applications,
    // where the application exists within the given context.
    'mod/approval:view_application_applicant' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CU04
    // Allows a user to view the non-draft, published applications of applicants,
    // usually their staff.
    'mod/approval:view_application_user' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'staffmanager' => CAP_ALLOW,
        ]
    ],
    // CA04
    // Allows a user to view the non-draft, published applications of all users,
    // where the application exists within the given context.
    'mod/approval:view_application_any' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowapprover' => CAP_ALLOW,
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX05 Application view specifically when pending for the viewer
    // Pending indicates that the user is an approver and they can currently approve
    // or reject the application.
    /////////////////////////////////////////////////////////////////////
    // CU05
    // Allows a user to view only pending applications of applicants,
    // usually their staff.
    'mod/approval:view_pending_application_user' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA05
    // Allows a user to view only pending applications of all users,
    // where the application exists within the given context.
    'mod/approval:view_pending_application_any' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX06 Before-submission application edit and submit
    /////////////////////////////////////////////////////////////////////
    // CW06
    // Allows the owner to edit their unsubmitted applications,
    // where the application exists within the given context.
    'mod/approval:edit_unsubmitted_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CP06
    // Allows the applicant to edit their unsubmitted applications,
    // where the application exists within the given context.
    'mod/approval:edit_unsubmitted_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CU06
    // Allows a user to edit the unsubmitted applications of other users,
    // usually their staff.
    'mod/approval:edit_unsubmitted_application_user' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA06
    // Allows a user to edit the unsubmitted applications of other users,
    // where the application exists within the given context.
    'mod/approval:edit_unsubmitted_application_any' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX07 After submission, in-approvals application edit and save
    /////////////////////////////////////////////////////////////////////
    // CW07
    // Allows the owner to edit their in-approvals applications,
    // where the application exists within the given context.
    'mod/approval:edit_in_approvals_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CP07
    // Allows the applicant to edit their in-approvals applications,
    // where the application exists within the given context.
    'mod/approval:edit_in_approvals_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU07
    // Allows a user to edit the in-approvals applications of other users,
    // usually their staff.
    'mod/approval:edit_in_approvals_application_user' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA07
    // Allows a user to edit the in-approvals applications of other users,
    // where the application exists within the given context.
    'mod/approval:edit_in_approvals_application_any' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX08 After submission, in-approvals pending application edit and save
    // Pending indicates that the user is an approver and they can currently approve
    // or reject the application.
    /////////////////////////////////////////////////////////////////////
    // CU08
    // Allows a user to edit the in-approvals pending applications of other users,
    // usually their staff.
    'mod/approval:edit_in_approvals_pending_application_user' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'staffmanager' => CAP_ALLOW,
        ]
    ],
    // CA08
    // Allows a user to edit only pending applications of other users,
    // where the application exists within the given context.
    'mod/approval:edit_in_approvals_pending_application_any' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowapprover' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX09 In first level approval application edit and save
    /////////////////////////////////////////////////////////////////////
    // CW09
    // Allows the owner to edit their first approval level applications,
    // where the application exists within the given context.
    'mod/approval:edit_first_approval_level_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CP09
    // Allows the applicant to edit their first approval level applications,
    // where the application exists within the given context.
    'mod/approval:edit_first_approval_level_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU09
    // Allows a user to edit the first approval level applications of other users,
    // usually their staff.
    'mod/approval:edit_first_approval_level_application_user' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA09
    // Allows a user to edit the first approval level applications of other users,
    // where the application exists within the given context.
    'mod/approval:edit_first_approval_level_application_any' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX10 In first level approval pending application edit and save
    // Pending indicates that the user is an approver and they can currently approve
    // or reject the application.
    /////////////////////////////////////////////////////////////////////
    // CU10
    // Allows a user to edit only pending first approval level applications of other users,
    // usually their staff.
    'mod/approval:edit_first_approval_level_pending_application_user' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CA10
    // Allows a user to edit only pending first approval level applications of other users,
    // where the application exists within the given context.
    'mod/approval:edit_first_approval_level_pending_application_any' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX11 Edit without resetting approvals
    // This capability is a modifier for the standard edit capabilities. This
    // capability alone does not allow a user to edit applications.
    /////////////////////////////////////////////////////////////////////
    // CW11
    // Allows the owner to edit their applications without resetting approvals,
    // where the application exists within the given context.
    'mod/approval:edit_without_invalidating_approvals_owner' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CP11
    // Allows the applicant to edit their applications without resetting approvals,
    // where the application exists within the given context.
    'mod/approval:edit_without_invalidating_approvals_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU11
    // Allows a user to edit the applications of other users without resetting approvals,
    // usually their staff.
    'mod/approval:edit_without_invalidating_approvals_user' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA11
    // Allows a user to edit the applications of other users without resetting approvals,
    // where the application exists within the given context.
    'mod/approval:edit_without_invalidating_approvals_any' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX21 Edit the full form, regardless of the current stage's form view
    /////////////////////////////////////////////////////////////////////
    // CW21
    // Allows the owner to edit their applications without resetting approvals,
    // where the application exists within the given context.
    'mod/approval:edit_full_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CP21
    // Allows the applicant to edit their applications without resetting approvals,
    // where the application exists within the given context.
    'mod/approval:edit_full_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU21
    // Allows a user to edit the applications of other users without resetting approvals,
    // usually their staff.
    'mod/approval:edit_full_application_user' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA21
    // Allows a user to edit the applications of other users without resetting approvals,
    // where the application exists within the given context.
    'mod/approval:edit_full_application_any' => [
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX12 Approve and reject applications
    /////////////////////////////////////////////////////////////////////
    // CW12
    // Allows the owner to approve their applications,
    // where the application exists within the given context.
    'mod/approval:approve_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CP12
    // Allows the applicant to approve their applications,
    // where the application exists within the given context.
    'mod/approval:approve_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU12
    // Allows a user to approve the applications of other users,
    // usually their staff.
    'mod/approval:approve_application_user' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA12
    // Allows a user to approve the applications of other users,
    // where the application exists within the given context.
    'mod/approval:approve_application_any' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX13 Approve and reject pending applications
    // Pending indicates that the user is an approver and they can currently approve
    // or reject the application.
    /////////////////////////////////////////////////////////////////////
    // CW13
    // Allows the owner to approve their pending applications,
    // where the application exists within the given context.
    'mod/approval:approve_pending_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CP13
    // Allows the applicant to approve their applications,
    // where the application exists within the given context.
    'mod/approval:approve_pending_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CU13
    // Allows a user to approve the pending applications of other users,
    // usually their staff.
    'mod/approval:approve_pending_application_user' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'staffmanager' => CAP_ALLOW,
        ]
    ],
    // CA13
    // Allows a user to approve the pending applications of other users,
    // where the application exists within the given context.
    'mod/approval:approve_pending_application_any' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowapprover' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX14 Attach file to application
    // This capability needs to be granted in combination with some editing
    // capability - it grants no access on its own. If a user can edit, it
    // determines if they can attach files.
    /////////////////////////////////////////////////////////////////////
    // CW14
    // Allows the owner to attach a file to their applications,
    // where the application exists within the given context.
    'mod/approval:attach_file_to_application_owner' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CP14
    // Allows the applicant to attach a file to their applications,
    // where the application exists within the given context.
    'mod/approval:attach_file_to_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CU14
    // Allows a user to attach a file to the applications of other users,
    // usually their staff.
    'mod/approval:attach_file_to_application_user' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'staffmanager' => CAP_ALLOW,
        ]
    ],
    // CA14
    // Allows a user to attach a file to the applications of other users,
    // where the application exists within the given context.
    'mod/approval:attach_file_to_application_any' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowapprover' => CAP_ALLOW,
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX15 View comments on application
    /////////////////////////////////////////////////////////////////////
    // CW15
    // Allows the owner to view comments on their applications,
    // where the application exists within the given context.
    'mod/approval:view_comment_on_application_owner' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CP15
    // Allows the applicant to view comments on their applications,
    // where the application exists within the given context.
    'mod/approval:view_comment_on_application_applicant' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CU15
    // Allows a user to view comments on the applications of other users,
    //usually their staff.
    'mod/approval:view_comment_on_application_user' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'staffmanager' => CAP_ALLOW,
        ]
    ],
    // CA15
    // Allows a user to view comments on the applications of other users,
    // where the application exists within the given context.
    'mod/approval:view_comment_on_application_any' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowapprover' => CAP_ALLOW,
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX16 Post comments on applications
    /////////////////////////////////////////////////////////////////////
    // CW16
    // Allows the owner to post comments on their applications,
    // where the application exists within the given context.
    'mod/approval:post_comment_on_application_owner' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CP16
    // Allows the applicant to post comments on their applications,
    // where the application exists within the given context.
    'mod/approval:post_comment_on_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CU16
    // Allows a user to post comments on the applications of other users,
    //usually their staff.
    'mod/approval:post_comment_on_application_user' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'staffmanager' => CAP_ALLOW,
        ]
    ],
    // CA16
    // Allows a user to post comments on the applications of other users,
    // where the application exists within the given context.
    'mod/approval:post_comment_on_application_any' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowapprover' => CAP_ALLOW,
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX17 Post comments on pending applications
    /////////////////////////////////////////////////////////////////////
    // CU17
    // Allows a user to post comments on only pending applications of other users,
    //usually their staff.
    'mod/approval:post_comment_on_pending_application_user' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA17
    // Allows a user to post comments on only pending applications of other users,
    // where the application exists within the given context.
    'mod/approval:post_comment_on_pending_application_any' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX18 Withdraw unsubmitted application
    /////////////////////////////////////////////////////////////////////
    // CW18
    // Allows the owner to withdraw their unsubmitted applications,
    // where the application exists within the given context.
    'mod/approval:withdraw_unsubmitted_application_owner' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CP18
    // Allows the applicant to withdraw their unsubmitted applications,
    // where the application exists within the given context.
    'mod/approval:withdraw_unsubmitted_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CU18
    // Allows a user to withdraw the unsubmitted applications of other users,
    // usually their staff.
    'mod/approval:withdraw_unsubmitted_application_user' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA18
    // Allows a user to withdraw the unsubmitted applications of other users,
    // where the application exists within the given context.
    'mod/approval:withdraw_unsubmitted_application_any' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX19 After submission, in-approvals withdraw application
    /////////////////////////////////////////////////////////////////////
    // CW19
    // Allows the owner to withdraw their in-approval applications,
    // where the application exists within the given context.
    'mod/approval:withdraw_in_approvals_application_owner' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
        ]
    ],
    // CP19
    // Allows the applicant to withdraw their in-approval applications,
    // where the application exists within the given context.
    'mod/approval:withdraw_in_approvals_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CU19
    // Allows a user to withdraw the in-approval applications of other users,
    // usually their staff.
    'mod/approval:withdraw_in_approvals_application_user' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA19
    // Allows a user to withdraw the in-approval applications of other users,
    // where the application exists within the given context.
    'mod/approval:withdraw_in_approvals_application_any' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX20 Can backdate dates when editing
    /////////////////////////////////////////////////////////////////////
    // CW20
    // Allows an owner to backdate their applications.
    'mod/approval:backdate_application_owner' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
        ]
    ],
    // CP20
    // Allows an applicant to backdate their applications.
    'mod/approval:backdate_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
        ]
    ],
    // CU20
    // Allows a user to backdate the applications of other users, usually their staff.
    'mod/approval:backdate_application_user' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA20
    // Allows a user to backdate the applications of other users,
    // where the application exists within the given context.
    'mod/approval:backdate_application_any' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CX00 Application creation
    // This determines what applications the user can create. As such, there's
    // no need for an 'owner' capability.
    /////////////////////////////////////////////////////////////////////
    // CP00
    // Allows a user to create applications for themselves.
    'mod/approval:create_application_applicant' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ]
    ],
    // CU00
    // Allows a user to create applications for other users,
    // usually their staff.
    'mod/approval:create_application_user' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
        ]
    ],
    // CA00
    // Allows a user to create applications for all users,
    // where the application exists within the given context.
    'mod/approval:create_application_any' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'approvalworkflowmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // Workflow-management-related capabilities
    /////////////////////////////////////////////////////////////////////
    // CWKF00
    'mod/approval:manage_workflows' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF01
    'mod/approval:create_workflow_from_template' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF02
    'mod/approval:create_workflow' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF03
    'mod/approval:clone_workflow' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF04
    'mod/approval:edit_draft_workflow' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF05
    'mod/approval:edit_active_workflow' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF06
    'mod/approval:activate_workflow' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF07
    'mod/approval:archive_workflow' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF08
    'mod/approval:create_workflow_template' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF09
    'mod/approval:edit_workflow_template' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF10
    'mod/approval:manage_workflow_stages' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF11
    'mod/approval:manage_workflow_form_view' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF12
    'mod/approval:add_workflow_approval_level' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF13
    'mod/approval:reorder_workflow_approval_level' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF14
    'mod/approval:manage_individual_workflow_approvers' => [
        'riskbitmask' => RISK_SPAM | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF15
    'mod/approval:manage_relationship_workflow_approvers' => [
        'riskbitmask' => RISK_SPAM | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF16
    'mod/approval:manage_workflow_assignment_overrides' => [
        'riskbitmask' => RISK_SPAM | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF17
    'mod/approval:manage_workflow_transitions' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF18
    'mod/approval:manage_workflow_notifications' => [
        'riskbitmask' => RISK_SPAM | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF19
    'mod/approval:move_application_between_workflows' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CWKF20
    'mod/approval:delete_workflow_approval_level' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    /////////////////////////////////////////////////////////////////////
    // CS01
    'mod/approval:manage_lookup_tables' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    'mod/approval:view_workflow_applications_report' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'tenantdomainmanager' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ]
    ],

];