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

import { getApplications } from 'mod_approval/graphql_selectors/others_applications';
import { getMyApplications } from 'mod_approval/graphql_selectors/my_applications';
import { MY, OTHERS } from 'mod_approval/constants';
export { hasNotify } from 'mod_approval/common/guards';

export const hasApplications = context => getApplications(context).length > 0;
export const hasMyApplications = context =>
  getMyApplications(context).length > 0;
export const invalidTab = (_, __, { cond: { specifiedTab } }) => {
  const tabIsInvalid = ![MY, OTHERS].includes(specifiedTab);
  return !specifiedTab || tabIsInvalid;
};

export const canViewOtherApplicationsTab = (
  _,
  __,
  { cond: { canApprove, specifiedTab } }
) => {
  return canApprove && specifiedTab === OTHERS;
};

export const canViewMyApplicationsTab = (
  _,
  __,
  { cond: { canApprove, specifiedTab } }
) => {
  return !canApprove || specifiedTab === MY;
};

/**
 * @typedef {{ type: 'CHANGE_PAGE'|'CHANGE_COUNT'|'FILTER'|'SORT', path: string[], value: ?string|?number }} SetQueryOptionsEvent
 */

/**
 * @param {object} context
 * @param {SetQueryOptionsEvent} event
 */
export const setQueryOptionsEvent = (context, event) => Boolean(event.path);
