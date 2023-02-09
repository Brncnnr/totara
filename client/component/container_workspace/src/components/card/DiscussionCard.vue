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
  <div class="tui-workspaceDiscussionCard" :data-id="discussionId">
    <ConfirmationModal
      :open="modal.delete"
      :title="$str('delete_discussion_title', 'container_workspace')"
      :loading="submitting"
      :confirm-button-text="$str('delete', 'core')"
      @confirm="deleteDiscussion"
      @cancel="modal.delete = false"
    >
      <p>
        {{ $str('delete_discussion_warning_msg_1', 'container_workspace') }}
      </p>
      <p>
        {{ $str('delete_discussion_warning_msg_2', 'container_workspace') }}
      </p>
    </ConfirmationModal>

    <div v-if="pinned" class="tui-workspaceDiscussionCard__pinBox">
      <Lozenge :text="$str('pinned_post', 'container_workspace')" type="info" />
    </div>

    <div class="tui-workspaceDiscussionCard__card">
      <DiscussionAvatar
        :image-src="creatorImageSrc"
        :image-alt="creatorImageAlt"
        :profile-url="profileUrl"
        class="tui-workspaceDiscussionCard__avatar"
      />

      <div class="tui-workspaceDiscussionCard__content">
        <div class="tui-workspaceDiscussionCard__head">
          <a :id="labelId" :href="profileUrl">
            {{ creatorFullname }}
          </a>
          <p>{{ timeDescription }}</p>
          <p v-if="edited">
            {{ $str('edited', 'container_workspace') }}
          </p>
        </div>

        <div v-if="removed" class="tui-workspaceDiscussionCard__body--deleted">
          <p>{{ $str('removed_discussion', 'container_workspace') }}</p>
        </div>
        <div
          v-else-if="!edit"
          ref="discussion-content"
          class="tui-workspaceDiscussionCard__body"
          v-html="discussionContent"
        />
        <EditPostDiscussionForm
          v-else
          :discussion-id="discussionId"
          :submitting="submitting"
          :has-error="hasError"
          @cancel="edit = false"
          @submit="updateDiscussionContent"
        />

        <div class="tui-workspaceDiscussionCard__buttons">
          <SimpleLike
            :button-aria-label="likeButtonAriaLabel"
            :total-likes="totalReactions"
            :liked="reacted"
            :disabled="!reactAble"
            component="container_workspace"
            area="discussion"
            :show-text="true"
            :instance-id="discussionId"
            class="tui-workspaceDiscussionCard__buttons-like"
            @update-like-status="updateReactStatus"
            @created-like="updateReactStatus(true)"
            @removed-like="updateReactStatus(false)"
          />

          <div class="tui-workspaceDiscussionCard__buttons-comment">
            <ButtonIcon
              :aria-label="$str('comment_on_discussion', 'container_workspace')"
              :text="$str('comment', 'container_workspace')"
              :styleclass="{
                small: true,
                transparent: true,
                transparentNoPadding: true,
              }"
              :disabled="!commentAble"
              @click="$emit('trigger-comment')"
            >
              <CommentIcon />
            </ButtonIcon>

            <span v-if="0 < totalComments">
              {{ $str('bracket_number', 'container_workspace', totalComments) }}
            </span>
          </div>
        </div>
      </div>

      <Dropdown position="bottom-right">
        <template v-slot:trigger="{ toggle, isOpen }">
          <ButtonIcon
            :aria-expanded="isOpen"
            :aria-label="$str('discussion_actions', 'container_workspace')"
            :styleclass="{
              small: true,
              transparentNoPadding: true,
            }"
            @click.prevent="toggle"
          >
            <MoreIcon size="300" />
          </ButtonIcon>
        </template>

        <DropdownItem v-if="updateAble" @click="edit = true">
          {{ $str('edit', 'core') }}
        </DropdownItem>

        <DropdownItem v-if="deleteAble" @click="modal.delete = true">
          {{ $str('delete', 'core') }}
        </DropdownItem>

        <DropdownItem v-if="reportAble" @click="reportDiscussion">
          {{ $str('report_discussion', 'container_workspace') }}
        </DropdownItem>

        <DropdownItem v-if="showDiscussionLink" :href="discussionUrl">
          {{ $str('view_full_discussion', 'container_workspace') }}
        </DropdownItem>

        <DropdownItem @click="handleCopyLink">
          {{ $str('copy_discussion_link_text', 'container_workspace') }}
        </DropdownItem>
      </Dropdown>
    </div>
  </div>
