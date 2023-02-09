/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./client/component/performelement_static_content/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\]internal[/\\\\]).)*$":
/*!***************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/ sync ^(?:(?%21__[a-z]*__%7C[/\\]internal[/\\]).)*$ ***!
  \***************************************************************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

eval("var map = {\n\t\"./StaticContentAdminEdit\": \"./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue\",\n\t\"./StaticContentAdminEdit.vue\": \"./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue\",\n\t\"./StaticContentAdminSummary\": \"./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue\",\n\t\"./StaticContentAdminSummary.vue\": \"./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue\",\n\t\"./StaticContentAdminView\": \"./client/component/performelement_static_content/src/components/StaticContentAdminView.vue\",\n\t\"./StaticContentAdminView.vue\": \"./client/component/performelement_static_content/src/components/StaticContentAdminView.vue\",\n\t\"./StaticContentParticipantForm\": \"./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue\",\n\t\"./StaticContentParticipantForm.vue\": \"./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./client/component/performelement_static_content/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\";\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/_sync_^(?");

/***/ }),

/***/ "./server/lib/webapi/ajax/file_unused_draft_item_id.graphql":
/*!******************************************************************!*\
  !*** ./server/lib/webapi/ajax/file_unused_draft_item_id.graphql ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"mutation\",\"name\":{\"kind\":\"Name\",\"value\":\"core_file_unused_draft_item_id\"},\"variableDefinitions\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"item_id\"},\"name\":{\"kind\":\"Name\",\"value\":\"core_file_unused_draft_item_id\"},\"arguments\":[],\"directives\":[]}]}}]};\n    /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (doc);\n  \n\n//# sourceURL=webpack:///./server/lib/webapi/ajax/file_unused_draft_item_id.graphql?");

/***/ }),

/***/ "./server/mod/perform/element/static_content/webapi/ajax/prepare_draft_area.graphql":
/*!******************************************************************************************!*\
  !*** ./server/mod/perform/element/static_content/webapi/ajax/prepare_draft_area.graphql ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"mutation\",\"name\":{\"kind\":\"Name\",\"value\":\"performelement_static_content_prepare_draft_area\"},\"variableDefinitions\":[{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"section_id\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"core_id\"}}},\"directives\":[]},{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"element_id\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"core_id\"}}},\"directives\":[]}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"draft_id\"},\"name\":{\"kind\":\"Name\",\"value\":\"performelement_static_content_prepare_draft_area\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"section_id\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"section_id\"}}},{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"element_id\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"element_id\"}}}],\"directives\":[]}]}}]};\n    /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (doc);\n  \n\n//# sourceURL=webpack:///./server/mod/perform/element/static_content/webapi/ajax/prepare_draft_area.graphql?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/tui.json":
/*!*********************************************************************!*\
  !*** ./client/component/performelement_static_content/src/tui.json ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

eval("!function() {\n\"use strict\";\n\nif (typeof tui !== 'undefined' && tui._bundle.isLoaded(\"performelement_static_content\")) {\n  console.warn(\n    '[tui bundle] The bundle \"' + \"performelement_static_content\" +\n    '\" is already loaded, skipping initialisation.'\n  );\n  return;\n};\ntui._bundle.register(\"performelement_static_content\")\ntui._bundle.addModulesFromContext(\"performelement_static_content/components\", __webpack_require__(\"./client/component/performelement_static_content/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\"));\n}();\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/tui.json?");

/***/ }),

