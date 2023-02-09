<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<!-- All the workspace related actions are here -->
<template>
  <div
    class="tui-workspacePrimaryAction"
    :class="{
      'tui-workspacePrimaryAction--initialised': $apollo.loading,
    }"
  >
    <ConfirmationModal
      :open="modal.leaveConfirm"
      :title="$str('leave_workspace', 'container_workspace')"
      :confirm-button-text="$str('leave', 'container_workspace')"
      :loading="leaving"
      @confirm="leaveWorkspace"
      @cancel="modal.leaveConfirm = false"
    >
      {{ getLeaveMessage }}
    </ConfirmationModal>

    <ConfirmationModal
      :open="modal.deleteConfirm"
      :title="$str('delete_warning_title', 'container_workspace')"
      :confirm-button-text="$str('delete', 'core')"
      :loading="deleting"
      @confirm="handleDelete"
      @cancel="modal.deleteConfirm = false"
    >
      <p>{{ $str('delete_workspace_warning_msg_1', 'container_workspace') }}</p>
      <p>{{ $str('delete_workspace_warning_msg_2', 'container_workspace') }}</p>
    </ConfirmationModal>

    <ModalPresenter :open="modal.edit" @request-close="modal.edit = false">
      <WorkspaceEditModal
        :workspace-id="workspaceId"
        @update-workspace="updateWorkspace"
      />
    </ModalPresenter>

    <ModalPresenter
      :open="modal.transferOwner"
      @request-close="modal.transferOwner = false"
    >
      <WorkspaceTransferOwnerModal
        :workspace-id="workspaceId"
        @transfered-owner="modal.transferOwner = false"
      />
    </ModalPresenter>

    <!--
      We only enable the user adder if the actor is either owner or a site admin.

      Also note there is no existingItems property passed to the IndividualAdder.
      In normal usage, IndividualAdder displays already selected users as greyed
      out and unselectable. However, the workspaces IndividualAdder only shows
      *non members*. In other words, once someone becomes a member, that person
      can never appear in a list of "already selected non members" to be greyed
      out.
    -->
    <IndividualAdder
      v-if="interactor.own || interactor.workspaces_admin"
      :title="$str('add_members_to_space', 'container_workspace')"
      :open="modal.adder"
      :custom-query="nonMemberQuery"
      :custom-query-filters="individualQueryFilters()"
      :custom-query-key="nonMemberQueryKey"
      @added="handleAddMembers"
      @cancel="modal.adder = false"
    />

    <ModalPresenter
      :open="modal.requestJoin"
      @request-close="modal.requestJoin = false"
    >
      <WorkspaceRequestModal
        :title="$str('request_to_join', 'container_workspace')"
        :body-text="$str('request_to_join_text', 'container_workspace')"
        :button-text="$str('submit', 'core')"
        :is-saving="isSaving"
        @submit="requestToJoinWorkspace"
      />
    </ModalPresenter>

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

    <Button
      v-if="$apollo.loading"
      loading
      :text="$str('actions', 'container_workspace')"
    />

    <template v-else>
      <!-- Owner section -->
      <Dropdown
        v-if="interactor.own || interactor.workspaces_admin"
        position="bottom-right"
        class="tui-workspacePrimaryAction__dropdown"
      >
        <template v-slot:trigger="{ toggle, isOpen }">
          <Button
            :text="
              interactor.own
                ? $str('owner', 'container_workspace')
                : $str('admin', 'core')
            "
            :aria-label="
              $str(
                'actions_label',
                'container_workspace',
                interactor.own
                  ? $str('owner', 'container_workspace')
                  : $str('admin', 'core')
              )
            "
            :aria-expanded="isOpen"
            :caret="true"
            class="tui-workspacePrimaryAction__dropdown-button"
            @click.prevent="toggle"
          />
        </template>

        <DropdownItem v-if="interactor.can_join" @click="joinWorkspace">
          {{ $str('join_workspace', 'container_workspace') }}
        </DropdownItem>

        <DropdownItem
          v-if="interactor.can_leave"
          @click="handleLeaveWorkspaceClick"
        >
          {{ $str('leave_workspace', 'container_workspace') }}
        </DropdownItem>

        <DropdownItem
          v-if="interactor.can_add_members"
          @click="modal.adder = true"
        >
          {{ $str('add_members', 'container_workspace') }}
        </DropdownItem>

        <DropdownItem
          v-if="interactor.can_add_audiences"
          @click="$emit('add-audience')"
        >
          {{ $str('add_audiences', 'container_workspace') }}
        </DropdownItem>

        <DropdownItem v-if="interactor.can_update" @click="modal.edit = true">
          {{ $str('edit_space', 'container_workspace') }}
        </DropdownItem>

        <DropdownItem
          v-if="interactor.joined"
          @click="updateMuteStatus(!interactor.muted)"
        >
          <!-- Drop down item to toggle the mute status -->
          <template v-if="!interactor.muted">
            {{ $str('mute_notifications', 'container_workspace') }}
          </template>
          <template v-else>
            {{ $str('unmute_notifications', 'container_workspace') }}
          </template>
        </DropdownItem>

        <DropdownItem
          v-if="interactor.can_transfer_ownership"
          @click="modal.transferOwner = true"
        >
          {{ $str('transfer_ownership', 'container_workspace') }}
        </DropdownItem>

        <DropdownItem
          v-if="interactor.can_delete"
          @click="modal.deleteConfirm = true"
        >
          {{ $str('delete_workspace', 'container_workspace') }}
        </DropdownItem>
      </Dropdown>
      <!-- End of owner section -->

      <!-- Normal user section -->
      <template v-else>
        <!-- A normal user interactor - non owner nor admin -->
        <LoadingButton
          v-if="!interactor.joined && interactor.can_join"
          :loading="innerSubmitting"
          :text="$str('join_workspace', 'container_workspace')"
          :aria-disabled="innerSubmitting"
          :aria-label="$str('join_space', 'container_workspace', workspaceName)"
          class="tui-workspacePrimaryAction__button"
          @click="joinWorkspace"
        />

        <!--
          Button to cancel the created request to join workspace. This has to be put before request to join button
          as you are still able to request to join the workspace even though you had already requested
         -->
        <LoadingButton
          v-else-if="!interactor.joined && interactor.has_requested_to_join"
          :loading="innerSubmitting"
          :text="$str('cancel_request', 'container_workspace')"
          :aria-disabled="innerSubmitting"
          :aria-label="$str('cancel_request', 'container_workspace')"
          class="tui-workspacePrimaryAction__button"
          @click="cancelRequestToJoinWorkspace"
        />

        <!-- Button to request to join workspace -->
        <LoadingButton
          v-else-if="!interactor.joined && interactor.can_request_to_join"
          :loading="innerSubmitting"
          :text="$str('request_to_join', 'container_workspace')"
          :aria-disabled="false"
          :aria-label="$str('request_to_join', 'container_workspace')"
          class="tui-workspacePrimaryAction__button"
          @click="modal.requestJoin = true"
        />

        <Dropdown
          v-else
          position="bottom-right"
          class="tui-workspacePrimaryAction__dropdown"
        >
          <template v-slot:trigger="{ toggle, isOpen }">
            <Button
              v-if="interactor.joined"
              :text="$str('joined', 'container_workspace')"
              :aria-label="
                $str(
                  'actions_label',
                  'container_workspace',
                  $str('member', 'container_workspace')
                )
              "
              :caret="true"
              :aria-expanded="isOpen"
              class="tui-workspacePrimaryAction__dropdown-button"
              @click.prevent="toggle"
            />
          </template>

          <DropdownItem
            v-if="interactor.can_leave"
            @click="handleLeaveWorkspaceClick"
          >
            {{ $str('leave_workspace', 'container_workspace') }}
          </DropdownItem>
          <DropdownItem
            v-if="interactor.joined"
            @click="updateMuteStatus(!interactor.muted)"
          >
            <!-- Drop down item to toggle the mute status -->
            <template v-if="!interactor.muted">
              {{ $str('mute_notifications', 'container_workspace') }}
            </template>
            <template v-else>
              {{ $str('unmute_notifications', 'container_workspace') }}
            </template>
          </DropdownItem>
        </Dropdown>
      </template>
      <!-- End of normal user section -->
    </template>
  </div>
