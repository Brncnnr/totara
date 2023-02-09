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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module mod_approval
 */

import { assign, shimmerAssign } from 'tui_xstate/xstate';
import { get, uniqueId, totaraUrl } from 'tui/util';
import { notify } from 'tui/notifications';
import { getString } from 'tui/i18n';
import { FormviewVisibility, LOAD_WORKFLOW } from 'mod_approval/constants';
import apollo from 'tui/apollo_client';
import { produce } from 'tui/immutable';
import loadWorkflow from 'mod_approval/graphql/load_workflow';
import {
  getActiveStageId,
  getOverridesStageKey,
  defaultVariables,
  getPreviousStage,
  getNextStage,
  getFormSchemaSectionsFieldKeys,
} from './selectors';
import {
  getWorkflow,
  getWorkflowId,
  getWorkflowContextId,
  getWorkflowStages,
} from 'mod_approval/graphql_selectors/load_workflow';
import { variables as selectableUsersVariables } from 'mod_approval/graphql_selectors/selectable_users';
import { getSuccessMessageAsync } from 'mod_approval/messages';
import { ADD_APPROVAL_LEVEL, DELETE_APPROVAL_LEVEL } from './state/persistence';
import { loadWorkflowOptions } from './query_options';

const loadWorkflowLatestVersionPath = [
  'mod_approval_load_workflow',
  'workflow',
  'latest_version',
];

const latestVersionPath = [LOAD_WORKFLOW, ...loadWorkflowLatestVersionPath];

const getSectionFormviews = (context, workflowStageId, sectionKey) => {
  const stage = getWorkflowStages(context).find(x => x.id === workflowStageId);
  const stageFormviews = (stage && stage.formviews) || [];
  const sectionFields =
    getFormSchemaSectionsFieldKeys(context)[sectionKey] || [];
  return stageFormviews.filter(formview =>
    sectionFields.includes(formview.field_key)
  );
};

export const setToArchiveOverrides = shimmerAssign((context, event) => {
  context.toArchiveOverrides = event.overrideAssignment;
});

export const setRenameWorkflowStageId = shimmerAssign((context, event) => {
  context.toEditStageId = event.stageId;
});

export const updateStageName = (context, event) => {
  const newStages = getWorkflowStages(context).map(stage => {
    if (stage.id === event.workflowStageId) {
      return Object.assign({}, stage, { name: event.name });
    } else {
      return stage;
    }
  });

  const workflow = getWorkflow(context);

  apollo.writeQuery({
    query: loadWorkflow,
    variables: {
      input: {
        workflow_id: getWorkflowId(context),
      },
    },
    data: {
      mod_approval_load_workflow: {
        workflow: produce(workflow, draft => {
          draft.latest_version.stages = newStages;
        }),
      },
    },
  });
};

export const updateApprovalLevelName = (context, { name, approvalLevelId }) => {
  const workflow = getWorkflow(context);
  const activeStageId = getActiveStageId(context);

  apollo.writeQuery({
    query: loadWorkflow,
    variables: {
      input: {
        workflow_id: getWorkflowId(context),
      },
    },
    data: {
      mod_approval_load_workflow: {
        workflow: produce(workflow, draft => {
          draft.latest_version.stages.forEach(stage => {
            if (stage.id === activeStageId) {
              stage.approval_levels.forEach(approvalLevel => {
                if (approvalLevel.id === approvalLevelId) {
                  approvalLevel.name = name;
                }
              });
            }
          });
        }),
      },
    },
  });
};

export const setToEditStageId = shimmerAssign((context, event) => {
  context.toEditStageId = event.stageId;
});

export const unsetToEditStageId = assign({ toEditStageId: null });

export const setToEditApprovalLevelId = shimmerAssign((context, event) => {
  context.toEditApprovalLevelId = event.approvalLevelId;
});
export const unsetToEditApprovalLevelId = shimmerAssign(context => {
  context.toEditApprovalLevelId = null;
});
export const setAppendUsers = assign({ appendUsers: true });
export const unsetAppendUsers = assign({ appendUsers: false });

export const setToEditInteraction = shimmerAssign(
  (context, { interaction }) => {
    context.toEditInteraction = interaction;
  }
);

export const setApprovers = shimmerAssign(
  (
    context,
    { approvers, approvalModalTitle, inheritedFrom, approvalModalLevelName }
  ) => {
    return {
      approvers,
      approvalModalTitle,
      approvalModalLevelName,
      inheritedFrom,
    };
  }
);

export const updateSelectedWorkflowStageId = shimmerAssign((context, event) => {
  context.activeWorkflowStageId = event.stageId;
});

export const updatedNameNotify = () =>
  notify({
    message: getString('success:rename_stage', 'mod_approval'),
    type: 'success',
    duration: 3000,
  });

