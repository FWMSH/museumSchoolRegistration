<?php
   include "db.php";

   class web
   {
      function __construct ( $db )
      {
         $this->db = $db;

         switch ( $_POST [ 'action' ] )
         {
            case "LOAD":$this->loadReservation();break;
            case "DASHBOARD":$this->dashboard();break;
            case "DETAILS":$this->details();break;
         }
      }

      function responseScript ( $script )
      {
         $this->response[] = array ( "SCRIPT" , $script );
      }

      function responseHTML ( $div , $content )
      {
         $this->response[] = array ( "HTML" , $div , $content );
      }

      function send()
      {
         echo json_encode ( $this->response );
         die();
      }

      function dashboard()
      {
         $sql = "SELECT * FROM `exhibits` ORDER BY `Gallery`";
         $response = $this->db->query ( $sql );

         $html = "<div class='container-fluid'>";
         $lastGallery = "";
         $first = True;

         while ( $exhibit = $response->fetch ( PDO::FETCH_ASSOC ) )
         {
            $sql = "SELECT * FROM `tickets` WHERE `exhibitID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $exhibit [ 'ID' ] , PDO::PARAM_INT );
            $query->execute();
            
            $exhibitStatus = "UP";
            $statusColor = "";
            $projectorColor = "label-success";
            $touchscreenColor = "label-success";
            $computerColor = "label-success";
            $audioColor = "label-success";

            while ( $ticket = $query->fetch ( PDO::FETCH_ASSOC ) )
            {
               if ( $exhibitStatus == "UP" && $ticket [ 'status' ] != "UP" ) $exhibitStatus = $ticket [ 'status' ];
               if ( $exhibitStatus == "MAINT"  && $ticket [ 'status' ] == "DOWN" ) $exhibitStatus = "DOWN";
               if ( $exhibit [ 'Projector' ] == '1' && $ticket [ 'projectorStatus' ] != "UP" )
               {
                  $projectorStatus = $ticket [ 'projectorStatus' ];
                  if ( $projectorStatus == "MAINT" && $exhibitStatus == "UP" ) $exhibitStatus = "MAINT";
                  else if ( $projectorStatus == "DOWN" ) $exhibitStatus = "DOWN";

                  switch ( $projectorStatus )
                  {
                     case "UP":$projectorColor = "label-success";break;
                     case "MAINT":$projectorColor = "label-warning";break;
                     case "DOWN":$projectorColor = "label-danger";break;
                  }
               }

               if ( $exhibit [ 'Touchscreen' ] == '1' && $ticket [ 'touchscreenStatus' ] != "UP" )
               {
                  $touchscreenStatus = $ticket [ 'touchscreenStatus' ];
                  if ( $touchscreenStatus == "MAINT" && $exhibitStatus == "UP" ) $exhibitStatus = "MAINT";
                  else if ( $touchscreenStatus == "DOWN" ) $exhibitStatus = "DOWN";

                  switch ( $touchscreenStatus )
                  {
                     case "UP":$touchscreenColor = "label-success";break;
                     case "MAINT":$touchscreenColor = "label-warning";break;
                     case "DOWN":$touchscreenColor = "label-danger";break;
                  }
               }

               if ( $exhibit [ 'Computer' ] == '1' && $ticket [ 'computerStatus' ] != "UP" )
               {
                  $computerStatus = $ticket [ 'computerStatus' ];
                  if ( $computerStatus == "MAINT" && $exhibitStatus == "UP" ) $exhibitStatus = "MAINT";
                  else if ( $computerStatus == "DOWN" ) $exhibitStatus = "DOWN";

                  switch ( $computerStatus )
                  {
                     case "UP":$computerColor = "label-success";break;
                     case "MAINT":$computerColor = "label-warning";break;
                     case "DOWN":$computerColor = "label-danger";break;
                  }

               }

               if ( $exhibit [ 'Audio' ] == '1' && $ticket [ 'audioStatus' ] != "UP" )
               {
                  $audioStatus = $ticket [ 'audioStatus' ];
                  if ( $audioStatus == "MAINT" && $exhibitStatus == "UP" ) $exhibitStatus = "MAINT";
                  else if ( $audioStatus == "DOWN" ) $exhibitStatus = "DOWN";
               }

            }

            $sql = "SELECT * FROM `processes` WHERE `exhibitID` = ?";

            $processQuery = $this->db->prepare ( $sql );
            $processQuery->bindValue ( 1 , $exhibit [ 'ID' ] , PDO::PARAM_INT );
            $processQuery->execute();

            while ( $process = $processQuery->fetch ( PDO::FETCH_ASSOC ) )
            {
               if ( $process [ 'status' ] == '0' )
               {
                  $computerStatus = "AUTODOWN";
                  $computerColor = "label-default";
                  $exhibitStatus = "DOWN";
               }
            }

            if ( $exhibit [ 'LastInspection' ] != "" && $exhibitStatus == "UP" )
            {
               $lastInspection = DateTime::createFromFormat ( "Y-m-d" , $exhibit [ 'LastInspection' ] );
               $today = new DateTime ( "now" );
               $lastInspection->add ( new DateInterval ( "P".$exhibit [ 'InspectionFrequency' ]."D" ) );
               if ( $today > $lastInspection ) $exhibitStatus = "INSPECTION";
            }

            switch ( $exhibitStatus )
            {
               case "INSPECTION":$exhibitColor = "AQUA";break;
               case "UP":$exhibitColor = "WHITE";break;
               case "MAINT":$exhibitColor = "ORANGE";break;
               case "DOWN":$exhibitColor = "RED";break;
            }

            if ( $lastGallery != $exhibit [ 'Gallery' ] )
            {
               if ( $first == False )
               {
                  $html .= "</div>";	// End previous row
               }
               $first = False;

               $html .= "<div class='row'><h3>".$exhibit [ 'Gallery' ]."</h3></div>";
               $lastGallery = $exhibit [ 'Gallery' ];
               $html .= "<div class='row'>";
            }
            $html .= "<div class='col-md-2' style='border:1px black solid;border-radius:5px;background-color:".$exhibitColor."' onclick='details ( ".$exhibit [ 'ID' ]." )'>";
            $html .= "<div style='font-weight:bold;'>".$exhibit [ 'Name' ]."</div>";

            // Element Icons
            $html .= "<div class='container-fluid' style='background-color:rgba(255,255,255,0.4);'>";
            $html .= "<div class='row'>";

            // Projector
            if ( $exhibit [ 'Projector' ] == '1' )
            {
               $html .= "<div class='col-md-3'>";
               if ( $projectorColor == "label-success" )
               {
                  $html .= "<div class='label ".$projectorColor."'>";
               }
               else
               {
                  $html .= "<div class='label ".$projectorColor."' style='text-decoration:blink;'>";
               }
               $html .= "<span class='glyphicon glyphicon-facetime-video'></span>";
               $html .= "</div>";	// End Label
               $html .= "</div>";	// End Column
            }
            else
            {
               //$html .= "<div class='col-md-3'>";
               //$html .= "<span class='glyphicon glyphicon-facetime-video'></span>";
               //$html .= "</div>";	// End Column
            }

            // Touchscreen
            if ( $exhibit [ 'Touchscreen' ] == '1' )
            {
               $html .= "<div class='col-md-3'>";
               $html .= "<div class='label ".$touchscreenColor."'>";
               $html .= "<span class='glyphicon glyphicon-blackboard'></span>";
               $html .= "</div>";	// End Label
               $html .= "</div>";	// End Column
            }
            else
            {
               //$html .= "<div class='col-md-3'>";
               //$html .= "<span class='glyphicon glyphicon-blackboard'></span>";
               //$html .= "</div>";	// End Column
            }

            // Computer
            if ( $exhibit [ 'Computer' ] == '1' )
            {
               $html .= "<div class='col-md-3'>";
               if ( $computerColor == "label-success" )
               {
                  $html .= "<div class='label ".$computerColor."'>";
               }
               else
               {
                  $html .= "<div class='flash label ".$computerColor."'>";
               }

               $html .= "<span class='glyphicon glyphicon-hdd'></span>";
               $html .= "</div>";	// End Label
               $html .= "</div>";	// End Column
            }
            else
            {
               //$html .= "<div class='col-md-3'>";
               //$html .= "<span class='glyphicon glyphicon-hdd'></span>";
               //$html .= "</div>";	// End Column
            }

            // Audio
            if ( $exhibit [ 'Audio' ] == '1' )
            {
               $html .= "<div class='col-md-3'>";
               $html .= "<div class='label label-success'>";
               $html .= "<span class='glyphicon glyphicon-volume-up'></span>";
               $html .= "</div>";	// End Label
               $html .= "</div>";	// End Column
            }
            else
            {
               //$html .= "<div class='col-md-3'>";
               //$html .= "<span class='glyphicon glyphicon-volume-up'></span>";
               //$html .= "</div>";	// End Column
            }

            $html .= "</div>";	// End Row
            $html .= "</div>";	// End Container
            $html .= "</div>";	// End Column

         }
         $html .= "</div>";	// End Row
         $html .= "</div>";	// End Container

         $this->responseHTML ( "response" , $html );
         $this->send();
      }

      function details()
      {
         $sql = "SELECT * FROM `exhibits` WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'exhibitID' ] , PDO::PARAM_INT );
         $query->execute();
         $exhibit = $query->fetch ( PDO::FETCH_ASSOC );

         $title = $exhibit [ 'Name' ];
        
         $sql = "SELECT * FROM `tickets` WHERE `exhibitID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $exhibit [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();
            
         $exhibitStatus = "UP";
         $statusColor = "";
         $projectorColor = "label-success";
         $touchscreenColor = "label-success";
         $computerColor = "label-success";
         $audioColor = "label-success";


         while ( $ticket = $query->fetch ( PDO::FETCH_ASSOC ) )
         {
            if ( $exhibitStatus == "UP" && $ticket [ 'status' ] != "UP" ) $exhibitStatus = $ticket [ 'status' ];
            if ( $exhibitStatus == "MAINT"  && $ticket [ 'status' ] == "DOWN" ) $exhibitStatus = "DOWN";
            if ( $exhibit [ 'Projector' ] == '1' && $ticket [ 'projectorStatus' ] != "UP" )
            {
               $projectorStatus = $ticket [ 'projectorStatus' ];
               if ( $projectorStatus == "MAINT" && $exhibitStatus == "UP" ) $exhibitStatus = "MAINT";
               else if ( $projectorStatus == "DOWN" ) $exhibitStatus = "DOWN";

               switch ( $projectorStatus )
               {
                  case "UP":$projectorColor = "label-success";break;
                  case "MAINT":$projectorColor = "label-warning";break;
                  case "DOWN":$projectorColor = "label-danger";break;
               }
            }

            if ( $exhibit [ 'Touchscreen' ] == '1' && $ticket [ 'touchscreenStatus' ] != "UP" )
            {
               $touchscreenStatus = $ticket [ 'touchscreenStatus' ];
               if ( $touchscreenStatus == "MAINT" && $exhibitStatus == "UP" ) $exhibitStatus = "MAINT";
               else if ( $touchscreenStatus == "DOWN" ) $exhibitStatus = "DOWN";

               switch ( $touchscreenStatus )
               {
                  case "UP":$touchscreenColor = "label-success";break;
                  case "MAINT":$touchscreenColor = "label-warning";break;
                  case "DOWN":$touchscreenColor = "label-danger";break;
               }
            }

            if ( $exhibit [ 'Computer' ] == '1' && $ticket [ 'computerStatus' ] != "UP" )
            {
               $computerStatus = $ticket [ 'computerStatus' ];
               if ( $computerStatus == "MAINT" && $exhibitStatus == "UP" ) $exhibitStatus = "MAINT";
               else if ( $computerStatus == "DOWN" ) $exhibitStatus = "DOWN";
            }

            if ( $exhibit [ 'Audio' ] == '1' && $ticket [ 'audioStatus' ] != "UP" )
            {
               $audioStatus = $ticket [ 'audioStatus' ];
               if ( $audioStatus == "MAINT" && $exhibitStatus == "UP" ) $exhibitStatus = "MAINT";
               else if ( $audioStatus == "DOWN" ) $exhibitStatus = "DOWN";
            }

            $ticketHTML .= "<div class='container-fluid'>";
            $ticketHTML .= "<div class='row'>";
            $ticketHTML .= "<div class='col-md-2'>Ticket #".$ticket [ 'ID' ]."</div>";
            $ticketHTML .= "<div class='col-md-4'>Submitted by:".$ticket [ 'createdBy' ]."</div>";
            $ticketHTML .= "<div class='col-md-4'>Assigned to:".$ticket [ 'assignedTo' ]."</div>";
            $ticketHTML .= "</div>";
            $ticketHTML .= "<div class='row'>";
            $ticketHTML .= "<div class='col-md-12'><pre>".$ticket [ 'problemDescription' ]."</pre></div>";
            $ticketHTML .= "</div>";	// End row
            $ticketHTML .= "</div>";	// End container
         }

         $monitorHTML = "<h3>Automated Monitoring</h3>";
         $sql = "SELECT * FROM `processes` WHERE `exhibitID` = ?";

         $processQuery = $this->db->prepare ( $sql );
         $processQuery->bindValue ( 1 , $exhibit [ 'ID' ] , PDO::PARAM_INT );
         $processQuery->execute();

         $monitorStatus = "UP";
         if ( $processQuery->rowCount() == 0 ) $monitorHTML .= "<div>(No automatic monitors configured on this exhibit)</div>";
         else
         {
            $monitorHTML .= "<div class='container-fluid'>";
            $monitorHTML .= "<div class='row'>";
            $monitorHTML .= "<div class='col-md-2' style='font-weight:bold;'>Computer</div>";
            $monitorHTML .= "<div class='col-md-2' style='font-weight:bold;'>Process</div>";
            $monitorHTML .= "<div class='col-md-4' style='font-weight:bold;'>Label</div>";
            $monitorHTML .= "<div class='col-md-1' style='font-weight:bold;'>Status</div>";
            $monitorHTML .= "<div class='col-md-3' style='font-weight:bold;'>Last Check</div>";
            $monitorHTML .= "</div>";	// End Row

            while ( $process = $processQuery->fetch ( PDO::FETCH_ASSOC ) )
            {
               if ( $process [ 'status' ] == '0' )
               {
                  $monitorStatus = "DOWN";
                  $monitorLabel = "<div class='label label-default'>DOWN</div>";
               }
               else
               {
                  $monitorLabel = "<div class='label label-success'>UP</div>";
               }

               $monitorHTML .= "<div class='row'>";
               $monitorHTML .= "<div class='col-md-2'>".$process [ 'computerName' ]."</div>";
               $monitorHTML .= "<div class='col-md-2'>".$process [ 'processName' ]."</div>";
               $monitorHTML .= "<div class='col-md-4'>".$process [ 'label' ]."</div>";
               $monitorHTML .= "<div class='col-md-1'>".$monitorLabel."</div>";
               $monitorHTML .= "<div class='col-md-3'>".$process [ 'lastCheck' ]."</div>";
               $monitorHTML .= "</div>";	// End Row
            }
            $monitorHTML .= "</div>";	// End Container
         }

         if ( $exhibit [ 'LastInspection' ] != "" )
         {
            $exhibitHTML .= "<h4>Inspections</h4>";
            $exhibitHTML .= "<div class='container-fluid'>";
            $exhibitHTML .= "<div class='row'>";
            $lastInspection = DateTime::createFromFormat ( "Y-m-d" , $exhibit [ 'LastInspection' ] );
            $exhibitHTML .= "<div class='col-md-4'>Last Inspection:<br>".$lastInspection->format ( "m-d-Y" )."</div>";
            switch ( $exhibit [ 'InspectionFrequency' ] )
            {
               case ( '1' ): $frequency = "DAILY";break;
               case ( '7' ): $frequency = "WEEKLY";break;
               default: $frequency = $exhibit [ 'InspectionFrequency' ]." days";break;
            }
            
            $exhibitHTML .= "<div class='col-md-3'>Frequency:<br>".$frequency."</div>";

            $today = new DateTime ( "now" );
            $lastInspection->add ( new DateInterval ( "P".$exhibit [ 'InspectionFrequency' ]."D" ) );
            if ( $today < $lastInspection ) $exhibitHTML .= "<div class='col-md-5'>Next Inspection:<br>".$lastInspection->format ( "m-d-Y" )."</div>";
            else $exhibitHTML .= "<div class='col-md-5' style='text-align:center;'><div class='alert alert-info' role='alert'>INSPECTION OVERDUE<br>Last Inspection due on<br>".$lastInspection->format ( "m-d-Y" )."</div></div>";
            $exhibitHTML .= "</div>";	// End row
            $exhibitHTML .= "</div>";	// End container
         }

         switch ( $exhibitStatus )
         {
            case "UP":$exhibitColor = "WHITE";break;
            case "MAINT":$exhibitColor = "ORANGE";break;
            case "DOWN":$exhibitColor = "RED";break;
         }

         // Element Icons
         $html .= "<div class='container-fluid' style='background-color:rgba(255,255,255,0.4);'>";
         $html .= "<div class='row'>";

         // Projector
         if ( $exhibit [ 'Projector' ] == '1' )
         {
            $html .= "<div class='col-md-3'>";
            $html .= "<div class='label ".$projectorColor."'>";
            $html .= "<span class='glyphicon glyphicon-facetime-video'></span> Projector Status";
            $html .= "</div>";	// End Label
            $html .= "</div>";	// End Column
         }
         else
         {
            //$html .= "<div class='col-md-3'>";
            //$html .= "<span class='glyphicon glyphicon-facetime-video'></span>";
            //$html .= "</div>";	// End Column
         }

         // Touchscreen
         if ( $exhibit [ 'Touchscreen' ] == '1' )
         {
            $html .= "<div class='col-md-3'>";
            $html .= "<div class='label ".$touchscreenColor."'>";
            $html .= "<span class='glyphicon glyphicon-blackboard'></span> Touchscreen Status";
            $html .= "</div>";	// End Label
            $html .= "</div>";	// End Column
         }
         else
         {
            //$html .= "<div class='col-md-3'>";
            //$html .= "<span class='glyphicon glyphicon-blackboard'></span>";
            //$html .= "</div>";	// End Column
         }

         // Computer
         if ( $monitorStatus == "DOWN" )
         {
            $html .= "<div class='col-md-3'>";
            $html .= "<div class='label label-default'>";
            $html .= "<span class='glyphicon glyphicon-hdd'></span> Computer Status";
            $html .= "</div>";	// End Label
            $html .= "</div>";	// End Column

         }
         else if ( $exhibit [ 'Computer' ] == '1' )
         {
            $html .= "<div class='col-md-3'>";
            $html .= "<div class='label label-success'>";
            $html .= "<span class='glyphicon glyphicon-hdd'></span> Computer Status";
            $html .= "</div>";	// End Label
            $html .= "</div>";	// End Column
         }
         else
         {
            //$html .= "<div class='col-md-3'>";
            //$html .= "<span class='glyphicon glyphicon-hdd'></span>";
            //$html .= "</div>";	// End Column
         }

         // Audio
         if ( $exhibit [ 'Audio' ] == '1' )
         {
            $html .= "<div class='col-md-3'>";
            $html .= "<div class='label label-success'>";
            $html .= "<span class='glyphicon glyphicon-volume-up'></span> Audio Status";
            $html .= "</div>";	// End Label
            $html .= "</div>";	// End Column
         }
         else
         {
            //$html .= "<div class='col-md-3'>";
            //$html .= "<span class='glyphicon glyphicon-volume-up'></span>";
            //$html .= "</div>";	// End Column
         }

         $html .= "</div>";	// End Row
         $html .= "</div>";	// End Container
         $html .= "</div>";	// End Column
         $html .= $exhibitHTML;
         $html .= $monitorHTML;
         $html .= "<h4>Open Tickets</h4>";
         $html .= $ticketHTML;

         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalBody" , $html );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( 'show' )" );
         $this->send();
      }
   }

   new web ( $db );

?>
