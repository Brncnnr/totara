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
  @module totara_competency
-->

<template>
  <ChartJs
    :type="type"
    :data="data"
    :options="options"
    :header="assignmentProgress.name"
    @click="chartClicked"
    @hover="chartHovered"
  />
</template>

<script>
import ChartJs from 'tui_charts/components/ChartJs';
import theme from 'tui/theme';
import { unique } from 'tui/util';

let rotate = (x, y, angle) => {
  return {
    x: x * Math.cos(angle) - y * Math.sin(angle),
    y: x * Math.sin(angle) + y * Math.cos(angle),
  };
};

let insideRect = (x, y, x1, y1, x2, y2, x3, y3, x4, y4) => {
  let a1 = Math.sqrt(Math.pow(x1 - x2, 2) + Math.pow(y1 - y2, 2));
  let a2 = Math.sqrt(Math.pow(x2 - x3, 2) + Math.pow(y2 - y3, 2));
  let a3 = Math.sqrt(Math.pow(x3 - x4, 2) + Math.pow(y3 - y4, 2));
  let a4 = Math.sqrt(Math.pow(x4 - x1, 2) + Math.pow(y4 - y1, 2));

  let b1 = Math.sqrt(Math.pow(x1 - x, 2) + Math.pow(y1 - y, 2));
  let b2 = Math.sqrt(Math.pow(x2 - x, 2) + Math.pow(y2 - y, 2));
  let b3 = Math.sqrt(Math.pow(x3 - x, 2) + Math.pow(y3 - y, 2));
  let b4 = Math.sqrt(Math.pow(x4 - x, 2) + Math.pow(y4 - y, 2));

  let u1 = (a1 + b1 + b2) / 2;
  let u2 = (a2 + b2 + b3) / 2;
  let u3 = (a3 + b3 + b4) / 2;
  let u4 = (a4 + b4 + b1) / 2;

  let A1 = Math.sqrt(u1 * (u1 - a1) * (u1 - b1) * (u1 - b2));
  let A2 = Math.sqrt(u2 * (u2 - a2) * (u2 - b2) * (u2 - b3));
  let A3 = Math.sqrt(u3 * (u3 - a3) * (u3 - b3) * (u3 - b4));
  let A4 = Math.sqrt(u4 * (u4 - a4) * (u4 - b4) * (u4 - b1));

  return Math.round(A1 + A2 + A3 + A4) === Math.round(a1 * a2);
};

let getLabelSize = function(context, label) {
  let labelText = label.label;
  let width = 0;

  context.font = label.font.fontString;

  if (labelText !== undefined && `${labelText}`.trim() !== '') {
    // ChartJS uses cache for it, should we too?
    width = context.measureText(labelText).width;
  }

  return {
    width,
    height: label.font.lineHeight,
  };
};

