/*
YUI 3.17.2 (build 9c3c78e)
Copyright 2014 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("series-combospline-stacked",function(e,t){e.StackedComboSplineSeries=e.Base.create("stackedComboSplineSeries",e.StackedComboSeries,[e.CurveUtil],{drawSeries:function(){this.get("showAreaFill")&&this.drawStackedAreaSpline(),this.get("showLines")&&this.drawSpline(),this.get("showMarkers")&&this.drawPlots()}},{ATTRS:{type:{value:"stackedComboSpline"},showAreaFill:{value:!0}}})},"3.17.2",{requires:["series-combo-stacked","series-curve-util"]});
