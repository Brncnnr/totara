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
import { getIsFinalApprovalLevel } from 'mod_approval/graphql_selectors/load_application';
import { getActivities } from 'mod_approval/graphql_selectors/load_application_activities';

export { hasNotify, fromDashboard } from 'mod_approval/common/guards';
export const gotToActivity = (context, event) => event.tab === 'activityTab';
export const gotToComments = (context, event) => event.tab === 'commentsTab';
export const isSchemaReady = context => context.schemaReady;
export const isFinalApprovalLevel = getIsFinalApprovalLevel;
export const refetchSchema = context => context.refetchSchema;
export const emptyActivity = context => getActivities(context).length === 0;
