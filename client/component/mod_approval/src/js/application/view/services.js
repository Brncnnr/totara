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
import { APPLICATION_FORM_SCHEMA } from 'mod_approval/constants';
import apollo from 'tui/apollo_client';
import { get } from 'tui/util';
import createCommentMutation from 'totara_comment/graphql/create_comment';
import applicationFormSchemaQuery from 'mod_approval/graphql/application_form_schema';
import loadApplicationActivitiesQuery from 'mod_approval/graphql/load_application_activities';
import applicationApproversQuery from 'mod_approval/graphql/application_approvers';
import {
  getApplicationId,
  getCurrentApprovalLevelId,
} from 'mod_approval/graphql_selectors/load_application';
import { loadApplicationOptions } from 'mod_approval/common/query_options';
export * from 'mod_approval/common/services';

export function applicationFormSchema(context) {
  return {
    query: applicationFormSchemaQuery,
    variables: {
      input: {
        application_id: getApplicationId(context),
      },
      full_schema: true,
    },
    fetchPolicy: 'network-only',
  };
}
applicationFormSchema.updateContext = (context, { data }) => {
  const formSchema =
    get(data, ['mod_approval_application_form_schema', 'form_schema']) || '{}';
  const formData =
    get(data, ['mod_approval_application_form_schema', 'form_data']) || '{}';

  return Object.assign({}, context, {
    [APPLICATION_FORM_SCHEMA]: data,
    parsedFormSchema: JSON.parse(formSchema),
    parsedFormData: JSON.parse(formData),
  });
};

export function loadApplication(context) {
  return Object.assign({}, loadApplicationOptions(getApplicationId(context)), {
    fetchPolicy: 'network-only',
  });
}

export function loadApplicationActivities(context) {
  return {
    query: loadApplicationActivitiesQuery,
    variables: {
      input: {
        application_id: getApplicationId(context),
      },
    },
    fetchPolicy: 'network-only',
  };
}

export function applicationApprovers(context) {
  return {
    query: applicationApproversQuery,
    variables: {
      input: {
        application_id: getApplicationId(context),
        workflow_stage_approval_level_id: getCurrentApprovalLevelId(context),
      },
    },
  };
}

export const createComment = (context, event) => {
  return apollo.mutate({
    mutation: createCommentMutation,
    variables: event.createCommentVariables,
  });
};
