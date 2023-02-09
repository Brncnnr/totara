<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->

<template>
  <div>
    <WorkflowHeader :back-url="backUrl" :machine-id="machineId" />

    <ModalPresenter :open="$matches('persistence.error')">
      <UnrecoverableErrorModal />
    </ModalPresenter>

    <LayoutTwoColumn class="tui-mod_approval-workflowEdit__body">
      <template v-slot:left>
        <div class="tui-mod_approval-workflowEdit__workflowStages">
          <div class="tui-mod_approval-workflowEdit__workflowStagesHeaderRow">
            <h4 class="tui-mod_approval-workflowEdit__workflowStagesHeader">
              {{ $str('workflow_stages', 'mod_approval') }}
            </h4>

            <ButtonIcon
              v-if="!$selectors.getWorkflowIsActive($context)"
              class="tui-mod_approval-workflowEdit__addWorkflowStage"
              :aria-label="$str('button_add_workflow_stage', 'mod_approval')"
              :styleclass="{
                circle: true,
                small: true,
              }"
              :disabled="$matches('persistence.saving')"
              @click="$send($e.ADD_WORKFLOW_STAGE)"
            >
              <AddIcon size="200" />
            </ButtonIcon>
          </div>

          <ProgressTrackerNav
            :items="workflowStages"
            :force-vertical="forceVertical"
            marker-mode="workflow"
            :popover-trigger-type="popoverTriggerType"
            :label-opens-popover="labelOpensPopover"
          >
            <template v-slot="{ entry, index }">
              <div class="tui-mod_approval-workflowEdit__stageSection">
                <div class="tui-mod_approval-workflowEdit__stageItem">
                  <!-- using an a[role=button] instead of Button
                  because our Button only allows a string as content -->
                  <a
                    href="#workflowDetails"
                    role="button"
                    class="tui-mod_approval-workflowEdit__stageLink"
                    :aria-expanded="isStageActive(entry) ? 'true' : 'false'"
                    @click.prevent="handleStageClick(entry)"
                  >
                    <div class="tui-mod_approval-workflowEdit__stageNumber">
                      {{
                        $str('stage_number_type', 'mod_approval', {
                          ordinal_number: index + 1,
                          type: entry.type.label,
                        })
                      }}
                    </div>
                    <div class="tui-mod_approval-workflowEdit__stageName">
                      {{ entry.name }}
                    </div>
                  </a>
                  <div class="tui-mod_approval-workflowEdit__stageMenu">
                    <Dropdown
                      v-if="!$selectors.getWorkflowIsActive($context)"
                      position="bottom-left"
                      class="tui-mod_approval-workflowEdit__dropdown"
                    >
                      <template v-slot:trigger="{ toggle }">
                        <MoreButton
                          :aria-label="
                            $str(
                              'more_actions_for_stage_x',
                              'mod_approval',
                              entry.name
                            )
                          "
                          @click="toggle"
                        />
                      </template>
                      <DropdownItem
                        @click="
                          $send({
                            type: $e.RENAME_WORKFLOW_STAGE,
                            stageId: entry.id,
                          })
                        "
                      >
                        {{ $str('stage_rename', 'mod_approval') }}
                      </DropdownItem>
                      <DropdownItem
                        :disabled="!canDeleteWorkflowStage(entry)"
                        @click="
                          $send({
                            type: $e.DELETE_WORKFLOW_STAGE,
                            stageId: entry.id,
                          })
                        "
                      >
                        {{ $str('delete', 'core') }}
                      </DropdownItem>
                    </Dropdown>
                  </div>
                </div>
                <div
                  v-if="isStageActive(entry)"
                  class="tui-mod_approval-workflowEdit__subSections"
                >
                  <a
                    v-for="item in navSubSections(entry)"
                    :key="item.event"
                    href="#workflowSubSection"
                    role="button"
                    class="tui-mod_approval-workflowEdit__subSectionNavItem"
                    :class="{
                      'tui-mod_approval-workflowEdit__subSectionNavItem--active':
                        item.active,
                    }"
                    @click.prevent="$send(item.event)"
                  >
                    {{ item.text }}
                  </a>
                </div>
              </div>
            </template>
          </ProgressTrackerNav>
        </div>
      </template>

      <template v-slot:right>
        <WorkflowDetails
          v-if="workflowStages.length > 0"
          id="workflowDetails"
          :key="activeStageId"
          class="tui-mod_approval-workflowEdit__workflowDetails"
        />
      </template>
    </LayoutTwoColumn>

    <Loader v-if="fullpageLoading" :loading="true" fullpage />

    <ModalPresenter
      :open="$matches('persistence.addStage')"
      @request-close="$send({ type: $e.CANCEL })"
    >
      <AddStageModal />
    </ModalPresenter>
    <ModalPresenter
      :open="$matches('persistence.renameStage')"
      @request-close="$send({ type: $e.CANCEL })"
    >
      <RenameStageModal :workflow-stage="$selectors.getToEditStage($context)" />
    </ModalPresenter>
    <ModalPresenter
      :open="$matches('persistence.renameApprovalLevel')"
      @request-close="$send({ type: $e.CANCEL })"
    >
      <RenameApprovalLevelModal
        :approval-level="$selectors.getToEditApprovalLevel($context)"
      />
    </ModalPresenter>

    <ConfirmationModal
      :open="$matches('persistence.deleteStage')"
      close-button
      :title="$str('delete_stage', 'mod_approval')"
      :confirm-button-text="$str('delete', 'core')"
      @confirm="$send({ type: $e.CONFIRM })"
      @cancel="$send({ type: $e.CANCEL })"
    >
      {{
        $str(
          'delete_stage_warning_message',
          'mod_approval',
          $selectors.getToEditStageName($context)
        )
      }}
    </ConfirmationModal>
  </div>
