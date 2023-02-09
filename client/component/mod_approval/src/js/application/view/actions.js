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
import { notify } from 'tui/notifications';
import apollo from 'tui/apollo_client';
import getCommentsQuery from 'totara_comment/graphql/get_comments';
import loadApplicationActivitiesQuery from 'mod_approval/graphql/load_application_activities';
import { prepareValuesForView } from 'mod_approval/schema_form';
import { getApplicationId } from 'mod_approval/graphql_selectors/load_application';
import { getSuccessMessage } from 'mod_approval/messages';
export * from 'mod_approval/common/actions';

export const setRefetchSchema = assign({ refetchSchema: true });
export const unsetRefetchSchema = assign({ refetchSchema: false });

export const writeOrFetchComments = async (context, event) => {
  const { comment } = event.data.data;
  const variables = {
    component: 'mod_approval',
    area: 'comment',
    // comment query is indexed in cache by integer ids
    instanceid: parseInt(getApplicationId(context), 10),
    cursor: null,
  };

  try {
    const commentData = apollo.readQuery({
      query: getCommentsQuery,
      variables,
    });
    const comments = [...commentData.comments, comment];
    const cursor = Object.assign({}, commentData.cursor);
    apollo.writeQuery({
      query: getCommentsQuery,
      variables,
      data: { comments, cursor },
    });
  } catch (error) {
    // getComments query does not yet exist in cache
    // https://www.apollographql.com/docs/react/v2/caching/cache-interaction/#readquery

    // refetch comments in the background
    // no need to write to cache
    await apollo.query({ query: getCommentsQuery, variables });
  }
};

export const setHasRejectionComment = assign({
  hasRejectionComment: (context, { hasRejectionComment }) =>
    hasRejectionComment,
});

export const setSchemaReady = assign({ schemaReady: true });

export const successNotify = (context, event) => {
  return notify({
    message: getSuccessMessage(event),
    type: 'success',
  });
};

export const setupFormData = assign({
  formData: context => {
    return prepareValuesForView(
      context.parsedFormSchema,
      context.parsedFormData
    );
  },
});

/* Query triggered as a side-effect to fetch comment activity in the background
 * while user is viewing the comments tab
 */
export const refetchApplicationActivity = context => {
  return apollo.query({
    query: loadApplicationActivitiesQuery,
    variables: {
      input: {
        application_id: getApplicationId(context),
      },
    },
    fetchPolicy: 'network-only',
  });
};
