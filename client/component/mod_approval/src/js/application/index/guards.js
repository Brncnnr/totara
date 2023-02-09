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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module mod_approval
 */

import { getJobAssignments } from 'mod_approval/graphql_selectors/create_new_application_menu';
export { hasNotify } from 'mod_approval/common/guards';

export const hasMultipleJobAssignments = context =>
  getJobAssignments(context).length > 1;

export const hasCreateData = (context, event) =>
  event.data && event.data.selectedUser && event.data.selectedJobAssignment;
