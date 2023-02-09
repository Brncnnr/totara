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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->
<template>
  <ModalContent :title="$str('edit_override_approvers', 'mod_approval')">
    <div>
      <Form v-if="$context.overrideAssignment" input-width="full">
        <div
          v-for="({ approval_level }, index) in $selectors.getApprovalLevels(
            $context
          )"
          :key="index"
          class="tui-mod_approval-editStep__row"
        >
          <FormRow :label="approval_level.name">
            <Checkbox
              :checked="$context.formValues.overridden[approval_level.id]"
              :aria-label="
                $str('override_x', 'mod_approval', approval_level.name)
              "
              class="tui-mod_approval-editStep__checkbox"
              @change="
                checked =>
                  handleCheck({
                    approvalLevelId: approval_level.id,
                    approverType:
                      $context.formValues.approverTypes[approval_level.id],
                    checked,
                  })
              "
            >
              {{ $str('override', 'mod_approval') }}
            </Checkbox>
          </FormRow>
          <div v-if="$context.formValues.overridden[approval_level.id]">
            <div class="tui-mod_approval-editStep__approverTypeSelect">
              <Select
                :aria-label="
                  $str(
                    'approver_level_approver_type_label',
                    'mod_approval',
                    approval_level.name
                  )
                "
                :value="$context.formValues.approverTypes[approval_level.id]"
                :options="approverTypeOptions"
                :char-length="20"
                @input="
                  approverType =>
                    handleApproverTypeSelect({
                      approverType,
                      approvalLevelId: approval_level.id,
                    })
                "
              />
            </div>
            <div
              v-if="
                $context.formValues.approverTypes[approval_level.id] ===
                  types.USER
              "
            >
              <TagList
                class="tui-mod_approval-editStep__approverSelect"
                :items="availableUsers"
                :tags="$context.formValues[types.USER][approval_level.id]"
                :filter="search(approval_level.id)"
                :label-name="
                  $str(
                    'individuals_for_approver_level_taglist',
                    'mod_approval',
                    approval_level.name
                  )
                "
                @open="
                  $send({
                    type: $e.ACTIVATE_TAGLIST,
                    approvalLevelId: approval_level.id,
                    search: '',
                  })
                "
                @filter="
                  search =>
                    handleFilter({
                      approvalLevelId: approval_level.id,
                      search,
                    })
                "
                @select="
                  user =>
                    handleSelect({
                      approvalLevelId: approval_level.id,
                      user,
                    })
                "
                @remove="
                  tag =>
                    handleRemove({
                      approvalLevelId: approval_level.id,
                      tag,
                    })
                "
              >
                <template v-slot:item="{ item }">
                  <div>
                    <Loader v-if="item.loader" :loading="true" />
                    <MiniProfileCard
                      v-else
                      :class="{
                        'tui-mod_approval-editStep__profileCard--searching': searching,
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
                v-if="$context.approversEmpty[approval_level.id]"
                :error="$str('error:add_user', 'mod_approval')"
              />
            </div>
            <!-- disabled until we have selectable relationship options  -->
            <TagList
              v-else
              :label-name="
                $str(
                  'relationships_for_approver_level_taglist',
                  'mod_approval',
                  approval_level.name
                )
              "
              class="tui-mod_approval-editStep__approverSelect"
              :tags="$context.formValues[types.RELATIONSHIP][approval_level.id]"
            >
              <template v-slot:tag="{ tag }">
                <Tag :text="tag.text" />
              </template>
            </TagList>
          </div>
          <div v-else>
            <div class="tui-mod_approval-editStep__inherited">
              <div class="tui-mod_approval-editStep__inheritedRow">
                <strong>
                  {{ $str('inherited_from', 'mod_approval') }}
                </strong>
              </div>
              <div class="tui-mod_approval-editStep__inheritedRow">
                {{
                  inheritedOrgName(
                    $selectors.getApprovalLevels($context)[index]
                  )
                }}
              </div>
            </div>
          </div>
        </div>
      </Form>
    </div>
    <template v-slot:footer-content>
      <div class="tui-mod_approval-editStep__buttons">
        <ButtonGroup>
          <Button
            :styleclass="{ primary: true }"
            :text="$str('save', 'mod_approval')"
            @click="$send({ type: $e.SAVE })"
          />
          <CancelButton @click="$send({ type: $e.CANCEL })" />
        </ButtonGroup>
      </div>
    </template>
  </ModalContent>
</template>

<script>
import { get } from 'tui/util';
import ModalContent from 'tui/components/modal/ModalContent';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';
import Checkbox from 'tui/components/form/Checkbox';
import FieldError from 'tui/components/form/FieldError';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import Loader from 'tui/components/loading/Loader';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import Select from 'tui/components/form/Select';
import Tag from 'tui/components/tag/Tag';
import TagList from 'tui/components/tag/TagList';

import {
  MOD_APPROVAL__EDIT_OVERRIDES,
  ApproverType,
} from 'mod_approval/constants';
import * as approvalLevelSelectors from 'mod_approval/item_selectors/approval_level';

