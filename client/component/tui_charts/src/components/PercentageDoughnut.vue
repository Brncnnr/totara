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
  @module tui_charts
-->

<template>
  <ChartJs
    type="doughnut"
    :data="data"
    :options="options"
    :header="header"
    :canvas-attributes="canvasAttributes"
    :aria-label="fallback"
  />
</template>

<script>
import ChartJs from 'tui_charts/components/ChartJs';
import theme from 'tui/theme';

let defaultColor = theme.getVar('color-chart-background-1');
let defaultLabelFontSize = parseInt(
  theme.getVar('font-size-chart-doughnut-label')
);
let defaultPercentageFontSize = parseInt(
  theme.getVar('font-size-chart-doughnut-percentage-label')
);
let defaultLabelColor = theme.getVar('color-chart-doughnut-label');
let defaultBackgroundColor = theme.getVar('color-neutral-4');

export default {
  components: { ChartJs },
  props: {
    header: {
      type: String,
    },
    percentage: {
      type: Number,
      required: true,
    },
    label: {
      type: String,
      default: '',
    },
    showPercentage: {
      type: Boolean,
      default: true,
    },
    color: {
      type: String,
    },
    labelColor: {
      type: String,
    },
    labelFontSize: {
      type: Number,
    },
    labelFontWeight: {
      type: [String, Number],
      default: undefined,
    },
    percentageFontSize: {
      type: Number,
    },
    backgroundColor: {
      type: String,
    },
    cutout: {
      type: Number,
      default: 85,
    },
    square: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    data() {
      let colors = [
        this.color ? this.color : defaultColor,
        this.backgroundColor ? this.backgroundColor : defaultBackgroundColor,
      ];

      let data = [this.percentage, 100 - this.percentage];

      if (this.percentage === 100) {
        colors = [this.color ? this.color : defaultColor];
        data = [this.percentage];
      }

      return {
        datasets: [
          {
            backgroundColor: colors,
            hoverBackgroundColor: colors,
            hoverBorderColor: colors,
            borderWidth: 2,
            borderColor: colors,
            data: data,
          },
        ],
      };
    },

    options() {
      let labels = [];

      if (this.showPercentage) {
        labels.push({
          text: this.percentage + '%',
          font: {
            size: this.percentageFontSize
              ? this.percentageFontSize
              : defaultPercentageFontSize,
            weight: 'bold',
          },
          color: this.labelColor ? this.labelColor : defaultLabelColor,
        });
      }

      if (this.label.trim() !== '') {
        labels.push({
          text: this.label,
          font: {
            size: this.labelFontSize
              ? this.labelFontSize
              : defaultLabelFontSize,
            weight: this.labelFontWeight,
          },
          color: this.labelColor ? this.labelColor : defaultLabelColor,
        });
      }

      let layout = this.square
        ? {
            padding: {
              left: 0,
              right: 0,
              top: 0,
              bottom: 0,
            },
          }
        : {};

      return {
        cutoutPercentage: this.cutout,
        tooltips: {
          enabled: false,
        },
        plugins: {
          doughnutlabel: {
            labels,
          },
        },
        legend: {
          display: false,
        },
        layout,
      };
    },

    canvasAttributes() {
      return this.square
        ? {
            width: '100%',
            height: '100%',
          }
        : {};
    },

    fallback() {
      return (
        (this.label.trim() !== '' ? `${this.label} ` : '') +
        this.percentage +
        '%'
      );
    },
  },
};
</script>
