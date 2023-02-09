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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div>
    <SubPageHeading :title="$str('interactions_feature', 'mod_approval')" />
    <Separator />
    <div class="tui-mod_approval-interactionsSection__events">
      <CollapsibleCard
        v-for="interaction in $selectors.getActiveStageInteractions($context)"
        :key="interaction.id"
        class="tui-interactionsSection__event"
        :label="label(interaction)"
        :has-hover-shadow="true"
        :initial-state="true"
      >
        <template v-slot:actions>
          <div
            v-if="$selectors.getWorkflowIsDraft($context)"
            class="tui-mod_approval-interactionsSection__eventActions"
          >
            <!-- disable buttons until implemented
            <ButtonIcon
              :styleclass="{ small: true }"
              :aria-label="$str('add_action', 'mod_approval')"
              :text="$str('add_action', 'mod_approval')"
            >
              <ActionIcon size="200" />
            </ButtonIcon>

            <ButtonIcon
              :styleclass="{ small: true }"
              :aria-label="$str('add_transition', 'mod_approval')"
              :text="$str('add_transition', 'mod_approval')"
            >
              <TransitionIcon size="200" />
            </ButtonIcon>
            -->
            <!-- disable remove until implemented
            <Dropdown position="bottom-right">
              <template v-slot:trigger="{ toggle, isOpen }">
                <MoreButton
                  :aria-label="$str('more_actions', 'mod_approval')"
                  :aria-expanded="String(isOpen)"
                  @click="toggle"
                />
              </template>
              <DropdownItem>
                {{ $str('remove_interaction', 'mod_approval') }}
              </DropdownItem>
            </Dropdown>
            -->
          </div>
        </template>
        <template v-slot:content>
          <p>{{ $str('transition', 'mod_approval') }}</p>
          <TransitionCard :items="defaultTransition(interaction)">
            <template v-slot:left-icon>
              <TransitionIcon />
            </template>
            <template v-slot:buttons>
              <ButtonIcon
                v-if="$selectors.getWorkflowIsDraft($context)"
                :styleclass="{
                  small: true,
                  transparent: true,
                }"
                :aria-label="$str('edit', 'core')"
                @click="$send({ type: $e.EDIT_TRANSITION, interaction })"
              >
                <EditIcon />
              </ButtonIcon>
            </template>
          </TransitionCard>
        </template>
      </CollapsibleCard>
      <ModalPresenter
        :open="$matches('navigation.interactions.editTransition')"
        @request-close="$send({ type: $e.CANCEL })"
      >
        <EditTransitionModal
          v-if="$context.toEditInteraction"
          :interaction-title="label($context.toEditInteraction)"
        />
      </ModalPresenter>
    </div>
  </div>
</template>

<script>
import {
  MOD_APPROVAL__WORKFLOW_EDIT,
  ApplicationAction,
  Transition,
} from 'mod_approval/constants';
//import ActionIcon from 'tui/components/icons/Action';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import CollapsibleCard from 'mod_approval/components/cards/CollapsibleCard';
//import Dropdown from 'tui/components/dropdown/Dropdown';
//import DropdownItem from 'tui/components/dropdown/DropdownItem';
import EditIcon from 'tui/components/icons/Edit';
import EditTransitionModal from 'mod_approval/components/workflow/interactions/EditTransitionModal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
//import MoreButton from 'tui/components/buttons/MoreIcon';
import SubPageHeading from 'mod_approval/components/page/SubPageHeading';
import Separator from 'tui/components/decor/Separator';
import TransitionCard from 'mod_approval/components/cards/TransitionCard';
import TransitionIcon from 'tui/components/icons/Transition';

export default {
  // Temporarily removed: ActionIcon, Dropdown, DropdownItem, MoreButton
  components: {
    ButtonIcon,
    CollapsibleCard,
    EditIcon,
    EditTransitionModal,
    ModalPresenter,
    Separator,
    SubPageHeading,
    TransitionCard,
    TransitionIcon,
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },

  methods: {
    /**
     * @param {object} interaction
     * @returns {string}
     */
    label(interaction) {
      switch (interaction.action_code) {
        case ApplicationAction.APPROVE:
          return this.$str('on_approved', 'mod_approval');
        case ApplicationAction.REJECT:
          return this.$str('on_rejected', 'mod_approval');
        case ApplicationAction.WITHDRAW_BEFORE_SUBMISSION:
        case ApplicationAction.WITHDRAW_IN_APPROVALS:
          return this.$str('on_withdrawn', 'mod_approval');
        case ApplicationAction.SUBMIT:
          return this.$str('on_submitted', 'mod_approval');
        case ApplicationAction.RESET_APPROVALS:
          return this.$str('on_reset', 'mod_approval');
        default:
          return '';
      }
    },

    /**
     * @param {string} stageId
     * @returns {string}
     */
    stageName(stageId) {
      const stage = this.$selectors
        .getWorkflowStages(this.$context)
        .find(stage => stage.id === stageId);

      if (stage) {
        return this.$str('stage_number_name', 'mod_approval', stage);
      } else {
        throw Error(`stage with stage ID: ${stageId} not found`);
      }
    },

    /**
     * @param {string} transition
     * @returns {string}
     */
    transitionTo(transition) {
      switch (transition) {
        case Transition.PREVIOUS:
          return this.$str('previous_stage', 'mod_approval');
        case Transition.NEXT:
          return this.$str('next_stage', 'mod_approval');
        case Transition.RESET:
          return this.$str('stage_start', 'mod_approval');
        default:
          return this.stageName(transition);
      }
    },

    /**
     * @param {object} interaction
     * @returns {Object[]}
     */
    defaultTransition(interaction) {
      return [
        {
          header: this.$str('transition_type', 'mod_approval'),
          text: this.$str('default', 'mod_approval'),
        },
        {
          header: this.$str('move_to', 'mod_approval'),
          text: this.transitionTo(interaction.default_transition.transition),
        },
      ];
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "edit"
  ],
  "mod_approval": [
    "add_action",
    "add_transition",
    "default",
    "interactions_feature",
    "move_to",
    "more_actions",
    "next_stage",
    "on_approved",
    "on_rejected",
    "on_reset",
    "on_submitted",
    "on_withdrawn",
    "previous_stage",
    "remove_interaction",
    "stage_number_name",
    "stage_start",
    "transition",
    "transition_type"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-interactionsSection {
  &__events {
    > :first-child {
      margin-top: var(--gap-2);
    }

    > :not(:first-child) {
      margin-top: var(--gap-4);
    }
  }

  &__event {
    padding: var(--gap-1);
  }

  &__eventActions {
    @include tui-stack-horizontal(var(--gap-4));
    display: flex;
    align-items: center;
  }
}
</style>
