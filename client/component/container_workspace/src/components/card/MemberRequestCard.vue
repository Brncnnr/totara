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
<template>
  <div class="tui-workspaceMemberRequestCard">
    <MiniProfileCard
      :display="userCardDisplay"
      :no-border="true"
      :no-padding="true"
      class="tui-workspaceMemberRequestCard__info"
    />

    <Separator />

    <div class="tui-workspaceMemberRequestCard__body">
      <div>
        <p
          v-if="requestContent"
          class="tui-workspaceMemberRequestCard__body-content"
          v-html="requestContent"
        />
      </div>
      <div
        v-if="!isApproved && !isDeclined"
        class="tui-workspaceMemberRequestCard__actions"
      >
        <ButtonGroup>
          <LoadingButton
            :loading="submitting.accept"
            :disabled="disabled"
            :aria-label="
              $str('approve_member', 'container_workspace', userFullname)
            "
            :text="$str('approve', 'container_workspace')"
            :primary="true"
            :small="true"
            @click="acceptMemberRequest"
          />

          <LoadingButton
            :loading="submitting.decline"
            :disabled="disabled"
            :aria-label="
              $str('decline_member', 'container_workspace', userFullname)
            "
            :text="$str('decline', 'container_workspace')"
            :small="true"
            @click="modalOpen = true"
          />
        </ButtonGroup>
      </div>

      <div v-else>
        <p class="tui-workspaceMemberRequestCard__decision">
          {{ decisionLabelText }}
        </p>
      </div>
    </div>

    <ModalPresenter :open="modalOpen" @request-close="modalOpen = false">
      <WorkspaceRequestModal
        :title="$str('decline_request', 'container_workspace')"
        :body-text="$str('decline_request_text', 'container_workspace')"
        :button-text="$str('continue', 'core')"
        :is-saving="isSaving"
        @submit="declineMemberRequest"
      />
    </ModalPresenter>
  </div>
</template>

<script>
import LoadingButton from 'totara_engage/components/buttons/LoadingButton';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Separator from 'tui/components/decor/Separator';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import WorkspaceRequestModal from 'container_workspace/components/modal/WorkspaceRequestModal';
import { notify } from 'tui/notifications';

// GraphQL queries
import acceptMemberRequest from 'container_workspace/graphql/accept_member_request';
import declineMemberRequest from 'container_workspace/graphql/decline_member_request';

export default {
  components: {
    LoadingButton,
    ButtonGroup,
    MiniProfileCard,
    Separator,
    ModalPresenter,
    WorkspaceRequestModal,
  },

  props: {
    memberRequestId: {
      type: [Number, String],
      required: true,
    },

    userId: {
      type: [Number, String],
      required: true,
    },

    userFullname: {
      type: String,
      required: true,
    },

    isApproved: Boolean,
    isDeclined: Boolean,
    requestContent: String,

    userCardDisplay: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      submitting: {
        accept: false,
        decline: false,
      },
      modalOpen: false,
      isSaving: false,
    };
  },

  computed: {
    /**
     * @return {Boolean}
     */
    disabled() {
      return this.submitting.accept || this.submitting.decline;
    },

    profileUrl() {
      return this.$url('/user/profile.php', { id: this.userId });
    },

    decisionLabelText() {
      if (this.isDeclined) {
        return this.$str('declined', 'container_workspace');
      }

      return this.$str('approved', 'container_workspace');
    },
  },

  methods: {
    async acceptMemberRequest() {
      if (this.disabled) {
        return;
      }

      this.submitting.accept = true;
      try {
        const {
          data: { member_request },
        } = await this.$apollo.mutate({
          mutation: acceptMemberRequest,
          variables: {
            id: this.memberRequestId,
          },
        });

        if (member_request) {
          await notify({
            message: this.$str(
              'accept_member_request',
              'container_workspace',
              this.userFullname
            ),
            type: 'success',
          });

          this.$emit('accept-request', this.memberRequestId);
        }
      } catch (e) {
        await notify({
          message: this.$str(
            'error:accept_member_request',
            'container_workspace',
            this.userFullname
          ),
          type: 'error',
        });
      } finally {
        this.submitting.accept = false;
      }
    },

    /**
     *
     * @param {Object} formValue
     */
    async declineMemberRequest(formValue) {
      if (this.disabled) {
        return;
      }

      this.isSaving = true;
      try {
        const {
          data: { member_request },
        } = await this.$apollo.mutate({
          mutation: declineMemberRequest,
          variables: {
            id: this.memberRequestId,
            decline_content: formValue.messageContent,
          },
        });

        if (member_request) {
          await notify({
            message: this.$str('decline_member_request', 'container_workspace'),
            type: 'success',
          });
          this.$emit('decline-request', this.memberRequestId);
        }
      } catch (e) {
        await notify({
          message: this.$str(
            'error:decline_member_request',
            'container_workspace',
            this.userFullname
          ),
          type: 'error',
        });
      } finally {
        this.isSaving = false;
        this.modalOpen = false;
      }
    },
  },
};
</script>

<lang-strings>
{
  "container_workspace": [
    "approve",
    "approve_member",
    "accept_member_request",
    "decline",
    "decline_request",
    "decline_member",
    "decline_member_request",
    "decline_request_text",
    "error:accept_member_request",
    "error:decline_member_request",
    "approved",
    "declined"
  ],
  "core": [
    "continue"
  ]
}
</lang-strings>

<style lang="scss">
.tui-workspaceMemberRequestCard {
  display: flex;
  flex-direction: column;
  padding: var(--gap-4);

  &__body {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    @media (max-width: $tui-screen-sm) {
      display: block;
    }

    &-content {
      @include tui-wordbreak--hard();
      margin-right: var(--gap-4);
      @media screen and (max-width: $tui-screen-sm) {
        margin-right: 0;
        margin-bottom: var(--gap-4);
      }
    }
  }

  &__decision {
    white-space: nowrap;
  }
}
</style>
