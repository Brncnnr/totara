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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @module samples
-->

<template>
  <div>
    <p>
      Chart.js library wrapper for embedding charts. For data/options prop
      references please refer to the
      <a href="https://www.chartjs.org/docs/latest/charts/"
        >Chart.js documentation </a
      >, these have different structure depending on the chart type.
    </p>
    <p>
      <b>Note</b>: Our intention is to deprecate this component at some point in
      the future and replace it with components that do not expose the Chart.js
      API directly. This will allow us to update Chart.js in the future or
      replace it with another library without it being a breaking change. Please
      be aware of this when using this component.
    </p>
    <SamplesExample>
      <div v-if="appliedProps.type !== ''" class="tui-exampleChartJs">
        <ChartJs v-bind="appliedProps" class="tui-exampleChartJs__chart" />
      </div>
    </SamplesExample>
    <SamplesPropCtl>
      <FormRow label="Preset">
        <RadioGroup
          v-model="chartType"
          :horizontal="false"
          @input="updatePreset"
        >
          <Radio v-for="(chartProps, key) in charts" :key="key" :value="key">
            {{ chartProps.header }}
          </Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Chart type">
        <InputText v-model="props.type" />
      </FormRow>
      <FormRow label="Chart header">
        <InputText v-model="props.header" />
      </FormRow>
      <FormRow label="Chart data object">
        <Textarea v-model="props.data" :cols="120" :rows="20" />
      </FormRow>
      <FormRow label="Chart options object">
        <Textarea v-model="props.options" :cols="120" :rows="15" />
      </FormRow>
      <FormRow label="Chart options object">
        <Textarea v-model="props.canvasAttributes" :cols="120" :rows="10" />
      </FormRow>
      <FormRow>
        <Button :primary="true" text="Apply" @click="applyChartProps" />
      </FormRow>
    </SamplesPropCtl>
    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
