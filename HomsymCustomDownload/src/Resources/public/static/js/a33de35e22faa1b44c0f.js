(window["webpackJsonpPluginhomsym-custom-download"]=window["webpackJsonpPluginhomsym-custom-download"]||[]).push([[834],{551:function(){},834:function(e,t,r){"use strict";r.r(t),r.d(t,{default:function(){return n}}),r(548);var n={template:'{% block sw_customer_tool %}\r\n    <sw-page class="decryption-test">\r\n        <template #content>\r\n\r\n        <div class="sw-card__content">\r\n                <label for="encryptedValue">Encrypted Value:</label>\r\n                <input type="text" v-model="encryptedValue"\r\n                placeholder="Enter encrypted value"\r\n                @keyup.enter="decryptComment">\r\n\r\n                <button @click="decryptComment" class="sw-button sw-button--primary">\r\n                    Decrypt\r\n                </button>\r\n\r\n                <p><strong>Decrypted Order ID:</strong> {{ decryptedOrderID }}</p>\r\n                <p><strong>Decrypted Customer Number:</strong> {{ decryptedCustomerNumber }}</p>\r\n            </div>\r\n\r\n        </template>\r\n    </sw-page>\r\n{% endblock %}\r\n\r\n\r\n',inject:["repositoryFactory","acl"],data(){return{encryptedValue:"",decryptedOrderID:"",decryptedCustomerNumber:""}},methods:{decryptComment(){if(!this.encryptedValue){this.decryptedOrderID="No value provided",this.decryptedCustomerNumber="";return}let e=this.decryptCommentLogic(this.encryptedValue);"Invalid encrypted value"===e?(this.decryptedOrderID="Invalid value",this.decryptedCustomerNumber=""):(this.decryptedOrderID=e.orderID,this.decryptedCustomerNumber=e.customerNumber)},decryptCommentLogic(e){if(!e||28!==e.length)return"Invalid encrypted value";let t=e[24]+e[20]+e[17]+e[5]+e[1];return{orderID:e[11]+e[3]+e[25]+e[15]+e[22],customerNumber:t}}}}},548:function(e,t,r){var n=r(551);n.__esModule&&(n=n.default),"string"==typeof n&&(n=[[e.id,n,""]]),n.locals&&(e.exports=n.locals),(0,r(534).A)("0d934f5e",n,!0,{})},534:function(e,t,r){"use strict";function n(e,t){for(var r=[],n={},o=0;o<t.length;o++){var s=t[o],d=s[0],a={id:e+":"+o,css:s[1],media:s[2],sourceMap:s[3]};n[d]?n[d].parts.push(a):r.push(n[d]={id:d,parts:[a]})}return r}r.d(t,{A:function(){return y}});var o,s="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!s)throw Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var d={},a=s&&(document.head||document.getElementsByTagName("head")[0]),i=null,u=0,c=!1,l=function(){},p=null,m="data-vue-ssr-id",f="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function y(e,t,r,o){c=r,p=o||{};var s=n(e,t);return h(s),function(t){for(var r=[],o=0;o<s.length;o++){var a=d[s[o].id];a.refs--,r.push(a)}t?h(s=n(e,t)):s=[];for(var o=0;o<r.length;o++){var a=r[o];if(0===a.refs){for(var i=0;i<a.parts.length;i++)a.parts[i]();delete d[a.id]}}}}function h(e){for(var t=0;t<e.length;t++){var r=e[t],n=d[r.id];if(n){n.refs++;for(var o=0;o<n.parts.length;o++)n.parts[o](r.parts[o]);for(;o<r.parts.length;o++)n.parts.push(g(r.parts[o]));n.parts.length>r.parts.length&&(n.parts.length=r.parts.length)}else{for(var s=[],o=0;o<r.parts.length;o++)s.push(g(r.parts[o]));d[r.id]={id:r.id,refs:1,parts:s}}}}function v(){var e=document.createElement("style");return e.type="text/css",a.appendChild(e),e}function g(e){var t,r,n=document.querySelector("style["+m+'~="'+e.id+'"]');if(n){if(c)return l;n.parentNode.removeChild(n)}if(f){var o=u++;t=C.bind(null,n=i||(i=v()),o,!1),r=C.bind(null,n,o,!0)}else t=w.bind(null,n=v()),r=function(){n.parentNode.removeChild(n)};return t(e),function(n){n?(n.css!==e.css||n.media!==e.media||n.sourceMap!==e.sourceMap)&&t(e=n):r()}}var b=(o=[],function(e,t){return o[e]=t,o.filter(Boolean).join("\n")});function C(e,t,r,n){var o=r?"":n.css;if(e.styleSheet)e.styleSheet.cssText=b(t,o);else{var s=document.createTextNode(o),d=e.childNodes;d[t]&&e.removeChild(d[t]),d.length?e.insertBefore(s,d[t]):e.appendChild(s)}}function w(e,t){var r=t.css,n=t.media,o=t.sourceMap;if(n&&e.setAttribute("media",n),p.ssrId&&e.setAttribute(m,t.id),o&&(r+="\n/*# sourceURL="+o.sources[0]+" */",r+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */"),e.styleSheet)e.styleSheet.cssText=r;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(r))}}}}]);