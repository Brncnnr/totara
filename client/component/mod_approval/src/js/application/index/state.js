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
 * @author Simon Tegg <simon.teggfe@totaralearning.com>
 * @module mod_approval
 */

import { actions } from 'tui_xstate/xstate';
import { $QUERY_ALL } from 'tui_xstate/constants';
import modalContext from 'mod_approval/application/index/create_new/context';
import makeContext from 'mod_approval/application/index/context';
import {
  jobAssignmentSelectors,
  workflowTypeSelectors,
} from 'mod_approval/graphql_selectors';
import {
  getOwnAvailableWorkflowTypes,
  getOwnDefaultWorkflowType,
} from 'mod_approval/application/index/selectors';
import {
  LOAD_WORKFLOW_TYPES,
  MY_APPLICATIONS,
  APPLICATIONS_FROM_OTHERS,
  CREATE_NEW_APPLICATION_MENU,
  MOD_APPROVAL__DASHBOARD,
  MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL,
} from 'mod_approval/constants';

const { choose } = actions;

// EVENTS
const NEW_APPLICATION = 'NEW_APPLICATION';
const SHOW_ALL_PENDING = 'SHOW_ALL_PENDING';
const ON_BEHALF = 'ON_BEHALF';

export default function makeState({
  showApplicationsFromOthers,
  currentUserId,
}) {
  const loadingQueries = [
    LOAD_WORKFLOW_TYPES,
    MY_APPLICATIONS,
    CREATE_NEW_APPLICATION_MENU,
  ];
  if (showApplicationsFromOthers) {
    loadingQueries.push(APPLICATIONS_FROM_OTHERS);
  }

  return {
    id: MOD_APPROVAL__DASHBOARD,
    context: makeContext({ showApplicationsFromOthers, currentUserId }),
    initial: 'loading',
    states: {
      loading: {
        entry: choose([{ cond: 'hasNotify', actions: 'showNotify' }]),
        invoke: {
          src: {
            type: $QUERY_ALL,
            queries: loadingQueries,
          },
          onDone: [{ target: 'ready' }],
        },
        exit: choose([{ cond: 'hasNotify', actions: 'unsetNotify' }]),
      },

      ready: {
        id: 'ready',
        meta: {
          defaultErrorTarget: true,
        },
        on: {
          [SHOW_ALL_PENDING]: { actions: 'navigateToAllPending' },
          [NEW_APPLICATION]: [
            // open createNewApplicationModal to select the job assignment and/or workflow type
            {
              target: 'createNew',
              cond: 'hasMultipleJobAssignments',
            },
            // otherwise we have the applicantId and the jobAssignmentId
            // proceed directly to create
            { target: 'creatingApplication' },
          ],
          [ON_BEHALF]: 'createNew',
        },
      },

      createNew: {
        invoke: {
          id: MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL,
          src: 'createNewApplicationModalMachine',
          onDone: [
            { target: 'creatingApplication', cond: 'hasCreateData' },
            { target: 'ready' }, // modal cancelled
          ],
          data: (context, { type }) => {
            return type !== ON_BEHALF
              ? modalContext({
                  currentUserId,
                  // populate modal with already selected user
                  selectedUser: { id: currentUserId },
                  // populate jobAssignment <Select /> with 0th jobAssignment
                  selectedJobAssignment: jobAssignmentSelectors.getDefaultJobAssignment(
                    context
                  ),
                  workflowTypeOptions: getOwnAvailableWorkflowTypes(context),
                  selectedWorkflowType: getOwnDefaultWorkflowType(context),
                  createNewApplicationMenu:
                    context[CREATE_NEW_APPLICATION_MENU],
                })
              : modalContext({
                  currentUserId,
                  forYourself: false,
                  workflowTypeOptions: workflowTypeSelectors.getWorkflowTypes(
                    context
                  ),
                  selectedWorkflowType: workflowTypeSelectors.getDefaultWorkflowType(
                    context
                  ),
                });
          },
        },
      },
      creatingApplication: {
        invoke: {
          id: 'createApplication',
          src: 'createApplication',
          onDone: { actions: 'navigateToNew' },
        },
      },
    },
  };
}