</template>

<script>
// Constants
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';

// Machine
import workflowEditMachine from 'mod_approval/workflow/edit/machine';
import * as workflowStageSelectors from 'mod_approval/item_selectors/workflow_stage';
import setParams from 'mod_approval/workflow/edit/set_params';

// Components
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import MoreButton from 'tui/components/buttons/MoreIcon';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import AddIcon from 'tui/components/icons/Add';
import LayoutTwoColumn from 'tui/components/layouts/LayoutTwoColumn';
import Loader from 'tui/components/loading/Loader';
import ProgressTrackerNav from 'tui/components/progresstracker/ProgressTrackerNav';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import WorkflowHeader from 'mod_approval/components/workflow/WorkflowHeader';
import WorkflowDetails from 'mod_approval/components/workflow/WorkflowDetails';
import UnrecoverableErrorModal from 'mod_approval/components/workflow/UnrecoverableErrorModal';
import AddStageModal from 'mod_approval/components/workflow/AddStageModal';
import RenameStageModal from 'mod_approval/components/workflow/RenameStageModal';
import RenameApprovalLevelModal from 'mod_approval/components/workflow/RenameApprovalLevelModal';

export default {
  components: {
    ButtonIcon,
    MoreButton,
    Dropdown,
    DropdownItem,
    AddIcon,
    LayoutTwoColumn,
    Loader,
    ProgressTrackerNav,
    ConfirmationModal,
    ModalPresenter,
    WorkflowDetails,
    WorkflowHeader,
    AddStageModal,
    UnrecoverableErrorModal,
    RenameStageModal,
    RenameApprovalLevelModal,
  },

  props: {
    contextId: {
      type: Number,
      required: true,
    },

    stagesExtendedContexts: Array,

    backUrl: {
      required: true,
      type: String,
    },
    approverTypes: {
      type: Array,
      required: true,
    },
    stageTypes: {
      type: Array,
      required: true,
    },
    queryResults: {
      required: true,
      type: Object,
    },
  },

  data() {
    return {
      dismissable: {
        esc: true,
      },
      machineId: MOD_APPROVAL__WORKFLOW_EDIT,
      forceVertical: true,
      labelOpensPopover: false,
      popoverTriggerType: [],
      workflowStageSelectors,
    };
  },

  computed: {
    fullpageLoading() {
      return [
        'persistence.loading',
        'persistence.cloning',
        'persistence.deleting',
        'persistence.addingStage',
        'persistence.deletingStage',
        'persistence.preparingApprovalOverrideToAssignRoles',
      ].some(this.$matches);
    },

    activeStageId() {
      return this.$selectors.getActiveStageId(this.$context);
    },

    workflowStages() {
      return this.$selectors
        .getWorkflowStages(this.$context)
        .map(workflowStage =>
          Object.assign({}, workflowStage, {
            states: [this.isStageActive(workflowStage) ? 'selected' : 'ready'],
          })
        );
    },
  },

  xState: {
    machine() {
      const { workflow } = this.queryResults.mod_approval_load_workflow;
      return workflowEditMachine({
        categoryContextId: this.contextId,
        workflow,
        stagesExtendedContexts: this.stagesExtendedContexts,
        approverTypes: this.approverTypes.map(approverType =>
          Object.assign({}, approverType, { type: String(approverType.type) })
        ),
      });
    },

    mapStateToQueryParams(statePath, prevStatePath) {
      return setParams(statePath, prevStatePath);
    },

    mapQueryParamsToContext({ notify, notify_type, stage_id }) {
      const context = {
        notify: notify || null,
        notifyType: notify_type || null,
      };
      if (stage_id) {
        context.activeWorkflowStageId = stage_id;
      }
      return context;
    },

    mapContextToQueryParams(context, prevContext) {
      const params = {};

      if (
        context.notify != prevContext.notify &&
        context.notifyType != prevContext.notifyType
      ) {
        const notifyAndNotifyType = context.notify && context.notifyType;
        params.notify = notifyAndNotifyType ? prevContext.notify : undefined;
        params.notify_type = notifyAndNotifyType
          ? prevContext.notifyType
          : undefined;
      }

      const oldId = this.$selectors.getActiveStageId(prevContext);
      const id = this.$selectors.getActiveStageId(context);

      if (id !== oldId) {
        params.stage_id = id;
      }

      return params;
    },
  },

  methods: {
    isStageActive(stage) {
      return stage && this.activeStageId === stage.id;
    },

    canDeleteWorkflowStage(stage) {
      return this.$selectors.getWorkflowStagesDeletable(this.$context)[
        stage.id
      ];
    },

    navSubSections(stage) {
      return [
        {
          cond: workflowStageSelectors.hasFormViews(stage),
          active: this.$matches('navigation.form'),
          event: this.$e.TO_FORM_SUBSECTION,
          text: this.$str('form', 'mod_approval'),
        },
        {
          cond: workflowStageSelectors.hasApprovalLevels(stage),
          active: this.$matches('navigation.approvals'),
          event: this.$e.TO_APPROVALS_SUBSECTION,
          text: this.$str('approvals', 'mod_approval'),
        },
        {
          cond: workflowStageSelectors.hasInteractions(stage),
          active: this.$matches('navigation.interactions'),
          event: this.$e.TO_INTERACTIONS_SUBSECTION,
          text: this.$str('interactions_feature', 'mod_approval'),
        },
        {
          cond: true,
          active: this.$matches('navigation.notifications'),
          event: this.$e.TO_NOTIFICATIONS_SUBSECTION,
          text: this.$str('notifications', 'mod_approval'),
        },
      ].filter(({ cond }) => cond);
    },

    handleStageClick(stage) {
      if (!this.isStageActive(stage)) {
        this.$send({
          type: this.$e.TOGGLE_WORKFLOW_STAGE,
          stageId: stage.id,
        });
      }
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "delete"
  ],
  "mod_approval": [
    "approvals",
    "button_add_workflow_stage",
    "delete_stage",
    "delete_stage_warning_message",
    "form",
    "interactions_feature",
    "more_actions_for_stage_x",
    "notifications",
    "stage_number_type",
    "stage_rename",
    "success:save_workflow",
    "success:rename_stage",
    "success:clone_workflow",
    "success:create_workflow",
    "success:archive_workflow",
    "success:unarchive_workflow",
    "success:save_overrides",
    "workflow_stages",
    "error:generic"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-workflowEdit {
  &__body {
    padding-right: var(--gap-8);
    padding-left: var(--gap-8);
  }

  &__workflowStages,
  &__workflowDetails {
    padding-bottom: var(--gap-8);
  }

  &__workflowStages {
    height: 100%;
    padding-top: var(--gap-8);
    border-right: var(--border-width-thin) solid var(--color-neutral-5);
  }

  &__workflowStagesHeaderRow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--gap-6);
    padding-right: var(--gap-4);
  }

  &__workflowStagesHeader {
    margin: 0;
    font-size: var(--font-size-15);
  }

  &__stageItem {
    display: flex;
    // align so stage link is centered
    margin-top: calc(var(--gap-1) * -1);
  }

  &__stageLink {
    flex-grow: 1;
    padding-top: var(--gap-1);
    padding-bottom: var(--gap-1);
    padding-left: var(--gap-1);
    color: var(--color-text);
    &:hover,
    &:focus {
      color: var(--color-text);
      text-decoration: none;
    }
    &:hover {
      background-color: var(--color-state-highlight-neutral);
    }
  }

  &__stageNumber {
    @include tui-font-body-xx-small();
    color: var(--color-neutral-6);
  }

  &__stageName {
    @include tui-wordbreak--hard;
  }

  &__stageMenu {
    margin-top: var(--gap-2);
  }

  &__subSections {
    display: flex;
    flex-direction: column;
    margin-top: var(--gap-4);
  }

  &__subSectionNavItem {
    padding: var(--gap-1) var(--gap-2);
    &:hover,
    &:focus {
      text-decoration: none;
    }
    &:hover {
      background-color: var(--color-state-highlight-neutral);
    }

    &--active {
      color: var(--color-neutral-1);
      background: var(--color-state);
      &:hover,
      &:focus {
        color: var(--color-neutral-1);
        background: var(--color-state);
      }
    }
  }

  &__subSectionNavButton {
    &--active {
      color: var(--color-neutral-1);
      &:hover,
      &:focus {
        color: var(--color-neutral-1);
      }
    }
  }

  &__workflowDetails {
    padding-top: var(--gap-8);
  }

  &__approversCard {
    position: relative;
    margin-bottom: var(--gap-4);
    padding: var(--gap-4);
  }

  &__approversCard {
    flex-direction: column;
  }

  &__approversCardDropdown {
    position: absolute;
    top: var(--gap-2);
    right: var(--gap-2);
  }
}

.tui-pageHeading {
  &__title {
    @include tui-font-heading-small;
  }
}

//  progressTracker overrides
.tui-progressTrackerNav__itemContent {
  flex-grow: 1;
}
</style>