// This should return competency id or null
let getCompetencyByClickArea = (event, thisChart, Chart, component) => {
  // Position of click relative to canvas.
  let mouseX = event.offsetX;
  let mouseY = event.offsetY;
  let helpers = Chart.helpers;
  let context = thisChart.ctx;

  if (thisChart.config.type === 'bar') {
    // Let's check for clicks on the bar itself
    let elements = thisChart.getElementsAtEvent(event);

    if (elements.length > 0) {
      let index = elements[0]._index;

      if (typeof component.data.competencies[index] !== 'undefined') {
        return component.data.competencies[index];
      }

      return null;
    }

    let scale = thisChart.scales['x-axis-0'];

    let padding = 4;

    let progressItem = null;

    scale._computeLabelItems().forEach(item => {
      let x = item.x;
      let y = item.y;
      let angle = item.rotation;

      // Let's check whether we need to account for rotation
      // It's float, so let's do a sloppy comparison
      let rotated = Math.abs(angle) >= 0.001;

      // Get label size
      let { width, height } = getLabelSize(context, item);

      if (width === 0) {
        return;
      }

      // Append padding to increase click-box around it
      width += padding;
      height += padding;

      // We need default values
      let x1 = -width;
      let x2 = 0;
      let x3 = x2;
      let x4 = x1;

      let y1 = height / -2;
      let y2 = height / -2;
      let y3 = height / 2;
      let y4 = height / 2;

      let new1 = { x: width / -2, y: -padding / 2 };
      let new2 = { x: width / 2, y: -padding / 2 };
      let new3 = { x: new2.x, y: height - padding / 2 };
      let new4 = { x: new1.x, y: height - padding / 2 };

      if (rotated) {
        // We calculate box around objects differently for things that have been rotated and haven't
        new1 = rotate(x1, y1, angle);
        new2 = rotate(x2, y2, angle);
        new3 = rotate(x3, y3, angle);
        new4 = rotate(x4, y4, angle);
      }

      let inside = insideRect(
        mouseX,
        mouseY,
        x + new1.x,
        y + new1.y,
        x + new2.x,
        y + new2.y,
        x + new3.x,
        y + new3.y,
        x + new4.x,
        y + new4.y
      );

      if (inside) {
        // If we are inside click-box we'll draw a red square around for debugging purposes
        if (component.debug) {
          context.save();
          context.strokeStyle = 'red';
          context.lineWidth = 1;

          context.beginPath();
          context.moveTo(x + new1.x, y + new1.y);
          context.lineTo(x + new2.x, y + new2.y);
          context.lineTo(x + new3.x, y + new3.y);
          context.lineTo(x + new4.x, y + new4.y);
          context.lineTo(x + new1.x, y + new1.y);
          context.stroke();

          context.restore();

          console.log('SCIENCE, B!');
        }

        // Now let's try to figure out where we've clicked.
        let currentItem = component.assignmentProgress.items.find(
          pi => pi.competency.fullname === item.label
        );

        if (currentItem) {
          progressItem = currentItem;
        }
      } else {
        component.debug && console.log('OUTSIDE');
      }
    });

    if (progressItem) {
      return progressItem.competency.id;
    }

    return null;
  }

  let scale = thisChart.scale;
  let opts = scale.options;
  let tickOpts = opts.ticks;

  let labelPadding = 5; // number pixels to expand label bounding box by

  // get the label render position
  // calcs taken from drawPointLabels() in scale.radialLinear.js
  let tickBackdropHeight =
    tickOpts.display && opts.display
      ? helpers.valueOrDefault(
          tickOpts.fontSize,
          Chart.defaults.global.defaultFontSize
        ) + 5
      : 0;
  let outerDistance = scale.getDistanceFromCenterForValue(
    opts.ticks.reverse ? scale.min : scale.max
  );
  for (let i = 0; i < scale.pointLabels.length; i++) {
    // Extra spacing for top value due to axis labels
    let extra = i === 0 ? tickBackdropHeight / 2 : 0;
    let pointLabelPosition = scale.getPointPosition(
      i,
      outerDistance + extra + 5
    );

    // get label size info.
    // TODO fix width=0 calc in Brave?
    // https://github.com/brave/brave-browser/issues/1738
    let plSize = scale._pointLabelSizes[i];

    // get label textAlign info
    let angleRadians = scale.getIndexAngle(i);
    let angle = helpers.toDegrees(angleRadians);
    let textAlign = 'right';
    if (angle === 0 || angle === 180) {
      textAlign = 'center';
    } else if (angle < 180) {
      textAlign = 'left';
    }

    // get label vertical offset info
    // also from drawPointLabels() calcs
    let verticalTextOffset = 0;
    if (angle === 90 || angle === 270) {
      verticalTextOffset = plSize.h / 2;
    } else if (angle > 270 || angle < 90) {
      verticalTextOffset = plSize.h;
    }

    // Calculate bounding box based on textAlign
    let labelTop = pointLabelPosition.y - verticalTextOffset - labelPadding;
    let labelHeight = 2 * labelPadding + plSize.h;
    let labelBottom = labelTop + labelHeight;

    let labelWidth = plSize.w + 2 * labelPadding;
    let labelLeft;
    switch (textAlign) {
      case 'center':
        labelLeft = pointLabelPosition.x - labelWidth / 2;
        break;
      case 'left':
        labelLeft = pointLabelPosition.x - labelPadding;

        break;
      case 'right':
        labelLeft = pointLabelPosition.x - labelWidth + labelPadding;
        break;
      default:
        console.log('ERROR: unknown textAlign ' + textAlign);
    }
    let labelRight = labelLeft + labelWidth;

    // Render a rectangle for testing purposes
    if (component.debug) {
      context.save();
      context.strokeStyle = 'red';
      context.lineWidth = 1;
      context.strokeRect(labelLeft, labelTop, labelWidth, labelHeight);
      context.restore();
    }

    // compare to the current click
    if (
      mouseX >= labelLeft &&
      mouseX <= labelRight &&
      mouseY <= labelBottom &&
      mouseY >= labelTop
    ) {
      return component.data['competencies'][i];
    }
  }

  return null;
};

