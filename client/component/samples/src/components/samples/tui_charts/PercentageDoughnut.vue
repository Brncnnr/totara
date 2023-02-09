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
    <SamplesExample>
      <div v-if="display" class="tui-exampleChartJs">
        <PercentageDoughnut
          v-bind="appliedProps"
          class="tui-exampleChartJs__chart"
        />
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
      <FormRow label="Chart header">
        <InputText v-model="props.header" />
      </FormRow>
      <FormRow label="Doughnut label">
        <InputText v-model="props.label" />
      </FormRow>
      <FormRow label="Doughnut percentage">
        <InputText v-model="props.percentage" type="number" />
      </FormRow>
      <FormRow label="Show percentage">
        <CheckBox v-model="props.showPercentage" />
      </FormRow>
      <FormRow label="Doughnut color">
        <InputText v-model="props.color" />
      </FormRow>
      <FormRow label="Doughnut label color">
        <InputText v-model="props.labelColor" />
      </FormRow>
      <FormRow label="Doughnut label font size">
        <InputText v-model="props.labelFontSize" type="number" />
      </FormRow>
      <FormRow label="Doughnut percentage font size">
        <InputText v-model="props.percentageFontSize" type="number" />
      </FormRow>
      <FormRow label="Doughnut background color">
        <InputText v-model="props.backgroundColor" />
      </FormRow>
      <FormRow label="Doughnut cutout">
        <InputText v-model="props.cutout" type="number" />
      </FormRow>
      <FormRow label="Square doughnut (cuts empty space around the doughnut)">
        <CheckBox v-model="props.square" />
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
import PercentageDoughnut from 'tui_charts/components/PercentageDoughnut';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import CheckBox from 'tui/components/form/Checkbox';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    PercentageDoughnut,
    SamplesPropCtl,
    FormRow,
    SamplesCode,
    SamplesExample,
    Radio,
    RadioGroup,
    InputText,
    Button,
    CheckBox,
  },

  data() {
    return {
      display: true,
      chartType: 'simpleDoughnut',
      props: {
        header: '',
        percentage: 25,
        label: '',
        showPercentage: true,
        color: '#3869b1',
        labelColor: '#000',
        labelFontSize: 24,
        percentageFontSize: 40,
        backgroundColor: '#d2d2d2',
        cutout: 65,
        square: false,
      },

      appliedProps: {
        header: '',
        percentage: 25,
        label: '',
        showPercentage: true,
        color: '#3869b1',
        labelColor: '#000',
        labelFontSize: 24,
        percentageFontSize: 40,
        backgroundColor: '#d2d2d2',
        cutout: 65,
        square: false,
      },
    };
  },
  computed: {
    codeTemplate() {
      return `<PercentageDoughnut :type="type"
   :header="header"
   :percentage="percentage"
   :label="label"
   :show-percentage="showPercentage"
   :color="color"
   :label-color="labelColor"
   :label-font-size="labelFontSize"
   :percentage-font-size="percentageFontSize"
   :background-color="backgroundColor"
   :cutout="cutout"
   :square="square"
   :style="style">
</PercentageDoughnut>`;
    },

    codeScript() {
      return `import PercentageDoughnut from 'tui_charts/components/PercentageDoughnut';

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
        simpleDoughnut: {
          header: 'Regular doughnut',
          percentage: 14,
          label: 'Progress',
          style: 'width: 250px',
        },

        squareDoughnut: {
          header: 'Square doughnut',
          percentage: 29,
          label: 'Progress',
          style: 'width: 250px',
          square: true,
        },

        colorfulDoughnut: {
          header: 'Colorful doughnut',
          percentage: 69,
          label: 'Doughnut',
          color: 'lime',
          backgroundColor: '#afafaf',
          labelColor: '#fa00ae',
          style: 'width: 400px',
        },

        labellessDoughnut: {
          header: 'Doughnut with no label but big percentage',
          percentage: 96,
          percentageFontSize: 64,
        },

        emptyDoughnut: {
          header: 'Doughnut with no label',
          percentage: 11,
          showPercentage: false,
        },

        bigRedTextDoughnut: {
          header: 'YES',
          percentage: 51,
          color: 'red',
          backgroundColor: 'lime',
          labelColor: 'red',
          labelFontSize: 96,
          label: 'YES',
          showPercentage: false,
        },

        almostPie: {
          header: 'Almost a pie doughnut',
          percentage: 11,
          showPercentage: false,
          cutout: 15,
        },
      };
    },

    formattedProps() {
      let props = {};

      props.header =
        this.charts[this.chartType].header !== undefined
          ? this.charts[this.chartType].header
          : '';
      props.label =
        this.charts[this.chartType].label !== undefined
          ? this.charts[this.chartType].label
          : '';
      props.percentage =
        this.charts[this.chartType].percentage !== undefined
          ? this.charts[this.chartType].percentage
          : 50;
      props.showPercentage =
        this.charts[this.chartType].showPercentage !== undefined
          ? this.charts[this.chartType].showPercentage
          : true;
      props.color =
        this.charts[this.chartType].color !== undefined
          ? this.charts[this.chartType].color
          : '';
      props.labelColor =
        this.charts[this.chartType].color !== undefined
          ? this.charts[this.chartType].color
          : '';
      props.labelFontSize =
        this.charts[this.chartType].labelFontSize !== undefined
          ? this.charts[this.chartType].labelFontSize
          : 24;
      props.percentageFontSize =
        this.charts[this.chartType].percentageFontSize !== undefined
          ? this.charts[this.chartType].percentageFontSize
          : 40;
      props.backgroundColor =
        this.charts[this.chartType].backgroundColor !== undefined
          ? this.charts[this.chartType].backgroundColor
          : '';
      props.cutout =
        this.charts[this.chartType].cutout !== undefined
          ? this.charts[this.chartType].cutout
          : 65;
      props.square =
        this.charts[this.chartType].square !== undefined
          ? this.charts[this.chartType].square
          : false;
      props.style =
        this.charts[this.chartType].style !== undefined
          ? this.charts[this.chartType].style
          : '';

      return JSON.stringify(props, null, 2);
    },
  },

  mounted() {
    this.applyPreset('simpleDoughnut');
    this.applyChartProps();
  },

  methods: {
    updatePreset() {
      this.applyPreset(this.chartType);
      this.applyChartProps();
    },

    applyChartProps() {
      // This will force to recreate Chart, otherwise it blows up badly
      // TODO Still blows up when changing plugin settings, need to force destroy and re-create chart component
      this.display = false;

      this.appliedProps.header = this.props.header;
      this.appliedProps.label = this.props.label;
      this.appliedProps.percentage = parseInt(this.props.percentage);
      this.appliedProps.showPercentage = this.props.showPercentage;
      this.appliedProps.color = this.props.color;
      this.appliedProps.labelColor = this.props.labelColor;
      this.appliedProps.labelFontSize = parseInt(this.props.labelFontSize);
      this.appliedProps.percentageFontSize = parseInt(
        this.props.percentageFontSize
      );
      this.appliedProps.backgroundColor = this.props.backgroundColor;
      this.appliedProps.cutout = parseInt(this.props.cutout);
      this.appliedProps.square = this.props.square;
      this.appliedProps.style = this.props.style;

      this.display = true;
    },

    applyPreset(key) {
      this.props.header =
        this.charts[key].header !== undefined ? this.charts[key].header : '';
      this.props.label =
        this.charts[key].label !== undefined ? this.charts[key].label : '';
      this.props.percentage =
        this.charts[key].percentage !== undefined
          ? String(this.charts[key].percentage)
          : '50';
      this.props.showPercentage =
        this.charts[key].showPercentage !== undefined
          ? this.charts[key].showPercentage
          : true;
      this.props.color =
        this.charts[key].color !== undefined ? this.charts[key].color : '';
      this.props.labelColor =
        this.charts[key].color !== undefined ? this.charts[key].color : '';
      this.props.labelFontSize =
        this.charts[key].labelFontSize !== undefined
          ? String(this.charts[key].labelFontSize)
          : '24';
      this.props.percentageFontSize =
        this.charts[key].percentageFontSize !== undefined
          ? String(this.charts[key].percentageFontSize)
          : '40';
      this.props.backgroundColor =
        this.charts[key].backgroundColor !== undefined
          ? this.charts[key].backgroundColor
          : '';
      this.props.cutout =
        this.charts[key].cutout !== undefined
          ? String(this.charts[key].cutout)
          : '65';
      this.props.square =
        this.charts[key].square !== undefined ? this.charts[key].square : false;
      this.props.style =
        this.charts[key].style !== undefined ? this.charts[key].style : '';
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
