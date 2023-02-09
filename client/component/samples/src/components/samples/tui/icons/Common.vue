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
    Available Icon components

    <SamplesExample>
      <Table :data="iconData" :expandable-rows="true">
        <template v-slot:header-row>
          <HeaderCell size="3">Icon</HeaderCell>
          <HeaderCell size="3">Name</HeaderCell>
          <HeaderCell size="10">Description</HeaderCell>
        </template>
        <template v-slot:row="{ row }">
          <Cell size="3" valign="center">
            <component :is="row.icon" :size="size" />
          </Cell>

          <Cell size="3" column-header="Name">
            {{ row.name }}
          </Cell>

          <Cell size="10" column-header="Description">
            {{ row.desc }}
          </Cell>
        </template>
      </Table>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Size">
        <RadioGroup v-model="size" :horizontal="true">
          <Radio value="100">100</Radio>
          <Radio value="200">200</Radio>
          <Radio value="300">300</Radio>
          <Radio value="400">400</Radio>
          <Radio value="500">500</Radio>
          <Radio value="600">600</Radio>
          <Radio value="700">700</Radio>
          <Radio :value="null"><code>null</code> (font size)</Radio>
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
import tui from 'tui/tui';

import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import Table from 'tui/components/datatable/Table';

const prefix = 'tui/components/icons';
const excludedPrefixes = [
  'tui/components/icons/implementation',
  'tui/components/icons/internal',
  'tui/components/icons/Flex',
  'tui/components/icons/flex',
];

const descriptions = {
  BackArrow: 'Used for going back to previous page or place',
  Collapse: 'Used for collapsing additional content',
  Expand: 'Used for expanding additional content',
  ForwardArrow: 'Used for going forward to next page or place',
  Hide: 'Used for collasping form like elements',
  Show: 'Used for expanding form like elements',
};

export default {
  components: {
    Cell,
    HeaderCell,
    FormRow,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
    Table,
  },

  data() {
    return {
      iconData: tui
        // eslint-disable-next-line tui/no-tui-internal
        ._getLoadedComponentModules('tui')
        .filter(
          x =>
            x.startsWith(prefix) &&
            excludedPrefixes.every(p => !x.startsWith(p)) &&
            !x.includes('compute')
        )
        .map(x => {
          const name = x.slice(prefix.length + 1);
          return {
            icon: tui.defaultExport(tui.require(x)),
            name,
            desc: descriptions[name] ? descriptions[name] : '...',
          };
        })
        .filter(x => !x.icon.deprecated),
      size: '200',
      codeTemplate: `<Close :size="size"/>`,
      codeScript: `import Close from 'tui/components/icons/Close';

export default {
  components: {
    Close,
  },
}`,
    };
  },
};
</script>
