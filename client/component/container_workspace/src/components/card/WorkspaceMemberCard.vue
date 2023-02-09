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
  <article
    class="tui-workspaceMemberCard"
    :aria-describedby="owner ? $id('lozenge') : false"
  >
    <MiniProfileCard
      :display="userCardDisplay"
      :label-id="labelId"
      :drop-down-button-aria-label="
        $str('more_action_for_member', 'container_workspace', userFullName)
      "
      :aria-describedby="owner ? $id('lozenge') : null"
      class="tui-workspaceMemberCard__profileCard"
    >
      <template v-slot:tag>
        <div class="tui-workspaceMemberCard__tagRow">
          <Lozenge
            v-if="owner"
            :id="$id('lozenge')"
            :text="$str('owner', 'container_workspace')"
            type="neutral"
            class="tui-workspaceMemberCard__profileCard-tag"
          />

          <Popover
            v-if="audiences && audiences.length > 0"
            position="right"
            :triggers="['click']"
            trigger-valign="center"
          >
            <template v-slot:trigger>
              <ButtonIcon
                :aria-label="
                  $str(
                    'audiences_for_member_x',
                    'container_workspace',
                    userFullName
                  )
                "
                :styleclass="{ transparent: true }"
                :title="false"
              >
                <UsersIcon />
              </ButtonIcon>
            </template>

            <template v-slot:default>
              <p>
                {{
                  $str(
                    'member_added_through_audiences_label',
                    'container_workspace'
                  )
                }}
              </p>
              <ul>
                <li v-for="audience in audiences" :key="audience.id">
                  {{ audience.name }}
                </li>
              </ul>
            </template>
          </Popover>
        </div>
      </template>

      <template v-if="deleteAble" v-slot:drop-down-items>
        <DropdownItem @click="modal.confirm = true">
          {{ $str('remove', 'core') }}
        </DropdownItem>
      </template>
    </MiniProfileCard>

    <ConfirmationModal
      :open="modal.confirm"
      :title="$str('remove_member_title_warning_msg', 'container_workspace')"
      :confirm-button-text="$str('remove', 'core')"
      :loading="removing"
      @confirm="removeMember"
      @cancel="modal.confirm = false"
    >
      <p>
        {{
          $str('remove_member_warning_msg', 'container_workspace', userFullName)
        }}
      </p>
    </ConfirmationModal>
  </article>
</template>

<script>
import { config } from 'tui/config';
import { notify } from 'tui/notifications';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import UsersIcon from 'tui/components/icons/Users';
import Lozenge from 'tui/components/lozenge/Lozenge';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Popover from 'tui/components/popover/Popover';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';

// GraphQL
import removeMemberFromWorkspace from 'container_workspace/graphql/remove_member_from_workspace';
import getWorkspace from 'container_workspace/graphql/get_workspace';

export default {
  components: {
    ButtonIcon,
    DropdownItem,
    UsersIcon,
    Lozenge,
    ConfirmationModal,
    Popover,
    MiniProfileCard,
  },

  props: {
    userFullName: {
      type: String,
      required: true,
    },

    userCardDisplay: {
      type: Object,
      required: true,
    },

    deleteAble: {
      type: Boolean,
      default: false,
    },

    /**
     * The user's id of a member.
     */
    userId: {
      type: [String, Number],
      required: true,
    },

    workspaceId: {
      type: [String, Number],
      required: true,
    },

    labelId: {
      type: String,
      required: true,
    },

    owner: Boolean,

    /** @type {import('vue').PropType<?Array<{ id: any, name: string }>>} */
    audiences: Array,
  },

  data() {
    return {
      removing: false,
      modal: {
        confirm: false,
      },
    };
  },

  methods: {
    async removeMember() {
      if (this.removing) {
        return;
      }

      this.removing = true;

      try {
        await this.$apollo.mutate({
          mutation: removeMemberFromWorkspace,
          refetchAll: false,
          variables: {
            workspace_id: this.workspaceId,
            user_id: this.userId,
          },

          refetchQueries: [
            {
              query: getWorkspace,
              variables: {
                id: this.workspaceId,
                theme: config.theme.name,
              },
            },
          ],
        });

        this.modal.confirm = false;
        this.$emit('remove-member', this.userId);
      } catch (e) {
        await notify({
          message: this.$str('error:remove_user', 'container_workspace'),
          type: 'error',
        });
      } finally {
        this.removing = false;
      }
    },
  },
};
</script>
<lang-strings>
  {
    "core": [
      "remove"
    ],
    "container_workspace": [
      "audiences_for_member_x",
      "error:remove_user",
      "remove_member_title_warning_msg",
      "remove_member_warning_msg",
      "member_added_through_audiences_label",
      "more_action_for_member",
      "owner"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceMemberCard {
  &__profileCard {
    width: 100%;

    &-tag {
      margin-left: var(--gap-1);
    }
  }

  &__tagRow {
    display: flex;
    align-items: center;
  }
}
</style>