/***/ "./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1329[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1329[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(component) {\n        component.options.__langStrings = \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n{\n  \"performelement_static_content\": [\n    \"required\",\n    \"static_content_placeholder\",\n    \"weka_enter_content\"\n  ]\n}\n;\n    }\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1329%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue":
/*!**************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _StaticContentAdminEdit_vue_vue_type_template_id_dece549e___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./StaticContentAdminEdit.vue?vue&type=template&id=dece549e& */ \"./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=template&id=dece549e&\");\n/* harmony import */ var _StaticContentAdminEdit_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./StaticContentAdminEdit.vue?vue&type=script&lang=js& */ \"./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n/* harmony import */ var _StaticContentAdminEdit_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./StaticContentAdminEdit.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=custom&index=0&blockType=lang-strings\");\n\n\n\n\n\n/* normalize component */\n;\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(\n  _StaticContentAdminEdit_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _StaticContentAdminEdit_vue_vue_type_template_id_dece549e___WEBPACK_IMPORTED_MODULE_0__.render,\n  _StaticContentAdminEdit_vue_vue_type_template_id_dece549e___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* custom blocks */\n;\nif (typeof _StaticContentAdminEdit_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_3__[\"default\"] === 'function') (0,_StaticContentAdminEdit_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(component)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue\"\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue":
/*!*****************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _StaticContentAdminSummary_vue_vue_type_template_id_4402decf___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./StaticContentAdminSummary.vue?vue&type=template&id=4402decf& */ \"./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=template&id=4402decf&\");\n/* harmony import */ var _StaticContentAdminSummary_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./StaticContentAdminSummary.vue?vue&type=script&lang=js& */ \"./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n\n\n\n\n\n/* normalize component */\n;\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(\n  _StaticContentAdminSummary_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _StaticContentAdminSummary_vue_vue_type_template_id_4402decf___WEBPACK_IMPORTED_MODULE_0__.render,\n  _StaticContentAdminSummary_vue_vue_type_template_id_4402decf___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue\"\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminView.vue":
/*!**************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminView.vue ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _StaticContentAdminView_vue_vue_type_template_id_7862dccc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./StaticContentAdminView.vue?vue&type=template&id=7862dccc& */ \"./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=template&id=7862dccc&\");\n/* harmony import */ var _StaticContentAdminView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./StaticContentAdminView.vue?vue&type=script&lang=js& */ \"./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n\n\n\n\n\n/* normalize component */\n;\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(\n  _StaticContentAdminView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _StaticContentAdminView_vue_vue_type_template_id_7862dccc___WEBPACK_IMPORTED_MODULE_0__.render,\n  _StaticContentAdminView_vue_vue_type_template_id_7862dccc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/performelement_static_content/src/components/StaticContentAdminView.vue\"\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue":
/*!********************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue ***!
  \********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _StaticContentParticipantForm_vue_vue_type_template_id_1e2f974f___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./StaticContentParticipantForm.vue?vue&type=template&id=1e2f974f& */ \"./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=template&id=1e2f974f&\");\n/* harmony import */ var _StaticContentParticipantForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./StaticContentParticipantForm.vue?vue&type=script&lang=js& */ \"./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n\n\n\n\n\n/* normalize component */\n;\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(\n  _StaticContentParticipantForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _StaticContentParticipantForm_vue_vue_type_template_id_1e2f974f___WEBPACK_IMPORTED_MODULE_0__.render,\n  _StaticContentParticipantForm_vue_vue_type_template_id_1e2f974f___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue\"\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?");

/***/ }),

