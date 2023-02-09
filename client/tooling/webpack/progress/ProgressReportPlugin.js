/**
 * Based on code from github.com/webpack/webpack and github.com/nuxt/webpackbar.
 *
 * Copyright JS Foundation and other contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * 'Software'), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

const pluginName = 'TuiProgressReportPlugin';

module.exports = class ProgressReportPlugin {
  constructor(options) {
    /** @type import('./ProgressReportPluginState') */
    this.pluginState = options.pluginState;
  }

  apply(compiler) {
    // Prevent adding multi instances to the same compiler
    if (compiler[pluginName]) {
      return;
    }
    compiler[pluginName] = this;
    this.name = compiler.options.name;
    this.pluginState.ensureState(this.name);
    const state = this.pluginState.states[this.name];

    const sendReporter = (fn, payload = {}) => {
      this.pluginState.sendReporter(fn, this, payload);
    };

    let estimatedModuleCount = 100;
    let isBadEstimate = true;

    const estimate = this.pluginState.estimator.getModules(
      compiler.options.name
    );
    if (estimate) {
      estimatedModuleCount = estimate;
      isBadEstimate = false;
    }

    let lastModuleCount = null;
    let moduleCount = null;
    let doneModules = 0;
    let otherTasks = 0;

    const emitUpdate = () => {
      let totalModules = lastModuleCount;
      if (totalModules == null) {
        totalModules = Math.max(estimatedModuleCount, moduleCount);
      }
      Object.assign(state, {
        modules: doneModules,
        totalModules,
        totalModulesBadEstimate: isBadEstimate,
        otherTasks,
      });
      sendReporter('progress');
    };

    const moduleAdd = () => {
      moduleCount++;
      otherTasks = 0.25;
      emitUpdate();
    };

    const moduleDone = () => {
      doneModules++;
      otherTasks = 0.25;
      emitUpdate();
    };

    compiler.hooks.compilation.tap(pluginName, compilation => {
      if (compilation.compiler.isChild()) return;
      lastModuleCount = moduleCount;
      moduleCount = 0;
      doneModules = 0;
      otherTasks = 0;
      emitUpdate();

      compilation.hooks.buildModule.tap(pluginName, moduleAdd);
      compilation.hooks.failedModule.tap(pluginName, moduleDone);
      compilation.hooks.succeedModule.tap(pluginName, moduleDone);
    });
    compiler.hooks.emit.intercept({
      name: pluginName,
      context: true,
      call: () => {
        otherTasks = 0.625;
        emitUpdate();
      },
      tap: () => {
        otherTasks = 0.625;
        emitUpdate();
      },
    });
    compiler.hooks.afterEmit.intercept({
      name: pluginName,
      context: true,
      call: () => {
        otherTasks = 0.7;
        emitUpdate();
      },
      tap: () => {
        otherTasks = 0.7;
        emitUpdate();
      },
    });
    compiler.hooks.done.tap(pluginName, () => {
      otherTasks = 1;
      estimatedModuleCount = moduleCount;
      this.pluginState.estimator.updateModules(
        compiler.options.name,
        moduleCount
      );
      isBadEstimate = false;
      emitUpdate();
    });

    // Hook into the compiler before a new compilation is created.
    compiler.hooks.compile.tap(pluginName, () => {
      this.pluginState.resetState(this.name);
      sendReporter('start');
    });

    // Watch compilation has been invalidated.
    compiler.hooks.invalid.tap(pluginName, (fileName, changeTime) => {
      sendReporter('change', {
        path: fileName,
        time: changeTime,
      });
    });

    // Compilation has completed
    compiler.hooks.done.tap(pluginName, stats => {
      // Prevent calling done twice
      if (state.done) {
        return;
      }

      const hasErrors = stats.hasErrors();

      Object.assign(state, {
        done: true,
        hasErrors,
      });

      sendReporter('progress');

      sendReporter('done', { stats });

      this.pluginState.pluginFinished(this);
    });
  }
};
