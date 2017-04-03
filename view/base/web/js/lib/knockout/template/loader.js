/**
 * MageSpecialist
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magespecialist.it so we can send you a copy immediately.
 *
 * @category   MSP
 * @package    MSP_DevTools
 * @copyright  Copyright (c) 2017 Skeeller srl (http://www.magespecialist.it)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

define(
    [
    'underscore',
    'jquery',
    'Core:Magento_Ui/js/lib/knockout/template/loader'
    ],
    function (_, $, CoreLoader) {
        'use strict';

        /**
         * Formats path of type "path.to.template" to RequireJS compatible
         *
         * @param  {String} path
         * @return {String} - formatted template path
         */
        function formatTemplatePath(path)
        {
            return 'text!' + path.replace(/^([^\/]+)/g, '$1/template') + '.html';
        }

        /**
         * Get a random block for MSP devtools
         *
         * @returns {string}
         */
        function getRandomBlockId()
        {
            var text = "";
            var possible = "abcdef0123456789";

            for (var i=0; i < 32; i++) {
                text += possible.charAt(Math.floor(Math.random() * possible.length)); }

            return text;
        }

        CoreLoader.loadTemplateOrig = CoreLoader.loadTemplate;

        CoreLoader.loadTemplate = function (path) {
            var content = CoreLoader.loadTemplateOrig(path);

            if (!window.mspDevTools) {
                return content;
            }

            if (content) {
                var defer = $.Deferred();

                content.done(
                    function (tmpl) {
                        var mspBlockId = getRandomBlockId();

                        var payload = {
                            component: path,
                            template: formatTemplatePath(path).replace('text!', ''),
                            type: 'uiComponent',
                            id: mspBlockId
                        };

                        // we need this to prevent parseHTML to load images
                        tmpl = tmpl.replace(/\s+src\s*=\s*/, " msp-tmp-src=");

                        var fragmentsOut = [];
                        var fragmentsIn = _.toArray($.parseHTML(tmpl));
                        for (var i=0; i<fragmentsIn.length; i++) {
                            var node = fragmentsIn[i];

                            if (node.nodeType == 1) { // HTML node
                                var $f = $(node);
                                $f.attr('data-mspdevtools-ui', mspBlockId);
                                fragmentsOut.push($f[0].outerHTML);
                            } else if (node.nodeType == 3) { // Text node
                                fragmentsOut.push(node.value);
                            } else if (node.nodeType == 8) { // Comment node
                                fragmentsOut.push('<!-- ' + node.nodeValue + '-->');
                            }
                        }

                        tmpl = fragmentsOut.join('');
                        tmpl = tmpl.replace(/&lt;%=(.+?)%&gt;/, '<%=$1%>');
                        tmpl = tmpl.replace(/\s+msp-tmp-src=/, " src=");

                        if (!window.mspDevTools) {
                            window.mspDevTools = {};
                        }
                        if (!window.mspDevTools['uiComponents']) {
                            window.mspDevTools['uiComponents'] = {};
                        }

                        window.mspDevTools['uiComponents'][mspBlockId] = payload;
                        window.postMessage('mspDevToolsUpdate', '*', []);

                        defer.resolve(tmpl);
                    }
                );

                return defer.promise();
            }

            return this.loadFromFile(path);
        };

        return CoreLoader;
    }
);