/***/ "./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var mod_perform_components_element_PerformAdminCustomElementEdit__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! mod_perform/components/element/PerformAdminCustomElementEdit */ \"mod_perform/components/element/PerformAdminCustomElementEdit\");\n/* harmony import */ var mod_perform_components_element_PerformAdminCustomElementEdit__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(mod_perform_components_element_PerformAdminCustomElementEdit__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var editor_weka_components_Weka__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! editor_weka/components/Weka */ \"editor_weka/components/Weka\");\n/* harmony import */ var editor_weka_components_Weka__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(editor_weka_components_Weka__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var editor_weka_WekaValue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! editor_weka/WekaValue */ \"editor_weka/WekaValue\");\n/* harmony import */ var editor_weka_WekaValue__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(editor_weka_WekaValue__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var tui_components_uniform__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! tui/components/uniform */ \"tui/components/uniform\");\n/* harmony import */ var tui_components_uniform__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(tui_components_uniform__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var core_graphql_file_unused_draft_item_id__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core/graphql/file_unused_draft_item_id */ \"./server/lib/webapi/ajax/file_unused_draft_item_id.graphql\");\n/* harmony import */ var performelement_static_content_graphql_prepare_draft_area__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! performelement_static_content/graphql/prepare_draft_area */ \"./server/mod/perform/element/static_content/webapi/ajax/prepare_draft_area.graphql\");\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n\n\n\n// GraphQL queries\n\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  components: {\n    FormField: tui_components_uniform__WEBPACK_IMPORTED_MODULE_3__.FormField,\n    FormRow: tui_components_uniform__WEBPACK_IMPORTED_MODULE_3__.FormRow,\n    PerformAdminCustomElementEdit: (mod_perform_components_element_PerformAdminCustomElementEdit__WEBPACK_IMPORTED_MODULE_0___default()),\n    Weka: (editor_weka_components_Weka__WEBPACK_IMPORTED_MODULE_1___default()),\n  },\n\n  inheritAttrs: false,\n\n  props: {\n    data: Object,\n    elementId: [Number, String],\n    identifier: String,\n    rawData: Object,\n    rawTitle: String,\n    sectionId: [Number, String],\n    settings: Object,\n    activityContextId: [Number, String],\n  },\n\n  data() {\n    return {\n      initialValues: {\n        data: this.data,\n        draftId: null,\n        identifier: this.identifier,\n        rawTitle: this.rawTitle,\n        wekaDoc: editor_weka_WekaValue__WEBPACK_IMPORTED_MODULE_2___default().empty(),\n      },\n      ready: false,\n    };\n  },\n\n  computed: {\n    actualElementId() {\n      return this.elementId && !isNaN(this.elementId) ? this.elementId : null;\n    },\n  },\n\n  async mounted() {\n    if (this.rawData && this.rawData.wekaDoc) {\n      this.initialValues.wekaDoc = editor_weka_WekaValue__WEBPACK_IMPORTED_MODULE_2___default().fromDoc(\n        JSON.parse(this.rawData.wekaDoc)\n      );\n    }\n    if (this.sectionId && this.actualElementId) {\n      await this.$_loadExistingDraftId();\n    } else {\n      await this.$_loadNewDraftId();\n    }\n\n    this.ready = true;\n  },\n\n  methods: {\n    async $_loadNewDraftId() {\n      const {\n        data: { item_id },\n      } = await this.$apollo.mutate({ mutation: core_graphql_file_unused_draft_item_id__WEBPACK_IMPORTED_MODULE_4__[\"default\"] });\n      this.initialValues.draftId = item_id;\n    },\n\n    async $_loadExistingDraftId() {\n      const {\n        data: { draft_id },\n      } = await this.$apollo.mutate({\n        mutation: performelement_static_content_graphql_prepare_draft_area__WEBPACK_IMPORTED_MODULE_5__[\"default\"],\n        variables: {\n          section_id: parseInt(this.sectionId),\n          element_id: parseInt(this.elementId),\n        },\n      });\n      this.initialValues.draftId = draft_id;\n    },\n\n    /**\n     * Stringify weka value and structure form data correctly for query\n     *\n     * @param {Object} values\n     * @returns {String}\n     */\n    processData(values) {\n      let modifiedValues = {\n        data: {\n          docFormat: 'FORMAT_JSON_EDITOR',\n          draftId: values.data.draftId,\n          format: 'HTML',\n          wekaDoc: JSON.stringify(values.data.wekaDoc.getDoc()),\n        },\n        title: values.title,\n      };\n\n      return modifiedValues;\n    },\n\n    /**\n     * Validate that weka editor value\n     *\n     * @param {Text} value\n     * @returns {String}\n     */\n    validateEditor(value) {\n      if (!value || value.isEmpty) {\n        return this.$str('required', 'performelement_static_content');\n      }\n    },\n  },\n});\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var mod_perform_components_element_PerformAdminCustomElementSummary__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! mod_perform/components/element/PerformAdminCustomElementSummary */ \"mod_perform/components/element/PerformAdminCustomElementSummary\");\n/* harmony import */ var mod_perform_components_element_PerformAdminCustomElementSummary__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(mod_perform_components_element_PerformAdminCustomElementSummary__WEBPACK_IMPORTED_MODULE_0__);\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  components: {\n    PerformAdminCustomElementSummary: (mod_perform_components_element_PerformAdminCustomElementSummary__WEBPACK_IMPORTED_MODULE_0___default()),\n  },\n\n  inheritAttrs: false,\n\n  props: {\n    data: Object,\n    identifier: String,\n    isRequired: Boolean,\n    settings: Object,\n    title: String,\n  },\n});\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var tui_tui__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tui/tui */ \"tui/tui\");\n/* harmony import */ var tui_tui__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(tui_tui__WEBPACK_IMPORTED_MODULE_0__);\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n// Utils\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  inheritAttrs: false,\n\n  props: {\n    data: Object,\n  },\n\n  mounted() {\n    this.$_scan();\n  },\n\n  updated() {\n    this.$_scan();\n  },\n\n  methods: {\n    $_scan() {\n      this.$nextTick().then(() => {\n        let content = this.$refs.content;\n        if (!content) {\n          return;\n        }\n\n        tui_tui__WEBPACK_IMPORTED_MODULE_0___default().scan(content);\n      });\n    },\n  },\n});\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var tui_tui__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tui/tui */ \"tui/tui\");\n/* harmony import */ var tui_tui__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(tui_tui__WEBPACK_IMPORTED_MODULE_0__);\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n// Utils\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  props: {\n    element: Object,\n  },\n\n  mounted() {\n    this.$_scan();\n  },\n\n  updated() {\n    this.$_scan();\n  },\n\n  methods: {\n    $_scan() {\n      this.$nextTick().then(() => {\n        let content = this.$refs.content;\n        if (!content) {\n          return;\n        }\n\n        tui_tui__WEBPACK_IMPORTED_MODULE_0___default().scan(content);\n      });\n    },\n  },\n});\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ normalizeComponent)\n/* harmony export */ });\n/* globals __VUE_SSR_CONTEXT__ */\n\n// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).\n// This module is a runtime utility for cleaner component module output and will\n// be included in the final webpack user bundle.\n\nfunction normalizeComponent (\n  scriptExports,\n  render,\n  staticRenderFns,\n  functionalTemplate,\n  injectStyles,\n  scopeId,\n  moduleIdentifier, /* server only */\n  shadowMode /* vue-cli only */\n) {\n  // Vue.extend constructor export interop\n  var options = typeof scriptExports === 'function'\n    ? scriptExports.options\n    : scriptExports\n\n  // render functions\n  if (render) {\n    options.render = render\n    options.staticRenderFns = staticRenderFns\n    options._compiled = true\n  }\n\n  // functional template\n  if (functionalTemplate) {\n    options.functional = true\n  }\n\n  // scopedId\n  if (scopeId) {\n    options._scopeId = 'data-v-' + scopeId\n  }\n\n  var hook\n  if (moduleIdentifier) { // server build\n    hook = function (context) {\n      // 2.3 injection\n      context =\n        context || // cached call\n        (this.$vnode && this.$vnode.ssrContext) || // stateful\n        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional\n      // 2.2 with runInNewContext: true\n      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {\n        context = __VUE_SSR_CONTEXT__\n      }\n      // inject component styles\n      if (injectStyles) {\n        injectStyles.call(this, context)\n      }\n      // register component module identifier for async chunk inferrence\n      if (context && context._registeredComponents) {\n        context._registeredComponents.add(moduleIdentifier)\n      }\n    }\n    // used by ssr in case component is cached and beforeCreate\n    // never gets called\n    options._ssrRegister = hook\n  } else if (injectStyles) {\n    hook = shadowMode\n      ? function () {\n        injectStyles.call(\n          this,\n          (options.functional ? this.parent : this).$root.$options.shadowRoot\n        )\n      }\n      : injectStyles\n  }\n\n  if (hook) {\n    if (options.functional) {\n      // for template-only hot-reload because in that case the render fn doesn't\n      // go through the normalizer\n      options._injectStyles = hook\n      // register for functional component in vue file\n      var originalRender = options.render\n      options.render = function renderWithStyleInjection (h, context) {\n        hook.call(context)\n        return originalRender(h, context)\n      }\n    } else {\n      // inject component registration as beforeCreate hook\n      var existing = options.beforeCreate\n      options.beforeCreate = existing\n        ? [].concat(existing, hook)\n        : [hook]\n    }\n  }\n\n  return {\n    exports: scriptExports,\n    options: options\n  }\n}\n\n\n//# sourceURL=webpack:///./node_modules/vue-loader/lib/runtime/componentNormalizer.js?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!*************************************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \*************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1329_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminEdit_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1329[0].rules[0].use[0]!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./StaticContentAdminEdit.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1329[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=custom&index=0&blockType=lang-strings\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1329_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminEdit_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=template&id=dece549e&":
/*!*********************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=template&id=dece549e& ***!
  \*********************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminEdit_vue_vue_type_template_id_dece549e___WEBPACK_IMPORTED_MODULE_0__.render),\n/* harmony export */   \"staticRenderFns\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminEdit_vue_vue_type_template_id_dece549e___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminEdit_vue_vue_type_template_id_dece549e___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./StaticContentAdminEdit.vue?vue&type=template&id=dece549e& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=template&id=dece549e&\");\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=template&id=4402decf&":
