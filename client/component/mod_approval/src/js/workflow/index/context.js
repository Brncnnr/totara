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
 * @author Simon Tegg <simon.teggfe@totaralearning.com>
 * @module mod_approval
 */

import {
  MANAGEABLE_WORKFLOWS,
  WorkflowsSortOption,
} from 'mod_approval/constants';

/** @type WorkflowIndexContext */
export default {
  [MANAGEABLE_WORKFLOWS]: {
    mod_approval_manageable_workflows: {
      items: [],
      total: 0,
    },
  },

  toMutateId: null,

  // track last used variables on context
  // will be updated when the tables is filtered, sorted etc
  // (updatePagination/updateSort/updateFilters)
  variables: {
    query_options: {
      sort_by: WorkflowsSortOption.UPDATED,
      pagination: {
        page: 1,
        limit: 20,
      },
    },
  },
};
