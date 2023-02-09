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

  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module mod_approval
-->

<template>
  <Droppable
    v-slot="{
      attrs,
      events,
      dragging,
      dropTarget,
      placeholder,
    }"
    class="tui-mod_approval-approvalLevelsEdit"
    :source-name="$str('approval_level', 'mod_approval')"
    :reorder-only="true"
    :disabled="!canReorderLevels"
    @drop="handleDrop"
  >
    <div
      :class="{
        'tui-mod_approval-approvalLevelsEdit__droppable': true,
        'tui-mod_approval-approvalLevelsEdit__droppable--dragging': dragging,
      }"
      role="list"
      v-bind="attrs"
      v-on="events"
    >
      <render :vnode="dropTarget" />
      <Draggable
        v-for="(level, index) in approvalLevels"
        :key="level.id"
        v-slot="{
          dragging,
          anyDragging,
          attrs,
          nativeDraggableEvents,
          nativeDragHandleEvents,
          moveMenu,
        }"
        :disabled="!canReorderLevels"
        :index="index"
        type="level"
        :value="level"
        :class="{
          'tui-mod_approval-approvalLevelsEdit__draggable': true,
          'tui-mod_approval-approvalLevelsEdit__draggable--last':
            index === approvalLevels.length - 1,
        }"
      >
        <div
          :class="{
            'tui-mod_approval-approvalLevelsEdit__draggableItem': true,
            'tui-mod_approval-approvalLevelsEdit__draggableItem--idle': !anyDragging,
          }"
          role="listitem"
          v-bind="attrs"
          v-on="nativeDraggableEvents"
        >
          <render :vnode="moveMenu" />
          <ApprovalLevelLoading
            v-if="level.loading"
            :class="{
              'tui-mod_approval-approvalLevelsEdit__level': true,
              'tui-mod_approval-approvalLevelsEdit__level--othersDragging':
                anyDragging && !dragging,
            }"
          >
            <span v-if="level.deleting"
              >{{ $str('deleting_approval_level', 'mod_approval') }}
            </span>
            <span v-else
              >{{ $str('adding_approval_level', 'mod_approval') }}
            </span>
          </ApprovalLevelLoading>
          <ApprovalLevel
            v-else
            :class="{
              'tui-mod_approval-approvalLevelsEdit__level': true,
              'tui-mod_approval-approvalLevelsEdit__level--othersDragging':
                anyDragging && !dragging,
            }"
            :approval-level="level"
            :has-shadow="dragging"
            :has-hover-shadow="!anyDragging && !dragging"
          >
            <div
              v-if="canReorderLevels"
              :class="{
                'tui-mod_approval-approvalLevelsEdit__dragHandle': true,
                'tui-mod_approval-approvalLevelsEdit__dragHandle--dragging': dragging,
              }"
              :aria-label="$str('move_x', 'mod_approval', level.name)"
              v-on="nativeDragHandleEvents"
            >
              <DragHandleIcon
                class="tui-mod_approval-approvalLevelsEdit__dragHandle-icon"
              />
            </div>
          </ApprovalLevel>
        </div>
      </Draggable>
      <render :vnode="placeholder" />
    </div>
  </Droppable>
</template>

<script>
import Draggable from 'tui/components/drag_drop/Draggable';
import Droppable from 'tui/components/drag_drop/Droppable';
import DragHandleIcon from 'tui/components/icons/DragHandle';
import ApprovalLevel from 'mod_approval/components/workflow/ApprovalLevel';
import ApprovalLevelLoading from 'mod_approval/components/workflow/ApprovalLevelLoading';
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';

export default {
  components: {
    Draggable,
    Droppable,
    DragHandleIcon,
    ApprovalLevel,
    ApprovalLevelLoading,
  },

  computed: {
    approvalLevels() {
      return this.$selectors.getActiveStageApprovalLevels(this.$context);
    },

    canReorderLevels() {
      return this.$selectors.getWorkflowIsDraft(this.$context);
    },
  },

  methods: {
    handleDrop(event) {
      this.$send({
        type: this.$e.REORDER_APPROVAL_LEVEL,
        from: event.source.index,
        to: event.destination.index,
        workflowStageId: this.$selectors.getActiveStageId(this.$context),
      });
    },
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },
};
</script>

<style lang="scss">
.tui-mod_approval-approvalLevelsEdit {
  &__droppable {
    margin-top: var(--gap-6);
    padding-bottom: var(--gap-6);
    border-top: 1px solid var(--color-neutral-5);
    // No idea what is eating the padding on the last item when dragging.
    &--dragging {
      padding-bottom: var(--gap-12);
    }
  }
  &__level {
    // hard-code background because body has hard-coded background: #fff.
    background: #fff;
  }
  &__draggable {
    position: relative;
    margin-top: var(--gap-6);
    cursor: default;
  }
  &__dragHandle {
    position: absolute;
    top: var(--gap-1);
    left: var(--gap-3);
    display: none;
    border-radius: 100%;
    cursor: grab;
    user-select: none;
    &-icon {
      margin: var(--gap-3);
    }
  }
  &__draggableItem--idle:hover &__dragHandle,
  &__draggableItem--idle:focus &__dragHandle,
  &__dragHandle--dragging {
    display: block;
  }
  [data-tui-droppable-location-indicator] {
    border-radius: var(--card-border-radius);
  }
}
</style>

<lang-strings>
{
  "mod_approval": [
    "adding_approval_level",
    "approval_level",
    "deleting_approval_level",
    "move_x"
  ]
}
</lang-strings>
