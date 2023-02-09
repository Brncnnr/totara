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
import {
  getIsInApprovals,
  getDefaultValuesMap,
} from 'mod_approval/graphql_selectors/load_application';
import { getFormData } from 'mod_approval/application/edit/selectors';

export { hasNotify, fromDashboard } from 'mod_approval/common/guards';
export const sectionChanged = (context, event) =>
  context.activeSectionIndex !== event.index;
export const isDraft = (context, event) => event.draft;
export const isFormInvalid = context => Boolean(context.validationErrors);
export const isFormValid = context => !context.validationErrors;
export const isSaved = context => !context.unsavedChanges;
export const isInApprovals = context => getIsInApprovals(context);
export const preventScrollSupported = context => context.preventScrollSupported;
export const prefersReducedMotionNoPreference = () =>
  window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
export const isFormEmpty = context => {
  const formData = getFormData(context);

  if (
    !formData ||
    Object.values(formData).every(v => v === '' || v == null || v == undefined)
  ) {
    return true;
  }

  const defaultValuesMap = getDefaultValuesMap(context);

  /*
   * Check if there is a user input value which differs from the default value.
   * Return true(aka !false) as FormIsEmpty when input value does not exist
   * Return false(aka !true) as FormNotEmpty when input value exists
   */
  return !Object.entries(formData).some(
    ([key, value]) => Boolean(value) && value !== defaultValuesMap[key]
  );
};
