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

import { MOD_APPROVAL__SCROLL_MACHINE } from 'mod_approval/constants';
import { actions } from 'tui_xstate/xstate';
export const SET_SECTIONS = 'SET_SECTIONS';
export const SCROLL = 'SCROLL';
export const UPDATE_ACTIVE_SECTION = 'UPDATE_ACTIVE_SECTION'; // used by notifyActiveSection

const { choose } = actions;

export default {
  id: MOD_APPROVAL__SCROLL_MACHINE,
  context: {
    sectionIds: [],
    positionBottoms: [],
    activeSectionIndex: null,
    indexHasChanged: false,
  },
  initial: 'active',
  on: {
    [SET_SECTIONS]: { actions: 'setSections' },
  },
  invoke: {
    src: () => send => {
      const handleScroll = () => {
        send(SCROLL);
      };

      window.addEventListener('scroll', handleScroll);

      return () => {
        window.removeEventListener('scroll', handleScroll);
      };
    },
  },

  states: {
    active: {
      on: {
        [SCROLL]: {
          target: 'throttling',
          actions: [
            'setPositionBottoms',
            'setActiveSection',
            choose([
              { cond: 'indexHasChanged', actions: 'notifyActiveSection' },
            ]),
          ],
        },
      },
    },
    throttling: {
      after: {
        50: 'active',
      },
    },
  },
};