/**
 *  To get line chart working properly there is trickery involved.
 *  The idea is as follows, we are wrapping our data set (appending & prepending)
 *  with the null values for bar chart and the values matching the first and the last respectively.
 *
 *  To make it work there is also a trick with X-axis ticks required, we have to tell chartjs to ignore
 *  our appended and prepended columns, to do so we have to specify min & max ticks matching first and
 *  last label.
 *
 *  To complicate stuff more we are appending chart with the empty data sets to prevent it from looking
 *  giant when displaying data for one or two competencies. To do so we are manually appending dataset
 *  with empty values BEFORE wrapping it with data from the comments above, that requires adjusting max
 *  tick manually to our fake one.
 */
export default {
  components: { ChartJs },
  props: {
    assignmentProgress: {
      required: true,
      type: Object,
    },
    userId: {
      type: Number,
      required: true,
    },
    isCurrentUser: {
      type: Boolean,
      required: true,
    },
    debug: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    let labels = this.assignmentProgress.items.map(
      item => item.competency.fullname
    );

    if (labels.length <= 2) {
      labels.unshift(['']);
      labels.push(['']);
    }

    return {
      labels: labels,
    };
  },

  computed: {
    options: function() {
      let that = this;
      let options = {
        tooltips: {
          filter(tooltipItem, data) {
            // We are filtering out
            let label = data.labels[tooltipItem.index];
            return [''].indexOf(label.join(' ').trim());
          },
        },

        legend: {
          position: 'bottom',
          display: true,
          labels: {
            boxWidth: 15,
            lineWidth: 0,
            padding: 25,
          },
        },
      };

      if (this.type === 'radar') {
        options.scale = {
          pointLabels: {
            fontSize: 12,
            fontColor: theme.getVar('color-state'),
          },
          ticks: {
            beginAtZero: true,
            display: false,
            max: 100,
          },
        };
      } else {
        // This is to make bar-line chart to expand line to the borders of the graph.
        // See the comment above and below.
        let min = this.assignmentProgress.items.slice(0, 1).pop();
        let max = this.assignmentProgress.items.slice(-1).pop();

        if (min) {
          min = min.competency.fullname;
        }

        if (max) {
          max = max.competency.fullname;

          // We also need this extra condition to make it work with our additional empty data to prevent
          // charts from being giant...
          if (this.assignmentProgress.items.length <= 2) {
            max = '   ';
          }
        }

        options.scales = {
          yAxes: [
            {
              ticks: {
                beginAtZero: true,
                display: false,
                fontSize: 12,
                max: 110,
              },
            },
          ],
          xAxes: [
            {
              ticks: {
                min,
                max,
                fontSize: 12,
                fontColor: theme.getVar('color-state'),
              },
            },
          ],
        };
      }

      options.tooltips.callbacks = {
        /**
         * Generates the Competency part of the tooltip
         *
         * @param {Object} tooltipItems the items to be shown on the tooltip
         * @returns {Array} An array of strings representing lines in the tooltip
         */
        title(tooltipItems) {
          return unique(tooltipItems.map(x => x.index))
            .map(x => that.shorten(that.labels[x], 60))
            .reduce((returnVal, item) => {
              item.map(title => returnVal.push(title));
              return returnVal;
            }, []);
        },

        label: that.getToolTipText,
      };

      return options;
    },

    data: function() {
      let data = {
        labels: [],
        competencies: [],
        datasets: [
          {
            label: this.$str('achievement_level', 'totara_competency'),
            backgroundColor: theme.getVar('color-chart-transparent-1'),
            borderColor: theme.getVar('color-chart-background-1'),
            borderWidth: 2,
            data: [],
            values: [],
          },
          {
            label: this.$str('proficiency_level', 'totara_competency'),

            // For bar charts the area under the line should be transparent
            backgroundColor:
              this.type === 'bar'
                ? 'transparent'
                : theme.getVar('color-chart-transparent-4'),
            borderColor: theme.getVar('color-chart-background-4'),
            pointBorderColor: theme.getVar('color-chart-background-4'),
            pointBackgroundColor: theme.getVar('color-chart-background-4'),
            borderWidth: 2,
            steppedLine: 'middle',
            data: [],
            values: [],
          },
        ],
      };

      this.assignmentProgress.items.forEach(
        function(item) {
          data.competencies.push(item.competency.id);
          data.labels.push(this.shorten(item.competency.fullname, 35));
          data.datasets[0].data.push(item.my_value.percentage);
          data.datasets[1].data.push(item.min_value.percentage);
          data.datasets[0].values.push(item.my_value.name);
          data.datasets[1].values.push(item.min_value.name);
        }.bind(this)
      );

      if (this.type === 'bar') {
        data.datasets[1].type = 'line';

        // Tricking ChartJS into showing the line graph expanding to the size of first and last bars
        // https://stackoverflow.com/questions/3216013/get-the-last-item-in-an-array

        if (data.datasets[0].data.length) {
          const first = data.datasets[1].data.slice(0, 1).pop();
          const last = data.datasets[1].data.slice(-1).pop();

          // Appending extra empty "bars" to the chart to avoid it being giant when displayed for a single
          // competency.
          if (this.assignmentProgress.items.length <= 2) {
            data.datasets[0].data.unshift(null);
            data.datasets[1].data.unshift(first);
            data.datasets[0].data.push(null);
            data.datasets[1].data.push(last);
            data.labels.unshift(['']);
            data.labels.push(['']);
            data.competencies.unshift(null);
            data.competencies.push(null);
          }
        }
      }

      return data;
    },

    type: function() {
      return this.assignmentProgress.items.length <= 2 ||
        this.assignmentProgress.items.length >= 12
        ? 'bar'
        : 'radar';
    },
  },

  methods: {
    competencyLink(id) {
      const params = { competency_id: id };
      if (!this.isMine) {
        params.user_id = this.userId;
      }
      return this.$url('/totara/competency/profile/details/index.php', params);
    },

    chartHovered(event, context, thisChart, Chart) {
      requestAnimationFrame(() => {
        if (getCompetencyByClickArea(event, thisChart, Chart, this)) {
          event.target.style.cursor = 'pointer';
        } else {
          event.target.style.cursor = 'auto';
        }
      });
    },

    chartClicked(event, context, thisChart, Chart) {
      let id = getCompetencyByClickArea(event, thisChart, Chart, this);

      if (id) {
        window.location.href = this.competencyLink(id);
      }
    },

    /**
     * Gets the correct index for the data
     *
     * @param {Number} index the index in the dataset that is required
     * @returns {Number} The index in this.assignmentProgress that the entry matches to
     */
    getDataIndex(index) {
      if (this.assignmentProgress.items.length > 2) {
        return index;
      } else {
        return index - 1;
      }
    },

    /**
     * Gets the tooltip text for the requested entry
     *
     * @param {Object} tooltipItem as provided by ChartJs
     * @param {Object} data chart data as supplied to ChartJS
     * @returns {String} The text to display in the tooltip
     */
    getToolTipText(tooltipItem, data) {
      const index = this.getDataIndex(tooltipItem.index);
      const label = data.datasets[tooltipItem.datasetIndex].label || '';
      let value = '';

      switch (tooltipItem.datasetIndex) {
        case 0:
          value = this.assignmentProgress.items[index].my_value.name;
          break;
        case 1:
          value = this.assignmentProgress.items[index].min_value.name;
          break;
      }

      return label + ': ' + value;
    },

    /**
     * Prepares a string to be used by chart JS as tooltips and labels
     *
     * @param {String} str The string to be manipulated
     * @param {Number} maxLen The max length of string to be manipulated
     * @param {String} separator The word separator
     *
     * @returns {Array} An array of up to 2 strings containing the string supplied
     */
    shorten: function(str, maxLen, separator = ' ') {
      const maxLines = 2;
      let returnVal = [];
      let start = 0;
      let end;

      if (!str) return [''];
      if (str.length <= maxLen) return [str.trim()];

      for (let line = 0; line < maxLines; line++) {
        if (start + maxLen >= str.length) {
          returnVal.push(str.substr(start).trim());
          break;
        } else {
          end = str.lastIndexOf(separator, start + maxLen);
          let string = str.substr(start, end - start).trim();
          start = end + 1;

          if (line === maxLines - 1) {
            string += String.fromCharCode(8230);
          }
          returnVal.push(string);
        }
      }

      return returnVal;
    },
  },
};
</script>

<lang-strings>
{
  "totara_competency" : [
    "proficient",
    "not_proficient",
    "proficiency_level",
    "achievement_level"
  ]
}
</lang-strings>
