/*
YUI 3.17.2 (build 9c3c78e)
Copyright 2014 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("datatype-date-parse",function(e,t){e.mix(e.namespace("Date"),{parse:function(t){var n=new Date(+t||t);return e.Lang.isDate(n)?n:null}}),e.namespace("Parsers").date=e.Date.parse,e.namespace("DataType"),e.DataType.Date=e.Date},"3.17.2");