export const savedNotify = () =>
  notify({
    message: getString('success:save_workflow', 'mod_approval'),
    type: 'success',
    duration: 3000,
  });

export const setActiveVariables = shimmerAssign((context, event) => {
  const overridesStageKey = getOverridesStageKey(context);
  const variables =
    event.variables || defaultVariables(getActiveStageId(context));

  context.variables[overridesStageKey] = variables;
});

export const updateSelectedApproverType = shimmerAssign(
  (
    context,
    {
      approverType,
      variables: {
        input: { approval_level_id },
      },
    }
  ) => {
    context.selectedApproverTypes[approval_level_id] = approverType;
  }
);

export const updateUserSearch = shimmerAssign(
  (context, { approvalLevelId, variables }) => {
    context.userSearchVariables[approvalLevelId] = variables;
    context.activeApprovalLevelId = approvalLevelId;
  }
);

export const setApprovalLevelApprovers = shimmerAssign(
  (
    context,
    {
      approvers,
      variables: {
        input: { approval_level_id },
      },
    }
  ) => {
    const activeStageId = getActiveStageId(context);
    const latestVersion = get(context, latestVersionPath);
    const stage = latestVersion.stages.find(x => x.id === activeStageId);
    const level = stage.approval_levels.find(x => x.id === approval_level_id);
    level.approvers = approvers;
  }
);

export const unsetActiveFullnameSearch = shimmerAssign(
  (
    context,
    {
      variables: {
        input: { approval_level_id },
      },
    }
  ) => {
    if (!context.userSearchVariables[approval_level_id]) {
      context.userSearchVariables[approval_level_id] = selectableUsersVariables(
        {
          workflowId: getWorkflowId(context),
        }
      );
    }

    context.userSearchVariables[approval_level_id].input.filters.fullname = '';
  }
);

export const reorderApprovalLevel = shimmerAssign((context, event) => {
  const activeStageId = getActiveStageId(context);
  const latestVersion = get(context, latestVersionPath);
  const stage = latestVersion.stages.find(x => x.id === activeStageId);
  const levelOfInterest = stage.approval_levels.splice(event.from, 1)[0];
  stage.approval_levels.splice(event.to, 0, levelOfInterest);
  stage.approval_levels.forEach((level, index) => {
    level.ordinal_number = index + 1;
  });
});

export const addOptimisticApprovalLevel = shimmerAssign(context => {
  const activeStageId = getActiveStageId(context);
  const latestVersion = get(context, latestVersionPath);
  const stage = latestVersion.stages.find(x => x.id === activeStageId);
  stage.approval_levels.push({ id: `temp-${uniqueId()}`, loading: true });
});

export const addToQueue = shimmerAssign(
  (context, { type, inputId, variables }) => {
    context.mutationQueue.push({ type, inputId, variables });
  }
);

export const replaceApprovalLevelWithLoader = shimmerAssign(context => {
  const activeStageId = getActiveStageId(context);
  const latestVersion = get(context, latestVersionPath);
  const stage = latestVersion.stages.find(x => x.id === activeStageId);
  stage.approval_levels = stage.approval_levels.map(approvalLevel => {
    if (approvalLevel.id == context.toEditApprovalLevelId) {
      approvalLevel = {
        id: `temp-${uniqueId()}`,
        loading: true,
        deleting: true,
      };
    }
    return approvalLevel;
  });
});

export const setActiveMutation = shimmerAssign(context => {
  const { mutationQueue } = context;
  const oldestMutation = mutationQueue[0];
  const sameInputMutations =
    mutationQueue.length > 1
      ? mutationQueue
          .slice(1)
          .filter(({ inputId }) => inputId === oldestMutation.inputId)
      : [];

  // Use the most recent mutation from an input as representative of the user's intent for that input
  // Otherwise use the oldest queued mutation
  const activeMutation =
    sameInputMutations.length > 0
      ? sameInputMutations[sameInputMutations.length - 1]
      : oldestMutation;

  // drop all mutations of the same input from the queue
  context.mutationQueue = mutationQueue.filter(
    ({ inputId }) => inputId !== activeMutation.inputId
  );
  context.activeMutation = activeMutation;
});

export const unsetActiveMutation = assign({ activeMutation: null });

export const setAssignRolesTargetAssignment = shimmerAssign(
  (context, event) => {
    context.assignRolesTargetAssignment = event.data.assignment;
  }
);

export const setNewWorkflowStagePending = assign({
  pendingSwitchToWorkflowStageId: (context, event) =>
    get(event, [
      'data',
      'data',
      'mod_approval_workflow_version_add_stage',
      'stage',
      'id',
    ]),
});

