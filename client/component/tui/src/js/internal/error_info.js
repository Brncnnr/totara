/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module tui
 */

import { showModal } from './modal';
import Vue from 'vue';

let displayedErrors = null;
let activeModal = null;

/**
 * Show Refresh page modal when sesskey is invalid.
 */
export function showSessionExpiredModal(category) {
  if (activeModal) {
    return;
  }
  showErrorModal('tui/components/errors/SessionExpiredModal', { category });
}

/**
 * Checks if an error modal is currently being shown.
 *
 * @return {Boolean}
 */
export function isShowingErrorModal() {
  return activeModal !== null;
}

/**
 * Display the provided ErrorInfo[] to the user.
 *
 * @param {ErrorInfo[]} errors
 */
export async function showErrorInfo(errors) {
  if (!displayedErrors) {
    displayedErrors = Vue.observable([]);
  }
  errors = errors.filter(x => !displayedErrors.some(y => errorInfoEqual(x, y)));

  if (errors.length === 0) return;

  errors.forEach(x => displayedErrors.push(x));
  showErrorModal('tui/components/errors/ErrorModal', {
    errors: displayedErrors,
  });
}

/**
 * Check if two ErrorInfo object are identical.
 *
 * @param {ErrorInfo} x
 * @param {ErrorInfo} y
 * @returns {boolean}
 */
function errorInfoEqual(x, y) {
  return JSON.stringify(x) === JSON.stringify(y);
}

/**
 * Show error modal
 */
function showErrorModal(component, props) {
  if (activeModal) {
    return;
  }

  activeModal = showModal({
    component: tui.defaultExport(tui.require(component)),
    props,
    onClose() {
      displayedErrors = null;
      activeModal = null;
    },
  });
}
