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
  <Card class="tui-performManageActivityAssignmentsIndividuals">
    <Loader :loading="updating">
      <h3 class="tui-performManageActivityAssignmentsIndividuals__title">
        {{ title }}
      </h3>

      <Table
        :border-bottom-hidden="true"
        class="tui-performManageActivityAssignmentsIndividuals__table"
        :data="assignments"
        :loading-preview="updating"
        :loading-preview-rows="assignments.length"
        :stack-at="764"
      >
        <template v-slot:row="{ row }">
          <Cell
            v-slot="{ isStacked }"
            :loader-lines-stacked="5"
            size="15"
            valign="center"
          >
            <MiniProfileCard
              :display="getCardData(row.group.extra)"
              :horizontal="!isStacked"
              :no-border="true"
              :no-padding="!isStacked"
              :read-only="true"
            />
          </Cell>

          <!-- Action buttons -->
          <Cell align="end" size="1" valign="center">
            <template v-slot:default="{ isStacked }">
              <div
                class="tui-performManageActivityAssignmentsIndividuals__actions"
              >
                <Button
                  v-if="isStacked && row.group.id"
                  :aria-label="
                    $str('remove_group_assignment', 'mod_perform', {
                      name: row.group.name,
                      type: title,
                    })
                  "
                  class="tui-performManageActivityAssignmentsIndividuals__actions-stacked"
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
import Loader from 'tui/components/loading/Loader';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import RemoveIcon from 'tui/components/icons/Remove';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    Button,
    ButtonIcon,
    Card,
    Cell,
    Loader,
    MiniProfileCard,
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

  methods: {
    /**
     * Parse the card data
     *
     * @param {String}
     * @return {Object}
     */
    getCardData(card) {
      return JSON.parse(card);
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
.tui-performManageActivityAssignmentsIndividuals {
  flex-direction: column;
  padding: var(--gap-4);

  & > * + * {
    margin-top: var(--gap-2);
  }

  &__actions {
    display: flex;

    &-stacked {
      margin: var(--gap-2) 0 0 var(--gap-1);
    }
  }

  &__table {
    margin-top: var(--gap-2);
  }

  &__title {
    margin: 0;
    padding-left: var(--gap-1);
    @include tui-font-heading-x-small();
  }
}
</style>
