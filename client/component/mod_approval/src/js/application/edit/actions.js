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

import { assign, spawn } from 'tui_xstate/xstate';
import { totaraUrl } from 'tui/util';
import { notify } from 'tui/notifications';
import { getString } from 'tui/i18n';
import { MOD_APPROVAL__SCROLL_MACHINE } from 'mod_approval/constants';
import scrollMachine from '../scroll/machine';
import { prepareValuesForEdit } from 'mod_approval/schema_form';
import { getApplicationId } from 'mod_approval/graphql_selectors/load_application';
import { getBackParams } from './selectors';

export * from 'mod_approval/common/actions';

export const addBeforeUnloadEventListener = () =>
  window.addEventListener('beforeunload', unloadHandler);

export const emptyNotify = assign({
  notification: () => ({
    message: getString(`warning:save_application_empty`, 'mod_approval'),
    type: 'warning',
  }),
});

export const focusFirstInvalid = context =>
  context.focusFirstInvalid && context.focusFirstInvalid();

export const navigateToSubmitted = context => {
  window.location.href = totaraUrl(
    '/mod/approval/application/view.php',
    Object.assign(
      {
        application_id: getApplicationId(context),
      },
      getBackParams(context)
    )
  );
};

export const navigateToSubmittedSuccess = context => {
  window.location.href = totaraUrl(
    '/mod/approval/application/view.php',
    Object.assign(
      {
        application_id: getApplicationId(context),
        notify_type: 'success',
        notify: 'submit_application',
      },
      getBackParams(context)
    )
  );
};

export const scrollToTop = () => window.scrollTo(0, 0);

export const focusRefNormal = (context, event) => {
  event.ref.focus({ preventScroll: true });
};

export const focusRefPolyfill = (context, event) => {
  const scrollX = window.pageXOffset;
  const scrollY = window.pageYOffset;
  event.ref.focus();
  window.scrollTo(scrollX, scrollY);
};

export const smoothScrollToRef = (context, event) =>
  event.ref.scrollIntoView({ behavior: 'smooth' });

export const abruptScrollToRef = (context, event) => event.ref.scrollIntoView();

export const removeBeforeUnloadListener = () =>
  window.removeEventListener('beforeunload', unloadHandler);

export const trySubmit = context => context.trySubmit();
export const updateActiveSection = assign({
  activeSectionIndex: (context, event) => event.index,
});
export const updateFormData = assign({
  formData: (context, event) => event.formData,
});

// triggers the form (Reform) to propagate 'validation-changed' event
// which then triggers updateValidationErrors below
export const validate = (context, event) => event.validate();
export const updateValidationErrors = assign({
  validationErrors: (context, { validationResult }) =>
    validationResult.isValid ? null : validationResult.getError(),
});

export const setRefMethods = assign((context, event) => {
  return {
    focusFirstInvalid: event.focusFirstInvalid,
    trySubmit: event.trySubmit,
  };
});

export const setUnsaved = assign({ unsavedChanges: true });
export const setSaved = assign({ unsavedChanges: false });
export const savedNotify = () => {
  notify({
    duration: 3000,
    message: getString(`success:save_application`, 'mod_approval'),
    type: 'success',
  });
};

export const noChangesNotify = assign({
  notification: () => ({
    message: getString(`warning:save_application_no_changes`, 'mod_approval'),
    type: 'warning',
  }),
});

export const setupFormData = assign({
  formData: context => {
    return prepareValuesForEdit(
      context.parsedFormSchema,
      context.parsedFormData
    );
  },
});

export const updateKeepApprovals = assign({
  keepApprovals: (_, { keepApprovals }) => {
    return keepApprovals;
  },
});
export const resetKeepApprovals = assign({ keepApprovals: false });

function unloadHandler(event) {
  event.preventDefault();

  // For older browsers that still show custom message.
  const discardUnsavedChanges = getString(
    `unsaved_changes_warning`,
    'mod_approval'
  );

  // Chrome requires returnValue to be set.
  event.returnValue = discardUnsavedChanges;

  return discardUnsavedChanges;
}

export const spawnScrollmachine = assign({
  scrollMachine: () => spawn(scrollMachine(), MOD_APPROVAL__SCROLL_MACHINE),
});