/*!************************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=template&id=4402decf& ***!
  \************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminSummary_vue_vue_type_template_id_4402decf___WEBPACK_IMPORTED_MODULE_0__.render),\n/* harmony export */   \"staticRenderFns\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminSummary_vue_vue_type_template_id_4402decf___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminSummary_vue_vue_type_template_id_4402decf___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./StaticContentAdminSummary.vue?vue&type=template&id=4402decf& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=template&id=4402decf&\");\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=template&id=7862dccc&":
/*!*********************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=template&id=7862dccc& ***!
  \*********************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminView_vue_vue_type_template_id_7862dccc___WEBPACK_IMPORTED_MODULE_0__.render),\n/* harmony export */   \"staticRenderFns\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminView_vue_vue_type_template_id_7862dccc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentAdminView_vue_vue_type_template_id_7862dccc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./StaticContentAdminView.vue?vue&type=template&id=7862dccc& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=template&id=7862dccc&\");\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=template&id=1e2f974f&":
/*!***************************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=template&id=1e2f974f& ***!
  \***************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentParticipantForm_vue_vue_type_template_id_1e2f974f___WEBPACK_IMPORTED_MODULE_0__.render),\n/* harmony export */   \"staticRenderFns\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentParticipantForm_vue_vue_type_template_id_1e2f974f___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StaticContentParticipantForm_vue_vue_type_template_id_1e2f974f___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./StaticContentParticipantForm.vue?vue&type=template&id=1e2f974f& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=template&id=1e2f974f&\");\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_StaticContentAdminEdit_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./StaticContentAdminEdit.vue?vue&type=script&lang=js& */ \"./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=script&lang=js&\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_StaticContentAdminEdit_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_StaticContentAdminSummary_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./StaticContentAdminSummary.vue?vue&type=script&lang=js& */ \"./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=script&lang=js&\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_StaticContentAdminSummary_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_StaticContentAdminView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./StaticContentAdminView.vue?vue&type=script&lang=js& */ \"./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=script&lang=js&\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_StaticContentAdminView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?");