export default {
  components: {
    ModalContent,
    Form,
    ButtonGroup,
    Button,
    CancelButton,
    Loader,
    MiniProfileCard,
    FormRow,
    FieldError,
    Select,
    Checkbox,
    Tag,
    TagList,
  },

  props: {
    approverTypes: {
      required: true,
      type: Array,
    },

    open: {
      type: Boolean,
    },
  },

  data() {
    return {
      types: ApproverType,
    };
  },

  computed: {
    approverTypeOptions() {
      return this.approverTypes.map(({ type, label }) => ({
        id: type,
        label,
      }));
    },

    searching() {
      return ['debouncing', 'searching'].some(this.$matches);
    },

    availableUsers() {
      const { activeLevelId } = this.$context;
      const approversById = this.$selectors.getApproversById(this.$context);
      const filterOutExisting = user => !approversById[user.id];
      const users = get(this.$context, ['users', activeLevelId, 'users']);

      if (users) {
        return users.filter(filterOutExisting);
      }

      return this.$selectors
        .getSelectableUsers(this.$context)
        .filter(filterOutExisting);
    },

    // TODO: TL-32777
    /*
    virtualScrollOptions() {
      return {
        dataKey: 'id',
        ariaLabel: this.$str('select_person', 'mod_approval'),
        isLoading: this.searching,
      };
    },
    */
  },

  methods: {
    inheritedOrgName(level) {
      const inherited = approvalLevelSelectors.getInherited(level);

      // QUESTION: ancestors may be loading?
      if (!inherited) {
        return this.$selectors
          .getAncestorAssignmentApprovalLevels(this.$context)
          .find(
            ancestor => ancestor.approval_level.id === level.approval_level.id
          ).assignment.name;
      }

      return approvalLevelSelectors.getInheritedAssignmentName(level);
    },

    search(approvalLevelId) {
      return get(this.$context, [
        'userSearchVariables',
        approvalLevelId,
        'input',
        'filters',
        'fullname',
      ]);
    },

    tags(approvers) {
      return approvers.map(approver => ({
        id: approver.approver_entity.id,
        text: approver.approver_entity.name,
        type: approver.type,
      }));
    },

    handleCheck({ approvalLevelId, checked }) {
      const approverType = this.$selectors.getFormApproverTypes(this.$context)[
        approvalLevelId
      ];
      this.$send({
        type: this.$e.TOGGLE_OVERRIDE,
        approverType,
        approvalLevelId,
        checked,
      });
    },

    handleApproverTypeSelect({ approverType, approvalLevelId }) {
      this.$send({
        type: this.$e.SELECT_APPROVER_TYPE,
        approverType,
        approvalLevelId,
      });
    },

    handleFilter({ approvalLevelId, search }) {
      this.$send({
        type: this.$e.FILTER,
        approvalLevelId,
        search,
      });
    },

    handleRemove({ approvalLevelId, tag }) {
      const approverType = this.$selectors.getFormApproverTypes(this.$context)[
        approvalLevelId
      ];
      this.$send({
        type: this.$e.REMOVE,
        approverType,
        approvalLevelId,
        tag,
      });
    },

    handleSelect({ approvalLevelId, user }) {
      const approverType = this.$selectors.getFormApproverTypes(this.$context)[
        approvalLevelId
      ];

      this.$send({
        type: this.$e.SELECT,
        approverType,
        approvalLevelId,
        user,
      });
    },

    // TODO: TL-32777
    /*
    handleScrollBottom({ approvalLevelId }) {
      let nextCursor;
      if (this.search[approvalLevelId]) {
        const users = this.$selectors.getUsers(this.$context)[
          this.approvalLevelId
        ];
        // cursor must be null or a non-empty string
        nextCursor = users && users.next_cursor ? users.next_cursor : null;
      } else {
        nextCursor = this.$selectors.getNextCursor(this.$context);
      }

      this.$send({
        type: this.$e.LOAD_MORE,
        approvalLevelId,
        nextCursor,
      });
    },
    */
  },

  xState: {
    machineId: MOD_APPROVAL__EDIT_OVERRIDES,
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "button_back",
    "edit_override_approvers",
    "error:add_user",
    "inherited_from",
    "override",
    "override_x",
    "reject_application",
    "approver_level_approver_type_label",
    "relationships_for_approver_level_taglist",
    "individuals_for_approver_level_taglist",
    "save",
    "select_person"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-editStep {
  &__inherited {
    @include tui-font-body-small();
    margin-top: var(--gap-4);
    margin-bottom: var(--gap-10);
  }

  &__inheritedRow {
    margin-top: var(--gap-1);
  }

  &__row {
    margin-top: var(--gap-6);
  }

  &__checkbox {
    align-items: flex-end;
    margin-top: var(--gap-1);
  }

  &__approverTypeSelect {
    margin-top: var(--gap-2);
  }

  &__approverSelect {
    margin-top: var(--gap-4);
  }

  &__profileCard {
    &--searching {
      opacity: 0.4;
    }
  }

  &__buttons {
    display: flex;
    flex: 1;
    justify-content: flex-end;
  }
}
</style>
