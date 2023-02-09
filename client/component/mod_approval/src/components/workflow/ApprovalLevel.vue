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
  <Card
    class="tui-mod_approval-approvalsEdit"
    :has-hover-shadow="hasHoverShadow"
    :has-shadow="hasShadow"
    :aria-label="ariaLabel"
  >
    <div
      :class="{
        'tui-mod_approval-approvalsEdit__actions': true,
        'tui-mod_approval-approvalsEdit__actions--disabled': disabled,
      }"
    >
      <Dropdown
        v-if="$selectors.getWorkflowIsDraft($context)"
        position="bottom-right"
      >
        <template v-slot:trigger="{ toggle, isOpen }">
          <ButtonIcon
            :aria-label="$str('more_actions', 'mod_approval')"
            :aria-expanded="isOpen"
            :styleclass="{ transparent: true }"
            @click="toggle"
          >
            <MoreIcon />
          </ButtonIcon>
        </template>
        <DropdownItem
          @click="$send({ type: $e.RENAME_APPROVAL_LEVEL, approvalLevelId })"
        >
          {{ $str('rename', 'core') }}
        </DropdownItem>
        <DropdownItem
          v-if="canDelete"
          @click="$send({ type: $e.DELETE_APPROVAL_LEVEL, approvalLevelId })"
        >
          {{ $str('delete', 'mod_approval') }}
        </DropdownItem>
      </Dropdown>
    </div>
    <Form
      :class="{
        'tui-mod_approval-approvalsEdit__form': true,
        'tui-mod_approval-approvalsEdit__form--disabled': disabled,
      }"
    >
      <FormRow :stacked="false" :label="approvalLevelName">
        <Select
          :aria-label="
            $str(
              'approver_level_approver_type_label',
              'mod_approval',
              approvalLevel.name
            )
          "
          :value="selectedApproverType"
          :options="approverTypeOptions"
          :size="1"
          :disabled="disabled"
          @input="handleSelectApproverType"
        />
      </FormRow>
    </Form>
    <component
      :is="tagList"
      :class="{
        'tui-mod_approval-approvalsEdit__approvers': true,
        'tui-mod_approval-approvalsEdit__approvers--disabled': disabled,
      }"
      :approval-level-id="approvalLevelId"
      :disabled="disabled"
    />
    <slot />
  </Card>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import Select from 'tui/components/form/Select';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import MoreIcon from 'tui/components/icons/More';
import Card from 'tui/components/card/Card';
import RelationshipTagList from 'mod_approval/components/workflow/taglist_type/RelationshipTagList';
import IndividualTagList from 'mod_approval/components/workflow/taglist_type/IndividualTagList';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';

import * as approvalLevelSelector from 'mod_approval/item_selectors/approval_level';
import {
  ApproverType,
  MOD_APPROVAL__WORKFLOW_EDIT,
} from 'mod_approval/constants';

const approverTypeProvider = {
  [ApproverType.RELATIONSHIP]: {
    component: RelationshipTagList,
    getApprovers() {
      // TODO: TL-32045 code below sets the Relationships to [Manager]
      // This is because [Manager] is not interactable in the RelationshipTagList.
      // TL-32045 will change this to a different design with an editable Relationship selection
      return this.$selectors.getRoleTypeOptions(this.$context).map(option => ({
        identifier: option.identifier,
        assignment_approver_type: ApproverType.RELATIONSHIP,
      }));
    },
  },
  [ApproverType.USER]: {
    component: IndividualTagList,
    getApprovers() {
      return this.$selectors
        .getActiveStageApprovalLevels(this.$context)
        .find(approvalLevel => approvalLevel.id === this.approvalLevelId)
        .approvers.filter(approver => approver.type === ApproverType.USER)
        .map(approver => ({
          identifier: approver.approver_entity.id,
          assignment_approver_type: ApproverType.USER,
        }));
    },
  },
};

export default {
  components: {
    Card,
    Dropdown,
    DropdownItem,
    Form,
    FormRow,
    Select,
    ButtonIcon,
    MoreIcon,
    RelationshipTagList,
    IndividualTagList,
  },
  props: {
    approvalLevel: { type: Object, required: true },
    disabled: Boolean,
    hasShadow: Boolean,
    hasHoverShadow: Boolean,
  },

  computed: {
    currentProvider() {
      const provider = approverTypeProvider[this.selectedApproverType];
      if (!provider) {
        throw new Error('unknown approver type');
      }
      return provider;
    },
    tagList() {
      return this.currentProvider.component;
    },

    approvalLevelName() {
      return this.$str(
        'add_approvers_name',
        'mod_approval',
        this.approvalLevel.name
      );
    },

    selectedApproverType() {
      return (
        this.$selectors.getSelectedApproverTypes(this.$context)[
          this.approvalLevel.id
        ] || approvalLevelSelector.getInitialApproverType(this.approvalLevel)
      );
    },

    canDelete() {
      return this.$selectors.getHasMultipleApprovalLevels(this.$context);
    },

    approvalLevelId() {
      return this.approvalLevel.id;
    },
    approverTypeOptions() {
      return this.$selectors
        .getApproverTypes(this.$context)
        .map(approverType => {
          return {
            id: approverType.type,
            label: approverType.label,
          };
        });
    },

    selectedApproverTypeName() {
      const selectedType = this.approverTypeOptions.find(
        option => option.id === this.selectedApproverType
      );
      return selectedType ? selectedType.label : '';
    },
    ariaLabel() {
      return this.$str('approval_level_summary', 'mod_approval', {
        level: this.approvalLevel.ordinal_number,
        name: this.approvalLevel.name,
        approver_type: this.selectedApproverTypeName,
      });
    },
  },
  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },

  methods: {
    handleSelectApproverType(approverType) {
      const newProvider = approverTypeProvider[approverType];
      const approvers = newProvider.getApprovers.call(this);
      this.$send({
        type: this.$e.SELECT_APPROVER_TYPE,
        inputId: `${approverType}-${this.approvalLevelId}`,
        approverType,
        variables: {
          input: {
            approvers,
            approval_level_id: this.approvalLevelId,
            assignment_id: this.$selectors.getDefaultAssignmentId(
              this.$context
            ),
          },
        },
      });
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-approvalsEdit {
  display: flex;
  flex-direction: column;
  padding: var(--gap-4) var(--gap-6);

  @mixin no-select {
    user-select: none;
    pointer-events: none;
  }

  &__form {
    &--disabled {
      @include no-select;
    }
  }
  &__actions {
    display: flex;
    justify-content: flex-end;
    &--disabled {
      @include no-select;
    }
  }

  &__approvers {
    margin-top: var(--gap-4);
    // taglist is buggy??
    &--disabled {
      @include no-select;
      opacity: 0.6;
    }
  }
}
</style>
<lang-strings>
{
  "core": [
    "rename"
  ],
  "mod_approval": [
    "add_approvers_name",
    "approval_level_summary",
    "approver_level_approver_type_label",
    "delete",
    "more_actions"
  ]
}
</lang-strings>
