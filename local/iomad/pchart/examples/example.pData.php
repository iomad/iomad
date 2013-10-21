<?php   
 /* CAT:Misc */

 /* pChart library inclusions */
 include("../class/pData.class.php");

 /* Create and populate the pData object */
 $MyData = new pData();  
 $MyData->addPoints(array(24,25,26,25,25),"My Serie 1");
 $MyData->addPoints(array(12,13,14,16,18),"My Serie 2");
 $MyData->addPoints(array(80,76,73,71,33),"My Serie 3");
 $MyData->addPoints(array(47,67,78,76,54),"My Serie 4");
 
 /* Define the series name */
 $MyData->setSerieDescription("My Serie 1","Temperature");
 $MyData->setSerieDescription("My Serie 2","Humidity");

 /* Dispatche the series on different axis */
 $MyData->setSerieOnAxis("My Serie 1",1);
 $MyData->setSerieOnAxis("My Serie 2",1);
 $MyData->setSerieOnAxis("My Serie 3",2);
 $MyData->setSerieOnAxis("My Serie 4",2);

 /* Set the format of the axis */
 $MyData->setAxisDisplay(1,AXIS_FORMAT_DEFAULT);
 $MyData->setAxisDisplay(2,AXIS_FORMAT_DEFAULT);
 $MyData->setAxisDisplay(1,AXIS_FORMAT_TIME,"H:i");

 /* Set the unit of the axis */
 $MyData->setAxisUnit(1,"°C");
 $MyData->setAxisUnit(2,"%");

 /* Set the name of the axis */
 $MyData->setAxisName(1,"Temperature");
 $MyData->setAxisName(2,"Humidity");

 /* Change the color of one serie */
 $serieSettings = array("R"=>229,"G"=>11,"B"=>11,"Alpha"=>80); 
 $MyData->setPalette("My Serie 4",$serieSettings);

 /* Load a palette file */
 $MyData->loadPalette("resources/palette.txt",FALSE);
 
 /* Output the data structure */
 print_r($MyData->getData());
?>