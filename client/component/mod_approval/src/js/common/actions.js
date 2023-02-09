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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module mod_approval
 */
import pending from 'tui/pending';
import { totaraUrl, get, parseQueryString, formatParams } from 'tui/util';
import { getString } from 'tui/i18n';
import { assign } from 'tui_xstate/xstate';
import { notify } from 'tui/notifications';
import { MY, OTHERS } from 'mod_approval/constants';

/**
 * Actions hold the side effects that happen on a page, e.g redirecting to another page, updating context, etc.
 */

export const navigateToDashboard = () => {
  const done = pending();
  setTimeout(() => {
    done();
    window.location.href = totaraUrl('/mod/approval/application/index.php', {
      notify_type: 'success',
      notify: 'delete_application',
    });
  }, 1000);
};

export const navigateToClone = (context, event) => {
  const editUrl = get(event, [
    'data',
    'data',
    'mod_approval_application_clone',
    'application',
    'page_urls',
    'edit',
  ]);
  window.location.href = totaraUrl(editUrl, {
    notify_type: 'success',
    notify: 'clone_application',
  });
};

// TODO: TL-30787 - distinction between toast errors and other errors in <Header />?
export const errorNotify = assign({
  notification: (context, event) => {
    return {
      message: getString(`error:${getErrorType(event.type)}`, 'mod_approval'),
      type: 'error',
    };
  },
});

export const withdrawnNotify = () => {
  notify({
    duration: 3000,
    message: getString(`success:withdraw_application`, 'mod_approval'),
    type: 'success',
  });
};

export const dismissNotification = assign({ notification: null });

export const showNotify = context => {
  notify({
    duration: 3000,
    message: getString(
      `${context.notifyType}:${context.notify}`,
      'mod_approval'
    ),
    type: context.notifyType,
  });
};

export const unsetNotify = assign({
  notify: null,
  notifyType: null,
});

/**
 * Removes the params the ApplicationsTable uses for preserving state.
 * These are not needed here beyond page load.
 */
export const stripDashboardParams = () => {
  const params = parseQueryString(window.location.search);
  const updatedParams = {};
  Object.keys(params).forEach(key => {
    if (
      !key.includes(OTHERS) &&
      !key.includes(MY) &&
      key !== 'tab' &&
      key !== 'from_dashboard'
    ) {
      updatedParams[key] = params[key];
    }
  });

  const formattedParams = formatParams(updatedParams);
  const url = `${window.location.pathname}?${formattedParams}`;
  window.history.replaceState(null, null, url);
};

// TODO: TL-31235
// consolidate with mod_approval/js/messages.js
function getErrorType(xStateError) {
  switch (xStateError) {
    case 'error.platform.saveApplication':
      return 'save_application';
    case 'error.platform.submitApplication':
      return 'submit_application';
    case 'error.platform.deleteApplication':
      return 'delete_application';
    case 'error.platform.withdrawApplication':
      return 'withdraw_application';
    case 'error.platform.cloneApplication':
      return 'clone_application';
    case 'error.platform.createComment':
    case 'error.platform.rejectApplication':
      return 'reject_application';
    case 'error.platform.approveApplication':
      return 'approve_application';

    default:
      return 'generic';
  }
}
