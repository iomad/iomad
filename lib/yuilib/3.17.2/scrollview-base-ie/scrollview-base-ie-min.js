/*
YUI 3.17.2 (build 9c3c78e)
Copyright 2014 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("scrollview-base-ie",function(e,t){e.mix(e.ScrollView.prototype,{_fixIESelect:function(t,n){this._cbDoc=n.get("ownerDocument"),this._nativeBody=e.Node.getDOMNode(e.one("body",this._cbDoc)),n.on("mousedown",function(){this._selectstart=this._nativeBody.onselectstart,this._nativeBody.onselectstart=this._iePreventSelect,this._cbDoc.once("mouseup",this._ieRestoreSelect,this)},this)},_iePreventSelect:function(){return!1},_ieRestoreSelect:function(){this._nativeBody.onselectstart=this._selectstart}},!0)},"3.17.2",{requires:["scrollview-base"]});
