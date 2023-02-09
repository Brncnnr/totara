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
  <div class="tui-mod_approval-header">
    <ApplicationNavigationBar
      :back-url="backUrlWithParams"
      :back-content="$str('back_to_applications', 'mod_approval')"
    />
    <div aria-live="assertive">
      <NotificationBanner
        v-if="$context.notification"
        class="tui-mod_approval-header__notificationRow"
        :type="$context.notification.type"
        :message="$context.notification.message"
        :dismissable="true"
        @dismiss="$send($e.DISMISS_NOTIFICATION)"
      />
    </div>
    <div class="tui-mod_approval-header__titleRow">
      <div>
        <div>
          <h2 class="tui-mod_approval-header__title">
            {{ applicationTitle }}
            <Lozenge
              class="tui-mod_approval-header__status"
              type="neutral"
              :text="overallProgressLabel"
            />
          </h2>
        </div>
        <span
          class="tui-mod_approval-header__dateTime"
          v-html="actionDateTime"
        />
        <div class="tui-mod_approval-header__applicationRow">
          <div class="tui-mod_approval-header__idCol">
            <strong>{{ $str('application_id:', 'mod_approval') }}</strong>
            <div class="tui-mod_approval-header__id">
              {{ applicationIdNumber }}
            </div>
          </div>
          <div class="tui-mod_approval-header__typeCol">
            <strong>{{ $str('application_type:', 'mod_approval') }}</strong>
            <div class="tui-mod_approval-header__type">
              {{ applicationType }}
            </div>
          </div>
        </div>
      </div>

      <div class="tui-mod_approval-header__actions">
        <SaveButtons
          v-if="canEdit && showSaveButtons"
          :machine-id="machineId"
          @submit="$emit('submit')"
        />
        <Dropdown
          position="bottom-right"
          class="tui-mod_approval-header__actions--options"
        >
          <template v-slot:trigger="{ toggle }">
            <ButtonIcon
              :aria-label="$str('more_actions', 'mod_approval')"
              @click="toggle"
            >
              <MoreIcon :size="400" />
            </ButtonIcon>
          </template>
          <DropdownItem v-if="canEdit && showEditDropdown" :href="editUrl">
            {{ $str('edit', 'mod_approval') }}
          </DropdownItem>
          <DropdownItem :href="previewUrl" target="_blank">
            {{ $str('print_preview', 'mod_approval') }}
          </DropdownItem>
          <DropdownItem
            v-if="canWithdraw"
            @click="$send({ type: $e.WITHDRAW_APPLICATION })"
          >
            {{ $str('withdraw', 'mod_approval') }}
          </DropdownItem>
          <DropdownItem
            v-if="canClone"
            @click="$send({ type: $e.CLONE_APPLICATION })"
          >
            {{ $str('clone', 'mod_approval') }}
          </DropdownItem>
          <DropdownItem
            v-if="canBeDeleted"
            @click="$send({ type: $e.CONFIRM_DELETE_APPLICATION })"
          >
            {{ $str('delete', 'core') }}
          </DropdownItem>
        </Dropdown>
      </div>
      <ApplicationDeleteModal
        :open="confirmDelete"
        :title="applicationTitle"
        @confirm="$send($e.DELETE)"
        @cancel="$send($e.CANCEL)"
      />
      <ConfirmationModal
        :open="confirmWithdraw"
        :title="$str('withdraw_warning_title', 'mod_approval')"
        :confirm-button-text="$str('withdraw', 'mod_approval')"
        :loading="withdrawing"
        @confirm="$send($e.WITHDRAW)"
        @cancel="$send($e.CANCEL)"
      >
        <p>
          {{ $str('withdraw_warning_message', 'mod_approval') }}
        </p>
      </ConfirmationModal>
    </div>
    <Loader :loading="loading" :fullpage="true" />
  </div>
</template>
<script>
import { OverallProgressState } from 'mod_approval/constants';
import { getProfileAnchor } from 'mod_approval/item_selectors/user';

// Components
import ApplicationNavigationBar from 'mod_approval/components/application/header/NavigationBar';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import Loader from 'tui/components/loading/Loader';
import Lozenge from 'tui/components/lozenge/Lozenge';
import MoreIcon from 'tui/components/icons/More';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import SaveButtons from 'mod_approval/components/application/SaveButtons';
import ApplicationDeleteModal from 'mod_approval/components/application/ApplicationDeleteModal';

