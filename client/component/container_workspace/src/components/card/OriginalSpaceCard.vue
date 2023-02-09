<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<template>
  <div
    class="tui-originalSpaceCard"
    :style="{
      'background-image': `url('${workspace.image}')`,
    }"
    @click="handleClick"
  >
    <div class="tui-originalSpaceCard__titleBox">
      <h3 class="tui-originalSpaceCard__title">
        <a class="tui-originalSpaceCard__link" :href="workspace.url">
          {{ workspace.name }}
        </a>
      </h3>
    </div>

    <div
      class="tui-originalSpaceCard__actions"
      :class="{
        'tui-originalSpaceCard__actions-gradient':
          !joined && hasRequestedToJoin,
      }"
    >
      <p
        v-if="!joined && hasRequestedToJoin"
        class="tui-originalSpaceCard__pendingText"
      >
        {{ $str('request_to_join_pending', 'container_workspace') }}
      </p>
      <LoadingButton
        v-if="!joined && canJoin"
        ref="actionButton"
        class="tui-originalSpaceCard__actions-button"
        :text="$str('join_me', 'container_workspace')"
        :aria-label="$str('join_space', 'container_workspace', workspace.name)"
        :loading="submitting"
        @click.stop="joinWorkspace"
      />

      <Button
        v-else-if="!joined && hasRequestedToJoin"
        ref="actionButton"
        :text="$str('cancel_request', 'container_workspace')"
        class="tui-originalSpaceCard__actions-button"
        :loading="submitting"
        @click.stop="cancelRequestJoinWorkspace"
      />

      <LoadingButton
        v-else-if="!joined && canRequestToJoin"
        ref="actionButton"
        :text="$str('request_to_join', 'container_workspace')"
        class="tui-originalSpaceCard__actions-button"
        :aria-label="
          $str('request_to_join_space', 'container_workspace', workspace.name)
        "
        :loading="submitting"
        @click.stop="modalOpen = true"
      />

      <!-- If the actor is an owner then this button will be disabled for them -->
      <Dropdown
        v-else-if="joined"
        class="tui-originalSpaceCard__actions-dropDown"
      >
        <template v-slot:trigger="{ isOpen, toggle }">
          <Button
            ref="actionButton"
            :text="$str('joined', 'container_workspace')"
            :caret="!owned"
            :aria-expanded="isOpen"
            :disabled="owned"
            @click.stop="toggle"
          />
        </template>

        <DropdownItem @click.stop.prevent="handleLeaveWorkspaceClick">
          {{ $str('leave_workspace', 'container_workspace') }}
        </DropdownItem>
      </Dropdown>
    </div>

    <ModalPresenter :open="modalOpen" @request-close="modalOpen = false">
      <WorkspaceRequestModal
        :title="$str('request_to_join', 'container_workspace')"
        :body-text="$str('request_to_join_text', 'container_workspace')"
        :button-text="$str('submit', 'core')"
        :is-saving="isSaving"
        @submit="requestToJoinWorkspace"
      />
    </ModalPresenter>

    <ConfirmationModal
      :open="showLeaveConfirm"
      :title="$str('leave_workspace', 'container_workspace')"
      :confirm-button-text="$str('leave', 'container_workspace')"
      :loading="submitting"
      @confirm="leaveWorkspace"
      @cancel="showLeaveConfirm = false"
    >
      {{ leaveConfirmMessage }}
    </ConfirmationModal>

    <InformationModal
      :title="$str('leave_workspace', 'container_workspace')"
      :open="showLeaveFailGroup"
      @close="showLeaveFailGroup = false"
    >
      {{
        $str(
          'leave_prevented_due_to_group_membership_message',
          'container_workspace'
        )
      }}
    </InformationModal>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import Dropdown from 'tui/components/dropdown/Dropdown';
import LoadingButton from 'totara_engage/components/buttons/LoadingButton';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import InformationModal from 'tui/components/modal/InformationModal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import WorkspaceRequestModal from 'container_workspace/components/modal/WorkspaceRequestModal';

// GraphQL queries
import joinWorkspace from 'container_workspace/graphql/join_workspace';
import requestToJoin from 'container_workspace/graphql/request_to_join';
import leaveWorkspace from 'container_workspace/graphql/leave_workspace';
import cancelMemberRequest from 'container_workspace/graphql/cancel_member_request';
import { PUBLIC } from 'container_workspace/access';

