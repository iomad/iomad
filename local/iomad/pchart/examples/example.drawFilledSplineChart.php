<?php   
 /* CAT:Spline chart */

 /* pChart library inclusions */
 include("../class/pData.class.php");
 include("../class/pDraw.class.php");
 include("../class/pImage.class.php");

 /* Create and populate the pData object */
 $MyData = new pData();
 $MyData->setAxisName(0,"Strength");
 for($i=0;$i<=720;$i=$i+20)
  {
   $MyData->addPoints(cos(deg2rad($i))*100,"Probe 1");
   $MyData->addPoints(cos(deg2rad($i+90))*60,"Probe 2");
  }

 /* Create the pChart object */
 $myPicture = new pImage(847,304,$MyData);
 $myPicture->drawGradientArea(0,0,847,304,DIRECTION_VERTICAL,array("StartR"=>47,"StartG"=>47,"StartB"=>47,"EndR"=>17,"EndG"=>17,"EndB"=>17,"Alpha"=>100));
 $myPicture->drawGradientArea(0,250,847,304,DIRECTION_VERTICAL,array("StartR"=>47,"StartG"=>47,"StartB"=>47,"EndR"=>27,"EndG"=>27,"EndB"=>27,"Alpha"=>100));
 $myPicture->drawLine(0,249,847,249,array("R"=>0,"G"=>0,"B"=>0));
 $myPicture->drawLine(0,250,847,250,array("R"=>70,"G"=>70,"B"=>70));

 /* Add a border to the picture */
 $myPicture->drawRectangle(0,0,846,303,array("R"=>204,"G"=>204,"B"=>204));
 
 /* Write the picture title */ 
 $myPicture->setFontProperties(array("FontName"=>"../fonts/pf_arma_five.ttf","FontSize"=>6));
 $myPicture->drawText(423,14,"Cyclic magnetic field strength",array("R"=>255,"G"=>255,"B"=>255,"Align"=>TEXT_ALIGN_MIDDLEMIDDLE));

 /* Define the chart area */
 $myPicture->setGraphArea(58,27,816,228);

 /* Draw a rectangle */
 $myPicture->drawFilledRectangle(58,27,816,228,array("R"=>0,"G"=>0,"B"=>0,"Dash"=>TRUE,"DashR"=>0,"DashG"=>51,"DashB"=>51,"BorderR"=>0,"BorderG"=>0,"BorderB"=>0));

 /* Turn on shadow computing */ 
 $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>20));
 
 /* Draw the scale */
 $myPicture->setFontProperties(array("R"=>255,"G"=>255,"B"=>255));
 $ScaleSettings = array("XMargin"=>4,"DrawSubTicks"=>TRUE,"GridR"=>255,"GridG"=>255,"GridB"=>255,"AxisR"=>255,"AxisG"=>255,"AxisB"=>255,"GridAlpha"=>30,"CycleBackground"=>TRUE);
 $myPicture->drawScale($ScaleSettings);

 /* Draw the spline chart */
 $myPicture->drawFilledSplineChart();

 /* Write the chart boundaries */
 $BoundsSettings = array("MaxDisplayR"=>237,"MaxDisplayG"=>23,"MaxDisplayB"=>48,"MinDisplayR"=>23,"MinDisplayG"=>144,"MinDisplayB"=>237);
 $myPicture->writeBounds(BOUND_BOTH,$BoundsSettings);

 /* Write the 0 line */
 $myPicture->drawThreshold(0,array("WriteCaption"=>TRUE));

 /* Write the chart legend */
 $myPicture->setFontProperties(array("R"=>255,"G"=>255,"B"=>255));
 $myPicture->drawLegend(560,266,array("Style"=>LEGEND_NOBORDER));

 /* Write the 1st data series statistics */
 $Settings = array("R"=>188,"G"=>224,"B"=>46,"Align"=>TEXT_ALIGN_BOTTOMLEFT);
 $myPicture->drawText(620,270,"Max : ".ceil($MyData->getMax("Probe 1")),$Settings);
 $myPicture->drawText(680,270,"Min : ".ceil($MyData->getMin("Probe 1")),$Settings);
 $myPicture->drawText(740,270,"Avg : ".ceil($MyData->getSerieAverage("Probe 1")),$Settings);

 /* Write the 2nd data series statistics */
 $Settings = array("R"=>224,"G"=>100,"B"=>46,"Align"=>TEXT_ALIGN_BOTTOMLEFT);
 $myPicture->drawText(620,283,"Max : ".ceil($MyData->getMax("Probe 2")),$Settings);
 $myPicture->drawText(680,283,"Min : ".ceil($MyData->getMin("Probe 2")),$Settings);
 $myPicture->drawText(740,283,"Avg : ".ceil($MyData->getSerieAverage("Probe 2")),$Settings);

 /* Render the picture (choose the best way) */
 $myPicture->autoOutput("pictures/example.drawFilledSplineChart.png");
?>