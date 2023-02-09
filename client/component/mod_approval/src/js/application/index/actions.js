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
import { totaraUrl } from 'tui/util';
import { notify } from 'tui/notifications';
import { getString } from 'tui/i18n';
import { getErrorMessageAsync } from 'mod_approval/messages';

export const navigateToNew = (context, event) => {
  const { application_id } = event.data.data.mod_approval_create_application;
  window.location.href = totaraUrl('/mod/approval/application/edit.php', {
    application_id,
    notify_type: 'success',
    notify: 'create_draft_application',
  });
};

export const navigateToAllPending = () => {
  window.location.href = totaraUrl('/mod/approval/application/pending.php');
};

export const updateUserSearch = assign({
  fullname: (context, { fullname }) => fullname,
});

export const errorNotify = async (context, event) => {
  await notify({
    message: await getErrorMessageAsync(event),
    type: 'error',
  });
};

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
