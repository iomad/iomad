/*
YUI 3.17.2 (build 9c3c78e)
Copyright 2014 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("array-invoke",function(e,t){e.Array.invoke=function(t,n){var r=e.Array(arguments,2,!0),i=e.Lang.isFunction,s=[];return e.Array.each(e.Array(t),function(e,t){e&&i(e[n])&&(s[t]=e[n].apply(e,r))}),s}},"3.17.2",{requires:["yui-base"]});