export const setPendingSwitchToOtherStage = shimmerAssign(context => {
  const prevStage = getPreviousStage(context);
  const nextStage = getNextStage(context);

  let switchTo = null;
  if (prevStage) {
    switchTo = prevStage.id;
  } else if (nextStage) {
    switchTo = nextStage.id;
  }

  context.pendingSwitchToWorkflowStageId = switchTo;
});

export const doPendingStageSwitch = shimmerAssign(context => {
  context.activeWorkflowStageId = context.pendingSwitchToWorkflowStageId;
  context.pendingSwitchToWorkflowStageId = null;
});

export const navigateToClone = (context, event) => {
  const id = get(event, [
    'data',
    'data',
    'mod_approval_workflow_clone',
    'workflow',
    'id',
  ]);
  window.location.href = totaraUrl('/mod/approval/workflow/edit.php', {
    workflow_id: id,
    notify_type: 'success',
    notify: 'clone_workflow',
  });
};

export const navigateToDashboard = () => {
  window.location.href = totaraUrl('/mod/approval/workflow/index.php');
};

export const navigateToAssignRolesInWorkflow = context => {
  window.location.href = totaraUrl('/admin/roles/assign.php', {
    contextid: getWorkflowContextId(context),
  });
};

export const navigateToAssignRolesInExistingApprovalOverride = (
  context,
  event
) => {
  window.location.href = totaraUrl('/admin/roles/assign.php', {
    contextid: get(event, [
      'data',
      'data',
      'mod_approval_override_for_assignment_type',
      'contextid',
    ]),
  });
};

export const navigateToAssignRolesInNewApprovalOverride = (context, event) => {
  window.location.href = totaraUrl('/admin/roles/assign.php', {
    contextid: get(event, [
      'data',
      'data',
      'mod_approval_assignment_manage',
      'assignment',
      'contextid',
    ]),
  });
};

