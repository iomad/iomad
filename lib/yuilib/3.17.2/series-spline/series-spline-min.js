/*
YUI 3.17.2 (build 9c3c78e)
Copyright 2014 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("series-spline",function(e,t){e.SplineSeries=e.Base.create("splineSeries",e.LineSeries,[e.CurveUtil,e.Lines],{drawSeries:function(){this.drawSpline()}},{ATTRS:{type:{value:"spline"}}})},"3.17.2",{requires:["series-line","series-curve-util"]});
