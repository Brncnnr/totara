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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

import tui from './tui';
import Container from 'tui/components/notifications/ToastContainer';
import { memoizeLoad } from './util';
import { isShowingErrorModal } from './internal/error_info';

/**
 * Get the toast container to add notifications to.
 *
 * One will be created if it does not exist.
 *
 * @private
 * @function
 * @returns {Promise<Vue>}
 */
const getContainer = memoizeLoad(async () => {
  const tmpSpan = document.createElement('span');
  document.body.appendChild(tmpSpan);
  const container = await tui.mount(Container, {}, tmpSpan);
  await new Promise(r => setTimeout(r, 0));
  return container;
});

/**
 * Show a notification toast.
 *
 * @param {object} options
 * @param {string} options.message Message to display.
 * @param {(number|null)} [options.duration] Duration to display the message, or null to display until dismissed.
 * @param {('info' | 'success' | 'warning' | 'error')} [options.type] Importance type of the notification.
 */
export async function notify(options) {
  if (isShowingErrorModal()) {
    return;
  }
  options = Object.assign(
    {
      duration: 5000,
      message: '...',
      type: 'success',
    },
    options
  );

  const container = await getContainer();
  container.addNotification(options);
}
