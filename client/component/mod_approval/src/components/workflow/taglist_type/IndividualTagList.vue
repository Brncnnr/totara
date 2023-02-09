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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->

<template>
  <TagList
    :disabled="disabled"
    :items="availableUsers"
    :tags="selectedApprovers"
    :filter="fullnameSearch"
    :virtual-scroll-options="virtualScrollOptions"
    :label-name="
      $str(
        'individuals_for_approver_level_taglist',
        'mod_approval',
        approverLevel.name
      )
    "
    @filter="handleFilter"
    @select="handleSelect"
    @remove="handleRemove"
    @scrollbottom="handleScrollBottom"
  >
    <template v-slot:item="{ item }">
      <div>
        <Loader v-if="item.loader" :loading="true" />
        <MiniProfileCard
          v-else
          :class="{
            'tui-mod_approval-individualTagList__profileCard--searching': searching,
          }"
          :no-border="true"
          :no-padding="true"
          :read-only="true"
          :display="item.card_display"
        />
      </div>
    </template>
  </TagList>
</template>
<script>
import {
  ApproverType,
  MOD_APPROVAL__WORKFLOW_EDIT,
} from 'mod_approval/constants';
import { structuralDeepClone } from 'tui/util';
import Loader from 'tui/components/loading/Loader';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import TagList from 'tui/components/tag/TagList';

export default {
  components: {
    Loader,
    MiniProfileCard,
    TagList,
  },

  props: {
    disabled: Boolean,
    approvalLevelId: {
      type: String,
      required: true,
    },
  },
  computed: {
    searching() {
      return (
        this.$matches('navigation.approvals.approvers.searching') ||
        this.$matches('navigation.approvals.approvers.debouncing')
      );
    },

    approverLevel() {
      return this.$selectors
        .getActiveStageApprovalLevels(this.$context)
        .find(approvalLevel => approvalLevel.id === this.approvalLevelId);
    },

    selectedApprovers() {
      return this.approverLevel.approvers
        .filter(approver => approver.type === ApproverType.USER)
        .map(approver =>
          // Add text for the taglist to pick up
          Object.assign({ text: approver.approver_entity.fullname }, approver)
        );
    },

    fullnameSearch() {
      const variables = this.$selectors.getUserSearchVariables(this.$context)[
        this.approvalLevelId
      ];
      if (variables) {
        return variables.input.filters.fullname;
      }
      return '';
    },

    /**
     * Switch between search-filtered users or the first 20
     * depending on the presence of a search filter
     */
    availableUsers() {
      let availableUsers;
      if (this.fullnameSearch) {
        const users = this.$selectors.getUsers(this.$context)[
          this.approvalLevelId
        ];

        if (users) {
          availableUsers = users.items;
        }
      }
      if (!availableUsers) {
        availableUsers = this.$selectors.getSelectableUsers(this.$context);
      }

      // Filter out users who are already selected
      return availableUsers.filter(
        user =>
          !this.selectedApprovers.some(
            approver => approver.approver_entity.id == user.id
          )
      );
    },

    virtualScrollOptions() {
      return {
        dataKey: 'id',
        ariaLabel: this.$str('select_person', 'mod_approval'),
        isLoading: this.searching,
      };
    },

    workflowId() {
      return this.$selectors.getWorkflowId(this.$context);
    },

    /**
     * Identifies mutations from the same input in the mutationQueue
     */
    inputId() {
      return `${ApproverType.USER}-${this.approvalLevelId}`;
    },
  },
  methods: {
    handleSelect(user) {
      // the tags to display immediately in the UI
      const approvers = this.selectedApprovers.concat([
        {
          id: `temp-${user.id}`,
          type: ApproverType.USER,
          approver_entity: Object.assign(
            {
              __typename: 'core_user',
            },
            structuralDeepClone(user)
          ),
        },
      ]);

      // the approvers to use in the mutation
      const approverInputs = approvers.map(approver => ({
        identifier: approver.approver_entity.id,
        assignment_approver_type: ApproverType.USER,
      }));

      this.$send({
        type: this.$e.UPDATE_APPROVAL_LEVEL_APPROVERS,
        inputId: this.inputId,
        approvers,
        variables: {
          input: {
            assignment_id: this.$selectors.getDefaultAssignmentId(
              this.$context
            ),
            approval_level_id: this.approvalLevelId,
            approvers: approverInputs,
          },
        },
      });
    },

    handleRemove(user) {
      const approvers = this.selectedApprovers.filter(
        approver => approver.id !== user.id
      );

      const approverInputs = approvers.map(({ approver_entity }) => ({
        identifier: approver_entity.id,
        assignment_approver_type: ApproverType.USER,
      }));

      this.$send({
        type: this.$e.UPDATE_APPROVAL_LEVEL_APPROVERS,
        inputId: this.inputId,
        approvers,
        variables: {
          input: {
            assignment_id: this.$selectors.getDefaultAssignmentId(
              this.$context
            ),
            approval_level_id: this.approvalLevelId,
            approvers: approverInputs,
          },
        },
      });
    },

    handleFilter(search) {
      this.$send({
        type: this.$e.FILTER,
        approvalLevelId: this.approvalLevelId,
        variables: {
          input: {
            workflow_id: this.$selectors.getWorkflowId(this.$context),
            filters: {
              fullname: search,
            },
            pagination: {},
          },
        },
      });
    },

    handleScrollBottom() {
      let nextCursor;
      if (this.fullnameSearch) {
        const users = this.$selectors.getUsers(this.$context)[
          this.approvalLevelId
        ];
        // cursor must be null or a non-empty string
        nextCursor = users && users.next_cursor ? users.next_cursor : null;
      } else {
        nextCursor = this.$selectors.getNextCursor(this.$context);
      }

      if (!nextCursor) {
        return;
      }

      this.$send({
        type: this.$e.LOAD_MORE_INDIVIDUAL_APPROVERS,
        approvalLevelId: this.fullnameSearch ? this.approvalLevelId : null,
        variables: {
          input: {
            workflow_id: this.$selectors.getWorkflowId(this.$context),
            filters: {
              fullname: this.fullnameSearch,
            },
            pagination: {
              cursor: nextCursor,
            },
          },
        },
      });
    },
  },
  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "individuals_for_approver_level_taglist",
    "select_person"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-individualTagList {
  &__profileCard {
    &--searching {
      opacity: 0.4;
    }
  }
}
</style>