</template>

<script>
import IndividualAdder from 'tui/components/adder/IndividualAdder';
import Button from 'tui/components/buttons/Button';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import InformationModal from 'tui/components/modal/InformationModal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import LoadingButton from 'totara_engage/components/buttons/LoadingButton';
import WorkspaceEditModal from 'container_workspace/components/modal/WorkspaceEditModal';
import WorkspaceRequestModal from 'container_workspace/components/modal/WorkspaceRequestModal';
import WorkspaceTransferOwnerModal from 'container_workspace/components/modal/WorkspaceTransferOwnerModal';

// GraphQL queries
import getWorkspaceInteractor from 'container_workspace/graphql/workspace_interactor';
import joinWorkspace from 'container_workspace/graphql/join_workspace';
import leaveWorkspace from 'container_workspace/graphql/leave_workspace';
import deleteWorkspace from 'container_workspace/graphql/delete_workspace';
import requestToJoinWorkspace from 'container_workspace/graphql/request_to_join';
import cancelMemberRequest from 'container_workspace/graphql/cancel_member_request';
import addMembers from 'container_workspace/graphql/add_members';
import muteWorkspace from 'container_workspace/graphql/mute_workspace';
import unmuteWorkspace from 'container_workspace/graphql/unmute_workspace';
import nonMembers from 'container_workspace/graphql/non_members';
import { PUBLIC, PRIVATE, HIDDEN } from 'container_workspace/access';

