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
import { createSelector } from 'tui_xstate/util';
import { SELECTABLE_USERS } from '../constants';

const KEY = 'mod_approval_selectable_users';

const getSelectableUserData = context =>
  (context[SELECTABLE_USERS] || {})[KEY] || {};

export const getSelectableUsers = createSelector(
  getSelectableUserData,
  data => data.items || []
);

export const getTotal = createSelector(getSelectableUserData, data =>
  'total' in data ? data.total : null
);

export const getNextCursor = createSelector(getSelectableUserData, data =>
  'next_cursor' in data ? data.next_cursor : null
);

/**
 * @param {Array=} items
 * @param {number=} total
 * @param {string=} next_cursor
 **/
export function create(items = [], total = null, nextCursor = null) {
  return {
    [KEY]: {
      items,
      total,
      next_cursor: nextCursor,
    },
  };
}

export function variables({ workflowId, search = '', cursor = undefined }) {
  return {
    input: {
      pagination: { cursor },
      filters: {
        fullname: search,
      },
      workflow_id: workflowId,
    },
  };
}
