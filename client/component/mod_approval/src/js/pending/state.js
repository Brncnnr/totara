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
  APPLICATIONS_FROM_OTHERS,
  MOD_APPROVAL__PENDING,
} from 'mod_approval/constants';
import context from './context';

// EVENTS
const LOAD_MORE = 'LOAD_MORE';
const SET_GRID_WIDTH = 'SET_GRID_WIDTH';

/**
 * @return {import('xstate').StateNodeConfig}
 */
export default () => ({
  id: MOD_APPROVAL__PENDING,
  context,
  initial: 'loading',

  on: {
    [SET_GRID_WIDTH]: { actions: 'setGridWidth' },
  },

  states: {
    loading: {
      invoke: {
        id: `loading_${APPLICATIONS_FROM_OTHERS}`,
        src: APPLICATIONS_FROM_OTHERS,
        onDone: 'ready',
      },
    },

    ready: {
      id: 'ready',
      meta: {
        defaultErrorTarget: true,
      },
      on: {
        [LOAD_MORE]: {
          cond: 'hasMore',
          target: 'loadingMore',
          actions: 'updateVariables',
        },
      },
    },

    loadingMore: {
      invoke: {
        id: `loadingMore_${APPLICATIONS_FROM_OTHERS}`,
        src: APPLICATIONS_FROM_OTHERS,
        onDone: 'ready',
      },
    },
  },
});