export default {
  components: {
    ApplicationNavigationBar,
    ButtonIcon,
    Dropdown,
    DropdownItem,
    Loader,
    Lozenge,
    MoreIcon,
    ConfirmationModal,
    NotificationBanner,
    SaveButtons,
    ApplicationDeleteModal,
  },

  props: {
    currentUserId: Number,

    machineId: {
      type: String,
      required: true,
    },
    backUrl: {
      type: String,
      required: true,
    },
    showEditDropdown: {
      type: Boolean,
      default: false,
    },
    showSaveButtons: {
      type: Boolean,
      default: false,
    },

    loading: Boolean,
    confirmDelete: Boolean,
    confirmWithdraw: Boolean,
    withdrawing: Boolean,
  },

  data() {
    return {
      OverallProgressState,
    };
  },

  computed: {
    backUrlWithParams() {
      return this.$url(this.backUrl, this.$context.backQueryParams);
    },

    isInDraft() {
      return this.overallProgress === OverallProgressState.DRAFT;
    },
    canBeDeleted() {
      return (
        this.$selectors.getCanBeDeleted(this.$context) &&
        this.inDraftOrWithdrawn
      );
    },
    inDraftOrWithdrawn() {
      return [
        OverallProgressState.DRAFT,
        OverallProgressState.WITHDRAWN,
      ].includes(this.overallProgress);
    },
    canEdit() {
      return this.$selectors.getCanEdit(this.$context);
    },
    canClone() {
      return this.$selectors.getCanClone(this.$context);
    },

    canWithdraw() {
      return this.$selectors.getCanWithdraw(this.$context);
    },

    overallProgress() {
      return this.$selectors.getOverallProgress(this.$context);
    },

    applicationTitle() {
      return this.$selectors.getApplicationTitle(this.$context);
    },

    applicationIdNumber() {
      return this.$selectors.getApplicationIdNumber(this.$context);
    },

    applicationType() {
      return this.$selectors.getWorkflowType(this.$context);
    },

    overallProgressLabel() {
      return this.$selectors.getOverallProgressLabel(this.$context);
    },

    previewUrl() {
      return this.$selectors.getPreviewUrl(this.$context);
    },

    editUrl() {
      return this.$selectors.getEditUrlWithParams(this.$context);
    },

    actionDateTime() {
      if (this.isInDraft) {
        // This has not yet been published, so there are no submitters yet. Just show creator.
        const creatorProfileAnchor = getProfileAnchor(
          this.$selectors.getCreator(this.$context)
        );
        const createdDateTime = this.$selectors.getCreated(this.$context);

        return this.$str('application_created_by', 'mod_approval', {
          profileAnchor: creatorProfileAnchor,
          dateTime: createdDateTime,
        });
      }

      const submitterProfileAnchor = getProfileAnchor(
        this.$selectors.getSubmitter(this.$context)
      );
      const submittedDateTime = this.$selectors.getSubmitted(this.$context);
      const lastPublishedSubmission = this.$selectors.getLastPublishedSubmission(
        this.$context
      );

      if (
        lastPublishedSubmission == null ||
        lastPublishedSubmission.is_first_submission
      ) {
        // This is no longer a hidden draft, but there is no submission record (maybe it was withdrawn), so we
        // just show the initial submission information. It's unlikely that the first submission was at the same
        // time as another submission, especially not by a different person.
        return this.$str('application_submitted_by', 'mod_approval', {
          profileAnchor: submitterProfileAnchor,
          dateTime: submittedDateTime,
        });
      }

      // There has been a submission since the initial submission, so show this extra information.
      const updaterProfileAnchor = getProfileAnchor(
        lastPublishedSubmission.user
      );

      return this.$str('application_last_updated_by', 'mod_approval', {
        submitterProfileAnchor,
        submittedDateTime,
        updaterProfileAnchor,
        updatedDateTime: lastPublishedSubmission.updated,
      });
    },
  },

  xState: {
    machineId() {
      return this.machineId;
    },
  },
};
</script>
<style lang="scss">
.tui-mod_approval-header {
  padding: var(--gap-10) var(--gap-8);
  background-color: var(--color-neutral-3);

  &__notificationRow {
    margin-top: var(--gap-6);
  }

  &__titleRow {
    margin-top: var(--gap-6);

    @media (min-width: $tui-screen-md) {
      display: flex;
      justify-content: space-between;
    }
  }

  &__title {
    display: inline;
  }

  &__status {
    margin-top: var(--gap-1);
    margin-bottom: calc(var(--gap-2) + (var(--gap-1) / 2));
    margin-left: var(--gap-2);
    padding-top: calc(var(--gap-1) / 2);
    white-space: nowrap;
    vertical-align: middle;
  }

  &__actions {
    display: flex;
    flex-shrink: 0;
    align-self: flex-start;
    margin-top: var(--gap-6);

    @media (min-width: $tui-screen-md) {
      margin-top: 0;
    }

    &--options {
      margin-left: var(--gap-4);
    }
  }

  &__applicationRow {
    @include tui-font-body-small();
    margin-top: var(--gap-4);

    @media (min-width: $tui-screen-sm) {
      display: flex;
    }
  }

  &__id,
  &__type {
    margin-top: var(--gap-2);
  }

  &__idCol {
    margin-right: var(--gap-4);
  }

  &__typeCol {
    margin-top: var(--gap-3);

    @media (min-width: $tui-screen-sm) {
      margin-top: 0;
    }
  }

  &__dateTime {
    @include tui-font-body-x-small();
    color: var(--color-neutral-7);
  }
}
</style>
<lang-strings>
{
  "core": [
    "cancel",
    "delete"
  ],
  "mod_approval": [
    "application_id:",
    "application_type:",
    "application_created_by",
    "application_last_updated_by",
    "application_submitted_by",
    "back_to_applications",
    "clone",
    "delete_warning_title",
    "error:clone_application",
    "error:delete_application",
    "error:withdraw_application",
    "edit",
    "more_actions",
    "print_preview",
    "success:withdraw_application",
    "success:delete_application",
    "withdraw",
    "withdraw_warning_message",
    "withdraw_warning_title"
  ]
}
</lang-strings>
