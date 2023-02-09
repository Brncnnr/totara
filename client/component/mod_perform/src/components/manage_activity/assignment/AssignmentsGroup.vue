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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->

<template>
  <Card class="tui-performManageActivityAssignmentsGroup">
    <Loader :loading="updating">
      <h3 class="tui-performManageActivityAssignmentsGroup__title">
        {{ title }}
      </h3>

      <Table
        :border-bottom-hidden="true"
        :data="assignments"
        :loading-preview="updating"
        :loading-preview-rows="assignments.length"
        :stack-at="764"
        :stacked-header-row-gap="true"
      >
        <template v-slot:header-row>
          <HeaderCell size="13" valign="center">
            {{ $str('user_group_assignment_name', 'mod_perform') }}
          </HeaderCell>
          <HeaderCell size="2" valign="center">
            {{ $str('user_group_assignment_usercount', 'mod_perform') }}
          </HeaderCell>
          <HeaderCell size="1" valign="center" />
        </template>

        <template v-slot:row="{ row }">
          <!-- Assignment name -->
          <Cell
            :column-header="$str('user_group_assignment_name', 'mod_perform')"
            size="13"
            valign="center"
          >
            <template v-slot:default>
              {{ row.group.name }}
            </template>
          </Cell>

          <!-- Number of users in assignment -->
          <Cell
            :column-header="
              $str('user_group_assignment_usercount', 'mod_perform')
            "
            size="2"
            valign="center"
          >
            <template v-slot:default>
              {{ row.group.size }}
            </template>
          </Cell>

          <!-- Action buttons -->
          <Cell align="end" size="1" valign="center">
            <template v-slot:default="{ isStacked }">
              <div class="tui-performManageActivityAssignmentsGroup__actions">
                <Button
                  v-if="isStacked && row.group.id"
                  :aria-label="
                    $str('remove_group_assignment', 'mod_perform', {
                      name: row.group.name,
                      type: title,
                    })
                  "
                  class="tui-performManageActivityAssignmentsGroup__actions-stacked"
                  :styleclass="{ small: true, transparent: true }"
                  :text="$str('remove', 'core')"
                  @click="
                    $emit('remove', {
                      assignmentType: row.type,
                      groupId: row.group.id,
                      groupType: row.group.type,
                    })
                  "
                />

                <ButtonIcon
                  v-else-if="row.group.id"
                  :aria-label="
                    $str('remove_group_assignment', 'mod_perform', {
                      name: row.group.name,
                      type: title,
                    })
                  "
                  :styleclass="{
                    small: true,
                    transparent: true,
                  }"
                  @click="
                    $emit('remove', {
                      assignmentType: row.type,
                      groupId: row.group.id,
                      groupType: row.group.type,
                    })
                  "
                >
                  <RemoveIcon size="300" state="warning" />
                </ButtonIcon>
              </div>
            </template>
          </Cell>
        </template>
      </Table>
    </Loader>
  </Card>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Card from 'tui/components/card/Card';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Loader from 'tui/components/loading/Loader';
import RemoveIcon from 'tui/components/icons/Remove';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    Button,
    ButtonIcon,
    Card,
    Cell,
    HeaderCell,
    Loader,
    RemoveIcon,
    Table,
  },

  props: {
    assignments: {
      type: Array,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    updating: {
      type: Boolean,
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "remove"
    ],
    "mod_perform" : [
      "remove_group_assignment",
      "user_group_assignment_name",
      "user_group_assignment_usercount"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performManageActivityAssignmentsGroup {
  flex-direction: column;
  padding: var(--gap-4);

  & > * + * {
    margin-top: var(--gap-2);
  }

  &__actions {
    display: flex;

    &-stacked {
      margin-top: var(--gap-3);
    }
  }

  &__title {
    margin: 0;
    padding-left: var(--gap-1);
    @include tui-font-heading-x-small();
  }
}
</style>
