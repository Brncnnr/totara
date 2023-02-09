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

  @author Simon Tegg <simon.teg@totaralearning.com>
  @module mod_approval
-->
<template>
  <ModalContent
    :title="$str('choose_form', 'mod_approval')"
    :title-id="$id('title-create')"
  >
    <div class="tui-mod_approval-chooseFormStep__content">
      <FormRow :label="$str('form', 'mod_approval')">
        <SearchFilter
          :value="$context.formSearch"
          :label="$str('form', 'mod_approval')"
          :placeholder="$str('search_by_form_name', 'mod_approval')"
          @input="$send({ type: $e.UPDATE_FORM_SEARCH, formSearch: $event })"
        />
      </FormRow>
      <Table
        class="tui-mod_approval-chooseFormStep__table"
        :class="{
          'tui-mod_approval-chooseFormStep__table--searching':
            loading && total !== null,
        }"
        :data="forms"
        :header-has-loaded="true"
        :loading-preview="loading"
        :loading-preview-rows="loadingPreviewRows"
        :loading-overlay-active="true"
        :no-items-text="$str('no_forms_match', 'mod_approval')"
      >
        <template v-slot:header-row>
          <HeaderCell size="10">
            {{ $str('form_name', 'mod_approval') }}
          </HeaderCell>
          <HeaderCell size="2">
            {{ $str('last_modified', 'mod_approval') }}
          </HeaderCell>
        </template>
        <template v-slot:row="{ row }">
          <Cell
            valign="center"
            size="10"
            :column-header="$str('form_name', 'mod_approval')"
          >
            <Radio
              :name="$id('radio')"
              :checked="row.id === $context.selectedFormId"
              :value="row.id"
              :disabled="$matches('chooseForm.searching')"
              @select="$send({ type: $e.SELECT_FORM, selectedFormId: $event })"
            >
              <slot :id="row.id" name="title" :row="row">
                {{ row.title }}
              </slot>
            </Radio>
          </Cell>
          <Cell size="2" :column-header="$str('last_modified', 'mod_approval')">
            {{ row.updated }}
          </Cell>
        </template>
      </Table>
      <Paging
        class="tui-mod_approval-workflowDashboard__paging"
        :page="$context.formPage"
        :items-per-page="$context.formLimit"
        :total-items="$selectors.getFormsTotal($context) || 1"
        @page-change="$send({ type: $e.UPDATE_FORM_PAGE, formPage: $event })"
        @count-change="$send({ type: $e.UPDATE_FORM_LIMIT, formLimit: $event })"
      />
    </div>

    <template v-slot:footer-content>
      <div class="tui-mod_approval-chooseFormStep__footer">
        <Button
          :text="$str('back', 'core')"
          @click="$send({ type: $e.BACK })"
        />
        <ButtonGroup>
          <Button
            :text="$str('next', 'core')"
            :styleclass="{ primary: true }"
            :disabled="!$context.selectedFormId"
            @click="$send({ type: $e.NEXT })"
          />
          <Button
            :text="$str('cancel', 'core')"
            @click="$send({ type: $e.CANCEL })"
          />
        </ButtonGroup>
      </div>
    </template>
  </ModalContent>
</template>

<script>
import ModalContent from 'tui/components/modal/ModalContent';
import FormRow from 'tui/components/form/FormRow';
import SearchFilter from 'tui/components/filters/SearchFilter';
import Table from 'tui/components/datatable/Table';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Cell from 'tui/components/datatable/Cell';
import Radio from 'tui/components/form/Radio';
import Paging from 'tui/components/paging/Paging';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import { MOD_APPROVAL__WORKFLOW_CREATE } from 'mod_approval/constants';

export default {
  components: {
    ModalContent,
    FormRow,
    SearchFilter,
    Table,
    HeaderCell,
    Cell,
    Radio,
    Paging,
    ButtonGroup,
    Button,
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_CREATE,
  },

  computed: {
    loading() {
      return ['chooseForm.debouncing', 'chooseForm.searching'].some(
        this.$matches
      );
    },

    forms() {
      return this.$selectors.getActiveForms(this.$context);
    },

    total() {
      return this.$selectors.getFormsTotal(this.$context);
    },

    loadingPreviewRows() {
      return this.forms.length !== 0 &&
        this.forms.length < this.$context.formLimit
        ? this.forms.length
        : this.$context.formLimit;
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "back",
    "cancel",
    "next"
  ],
  "mod_approval": [
    "choose_form",
    "form",
    "form_name",
    "last_modified",
    "no_forms_match",
    "search_by_form_name"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-chooseFormStep {
  &__content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    height: 50rem;
    padding-top: var(--gap-5);
  }

  &__table {
    margin-top: var(--gap-8);

    &--searching {
      .tui-dataTableRow {
        opacity: 0.4;
      }
    }
  }

  &__footer {
    display: flex;
    flex-grow: 1;
    justify-content: space-between;
  }
}
</style>
