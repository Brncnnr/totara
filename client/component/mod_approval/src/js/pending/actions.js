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
import { assign } from 'tui_xstate/xstate';
import { YourProgressState } from 'mod_approval/constants';
export { genericErrorNotify } from 'mod_approval/common/actions';

/**
 * Context
 * @typedef {import('./context).PendingContext} PendingContext
 *
 * Events
 * @typedef {{ type: 'LOAD_MORE', page: number }} LoadMoreEvent
 * @typedef {{ type: 'SET_GRID_WIDTH', gridWidth: number }} SetGridWidthEvent
 */

export const updateVariables = assign({
  /**
   * @param {PendingContext} context
   * @param {LoadMoreEvent} event
   * @return {object}
   */
  variables: (context, { page }) => {
    return {
      query_options: {
        pagination: {
          page,
          limit: 20,
        },
        filters: {
          your_progress: YourProgressState.PENDING,
        },
      },
    };
  },
});

export const setGridWidth = assign({
  /**
   * @param {PendingContext} context
   * @param {SetGridWidthEvent} event
   * @return {number}
   */
  gridWidth: (context, { gridWidth }) => gridWidth,
});
