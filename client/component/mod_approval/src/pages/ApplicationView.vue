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
  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module mod_approval
-->
<template>
  <div>
    <Header
      :back-url="backUrl"
      :machine-id="machineId"
      :current-user-id="Number(currentUser.id)"
      :show-edit-dropdown="true"
      :loading="
        [
          'application.cloningApplication',
          'application.deletingApplication',
        ].some(this.$matches)
      "
      :confirm-delete="$matches('application.confirmDelete')"
      :confirm-withdraw="$matches('application.confirmWithdraw')"
      :withdrawing="
        $matches('application.confirmWithdraw.withdrawingApplication')
      "
    />
    <Layout>
      <template v-slot:column>
        <div class="tui-mod_approval-applicationView__schemaForm">
          <Loader v-if="applicationLoading" :loading="true" />
          <SchemaView
            input-width="full"
            input-char-length="full"
            :display="!applicationLoading"
            :schema="formSchema"
            :value="formValues"
            @loaded="$send($e.SCHEMA_READY)"
          >
            <template v-slot:sections="{ sections, renderSection }">
              <template v-for="section in sections">
                <div :key="section.key">
                  <h3 class="tui-mod_approval-applicationView__sectionTitle">
                    {{
                      section.title ||
                        $str('section_title', 'mod_approval', section)
                    }}
                  </h3>
                  <render :vnode="renderSection(section)" />
                </div>
              </template>
            </template>

            <template v-slot:row="{ field, rowComponent, renderField }">
              <div class="tui-mod_approval-applicationView__field">
                <component
                  :is="rowComponent"
                  v-if="rowComponent"
                  :heading="field.label"
                />
                <template v-else>
                  <div class="tui-mod_approval-applicationView__labelContainer">
                    <span class="tui-mod_approval-applicationView__label">
                      {{ field.label }}
                      <span
                        v-if="field.required"
                        class="tui-mod_approval-applicationView__required"
                        :title="$str('required', 'core')"
                      >
                        <span aria-hidden="true">*</span>
                        <span class="sr-only">
                          {{ $str('required', 'core') }}
                        </span>
                      </span>
                    </span>
                  </div>
                  <render :vnode="renderField()" />
                </template>
              </div>
            </template>
          </SchemaView>
        </div>
      </template>

      <template v-slot:sidepanel>
        <ApprovalSidePanel class="tui-mod_approval-applicationView__sidePanel">
          <template v-slot:user-profile>
            <MicroProfileCard :user="user" :show-email="true" size="xsmall" />
          </template>
          <template v-slot:actions>
            <div
              v-if="!completed"
              class="tui-mod_approval-applicationView__actions"
            >
              <div class="tui-mod_approval-applicationView__actions_status">
                <span class="tui-mod_approval-applicationView__actions_label">
                  {{ $str('application_is_in:', 'mod_approval') }}
                </span>
                <span>{{
                  $str(
                    'stage_number_name',
                    'mod_approval',
                    currentWorkflowStage
                  )
                }}</span>
              </div>
              <div class="tui-mod_approval-applicationView__actions_status">
                <span class="tui-mod_approval-applicationView__actions_label">
                  {{ $str('status:', 'mod_approval') }}
                </span>
                <span v-if="currentApprovalLevel">
                  {{
                    $str(
                      'status_pending_level',
                      'mod_approval',
                      currentApprovalLevel.name
                    )
                  }}
                </span>
                <span v-else>
                  {{
                    $str(
                      'status_pending_stage',
                      'mod_approval',
                      currentWorkflowStage.name
                    )
                  }}
                </span>
                <Lozenge
                  v-if="!$matches('sidePanel.loading') && rejectedOrWithdrawn"
                  class="tui-mod_approval-applicationView__actions_last-action"
                  :text="lastActionName"
                  :type="rejected ? 'alert' : 'neutral'"
                />
              </div>
              <Button
                v-if="approvers.length > 0 && currentApprovalLevel"
                class="tui-mod_approval-applicationView__actions_approvers"
                :styleclass="{ transparent: true }"
                :text="
                  $str(
                    'view_current_approval_level_approvers',
                    'mod_approval',
                    currentApprovalLevel.name
                  )
                "
                @click="$send($e.SHOW_ALL_APPROVERS)"
              />
              <div
                v-if="approvers.length === 0 && currentApprovalLevel"
                class="tui-mod_approval-applicationView__actions_approvers"
              >
                <span class="tui-mod_approval-applicationView__actions_label">
                  {{
                    $str(
                      'no_approvers_on_level',
                      'mod_approval',
                      currentApprovalLevel.name
                    )
                  }}
                </span>
              </div>
              <div
                v-if="rejectedOrWithdrawn"
                class="tui-mod_approval-applicationView__actions_status"
              >
                <span v-html="rejectedOrWithdrawnMessage" />
              </div>
              <div
                v-if="interactor.can_approve || displayCompleteButton"
                class="tui-mod_approval-applicationView__actions_action"
              >
                <div class="tui-mod_approval-applicationView__actions_area">
                  <MicroProfileCard
                    :user="avatar"
                    :emphasise-name="true"
                    size="xxsmall"
                  />
                  <div
                    v-if="interactor.can_approve"
                    class="tui-mod_approval-applicationView__actions_buttons"
                  >
                    <ButtonGroup>
                      <Button
                        :disabled="$matches('sidePanel.loading')"
                        :styleclass="{ primary: true }"
                        :loading="
                          $matches('sidePanel.actionsTab.approvingApplication')
                        "
                        :text="$str('approve', 'mod_approval')"
                        @click="$send($e.APPROVE_APPLICATION)"
                      />
                      <Button
                        :disabled="$matches('sidePanel.loading')"
                        :text="$str('reject', 'mod_approval')"
                        @click="$send($e.REJECT_APPLICATION)"
                      />
                    </ButtonGroup>
                  </div>
                  <div
                    v-else-if="displayCompleteButton"
                    class="tui-mod_approval-applicationView__actions_buttons"
                  >
                    <ActionLink
                      :styleclass="{ primary: 'true' }"
                      :href="editUrl"
                      :disabled="$matches('sidePanel.loading')"
                      :text="
                        $str(
                          'complete_section',
                          'mod_approval',
                          currentWorkflowStage.name
                        )
                      "
                    />
                  </div>
                </div>
              </div>
              <ModalPresenter
                :open="$matches('sidePanel.actionsTab.confirmReject')"
                @request-close="$send($e.CANCEL)"
              >
                <RejectApplicationModal :context-id="contextId" />
              </ModalPresenter>
              <ModalPresenter
                :open="$matches('sidePanel.actionsTab.viewApproversOpen')"
                @request-close="$send($e.CANCEL)"
              >
                <ApproversModal
                  v-if="approvers.length > 0 && currentApprovalLevel"
                  :title="
                    currentApprovalLevel
                      ? $str(
                          'current_approval_level_approvers',
                          'mod_approval',
                          currentApprovalLevel.name
                        )
                      : ''
                  "
                  :approvers="approvers"
                  :approval-level-name="currentApprovalLevel.name"
                >
                  <template v-slot:lozenge>
                    <Lozenge
                      v-if="rejected"
                      :text="
                        $str(
                          'model_application_action_status_rejected',
                          'mod_approval',
                          currentApprovalLevel.name
                        )
                      "
                      type="neutral"
                    />
                  </template>
                </ApproversModal>
              </ModalPresenter>
            </div>
            <div
              v-if="completed"
              class="tui-mod_approval-applicationView__actions"
            >
              {{
                $str('application_finished_message', 'mod_approval', completed)
              }}
            </div>
          </template>
          <template v-slot:comments>
            <SidePanelCommentBox
              component="mod_approval"
              area="comment"
              editor-variant="simple"
              :extra-extensions="['mention']"
              :instance-id="applicationId"
              :editor-context-id="contextId"
              :show-comment-form="showCommentForm"
              :show-like-button="false"
              @create-comment="handleCommentsUpdate"
              @delete-comment="handleCommentsUpdate"
              @update-comment="handleCommentsUpdate"
              @add-reply="handleCommentsUpdate"
            />
          </template>
          <template v-slot:activity>
            <Loader
              v-if="$matches('sidePanel.activityTab.loading')"
              :loading="true"
            />
            <ProgressTrackerNav
              v-else
              :items="$selectors.getStagesWithStates($context)"
              :force-vertical="forceVertical"
              marker-mode="workflow"
              :popover-trigger-type="popoverTriggerType"
              :label-opens-popover="labelOpensPopover"
            >
              <template v-slot="{ entry }">
                <div class="tui-mod_approval-sidePanel__stage">
                  {{ $str('stage_x', 'mod_approval', entry.ordinal_number) }}
                </div>
                <div class="tui-mod_approval-sidePanel__stageName">
                  {{ entry.name }}
                </div>

                <ul class="tui-mod_approval-sidePanel__stageActivities">
                  <template v-for="activity in entry.activities">
                    <li
                      :key="activity.id"
                      class="tui-mod_approval-sidePanel__stageActivity"
                    >
                      <div
                        class="
                          tui-mod_approval-sidePanel__stageActivityDescription
                        "
                        v-html="activity.description"
                      />
                      <div
                        class="
                          tui-mod_approval-sidePanel__stageActivityTimestamp
                        "
                      >
                        {{ $str('on_x', 'mod_approval', activity.timestamp) }}
                      </div>
                    </li>
                  </template>
                </ul>
              </template>
            </ProgressTrackerNav>
          </template>
        </ApprovalSidePanel>
      </template>
    </Layout>
  </div>