/***/ }),

/***/ "./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************!*\
  !*** ./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_StaticContentParticipantForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./StaticContentParticipantForm.vue?vue&type=script&lang=js& */ \"./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=script&lang=js&\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_StaticContentParticipantForm_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=template&id=dece549e&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?vue&type=template&id=dece549e& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* binding */ render),\n/* harmony export */   \"staticRenderFns\": () => (/* binding */ staticRenderFns)\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:\"tui-staticContentAdminEdit\"},[(_vm.ready)?_c('PerformAdminCustomElementEdit',{attrs:{\"initial-values\":_vm.initialValues,\"settings\":_vm.settings},on:{\"cancel\":function($event){return _vm.$emit('display')},\"update\":function($event){_vm.$emit('update', _vm.processData($event))}}},[_c('FormRow',{attrs:{\"label\":_vm.$str('static_content_placeholder', 'performelement_static_content'),\"required\":true},scopedSlots:_vm._u([{key:\"default\",fn:function(ref){\nvar id = ref.id;\nreturn [_c('FormField',{attrs:{\"name\":\"wekaDoc\",\"validate\":_vm.validateEditor},scopedSlots:_vm._u([{key:\"default\",fn:function(ref){\nvar value = ref.value;\nvar update = ref.update;\nreturn [_c('Weka',{attrs:{\"id\":id,\"context-id\":_vm.activityContextId,\"value\":value,\"usage-identifier\":{\n            component: 'performelement_static_content',\n            area: 'content',\n            instanceId: _vm.actualElementId,\n          },\"variant\":\"full\",\"file-item-id\":_vm.initialValues.draftId,\"placeholder\":_vm.$str('weka_enter_content', 'performelement_static_content')},on:{\"input\":update}})]}}],null,true)})]}}],null,false,2190760222)})],1):_vm._e()],1)}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminEdit.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=template&id=4402decf&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?vue&type=template&id=4402decf& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* binding */ render),\n/* harmony export */   \"staticRenderFns\": () => (/* binding */ staticRenderFns)\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:\"tui-staticContentAdminSummary\"},[_c('PerformAdminCustomElementSummary',{attrs:{\"html-content\":_vm.data.content,\"identifier\":_vm.identifier,\"is-required\":_vm.isRequired,\"settings\":_vm.settings,\"title\":_vm.title},on:{\"display\":function($event){return _vm.$emit('display')}}})],1)}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminSummary.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=template&id=7862dccc&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?vue&type=template&id=7862dccc& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* binding */ render),\n/* harmony export */   \"staticRenderFns\": () => (/* binding */ staticRenderFns)\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:\"tui-staticContentAdminView\"},[_c('div',{ref:\"content\",domProps:{\"innerHTML\":_vm._s(_vm.data.content)}})])}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentAdminView.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=template&id=1e2f974f&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?vue&type=template&id=1e2f974f& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* binding */ render),\n/* harmony export */   \"staticRenderFns\": () => (/* binding */ staticRenderFns)\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:\"tui-staticContentElementParticipantForm\"},[_c('div',{ref:\"content\",domProps:{\"innerHTML\":_vm._s(_vm.element.data.content)}})])}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/performelement_static_content/src/components/StaticContentParticipantForm.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "editor_weka/WekaValue":