export const notifySuccess = async (context, event) => {
  return notify({
    duration: 3000,
    message: await getSuccessMessageAsync(event),
    type: 'success',
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

// TODO: this code creates an stagesExtendedContext client-side and is workaround.
// TL-33216 will modify load_workflow and workflow_version_add_stage so that the stagesExtendedContext is returned in
// this query and mutation
export const addStageExtendedContext = shimmerAssign((context, event) => {
  const stageId = get(event, [
    'data',
    'data',
    'mod_approval_workflow_version_add_stage',
    'stage',
    'id',
  ]);

  context.stagesExtendedContexts.push({
    area: 'workflow_stage',
    component: 'mod_approval',
    contextId: context.stagesExtendedContexts[0].contextId,
    itemId: parseInt(stageId, 10),
  });
});

export const updateFormviewInCache = (
  context,
  { workflowStageId, key, update }
) => {
  const variables = { input: { workflow_id: getWorkflowId(context) } };
  const data = apollo.readQuery({ query: loadWorkflow, variables });

  apollo.writeQuery({
    query: loadWorkflow,
    variables,
    data: produce(data, draft => {
      const latestVersion = get(draft, loadWorkflowLatestVersionPath);
      const stage = latestVersion.stages.find(x => x.id === workflowStageId);
      const formview = stage.formviews.find(x => x.field_key == key);
      if (formview) {
        Object.assign(formview, update);
      } else {
        stage.formviews.push(Object.assign({ field_key: key }, update));
      }
    }),
  });
};

export const updateSectionVisibilityInContext = shimmerAssign(
  (context, { workflowStageId, key, visible }) => {
    const stageMap = context.stagesSectionVisibility;
    if (!stageMap[workflowStageId]) {
      stageMap[workflowStageId] = {};
    }
    stageMap[workflowStageId][key] = visible;
  }
);

export const updateSectionVisibilityInCache = (
  context,
  { workflowStageId, key, visible }
) => {
  const fieldKeys = getFormSchemaSectionsFieldKeys(context)[key] || [];
  const variables = { input: { workflow_id: getWorkflowId(context) } };
  const data = apollo.readQuery({ query: loadWorkflow, variables });

  const sectionFormviewVisibility =
    get(context.savedFormviewVisibility, [workflowStageId, key]) || {};

  apollo.writeQuery({
    query: loadWorkflow,
    variables,
    data: produce(data, draft => {
      const latestVersion = get(draft, loadWorkflowLatestVersionPath);
      const stage = latestVersion.stages.find(x => x.id === workflowStageId);
      const sectionFormviews = stage.formviews.filter(x =>
        fieldKeys.includes(x.field_key)
      );

      sectionFormviews.forEach(formview => {
        if (visible === false) {
          formview.visibility = FormviewVisibility.HIDDEN;
        } else if (visible === true) {
          // restore visibility
          const visibility = sectionFormviewVisibility[formview.field_key];
          if (visibility != null) {
            formview.visibility = visibility;
          }
        }
      });
    }),
  });
};

export const storeSectionFieldVisibility = shimmerAssign(
  (context, { workflowStageId, key }) => {
    if (!context.savedFormviewVisibility[workflowStageId]) {
      context.savedFormviewVisibility[workflowStageId] = {};
    }

    // get every formview in the section and store visibility
    const sectionFormviews = getSectionFormviews(context, workflowStageId, key);

    context.savedFormviewVisibility[workflowStageId][
      key
    ] = sectionFormviews.reduce((acc, formview) => {
      acc[formview.field_key] = formview.visibility;
      return acc;
    }, {});
  }
);

export const setShowSectionVisibilityUpdates = shimmerAssign(
  (context, { workflowStageId, key }) => {
    if (!context.sectionVisibilityUpdates[workflowStageId]) {
      context.sectionVisibilityUpdates[workflowStageId] = {};
    }

    const stageFormviewVisibility =
      get(context.savedFormviewVisibility, [workflowStageId, key]) || {};

    const sectionFormviews = getSectionFormviews(context, workflowStageId, key);

    const updates = sectionFormviews.reduce((acc, formview) => {
      const visibility = stageFormviewVisibility[formview.field_key];
      if (visibility != null) {
        acc.push({
          field_key: formview.field_key,
          visibility,
        });
      }
      return acc;
    }, []);

    context.sectionVisibilityUpdates[workflowStageId][key] = updates;
  }
);

export const setHideSectionVisibilityUpdates = shimmerAssign(
  (context, { workflowStageId, key }) => {
    if (!context.sectionVisibilityUpdates[workflowStageId]) {
      context.sectionVisibilityUpdates[workflowStageId] = {};
    }

    const sectionFormviews = getSectionFormviews(context, workflowStageId, key);

    const updates = sectionFormviews.map(formview => ({
      field_key: formview.field_key,
      visibility: FormviewVisibility.HIDDEN,
    }));

    context.sectionVisibilityUpdates[workflowStageId][key] = updates;
  }
);

export const addFormviewUpdateToQueue = shimmerAssign(
  (context, { type, workflowStageId, key, update }) => {
    context.mutationQueue.push({
      type,
      inputId: `${type}-${workflowStageId}-${key}`,
      variables: { workflowStageId, key, update },
    });
  }
);

export const addSectionVisibilityToQueue = shimmerAssign(
  (context, { type, workflowStageId, key }) => {
    context.mutationQueue.push({
      type,
      inputId: `${type}-${workflowStageId}-${key}`,
      variables: {
        workflowStageId,
        key,
      },
    });
  }
);

export function removeDeletedWorkflowStage(context, { data: result }) {
  const stageId = result.context.stageId;
  const options = loadWorkflowOptions(getWorkflowId(context));
  const data = apollo.readQuery(options);
  apollo.writeQuery(
    Object.assign({}, options, {
      data: produce(data, draft => {
        const latestVersion = get(draft, loadWorkflowLatestVersionPath);
        latestVersion.stages = latestVersion.stages.filter(
          x => x.id !== stageId
        );
      }),
    })
  );
}

function addApprovalLevelDone(context, { mutation, result }) {
  const level =
    result.data.mod_approval_workflow_stage_add_approval_level.approval_level;
  const options = loadWorkflowOptions(getWorkflowId(context));
  const data = apollo.readQuery(options);
  apollo.writeQuery(
    Object.assign({}, options, {
      data: produce(data, draft => {
        const latestVersion = get(draft, loadWorkflowLatestVersionPath);
        const stage = latestVersion.stages.find(
          x => x.id === mutation.workflowStageId
        );
        stage.approval_levels.push(level);
      }),
    })
  );
}

function deleteApprovalLevelDone(context, { result }) {
  const stageId =
    result.data.mod_approval_workflow_stage_delete_approval_level.stage.id;
  const levelId = result.context.approvalLevelId;

  const options = loadWorkflowOptions(getWorkflowId(context));
  const data = apollo.readQuery(options);
  apollo.writeQuery(
    Object.assign({}, options, {
      data: produce(data, draft => {
        const latestVersion = get(draft, loadWorkflowLatestVersionPath);
        const stage = latestVersion.stages.find(x => x.id === stageId);
        stage.approval_levels = stage.approval_levels.filter(
          x => x.id !== levelId
        );
      }),
    })
  );
}

const mutationsDone = {
  [ADD_APPROVAL_LEVEL]: addApprovalLevelDone,
  [DELETE_APPROVAL_LEVEL]: deleteApprovalLevelDone,
};

export const mutationDone = (context, { data }) => {
  const type = data.mutation.type;
  if (mutationsDone[type]) {
    mutationsDone[type](context, data);
  }
};