</template>

<script>
import {
  MOD_APPROVAL__APPLICATION_VIEW,
  OverallProgressState,
} from 'mod_approval/constants';
import { getProfileAnchor } from 'mod_approval/item_selectors/user';
import { mapQueryParamsToContext } from 'mod_approval/common/helpers';

// Machine
import applicationViewMachine from 'mod_approval/application/view/machine';

// Components
import ApprovalSidePanel from 'mod_approval/components/application/ApprovalSidePanel';
import Header from 'mod_approval/components/application/header/ApplicationHeader';
import Layout from 'mod_approval/components/page/LayoutOneColumnWithSidePanel';
import MicroProfileCard from 'mod_approval/components/cards/MicroProfileCard';
import SchemaView from 'mod_approval/components/schema_form/SchemaView';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import ApproversModal from 'mod_approval/components/application/ApproversModal';
import ActionLink from 'tui/components/links/ActionLink';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Loader from 'tui/components/loading/Loader';
import Lozenge from 'tui/components/lozenge/Lozenge';
import RejectApplicationModal from 'mod_approval/components/application/RejectApplicationModal';
import ProgressTrackerNav from 'tui/components/progresstracker/ProgressTrackerNav';
import SidePanelCommentBox from 'totara_comment/components/box/SidePanelCommentBox';

