/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @module mod_approval
 */

import * as applicationSelectors from './load_application';
import * as workflowSelectors from './load_workflow';
import * as manageableWorflowsSelectors from './manageable_workflows';
import * as userSelectors from './selectable_users';
import * as applicantSelectors from './selectable_applicants';
import * as jobAssignmentSelectors from './create_new_application_menu';
import * as workflowTypeSelectors from './load_workflow_types';
import * as othersApplicationsSelectors from './others_applications';
import * as myApplicationsSelectors from './my_applications';
import * as ancestorSelectors from './ancestor_assignment_approval_levels';
import * as workflowIdNumberIsUniqueSelectors from './workflow_id_number_is_unique';
import * as getFormsSelectors from './get_active_forms';

export {
  applicantSelectors,
  applicationSelectors,
  jobAssignmentSelectors,
  workflowTypeSelectors,
  userSelectors,
  workflowSelectors,
  manageableWorflowsSelectors,
  othersApplicationsSelectors,
  myApplicationsSelectors,
  ancestorSelectors,
  workflowIdNumberIsUniqueSelectors,
  getFormsSelectors,
};
