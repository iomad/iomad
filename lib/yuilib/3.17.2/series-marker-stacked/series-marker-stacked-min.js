/*
YUI 3.17.2 (build 9c3c78e)
Copyright 2014 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("series-marker-stacked",function(e,t){e.StackedMarkerSeries=e.Base.create("stackedMarkerSeries",e.MarkerSeries,[e.StackingUtil],{setAreaData:function(){e.StackedMarkerSeries.superclass.setAreaData.apply(this),this._stackCoordinates.apply(this)}},{ATTRS:{type:{value:"stackedMarker"}}})},"3.17.2",{requires:["series-stacked","series-marker"]});