export default {
  components: {
    IndividualAdder,
    Button,
    Dropdown,
    DropdownItem,
    ConfirmationModal,
    InformationModal,
    ModalPresenter,
    LoadingButton,
    WorkspaceEditModal,
    WorkspaceRequestModal,
    WorkspaceTransferOwnerModal,
  },

  props: {
    workspaceId: {
      type: [String, Number],
      required: true,
    },

    workspaceName: {
      type: String,
      required: true,
    },

    workspaceAccess: {
      type: String,
      required: true,
      validator(prop) {
        return [PUBLIC, HIDDEN, PRIVATE].includes(prop);
      },
    },

    workspaceContextId: {
      type: Number,
    },

    workspaceInteractor: {
      type: Object,
      required: true,
    },
  },

  apollo: {
    /** @deprecated Since Totara 16.0 */
    fetchedInteractor: {
      query: getWorkspaceInteractor,
      context: { batch: true },
      variables() {
        return {
          workspace_id: this.workspaceId,
        };
      },
      // normally, workspaceInteractor should be passed. this is just a fallback for backwards compatibility
      skip() {
        return !!this.workspaceInteractor;
      },
      update: result => result.interactor,
    },
  },

  data() {
    return {
      innerSubmitting: false,
      deleting: false,
      leaving: false,
      modal: {
        audienceAdder: false,
        confirmAudienceAdderSelection: false,
        leaveConfirm: false,
        deleteConfirm: false,
        edit: false,
        adder: false,
        transferOwner: false,
        requestJoin: false,
      },
      audiencesToAdd: [],
      usersFromAudiencesToAdd: null,
      isAddingAudiences: false,
      isRequestingAudiencesToAdd: false,
      isSaving: false,
      nonMemberQuery: nonMembers,
      nonMemberQueryKey: 'container_workspace_non_members',
      showingAudienceAdder: false,
      addingAudiences: false,
      preparingAdder: false,
      currentAudienceIds: [],
      showLeaveFailGroup: false,
    };
  },

  computed: {
    interactor() {
      return this.workspaceInteractor || this.fetchedInteractor || {};
    },

    getLeaveMessage() {
      return this.workspaceAccess === PUBLIC
        ? this.$str('leave_workspace_message', 'container_workspace')
        : this.$str(
            'leave_workspace_message_not_public',
            'container_workspace'
          );
    },
  },

  methods: {
    individualQueryFilters() {
      let filters = {
        workspace_id: this.workspaceId,
      };

      return filters;
    },

    async joinWorkspace() {
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;
      try {
        await this.$apollo.mutate({
          mutation: joinWorkspace,
          refetchAll: true,
          variables: {
            workspace_id: this.workspaceId,
          },
        });

        this.$emit('join-workspace');
      } finally {
        this.innerSubmitting = false;
      }
    },

    async leaveWorkspace() {
      this.leaving = true;
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;

      try {
        const {
          data: { member },
        } = await this.$apollo.mutate({
          mutation: leaveWorkspace,
          refetchAll: true,
          variables: {
            workspace_id: this.workspaceId,
          },
        });

        this.modal.leaveConfirm = false;
        this.$emit('leave-workspace');
        if (member) {
          if (this.workspaceAccess !== PUBLIC) {
            document.location.href = this.$url(
              '/container/type/workspace/workspace.php',
              { id: this.workspaceId }
            );
          }
        }
      } finally {
        this.innerSubmitting = false;
      }
    },

    async handleDelete() {
      this.deleting = true;
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;

      try {
        const {
          data: { result },
        } = await this.$apollo.mutate({
          mutation: deleteWorkspace,
          refetchAll: false,
          variables: {
            workspace_id: this.workspaceId,
          },
        });

        if (result) {
          this.modal.confirm = false;
          this.$emit('deleted-workspace');
        }
      } finally {
        this.innerSubmitting = false;
      }
    },

    /**
     * Chaining the event up to parent. And in the same time hide the modal.
     *
     * @param {Object} workspace
     */
    updateWorkspace(workspace) {
      this.modal.edit = false;
      this.$emit('update-workspace', workspace);
    },

    /**
     *
     * @param {Object} formValue
     */
    async requestToJoinWorkspace(formValue) {
      if (!this.modal.requestJoin) {
        return;
      }

      this.isSaving = true;
      try {
        await this.$apollo.mutate({
          mutation: requestToJoinWorkspace,
          variables: {
            workspace_id: this.workspaceId,
            request_content: formValue.messageContent,
          },
          refetchQueries: [
            'container_workspace_get_workspace',
            'container_workspace_workspace_interactor',
          ],
        });

        this.$emit('request-to-join-workspace');
      } finally {
        this.modal.requestJoin = false;
        this.isSaving = false;
      }
    },

    async cancelRequestToJoinWorkspace() {
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;

      try {
        await this.$apollo.mutate({
          mutation: cancelMemberRequest,
          variables: {
            workspace_id: this.workspaceId,
          },
          refetchQueries: [
            'container_workspace_get_workspace',
            'container_workspace_workspace_interactor',
          ],
        });

        this.$emit('cancel-request-to-join-workspace');
      } finally {
        this.innerSubmitting = false;
      }
    },

    /**
     *
     * @param {Number[]} userIds
     */
    async handleAddMembers(userIds) {
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;
      try {
        await this.$apollo.mutate({
          mutation: addMembers,
          variables: {
            workspace_id: this.workspaceId,
            user_ids: userIds.ids,
          },
          refetchQueries: [
            'container_workspace_get_workspace',
            'container_workspace_find_members',
          ],
        });

        this.modal.adder = false;
        this.$emit('added-member');
      } finally {
        this.innerSubmitting = false;
      }
    },

    /**
     *
     * @param {Boolean} status
     * @return {Promise<void>}
     */
    async updateMuteStatus(status) {
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;

      try {
        const variables = {
          workspace_id: this.workspaceId,
        };

        let mutation = status ? muteWorkspace : unmuteWorkspace;
        await this.$apollo.mutate({
          mutation,
          variables,
          update: proxy => {
            proxy.writeQuery({
              query: getWorkspaceInteractor,
              variables: variables,
              data: {
                interactor: Object.assign({}, this.interactor, {
                  muted: status,
                }),
              },
              refetchQueries: [
                'container_workspace_get_workspace',
                'container_workspace_workspace_interactor',
              ],
            });
          },
        });

        this.$emit('update-mute-status', status);
      } finally {
        this.innerSubmitting = false;
      }
    },

    handleLeaveWorkspaceClick() {
      if (this.interactor.cannot_leave_reason === 'AUDIENCE_MEMBERSHIP') {
        this.showLeaveFailGroup = true;
      } else {
        this.modal.leaveConfirm = true;
      }
    },
  },
};
</script>

