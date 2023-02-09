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
import { assign, sendParent } from 'tui_xstate/xstate';
import { UPDATE_ACTIVE_SECTION } from './state';

const EYELINE = 2 / 3;

export const setSections = assign({
  sectionIds: (context, event) => event.sectionIds,
});

export const setPositionBottoms = assign({
  positionBottoms: ({ sectionIds }) =>
    sectionIds.map(
      sectionId =>
        document.getElementById(sectionId).getBoundingClientRect().bottom
    ),
});

export const setActiveSection = assign(
  ({ activeSectionIndex, positionBottoms }) => {
    const index =
      positionBottoms.findIndex((positionBottom, index, positionBottoms) => {
        // scrolling down
        // section header moves above the bottom third, but still on screen -> activate current section
        if (
          positionBottom >= 0 &&
          positionBottom < window.innerHeight * EYELINE
        ) {
          return true;
        }

        // scrolling up
        // next section drops below bottom third -> activate current section
        const nextIndex = index + 1;
        const next = positionBottoms[nextIndex];
        if (next && next > window.innerHeight * EYELINE) {
          return true;
        }

        // last section
        if (!next) {
          return true;
        }

        return false;
      }) || 0; //

    return {
      activeSectionIndex: index,
      indexHasChanged: index !== activeSectionIndex,
    };
  }
);

export const notifyActiveSection = sendParent(({ activeSectionIndex }) => ({
  type: UPDATE_ACTIVE_SECTION,
  index: activeSectionIndex,
}));
