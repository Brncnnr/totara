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
  <Modal size="large">
    <ModalContent :close-button="true" :title="title">
      <slot name="subtitle" />
      <!-- When editing a workflow an approval level may not have approvers -->
      <!-- Published workflows and applications will always have approvers and no-items-text is not relevant -->
      <Table
        :data="approvers"
        :no-items-text="
          $str('no_approvers_on_level', 'mod_approval', approvalLevelName)
        "
      >
        <template v-slot:header-row>
          <HeaderCell size="10" valign="center">
            {{ $str('approvers', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="6" valign="center">
            {{ isUser ? $str('email', 'mod_approval') : '' }}
          </HeaderCell>
        </template>
        <template v-slot:row="{ row }">
          <Cell
            size="10"
            :column-header="$str('approver_name', 'mod_approval')"
          >
            <div>
              <MicroProfileCard v-if="isUser" :user="row" size="xxsmall" />
              <div v-else>{{ row.name }}</div>
              <slot name="lozenge" />
            </div>
          </Cell>
          <Cell size="6" :column-header="$str('email', 'mod_approval')">
            <a :href="`mailto:${row.email}`">
              {{ row.email }}
            </a>
          </Cell>
        </template>
      </Table>
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Table from 'tui/components/datatable/Table';
import MicroProfileCard from 'mod_approval/components/cards/MicroProfileCard';

export default {
  components: {
    HeaderCell,
    Cell,
    Table,
    Modal,
    ModalContent,
    MicroProfileCard,
  },

  props: {
    title: {
      type: String,
      required: true,
    },

    approvers: {
      type: Array,
      required: true,
    },

    approvalLevelName: String,
  },

  computed: {
    isUser() {
      const approver = this.approvers[0];
      return Boolean(approver && approver.card_display);
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "confirm"
  ],
  "mod_approval": [
    "approver_name",
    "approvers",
    "approver_name",
    "email",
    "no_approvers_on_level"
  ]
}
</lang-strings>
