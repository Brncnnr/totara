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
  <Form
    :vertical="true"
    input-width="full"
    class="tui-workspaceTransferOwnerForm"
  >
    <FormRowStack class="tui-workspaceTransferOwnerForm__content">
      <FormRow
        v-if="displayCurrentOwner"
        :label="$str('current_owner', 'container_workspace')"
        :aria-disabled="true"
      >
        <p>{{ currentOwnerFullname }}</p>
      </FormRow>

      <FormRow
        v-slot="{ id }"
        :label="$str('select_new_owner', 'container_workspace')"
      >
        <TagList
          :id="id"
          :tags="selectedUsers"
          :items="users"
          :filter="searchPattern"
          :input-placeholder="
            $str('select_new_owner_placeholder', 'container_workspace')
          "
          close-on-click
          @filter="filterUsers"
          @select="selectUser"
          @remove="removeUser"
        >
          <template
            v-if="!$apollo.loading"
            v-slot:item="{ item: { card_display } }"
          >
            <MiniProfileCard
              :no-padding="true"
              :no-border="true"
              :display="card_display"
              :read-only="true"
            />
          </template>
        </TagList>
      </FormRow>
    </FormRowStack>

    <ButtonGroup class="tui-workspaceTransferOwnerForm__buttonGroup">
      <LoadingButton
        :text="$str('save', 'container_workspace')"
        :primary="true"
        :loading="submitting"
        :disabled="!selectedUser"
        @click="submitForm"
      />
      <!-- Separator -->
      <Button :text="$str('cancel', 'core')" @click="$emit('cancel', $event)" />
    </ButtonGroup>
  </Form>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import FormRowStack from 'tui/components/form/FormRowStack';
import TagList from 'tui/components/tag/TagList';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import LoadingButton from 'totara_engage/components/buttons/LoadingButton';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';

// GraphQL queries
import searchUsers from 'container_workspace/graphql/search_users';

export default {
  components: {
    Form,
    FormRow,
    FormRowStack,
    TagList,
    ButtonGroup,
    Button,
    LoadingButton,
    MiniProfileCard,
  },

  props: {
    workspaceId: {
      type: [String, Number],
      required: true,
    },
    displayCurrentOwner: Boolean,
    currentOwnerFullname: String,
    submitting: Boolean,
  },

  apollo: {
    availableUsers: {
      query: searchUsers,
      fetchPolicy: 'network-only',
      variables() {
        return {
          pattern: this.searchPattern,
          workspace_id: this.workspaceId,
        };
      },

      update({ users }) {
        return users;
      },
    },
  },

  data() {
    return {
      selectedUser: null,
      searchPattern: '',
      availableUsers: [],
    };
  },

  computed: {
    /**
     * Returning a list of users that filtering out the selected user's id.
     * @return {Array}
     */
    users() {
      if (!this.selectedUser) {
        return this.availableUsers;
      }

      return this.availableUsers.filter(({ id }) => id != this.selectedUser.id);
    },

    /**
     * Returning a list of selected users.
     * If the user are not selected, then empty array will be returned.
     *
     * @return {Array}
     */
    selectedUsers() {
      if (!this.selectedUser) {
        return [];
      }

      return [
        {
          id: this.selectedUser.id,
          text: this.selectedUser.fullname,
        },
      ];
    },
  },

  methods: {
    filterUsers(query) {
      this.searchPattern = query;
    },

    selectUser(item) {
      this.selectedUser = item;
      this.searchPattern = '';
    },

    removeUser() {
      this.selectedUser = null;
    },

    submitForm() {
      if (!this.selectedUser) {
        return;
      }

      this.$emit('submit', { userId: this.selectedUser.id });
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "current_owner",
      "current_owner_suspended",
      "save",
      "select_new_owner",
      "select_new_owner_placeholder",
      "transfer_ownership_help_text_one",
      "transfer_ownership_help_text_two"
    ],
    "core": [
      "cancel",
      "confirm"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceTransferOwnerForm {
  display: flex;
  flex-direction: column;

  &__content {
    flex-grow: 1;
  }

  &__helpText {
    @include tui-font-body-small();
    margin: 0;
    margin-bottom: var(--gap-4);
  }

  &__buttonGroup {
    justify-content: flex-end;
  }
}
</style>