// Components
import ChartJs from 'tui_charts/components/ChartJs';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import Textarea from 'tui/components/form/Textarea';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    ChartJs,
    SamplesPropCtl,
    FormRow,
    SamplesCode,
    SamplesExample,
    Radio,
    RadioGroup,
    InputText,
    Textarea,
    Button,
  },

  data() {
    return {
      collapsed: false,
      chartType: 'doughnut',
      props: {
        type: 'doughnut',
        header:
          'A doughnut showing a number of pilots closing their cockpit doors during the flight!?!',
        data: `{}`,
        options: `{}`,
        canvasAttributes: `{}`,
      },

      appliedProps: {
        type: '',
        header: '',
        data: {},
        options: {},
        canvasAttributes: {},
      },
    };
  },
  computed: {
    codeTemplate() {
      return `<ChartJs :type="type"
   :header="header"
   :data="data"
   :options="options"
   :canvas-attributes="canvasAttributes">
</ChartJs>`;
    },

    codeScript() {
      return `import ChartJs from 'tui_charts/components/ChartJs';

export default {
  components: {
    ChartJs,
  }
  data() {
    return ${this.formattedProps}
  }
}`;
    },

    charts() {
      return {
        doughnut: {
          type: 'doughnut',
          header:
            'A doughnut showing a number of pilots closing their cockpit doors during the flight!?!',
          data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
            datasets: [
              {
                data: [15, 75, 12, 56, 78, 23, 12, 19, 23, 11],
              },
            ],
          },
          options: {},
        },

        pie: {
          type: 'pie',
          header: 'Pie',
          data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
            datasets: [
              {
                data: [15, 75, 12, 56, 78, 23, 12, 19, 23],
              },
            ],
          },
          options: {},
        },

        bar: {
          type: 'bar',
          header: 'Bar',
          data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
            datasets: [
              {
                label: 'Dataset 1',
                data: [15, 9, 12, 56, 21, 23, 12, 19, 23, 12],
              },
            ],
          },
          options: {},
        },

        stackedBar: {
          type: 'bar',
          header: 'Stacked bar',
          data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
            datasets: [
              {
                label: 'Dataset 1',
                data: [15, 75, 12, 56, 78, 23, 12, 19, 23, 15],
              },
              {
                label: 'Dataset 2',
                data: [11, 23, 53, 15, 24, 34, 12, 3, 16, 39],
              },
              {
                label: 'Dataset 3',
                data: [15, 75, 12, 56, 78, 23, 12, 19, 23, 15],
              },
              {
                label: 'Dataset 4',
                data: [11, 23, 53, 15, 24, 34, 12, 3, 16, 39],
              },
              {
                label: 'Dataset 5',
                data: [2, 5, 12, 12, 14, 18, 11, 9, 0, 7, 14],
              },
            ],
          },
          options: {
            scales: {
              xAxes: [{ stacked: true }],
              yAxes: [{ stacked: true }],
            },
          },
        },

        polarArea: {
          type: 'polarArea',
          header: 'Polar area',
          data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9'],
            datasets: [
              {
                data: [15, 11, 12, 3, 19, 23, 12, 19, 23],
              },
            ],
          },
          options: {},
        },

        line: {
          type: 'line',
          header: 'Line',
          data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
            datasets: [
              {
                label: 'Dataset 1',
                data: [15, 75, 12, 56, 78, 23, 12, 19, 23, -5],
              },
              {
                label: 'Dataset 2',
                data: [1, 23, 4, 5, 7, -5, 12, 34, 12, -2, 13],
              },
              {
                label: 'Dataset 3',
                data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],
              },
            ],
          },
          options: {},
        },

        radar: {
          type: 'radar',
          header: 'Radar',
          data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
            datasets: [
              {
                label: 'Dataset 1',
                data: [15, 75, 12, 56, 78, 23, 12, 19, 23, -5],
              },
              {
                label: 'Dataset 2',
                data: [1, 23, 4, 5, 7, -5, 12, 34, 12, -2, 13],
              },
              {
                label: 'Dataset 3',
                data: [12, 42, 10, -5, 6, -4, 12, 4, 10, 1],
              },
            ],
          },
          options: {},
        },

        scatter: {
          type: 'scatter',
          header: 'Scatter',
          data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
            datasets: [
              {
                label: 'Dataset 1',
                data: [
                  { x: 5, y: 12 },
                  { x: 7, y: 3 },
                  { x: -2, y: 18 },
                  { x: 25, y: 31 },
                  { x: -10, y: 8 },
                ],
              },
              {
                label: 'Dataset 2',
                data: [
                  { x: 10, y: 55 },
                  { x: 2, y: 14 },
                  { x: 0, y: -4 },
                  { x: 25, y: -10 },
                  { x: 17, y: 0 },
                ],
              },
              {
                label: 'Dataset 3',
                data: [
                  { x: 16, y: 7 },
                  { x: 24, y: -9 },
                  { x: 15, y: 46 },
                  { x: 30, y: -7 },
                  { x: 50, y: 3 },
                ],
              },
            ],
          },
          options: {},
        },

        bubble: {
          type: 'bubble',
          header: 'Bubble',
          data: {
            labels: ['1', '2', '3', '4', '5'],
            datasets: [
              {
                label: 'Dataset 1',
                data: [
                  { x: 5, y: 12, r: 30 },
                  { x: 7, y: 3, r: 15 },
                  { x: -2, y: 18, r: 45 },
                  { x: 25, y: 31, r: 20 },
                  { x: -10, y: 8, r: 12 },
                ],
              },
              {
                label: 'Dataset 2',
                data: [
                  { x: 10, y: 55, r: 17 },
                  { x: 2, y: 14, r: 19 },
                  { x: 0, y: -4, r: 23 },
                  { x: 25, y: -10, r: 11 },
                  { x: 17, y: 0, r: 9 },
                ],
              },
              {
                label: 'Dataset 3',
                data: [
                  { x: 16, y: 7, r: 23 },
                  { x: 24, y: -9, r: 18 },
                  { x: 15, y: 46, r: 4 },
                  { x: 30, y: -7, r: 10 },
                  { x: 50, y: 3, r: 18 },
                ],
              },
            ],
          },
          options: {},
        },
      };
    },

    formattedProps() {
      let props = this.charts[this.chartType];

      if (typeof props.canvasAttributes === 'undefined') {
        props.canvasAttributes = {};
      }

      return JSON.stringify(props, null, 2);
    },
  },

  mounted() {
    this.applyPreset('doughnut');
    this.applyChartProps();
  },

  methods: {
    updatePreset() {
      this.applyPreset(this.chartType);
      this.applyChartProps();
    },

    applyChartProps() {
      let jsonData, jsonOptions, jsonCanvasAttributes;

      try {
        jsonData = JSON.parse(this.props.data);
        jsonOptions = JSON.parse(this.props.options);
        jsonCanvasAttributes = JSON.parse(this.props.canvasAttributes);
      } catch (e) {
        alert('Error parsing JSON data or options or canvas attributes');
      }

      this.appliedProps.type = this.props.type;
      this.appliedProps.header = this.props.header;
      this.appliedProps.data = jsonData;
      this.appliedProps.options = jsonOptions;
      this.appliedProps.canvasAttributes = jsonCanvasAttributes;
    },

    applyPreset(key) {
      this.props.type = this.charts[key].type;
      this.props.header = this.charts[key].header;
      this.props.data = JSON.stringify(this.charts[key].data, null, 2);

      if (typeof this.charts[key].options !== 'undefined') {
        this.props.options = JSON.stringify(this.charts[key].options, null, 2);
      } else {
        this.props.options = JSON.stringify({}, null, 2);
      }

      if (typeof this.charts[key].canvasAttributes !== 'undefined') {
        this.props.options = JSON.stringify(
          this.charts[key].canvasAttributes,
          null,
          2
        );
      } else {
        this.props.options = JSON.stringify({}, null, 2);
      }
    },
  },
};
</script>

<style lang="scss">
.tui-exampleChartJs {
  display: flex;
  flex-direction: column;

  & > * + * {
    margin-top: 7rem;
  }

  &__chart {
    align-self: center;
    width: 515px;
  }
}
</style>