</template>

<script>
import tui from 'tui/tui';
import DiscussionAvatar from 'container_workspace/components/profile/DiscussionAvatar';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import MoreIcon from 'tui/components/icons/More';
import SimpleLike from 'totara_reaction/components/SimpleLike';
import EditPostDiscussionForm from 'container_workspace/components/form/EditPostDiscussionForm';
import CommentIcon from 'tui/components/icons/Comment';
import { notify } from 'tui/notifications';
import Lozenge from 'tui/components/lozenge/Lozenge';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';

// GraphQL queries
import updateDiscussionContent from 'container_workspace/graphql/update_discussion_content';
import deleteDiscussion from 'container_workspace/graphql/delete_discussion';
import createReview from 'totara_reportedcontent/graphql/create_review';

export default {
  components: {
    DiscussionAvatar,
    SimpleLike,
    Dropdown,
    DropdownItem,
    ButtonIcon,
    MoreIcon,
    EditPostDiscussionForm,
    CommentIcon,
    Lozenge,
    ConfirmationModal,
  },

  props: {
    creatorImageSrc: {
      type: String,
      required: true,
    },

    creatorImageAlt: {
      type: String,
      default: '',
    },

    /**
     * The discussion's creator' id - in short user's id of this discussion's creator.
     */
    creatorId: {
      type: [String, Number],
      required: true,
    },

    creatorFullname: {
      type: String,
      required: true,
    },

    discussionContent: {
      type: String,
      required: true,
    },

    timeDescription: {
      type: String,
      required: true,
    },

    pinned: Boolean,
    reacted: Boolean,

    totalComments: {
      type: [String, Number],
      required: true,
    },

    totalReactions: {
      type: [String, Number],
      required: true,
    },

    discussionId: {
      type: [String, Number],
      required: true,
    },

    reactAble: {
      type: Boolean,
      default: true,
    },

    updateAble: {
      type: Boolean,
      default: true,
    },

    deleteAble: {
      type: Boolean,
      default: true,
    },

    reportAble: {
      type: Boolean,
      default: false,
    },

    commentAble: {
      type: Boolean,
      default: true,
    },

    removed: Boolean,

    showDiscussionLink: {
      type: Boolean,
      default: true,
    },

    edited: Boolean,

    labelId: String,
  },

  data() {
    return {
      edit: false,
      submitting: false,
      modal: {
        delete: false,
      },
      hasError: false,
    };
  },

  computed: {
    profileUrl() {
      return this.$url('/user/profile.php', { id: this.creatorId });
    },
    /**
     * Returning aria label text for like button
     * @return {String}
     */
    likeButtonAriaLabel() {
      if (this.reacted) {
        return this.$str('remove_like_discussion', 'container_workspace');
      }

      return this.$str('like_discussion', 'container_workspace');
    },

    /**
     * Returning the url to single discussion page.
     * @return {String}
     */
    discussionUrl() {
      return this.$url('/container/type/workspace/discussion.php', {
        id: this.discussionId,
      });
    },
  },

  mounted() {
    this.$_scan();
  },

  updated() {
    this.$_scan();
  },

  methods: {
    $_scan() {
      if (!this.$refs['discussion-content']) {
        return;
      }

      let element = this.$refs['discussion-content'];
      tui.scan(element);
    },

    async handleCopyLink() {
      let textArea = document.createElement('textarea');
      textArea.classList.add('tui-sr-only');
      textArea.value = this.discussionUrl;

      document.body.appendChild(textArea);
      textArea.select();

      try {
        document.execCommand('copy');

        await notify({
          message: this.$str('copied_to_clipboard', 'container_workspace'),
          type: 'success',
        });
      } catch (e) {
        await notify({
          message: this.$str('error:copy_to_clipboard', 'container_workspace'),
          type: 'error',
        });
      } finally {
        document.body.removeChild(textArea);
      }
    },

    /**
     *
     * @param {Boolean} status
     */
    updateReactStatus(status) {
      this.$emit('update-react-status', {
        discussionId: this.discussionId,
        status: status,
      });
    },

    /**
     *
     * @param {String} content
     * @param {Number|String} contentFormat
     * @param {Number} itemId
     * @returns {Promise<void>}
     */
    async updateDiscussionContent({ content, contentFormat, itemId }) {
      if (this.submitting) {
        return;
      }

      this.submitting = true;

      try {
        let {
          data: { discussion },
        } = await this.$apollo.mutate({
          mutation: updateDiscussionContent,
          refetchAll: false,
          variables: {
            id: this.discussionId,
            content: content,
            content_format: contentFormat,
            draft_id: itemId,
          },
        });

        this.edit = false;
        this.$emit('update-discussion', discussion);
        this.hasError = false;
      } catch (e) {
        this.hasError = true;
        await notify({
          message: this.$str('error:update_discussion', 'container_workspace'),
          type: 'error',
        });
      } finally {
        this.submitting = false;
      }
    },

    async deleteDiscussion() {
      if (this.submitting) {
        return;
      }

      this.submitting = true;
      try {
        let {
          data: { result },
        } = await this.$apollo.mutate({
          mutation: deleteDiscussion,
          refetchAll: false,
          variables: {
            id: this.discussionId,
          },
        });

        if (result) {
          // Start hiding the modal.
          this.modal.delete = false;
          this.$emit('delete-discussion', this.discussionId);

          return;
        }

        await notify({
          message: this.$str('error:delete_discussion', 'container_workspace'),
          type: 'error',
        });
      } catch (e) {
        await notify({
          message: this.$str('error:delete_discussion', 'container_workspace'),
          type: 'error',
        });
      } finally {
        this.submitting = false;
      }
    },

    /**
     * Report the attached discussion
     * @returns {Promise<void>}
     */
    async reportDiscussion() {
      if (this.submitting) {
        return;
      }
      this.submitting = true;
      try {
        let response = await this.$apollo
          .mutate({
            mutation: createReview,
            refetchAll: false,
            variables: {
              component: 'container_workspace',
              area: 'discussion',
              item_id: this.discussionId,
              url: window.location.href,
            },
          })
          .then(response => response.data.review);

        if (response.success) {
          await notify({
            message: this.$str('reported', 'totara_reportedcontent'),
            type: 'success',
          });
        } else {
          await notify({
            message: this.$str('reported_failed', 'totara_reportedcontent'),
            type: 'error',
          });
        }
      } catch (e) {
        await notify({
          message: this.$str('error:report_discussion', 'container_workspace'),
          type: 'error',
        });
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
      "discussion_actions",
      "copy_discussion_link_text",
      "like_discussion",
      "remove_like_discussion",
      "comment",
      "comment_on_discussion",
      "error:update_discussion",
      "error:copy_to_clipboard",
      "view_full_discussion",
      "bracket_number",
      "edited",
      "copied_to_clipboard",
      "pinned_post",
      "delete_discussion_warning_msg_1",
      "delete_discussion_warning_msg_2",
      "delete_discussion_title",
      "error:delete_discussion",
      "report_discussion",
      "error:report_discussion",
      "removed_discussion"
    ],
    "core": [
      "edit",
      "delete",
      "confirm"
    ],
    "totara_reportedcontent": [
      "reported",
      "reported_failed"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceDiscussionCard {
  display: flex;
  flex-direction: column;
  padding-top: var(--gap-4);
  color: var(--color-neutral-7);
  border: var(--border-width-thin) solid var(--color-neutral-5);

  &__pinBox {
    display: flex;
    justify-content: flex-start;
    margin-bottom: var(--gap-2);
  }

  &:hover,
  &:focus {
    text-decoration: none;
  }

  &__card {
    display: flex;
    padding: var(--gap-4);
    padding-top: 0;
  }

  &__avatar {
    margin-right: var(--gap-2);
  }

  &__content {
    flex-grow: 1;
    width: 100%;
  }

  &__head {
    display: flex;
    flex: 1;
    align-items: baseline;
    margin-bottom: var(--gap-4);
    padding-top: 2px;

    a {
      @include tui-font-body();
      color: var(--color-state);
      font-weight: bold;
    }

    p {
      @include tui-font-body-x-small();
      margin: 0;
      margin-left: var(--gap-2);
      color: var(--color-neutral-7);
    }
  }

  &__body {
    &--deleted {
      @include tui-font-body-small();
      font-style: italic;
    }
  }

  &__buttons {
    display: flex;
    margin-top: var(--gap-4);

    &-comment {
      display: flex;
      margin-left: var(--gap-4);
    }
  }
}
</style>
