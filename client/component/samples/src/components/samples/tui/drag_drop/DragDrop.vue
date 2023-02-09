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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-sampleDragDrop"
    :class="{
      'tui-sampleDragDrop--scrollAll': scrollAll,
    }"
  >
    <div class="tui-sampleDragDrop__options">
      <p>
        <ToggleSwitch
          v-model="scrollAll"
          text="Wrap sample in a scrollable div"
          :toggle-first="true"
        />
      </p>
      <p>
        <ToggleSwitch
          v-model="dropTargets"
          text="Render drop target placeholders"
          :toggle-first="true"
        />
      </p>
      <p>
        In this demo, items with even-numbered IDs can be moved between lists,
        while odd-numbered IDs can not.
      </p>
    </div>
    <div class="tui-sampleDragDrop__droppables">
      <h4>Lists</h4>
      <div class="tui-sampleDragDrop__dragLists">
        <Droppable
          v-slot="{
            attrs,
            events,
            isActive,
            isDropValid,
            dropTarget,
            placeholder,
          }"
          :source-id="$id('list-a')"
          source-name="Sample List A"
          :accept-drop="info => validateDrop(listA, info)"
          @remove="handleRemove(listA, $event)"
          @drop="handleDrop(listA, $event)"
        >
          <div
            class="tui-sampleDragDrop__dragList"
            :class="{
              'tui-sampleDragDrop__dragList--valid': isActive && isDropValid,
            }"
            v-bind="attrs"
            v-on="events"
          >
            <render v-if="dropTargets" :vnode="dropTarget" />
            <Draggable
              v-for="(x, index) in listA"
              :key="x.id"
              v-slot="{ dragging, attrs, events, moveMenu }"
              :index="index"
              type="sample"
              :value="x"
            >
              <div
                class="tui-sampleDragDrop__draggableItem"
                :class="{
                  'tui-sampleDragDrop__draggableItem--dragging': dragging,
                }"
                :style="x.customHeight && { minHeight: x.customHeight + 'px' }"
                v-bind="attrs"
                v-on="events"
              >
                <render :vnode="moveMenu" />
                id {{ x.id }}
              </div>
            </Draggable>
            <render :vnode="placeholder" />
          </div>
        </Droppable>

        <Droppable
          v-slot="{
            attrs,
            events,
            isActive,
            isDropValid,
            dropTarget,
            placeholder,
          }"
          :source-id="$id('list-b')"
          source-name="Sample List B"
          :accept-drop="info => validateDrop(listB, info)"
          @remove="handleRemove(listB, $event)"
          @drop="handleDrop(listB, $event)"
        >
          <div
            class="tui-sampleDragDrop__dragList"
            :class="{
              'tui-sampleDragDrop__dragList--valid': isActive && isDropValid,
            }"
            v-bind="attrs"
            v-on="events"
          >
            <render v-if="dropTargets" :vnode="dropTarget" />
            <Draggable
              v-for="(x, index) in listB"
              :key="x.id"
              v-slot="{ dragging, attrs, events, moveMenu }"
              :index="index"
              type="sample"
              :value="x"
            >
              <div
                class="tui-sampleDragDrop__draggableItem"
                :class="{
                  'tui-sampleDragDrop__draggableItem--dragging': dragging,
                }"
                :style="x.customHeight && { minHeight: x.customHeight + 'px' }"
                v-bind="attrs"
                v-on="events"
              >
                <render :vnode="moveMenu" />
                id {{ x.id }}
              </div>
            </Draggable>
            <render :vnode="placeholder" />
          </div>
        </Droppable>
      </div>
      <div class="tui-sampleDragDrop__drag-tables">
        <h4>Table</h4>
        <Droppable
          v-slot="{
            attrs,
            events,
            isActive,
            isDropValid,
            dropTarget,
            placeholder,
          }"
          :disabled="!reorderRows"
          :source-id="$id('table')"
          source-name="Sample Table"
          :accept-drop="info => validateDrop(table, info)"
          @remove="handleRemove(table, $event)"
          @drop="handleDrop(table, $event)"
        >
          <div v-bind="attrs" v-on="events">
            <Table
              :data="table"
              :draggable-rows="reorderRows"
              draggable-type="row-test"
              :draggable-placeholder="placeholder"
              :draggable-drop-target="dropTargets ? dropTarget : null"
            >
              <template v-slot:header-row>
                <HeaderCell size="2">ID</HeaderCell>
                <HeaderCell size="10">Name</HeaderCell>
              </template>
              <template v-slot:row="{ row }">
                <Cell size="2" column-header="col 2" valign="center">
                  {{ row.id }}
                </Cell>
                <Cell size="10" column-header="col 1" valign="center">
                  <InputText v-model="row.name" />
                </Cell>
              </template>
            </Table>
          </div>
        </Droppable>
        <ToggleSwitch
          v-model="reorderRows"
          class="tui-sampleDragDrop__table-toggle"
          text="Re-order rows"
          :toggle-first="true"
        />
      </div>
      <h4>Wrapping Grid</h4>
      <div>
        <Droppable
          v-slot="{ attrs, events, isActive, isDropValid }"
          :source-id="$id('grid-a')"
          source-name="Sample grid A"
          :accept-drop="info => validateDrop(gridA, info)"
          layout-interaction="grid-line"
          axis="horizontal"
          @remove="handleRemove(gridA, $event)"
          @drop="handleDrop(gridA, $event)"
        >
          <PropsProvider :provide="{ nativeListeners: events }">
            <transition-group
              name="tui-sampleDragDrop__dragGrid-item-transition"
              tag="div"
              class="tui-sampleDragDrop__dragGrid"
              :class="{
                'tui-sampleDragDrop__dragGrid--valid': isActive && isDropValid,
              }"
              v-bind="attrs"
            >
              <Draggable
                v-for="(x, index) in gridA"
                :key="x.id"
                v-slot="{ dragging, anyDragging, attrs, events, moveMenu }"
                :index="index"
                type="sample-grid-item"
                :value="x"
              >
                <div
                  class="tui-sampleDragDrop__dragGrid-item"
                  :class="{
                    'tui-sampleDragDrop__dragGrid-item--dragging': dragging,
                  }"
                  v-bind="attrs"
                  v-on="events"
                >
                  <div
                    v-if="!anyDragging || dragging"
                    class="tui-sampleDragDrop__dragGrid-item-moveIcon"
                  >
                    <DragHandleIcon />
                  </div>
                  <render :vnode="moveMenu" />
                  id {{ x.id }}
                </div>
              </Draggable>
            </transition-group>
          </PropsProvider>
        </Droppable>
      </div>
    </div>
  </div>
