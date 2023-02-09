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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module samples
-->

<template>
  <div>
    <SamplesExample>
      <Loader :loading="loadingPreview">
        <SelectTable
          v-model="selection"
          :data="dummyData"
          :color-odd-rows="colorOddRows"
          :checkbox-v-align="checkboxVAlign"
          :entire-selected="entireSelected"
          :large-check-box="largeCheckBox"
          :loading-overlay-active="true"
          :loading-preview="loadingPreview"
          :header-has-loaded="headerHasLoaded"
          row-label-key="display_name"
          :select-all-enabled="selectAllEnabled"
          :select-entire-enabled="selectEntireEnabled"
          :stack-at="Number(stackAt)"
        >
          <template v-slot:header-row>
            <HeaderCell size="12" valign="center">Col 1</HeaderCell>
            <HeaderCell size="4" valign="center">Col 2</HeaderCell>
          </template>

          <template v-slot:row="{ row }">
            <Cell size="12" :column-header="'Col 1'" valign="center">
              <template v-slot:default>
                {{ row.display_name }}
              </template>
            </Cell>

            <Cell size="4" :column-header="'Col 2'" valign="center">
              <template v-slot:default>
                {{ row.ready }}
              </template>
            </Cell>
          </template>
        </SelectTable>
      </Loader>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="value">
        <RadioGroup v-model="selection" :horizontal="true">
          <Radio :value="[]">[]</Radio>
          <Radio :value="[1, 3]">[1, 3]</Radio>
          <Radio :value="[0, 1, 2, 3]">[0, 1, 2, 3]</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="color-odd-rows">
        <RadioGroup v-model="colorOddRows" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="stack-at">
        <InputNumber v-model="stackAt" />
      </FormRow>

      <h4 class="tui-samplesCtl__optional">Selection (Optional)</h4>

      <FormRow label="large-check-box">
        <RadioGroup v-model="largeCheckBox" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="checkbox-v-align">
        <RadioGroup v-model="checkboxVAlign" :horizontal="true">
          <Radio value="start">Start</Radio>
          <Radio value="center">Center</Radio>
          <Radio value="end">End</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="select-all-enabled">
        <RadioGroup v-model="selectAllEnabled" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="select-entire-enabled">
        <RadioGroup v-model="selectEntireEnabled" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="entire-selected">
        <RadioGroup v-model="entireSelected" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="loading-preview">
        <RadioGroup v-model="loadingPreview" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="header-has-loaded">
        <RadioGroup v-model="headerHasLoaded" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
    </SamplesPropCtl>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Loader from 'tui/components/loading/Loader';
import SelectTable from 'tui/components/datatable/SelectTable';
import InputNumber from 'tui/components/form/InputNumber';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';

export default {
  components: {
    Cell,
    HeaderCell,
    Loader,
    SelectTable,
    InputNumber,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
    FormRow,
    Radio,
    RadioGroup,
  },

  data() {
    return {
      dummyData: [
        {
          ready: true,
          display_name: 'aaa',
        },
        {
          ready: true,
          display_name: 'Some example text',
        },
        {
          ready: false,
          display_name: 'ccc',
        },
        {
          ready: true,
          display_name: 'ddd',
        },
      ],
      selection: [1, 3],

      checkboxVAlign: 'center',
      colorOddRows: false,
      largeCheckBox: false,
      loadingPreview: false,
      headerHasLoaded: false,
      stackAt: 570,
      selectAllEnabled: false,
      selectEntireEnabled: false,
      entireSelected: false,

      codeTemplate: `<SelectTable
  v-model="selection"
  :data="data"
  :color-odd-rows="true"
  checkbox-v-align="center"
  :select-all-enabled="true"
  :select-entire-enabled="false"
  :entire-selected="false"
>
  <!-- Header content -->
  <template v-slot:header-row>
    <HeaderCell size="12" valign="center">Col 1</HeaderCell>
    <HeaderCell size="4" valign="center">Col 2</HeaderCell>
  </template>

  <!-- Rows -->
  <template v-slot:row="{ row }">
    <Cell size="12" :column-header="'Col 1'" valign="center">
      {{ row.title }}
    </Cell>

    <Cell size="4" :column-header="'Col 2'" valign="center">
      {{ row.ready }}
    </Cell>
  </template>
</SelectTable>`,
      codeScript: `import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import SelectTable from 'tui/components/datatable/SelectTable';

export default {
  components: {
    Cell,
    HeaderCell,
    SelectTable,
  },


  data() {
    return {
      data: [
        {
          ready: 'true',
          title: 'aaa',
        },
        {
          ready: 'false',
          title: 'some random text',
        },
      ],
      selection: [1, 3],
    }
  }
}`,
    };
  },
};
</script>
