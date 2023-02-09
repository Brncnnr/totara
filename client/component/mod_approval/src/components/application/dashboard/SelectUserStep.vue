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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module mod_approval
-->
<template>
  <ModalContent
    class="tui-mod_approval-selectUserStep"
    :title="title"
    :title-id="titleId"
  >
    <FormRow
      :label="label"
      :required="true"
      class="tui-mod_approval-selectUserStep__formRow"
    >
      <div
        class="tui-mod_approval-selectUserStep__tagList"
        :class="{
          'tui-mod_approval-selectUserStep__tagList--error': $matches(
            'selectUser.noPermission'
          ),
        }"
      >
        <TagList
          :items="items"
          :tags="selectedTags"
          :filter="$selectors.getNameSearch($context)"
          :close-on-click="true"
          :virtual-scroll-options="virtualScrollOptions"
          :label-name="label"
          @filter="handleFilter"
          @select="selectedUser => $send({ type: $e.SELECT, selectedUser })"
          @remove="$send({ type: $e.REMOVE, selectedUser: null })"
          @scrollbottom="handleScrollBottom"
        >
          <template v-slot:item="{ item }">
            <div>
              <Loader v-if="item.loader" :loading="true" />
              <MiniProfileCard
                v-else
                :class="{
                  'tui-mod_approval-selectUserStep__profileCard--searching': searching,
                }"
                :no-border="true"
                :no-padding="true"
                :read-only="true"
                :display="item.card_display"
              />
            </div>
          </template>
        </TagList>
        <FieldError
          v-if="$matches('selectUser.noPermission')"
          :error="
            $str('error:cannot_create_application_on_behalf', 'mod_approval')
          "
        />
      </div>
    </FormRow>
    <Loader v-if="$matches('selectUser.checkingPermission')" :loading="true" />
    <template v-slot:footer-content>
      <div class="tui-mod_approval-selectUserStep__buttons">
        <Button
          v-if="workflowTypeOptions.length > 1"
          :text="$str('button_back', 'mod_approval')"
          @click="$send($e.BACK)"
        />
        <ButtonGroup class="tui-mod_approval-selectUserStep__buttonsRight">
          <div>
            <Button
              v-if="jobAssignmentOptions.length === 1"
              :text="$str('button_create', 'mod_approval')"
              :styleclass="{ primary: true }"
              :disabled="!$matches('selectUser.selected')"
              @click="$send($e.CREATE)"
            />
            <Button
              v-else
              :text="$str('button_next', 'mod_approval')"
              :styleclass="{ primary: true }"
              :disabled="!$matches('selectUser.selected')"
              @click="$send($e.NEXT)"
            />
          </div>
          <Button
            :text="$str('button_cancel', 'mod_approval')"
            @click="$send($e.CANCEL)"
          />
        </ButtonGroup>
      </div>
    </template>
  </ModalContent>
</template>

<script>
import ModalContent from 'tui/components/modal/ModalContent';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import FormRow from 'tui/components/form/FormRow';
import FieldError from 'tui/components/form/FieldError';
import Loader from 'tui/components/loading/Loader';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import TagList from 'tui/components/tag/TagList';

import {
  MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL,
  SELECTABLE_APPLICANTS,
} from 'mod_approval/constants';

export default {
  components: {
    ButtonGroup,
    Button,
    ModalContent,
    FormRow,
    FieldError,
    Loader,
    MiniProfileCard,
    TagList,
  },

  props: {
    title: {
      required: true,
      type: String,
    },
    titleId: {
      required: true,
      type: String,
    },
  },

  computed: {
    searching() {
      return ['selectUser.searching', 'selectUser.debouncing'].some(
        this.$matches
      );
    },

    items() {
      let users = this.$selectors.getAvailableUsers(this.$context);
      if (this.searching) {
        users = users.concat([{ loader: true }]);
      }

      return users;
    },

    selectedTags() {
      const selectedUser = this.$selectors.getSelectedUser(this.$context);
      return selectedUser
        ? [{ text: selectedUser.fullname, id: selectedUser.id }]
        : [];
    },

    virtualScrollOptions() {
      return {
        dataKey: 'id',
        ariaLabel: this.label,
        isLoading: this.searching,
      };
    },

    label() {
      return this.$str('select_person', 'mod_approval');
    },

    workflowTypeOptions() {
      return this.$selectors.getWorkflowTypeOptions(this.$context);
    },

    jobAssignmentOptions() {
      return this.$selectors.getJobAssignmentOptions(this.$context);
    },
  },

  methods: {
    handleFilter(search) {
      this.$send({
        type: this.$e.FILTER,
        queryId: SELECTABLE_APPLICANTS,
        variables: {
          input: {
            filters: {
              fullname: search,
            },
            pagination: {},
          },
        },
      });
    },

    handleScrollBottom() {
      this.$send({
        type: this.$e.MORE,
        variables: {
          input: {
            filters: {
              fullname: this.$selectors.getNameSearch(this.$context),
            },
            pagination: {
              cursor: this.$selectors.getNextCursor(this.$context),
            },
          },
        },
      });
    },
  },

  xState: {
    machineId: MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL,
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "button_back",
    "button_create",
    "button_cancel",
    "button_next",
    "create_new_on_behalf",
    "create_new",
    "select_person",
    "error:cannot_create_application_on_behalf"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-selectUserStep {
  min-height: 360px;

  &__formRow {
    margin-top: var(--gap-4);
  }

  &__tagList {
    margin-top: var(--gap-1);

    &--error {
      .tui-tagList,
      .tui-tag {
        border: var(--form-input-border-size) solid
          var(--form-input-border-color-invalid);
      }
    }
  }

  &__buttons {
    display: flex;
    flex: 1;
  }

  &__buttonsRight {
    margin-left: auto;
  }

  // TODO: TL-32178
  // remove this searching state when <TagList /> gets a supported loading state
  &__profileCard {
    &--searching {
      opacity: 0.4;
    }
  }
}
</style>