</template>

<script>
import Draggable from 'tui/components/drag_drop/Draggable';
import Droppable from 'tui/components/drag_drop/Droppable';
import Table from 'tui/components/datatable/Table';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';
import InputText from 'tui/components/form/InputText';
import PropsProvider from 'tui/components/util/PropsProvider';
import DragHandleIcon from 'tui/components/icons/DragHandle';

export default {
  components: {
    Draggable,
    Droppable,
    Table,
    Cell,
    HeaderCell,
    ToggleSwitch,
    InputText,
    PropsProvider,
    DragHandleIcon,
  },

  data() {
    return {
      listA: [
        { id: 30 },
        { id: 5 },
        { id: 60, customHeight: 80 },
        { id: 2 },
        { id: 3 },
        { id: 4, customHeight: 100 },
        { id: 50 },
      ],
      listB: [
        { id: 16 },
        { id: 7 },
        { id: 18, customHeight: 80 },
        { id: 9 },
        { id: 10, customHeight: 100 },
        { id: 11 },
      ],
      table: [
        { id: 341, name: 'John' },
        { id: 957, name: 'Paul' },
        { id: 274, name: 'George' },
        { id: 817, name: 'Ringo' },
      ],
      gridA: [45, 81, 77, 69, 12, 75, 17, 90, 19, 57, 13, 30].map(x => ({
        id: x,
      })),

      reorderRows: false,
      scrollAll: false,
      dropTargets: true,
    };
  },

  methods: {
    /**
     * Called to check whether a drop is allowed.
     *
     * @param {Array} list
     * @param {DropInfo} info
     */
    validateDrop(list, info) {
      return (
        info.destination.sourceId == info.source.sourceId ||
        info.item.value.id % 2 == 0
      );
    },

    /**
     * Called when item is dropped on another droppable (but not the same droppable)
     *
     * @param {Array} list
     * @param {DropInfo} info
     */
    handleRemove(list, info) {
      list.splice(info.source.index, 1);
    },

    /**
     * Called when item is dropped on a list.
     *
     * @param {Array} list
     * @param {DropInfo} info
     */
    handleDrop(list, info) {
      if (info.destination.sourceId == info.source.sourceId) {
        // reorder
        const item = list.splice(info.source.index, 1)[0];
        list.splice(info.destination.index, 0, item);
      } else {
        // move
        list.splice(info.destination.index, 0, info.item.value);
      }
    },
  },
};
</script>

<style lang="scss">
.tui-sampleDragDrop {
  &--scrollAll &__droppables {
    max-height: 500px;
    overflow-y: scroll;
  }

  &__table-toggle {
    margin-top: var(--gap-2);
  }

  &__dragLists {
    display: flex;
    align-items: flex-start;
  }

  &__dragList {
    width: 200px;
    margin-right: 50px;
    padding: var(--gap-2);
    background: var(--color-neutral-4);
    transition: background-color 0.15s;

    &--valid {
      background: #d2edf9;
    }

    > [data-tui-draggable-placeholder] {
      margin-bottom: var(--gap-2);
    }
  }

  &__draggableItem {
    margin-bottom: var(--gap-2);
    padding: var(--gap-4);
    background: white;
    border: 1px solid black;
    transition: box-shadow 0.15s;
    user-select: none;

    &--dragging {
      box-shadow: var(--shadow-3);
    }
  }

  &__dragGrid {
    display: flex;
    flex-wrap: wrap;
    max-width: 800px;

    &-item {
      position: relative;
      display: flex;
      flex-grow: 0;
      flex-shrink: 0;
      align-items: center;
      justify-content: center;
      width: 150px;
      height: 180px;
      margin: var(--gap-2);
      padding: var(--gap-4);
      background: white;
      border: 1px solid black;
      transition: box-shadow 0.15s;
      user-select: none;

      &-moveIcon {
        position: absolute;
        top: var(--gap-2);
        left: var(--gap-2);
        display: none;
      }

      &:hover &-moveIcon,
      &--dragging &-moveIcon {
        display: block;
      }
    }

    &-item-transition-move {
      transition: transform 0.25s;
    }
  }
}
</style>