<lang-strings>
{
  "container_workspace": [
    "add_audiences",
    "add_members",
    "add_members_to_space",
    "actions",
    "actions_label",
    "cancel_request",
    "delete_workspace_warning_msg_1",
    "delete_workspace_warning_msg_2",
    "delete_warning_title",
    "delete_workspace",
    "edit_space",
    "joined",
    "join_workspace",
    "join_space",
    "leave",
    "leave_prevented_due_to_group_membership_message",
    "leave_workspace",
    "leave_workspace_message",
    "leave_workspace_message_not_public",
    "member",
    "mute_notifications",
    "owner",
    "request_to_join",
    "request_to_join_text",
    "transfer_ownership",
    "unmute_notifications"
  ],
  "core": [
    "admin",
    "delete",
    "submit"
  ]
}
</lang-strings>

<style lang="scss">
.tui-workspacePrimaryAction {
  display: flex;
  width: 100%;

  @media (min-width: $tui-screen-xs) {
    justify-content: flex-end;
  }

  &--initialise {
    justify-content: center;
    padding: var(--gap-2);
  }

  &__dropdown {
    width: 100%;
    margin-top: var(--gap-2);

    @media (min-width: $tui-screen-xs) {
      width: auto; // IE support.
      margin-top: 0;
    }

    &-button {
      width: 100%;

      @media (min-width: $tui-screen-xs) {
        // IE support - :(
        width: auto;
      }
    }
  }

  &__button {
    width: 100%;

    @media (min-width: $tui-screen-xs) {
      // IE Support - :(
      width: auto;
    }
  }
}
</style>