/*!*********************************************************!*\
  !*** external "tui.require(\"editor_weka/WekaValue\")" ***!
  \*********************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("editor_weka/WekaValue");

/***/ }),

/***/ "editor_weka/components/Weka":
/*!***************************************************************!*\
  !*** external "tui.require(\"editor_weka/components/Weka\")" ***!
  \***************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("editor_weka/components/Weka");

/***/ }),

/***/ "mod_perform/components/element/PerformAdminCustomElementEdit":
/*!************************************************************************************************!*\
  !*** external "tui.require(\"mod_perform/components/element/PerformAdminCustomElementEdit\")" ***!
  \************************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("mod_perform/components/element/PerformAdminCustomElementEdit");

/***/ }),

/***/ "mod_perform/components/element/PerformAdminCustomElementSummary":
/*!***************************************************************************************************!*\
  !*** external "tui.require(\"mod_perform/components/element/PerformAdminCustomElementSummary\")" ***!
  \***************************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("mod_perform/components/element/PerformAdminCustomElementSummary");

/***/ }),

/***/ "tui/components/uniform":
/*!**********************************************************!*\
  !*** external "tui.require(\"tui/components/uniform\")" ***!
  \**********************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/uniform");

/***/ }),

/***/ "tui/tui":
/*!*******************************************!*\
  !*** external "tui.require(\"tui/tui\")" ***!
  \*******************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/tui");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./client/component/performelement_static_content/src/tui.json");
/******/ 	
/******/ })()
;