export default {
  components: {
    Button,
    LoadingButton,
    DropdownItem,
    Dropdown,
    ConfirmationModal,
    InformationModal,
    ModalPresenter,
    WorkspaceRequestModal,
  },

  props: {
    workspace: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      submitting: false,
      modalOpen: false,
      isSaving: false,
      showLeaveConfirm: false,
      showLeaveFailGroup: false,
    };
  },

  computed: {
    interactor() {
      return this.workspace.interactor;
    },

    joined() {
      return this.interactor.joined;
    },

    /**
     * Whether the actor is able to join the workspace or. For now, every other user is able to join
     * the workspace.
     */
    canJoin() {
      return this.interactor.can_join;
    },

    /**
     * Whether the actor is able to request to join the workspace or not.
     */
    canRequestToJoin() {
      return this.interactor.can_request_to_join;
    },

    /**
     * Whether the actor has already requested to join or not.
     */
    hasRequestedToJoin() {
      return this.interactor.has_requested_to_join;
    },

    /**
     * Whether the actor is an owner of this workspace.
     */
    owned() {
      return this.interactor.own;
    },

    leaveConfirmMessage() {
      return this.workspaceAccess === PUBLIC
        ? this.$str('leave_workspace_message', 'container_workspace')
        : this.$str(
            'leave_workspace_message_not_public',
            'container_workspace'
          );
    },

    /**
     * This should confirm if a modal is open on the page or not
     *
     * @returns {Boolean} whether there is a modal opened from this component
     */
    modalOpened() {
      return this.showLeaveFailGroup || this.showLeaveConfirm || this.modalOpen;
    },
  },

  watch: {
    /**
     * When a modal is closed this ensures the button on the workspace to re-gain focus
     *
     * @param {Boolean} newVal
     */
    modalOpened(newVal) {
      if (!newVal) {
        this.$refs.actionButton.$el.focus();
      }
    },
  },

  methods: {
    handleClick() {
      window.location.href = this.workspace.url;
    },

    async joinWorkspace() {
      this.submitting = true;

      try {
        const {
          data: { member },
        } = await this.$apollo.mutate({
          mutation: joinWorkspace,
          refetchAll: false,
          variables: {
            workspace_id: this.workspace.id,
          },
        });

        this.$emit('join-workspace', member);
      } finally {
        this.submitting = false;
      }
    },

    handleLeaveWorkspaceClick() {
      if (this.interactor.cannot_leave_reason === 'AUDIENCE_MEMBERSHIP') {
        this.showLeaveFailGroup = true;
      } else {
        this.showLeaveConfirm = true;
      }
    },

    async leaveWorkspace() {
      this.submitting = true;
      try {
        const {
          data: { member },
        } = await this.$apollo.mutate({
          mutation: leaveWorkspace,
          variables: {
            workspace_id: this.workspace.id,
          },
        });

        this.$emit('leave-workspace', member);
      } finally {
        this.submitting = false;
        this.showLeaveConfirm = false;
      }
    },

    /**
     *
     * @param {Object} formValue
     */
    async requestToJoinWorkspace(formValue) {
      if (!this.modalOpen) {
        return;
      }

      this.isSaving = true;
      try {
        const {
          data: { member_request },
        } = await this.$apollo.mutate({
          mutation: requestToJoin,
          refetchAll: false,
          variables: {
            workspace_id: this.workspace.id,
            request_content: formValue.messageContent,
          },
        });
        this.$emit('request-to-join-workspace', member_request);
      } finally {
        this.isSaving = false;
        this.modalOpen = false;
      }
    },

    async cancelRequestJoinWorkspace() {
      if (this.modalOpen || this.submitting) {
        return;
      }

      this.submitting = true;

      try {
        const {
          data: { member_request },
        } = await this.$apollo.mutate({
          mutation: cancelMemberRequest,
          variables: {
            workspace_id: this.workspace.id,
          },
        });

        this.$emit('request-to-join-workspace', member_request);
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "cancel_request",
      "join_space",
      "join_me",
      "joined",
      "leave",
      "leave_prevented_due_to_group_membership_message",
      "leave_workspace",
      "leave_workspace_message",
      "leave_workspace_message_not_public",
      "request_to_join",
      "request_to_join_pending",
      "request_to_join_space",
      "request_to_join_text",
      "requested_to_join",
      "leave_workspace"
    ],
    "core": [
      "submit"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-originalSpaceCard {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: var(---engage-card-height);
  background-repeat: no-repeat;
  background-position: center;
  background-size: cover;
  border: var(--border-width-thin) solid var(--color-neutral-5);
  border-radius: var(--border-radius-normal);
  transition: box-shadow var(--transition-form-function)
    var(--transition-form-duration);

  &:hover {
    cursor: pointer;
  }

  &:hover,
  &:focus {
    box-shadow: var(--shadow-2);
  }

  &:focus-within,
  &.tui-focusWithin {
    box-shadow: var(--shadow-2);
  }

  &__titleBox {
    display: flex;
    flex-basis: 50%;
    flex-direction: column;
    width: 100%;
    padding: var(--gap-4) var(--gap-2);
    padding-bottom: 0;
    word-wrap: break-word;
    background-image: linear-gradient(
      to top,
      transparent 0%,
      var(--color-backdrop-heavy) 78%,
      var(--color-backdrop-heavy)
    );

    border-top-left-radius: var(--border-radius-normal);
    border-top-right-radius: var(--border-radius-normal);
  }

  &__title {
    @include tui-font-heading-x-small();
    margin: 0;
  }

  &__link {
    color: var(--color-neutral-1);
    &:hover,
    &:focus {
      color: var(--color-neutral-1);
      text-decoration: none;
      outline: none;
    }
  }

  &__pendingText {
    padding: 0 var(--gap-4);
    color: var(--color-neutral-1);
    text-align: center;
  }

  &__actions {
    display: flex;
    flex-basis: 50%;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    width: 100%;

    &-gradient {
      background-image: linear-gradient(
        to bottom,
        transparent 0%,
        var(--color-backdrop-heavy) 78%,
        var(--color-backdrop-heavy)
      );
      border-bottom-right-radius: var(--border-radius-normal);
      border-bottom-left-radius: var(--border-radius-normal);
    }

    &-button,
    &-dropDown {
      margin-bottom: var(--gap-4);
    }
  }
}
</style>
