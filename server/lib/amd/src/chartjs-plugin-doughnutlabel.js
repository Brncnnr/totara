/* MIT License

 Copyright (c) 2018 ciprianciurea

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in all
 copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 SOFTWARE.
 */

/**
 * Doughnut plugin for ChartJS
 *
 * TOTARA: To use this file, you can include the AMD module, and pass the ChartJS instance into the "register" function.
 *     * We've concatenated the original files into one AMD file
 *     * We've wrapped the concatenated code inside a "register" function to allow us to inject the lazy-loaded ChartJS instance
 *
 * @package    core
 * @copyright  2018 Ciprian Ciurea - https://github.com/ciprianciurea
 * @see https://github.com/ciprianciurea/chartjs-plugin-doughnutlabel
 * @license    MIT license
 */
define([], function() {
    function register(Chart) {
        'use strict';

        Chart = Chart && Chart.hasOwnProperty('default') ? Chart['default'] : Chart;

        /**
         * @module Options
         */

        var defaults = {
            /**
             * The font options used to draw the label text.
             * @member {Object|Array|Function}
             * @prop {String} font.family - defaults to Chart.defaults.global.defaultFontFamily
             * @prop {Number} font.lineHeight - defaults to 1.2
             * @prop {Number} font.size - defaults to Chart.defaults.global.defaultFontSize
             * @prop {String} font.style - defaults to Chart.defaults.global.defaultFontStyle
             * @prop {Number} font.weight - defaults to 'normal'
             * @default Chart.defaults.global.defaultFont.*
             */
            font: {
                family: undefined,
                lineHeight: 1.2,
                size: undefined,
                style: undefined,
                weight: null
            }
        };

        var helpers$1 = Chart.helpers;

        var utils = {

            /**
             * Parses the font settings for the doughnut labels
             * @param {Object} value user font settings object
             */
            parseFont: function(value) {
                var global = Chart.defaults.global;
                var size = helpers$1.valueOrDefault(value.size, global.defaultFontSize);
                var font = {
                    family: helpers$1.valueOrDefault(value.family, global.defaultFontFamily),
                    lineHeight: helpers$1.options.toLineHeight(value.lineHeight, size),
                    size: size,
                    style: helpers$1.valueOrDefault(value.style, global.defaultFontStyle),
                    weight: helpers$1.valueOrDefault(value.weight, null),
                    string: ''
                };

                font.string = utils.toFontString(font);
                return font;
            },

            /**
             * Converts a font settings object to a font setting string
             * @param {Object} font font settings object
             * @returns {String} font settings string
             */
            toFontString: function(font) {
                if (!font || helpers$1.isNullOrUndef(font.size) || helpers$1.isNullOrUndef(font.family)) {
                    return null;
                }

                return (font.style ? font.style + ' ' : '')
                    + (font.weight ? font.weight + ' ' : '')
                    + font.size + 'px '
                    + font.family;
            },

            /**
             * Calculates the text size for the labels, and scales it down if necessary
             * @param {Object} ctx chart context
             * @param {Array} labels labels for the chart
             * @returns {{height: number, width: number}}
             */
            textSize: function(ctx, labels) {
                var items = [].concat(labels);
                var ilen = items.length;
                var prev = ctx.font;
                var width = 0;
                var height = 0;
                var i;

                for (i = 0; i < ilen; ++i) {
                    ctx.font = items[i].font.string;
                    width = Math.max(ctx.measureText(items[i].text).width, width);
                    height += items[i].font.lineHeight;
                }

                ctx.font = prev;

                var result = {
                    height: height,
                    width: width
                };
                return result;
            }

        };

        var helpers = Chart.helpers;

        Chart.defaults.global.plugins.doughnutlabel = defaults;

        /**
         * Draws the doughnut label on the chart
         * @param {Chart} chart the chart object
         * @param {Object} options the chart options
         */
        function drawDoughnutLabel(chart, options) {
            if (options && options.labels && options.labels.length > 0) {
                var ctx = chart.ctx;
                var resolve = helpers.options.resolve;

                var innerLabels = [];
                options.labels.forEach(function(label) {
                    var text = typeof(label.text) === 'function' ? label.text(chart) : label.text;
                    var innerLabel = {
                        text: text,
                        font: utils.parseFont(resolve([label.font, options.font, {}], ctx, 0)),
                        color: resolve([label.color, options.color, Chart.defaults.global.defaultFontColor], ctx, 0)
                    };
                    innerLabels.push(innerLabel);
                });

                var textAreaSize = utils.textSize(ctx, innerLabels);

                // Calculate the adjustment ratio to fit the text area into the doughnut inner circle
                var hypotenuse = Math.sqrt(Math.pow(textAreaSize.width, 2) + Math.pow(textAreaSize.height, 2));
                var innerDiameter = chart.innerRadius * 2;
                var fitRatio = innerDiameter / hypotenuse;

                // Adjust the font if necessary and recalculate the text area after applying the fit ratio
                if (fitRatio < 1) {
                    innerLabels.forEach(function(innerLabel) {
                        innerLabel.font.size = Math.floor(innerLabel.font.size * fitRatio);
                        innerLabel.font.lineHeight = undefined;
                        innerLabel.font = utils.parseFont(resolve([innerLabel.font, {}], ctx, 0));
                    });

                    textAreaSize = utils.textSize(ctx, innerLabels);
                }

                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                // The center of the inner circle
                var centerX = ((chart.chartArea.left + chart.chartArea.right) / 2);
                var centerY = ((chart.chartArea.top + chart.chartArea.bottom) / 2);

                // The top Y coordinate of the text area
                var topY = centerY - textAreaSize.height / 2;

                var i;
                var ilen = innerLabels.length;
                var currentHeight = 0;
                for (i = 0; i < ilen; ++i) {
                    ctx.fillStyle = innerLabels[i].color;
                    ctx.font = innerLabels[i].font.string;

                    // The Y center of each line
                    var lineCenterY = topY + innerLabels[i].font.lineHeight / 2 + currentHeight;
                    currentHeight += innerLabels[i].font.lineHeight;

                    // Draw each line of text
                    ctx.fillText(innerLabels[i].text, centerX, lineCenterY);
                }
            }
        }

        Chart.plugins.register({
            id: 'doughnutlabel',
            beforeDatasetDraw: function(chart, args, options) {
                drawDoughnutLabel(chart, options);
            }
        });
    }

    return {
        register: register
    };
});