export default {
  name: 'ApplicationView',
  components: {
    ActionLink,
    ApprovalSidePanel,
    ModalPresenter,
    ApproversModal,
    Button,
    ButtonGroup,
    Cell,
    Header,
    HeaderCell,
    Layout,
    Loader,
    Lozenge,
    MicroProfileCard,
    ProgressTrackerNav,
    RejectApplicationModal,
    SchemaView,
    SidePanelCommentBox,
  },

  props: {
    currentUser: {
      type: Object,
      required: true,
    },

    queryResults: {
      type: Object,
      required: true,
    },
    backUrl: {
      required: true,
      type: String,
    },
    applicationId: { type: [String, Number], required: true },
    contextId: { type: [String, Number], required: true },
  },

  data() {
    return {
      machineId: MOD_APPROVAL__APPLICATION_VIEW,
      OverallProgressState: OverallProgressState,
      forceVertical: true,
      labelOpensPopover: false,
      popoverTriggerType: [],
    };
  },

  computed: {
    approvers() {
      return this.$selectors.getApprovers(this.$context);
    },
    avatar() {
      return this.$selectors.getOwnProfile(this.$context);
    },
    currentWorkflowStage() {
      return this.$selectors.getCurrentStage(this.$context);
    },
    currentApprovalLevel() {
      return this.$selectors.getCurrentApprovalLevel(this.$context);
    },
    displayCompleteButton() {
      return this.$selectors.getCanComplete(this.$context);
    },
    formValues() {
      return this.$selectors.getFormData(this.$context);
    },
    interactor() {
      return this.$selectors.getInteractor(this.$context);
    },
    user() {
      return this.$selectors.getUser(this.$context);
    },
    completed() {
      return this.$selectors.getCompleted(this.$context);
    },

    applicationLoading() {
      return [
        'application.loading',
        'sidePanel.actionsTab.approvingApplication.refetchingSchema',
      ].some(this.$matches);
    },

    sections() {
      return this.$selectors.getSections(this.$context);
    },
    formSchema() {
      return this.$selectors.getParsedFormSchema(this.$context);
    },
    editUrl() {
      return this.$selectors.getEditUrl(this.$context);
    },
    showCommentForm() {
      return !this.loading && !this.completed;
    },
    overallProgress() {
      return this.$selectors.getOverallProgress(this.$context);
    },
    lastActionName() {
      return this.$selectors.getLastActionName(this.$context);
    },
    lastActionUser() {
      return this.$selectors.getLastActionUser(this.$context);
    },
    rejected() {
      return this.overallProgress === OverallProgressState.REJECTED;
    },
    rejectedOrWithdrawn() {
      return [
        OverallProgressState.REJECTED,
        OverallProgressState.WITHDRAWN,
      ].includes(this.overallProgress);
    },
    rejectedOrWithdrawnMessage() {
      if (!this.rejectedOrWithdrawn) {
        return '';
      }

      // TODO TL-31452
      const profileAnchor = getProfileAnchor(this.lastActionUser);

      return this.$str(
        this.rejected
          ? 'application_rejected_message'
          : 'application_withdrawn_message',
        'mod_approval',
        {
          profileAnchor,
        }
      );
    },
  },

  xState: {
    machine() {
      const loadApplicationResult = this.queryResults
        .mod_approval_load_application;

      return applicationViewMachine({
        loadApplicationResult,
        currentUser: this.currentUser,
      });
    },
    mapQueryParamsToContext(params) {
      return mapQueryParamsToContext(params);
    },
    mapContextToQueryParams(context, prevContext) {
      const notifyAndNotifyType = context.notify && context.notifyType;
      return {
        notify: notifyAndNotifyType ? prevContext.notify : undefined,
        notify_type: notifyAndNotifyType ? prevContext.notifyType : undefined,
      };
    },
  },

  methods: {
    displayValue(value) {
      return value || this.$str('filter_na', 'mod_approval');
    },

    handleCommentsUpdate() {
      this.$send({ type: this.$e.COMMENTS_UPDATED });
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-applicationView {
  &__schemaForm {
    padding-bottom: var(--gap-8);
    padding-left: var(--gap-5);
  }

  &__sectionTitle {
    margin-top: 0;
    margin-bottom: var(--gap-6);
    padding-top: var(--gap-8);
    font-size: var(--font-size-20);
  }

  &__section {
    margin-top: var(--gap-12);
  }

  &__field {
    display: flex;
    margin-top: var(--gap-6);
  }

  &__required {
    color: var(--color-prompt-alert);
  }

  &__labelContainer {
    flex-basis: 50%;
    flex-shrink: 0;
  }

  &__label {
    @include tui-font-heading-label();
    min-width: 0;
    margin: 0;
    padding: 0 var(--gap-1) 0 0;
  }

  &__sidePanel {
    padding-top: var(--gap-8);
    padding-right: var(--gap-8);
    padding-bottom: var(--gap-8);
    padding-left: var(--gap-4);

    @media (min-width: $tui-screen-sm) {
      height: 100%;
      min-height: 42rem;
      border-left: var(--border-width-thin) solid var(--color-neutral-5);
    }
  }

  &__actions {
    &_status + &_status {
      margin-top: var(--gap-2);
    }
    &_label {
      font-weight: bold;
    }
    &_last-action {
      vertical-align: middle;
    }
    &_approvers {
      margin-top: var(--gap-4);
    }
    &_action {
      display: flex;
      margin-top: var(--gap-10);
    }
    &_area {
      margin-left: var(--gap-2);
    }
    &_buttons {
      margin-top: var(--gap-4);
    }
  }

  &__noApprovers {
    margin-top: var(--gap-6);
  }
}
</style>

<lang-strings>
{
  "core": [
    "required"
  ],
  "mod_approval": [
    "application_finished_message",
    "application_is_in:",
    "application_rejected_message",
    "application_withdrawn_message",
    "approve",
    "approver_name",
    "complete_section",
    "current_approval_level_approvers",
    "error:approve_application",
    "error:reject_application",
    "email",
    "filter_na",
    "form_sections",
    "model_application_action_status_rejected",
    "no_approvers_on_level",
    "on_x",
    "pending",
    "reject",
    "section_title",
    "stage_x",
    "status:",
    "status_pending_level",
    "status_pending_stage",
    "stage_number_name",
    "success:approve_application",
    "success:reject_application",
    "success:submit_application",
    "view_current_approval_level_approvers"
  ]
}
</lang-strings>
