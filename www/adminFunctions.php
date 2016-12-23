<?php
   include "db.php";

   class exhibit
   {
      function __construct ( $id )
      {
         global $db;

         $sql = "SELECT * FROM `exhibits` WHERE `ID` = ?";
         $query = $db->prepare ( $sql );
         $query->bindValue ( 1 , $id , PDO::PARAM_INT );
         $query->execute();
         $this->exhibit = $query->fetch ( PDO::FETCH_ASSOC );

         $this->ID = $this->exhibit [ 'ID' ];
         $this->name = $this->exhibit [ 'Name' ];
         $this->gallery = $this->exhibit [ 'Gallery' ];
         $this->location = $this->exhibit [ 'Location' ];
      
      }

      function subsystemLabels()
      {
         global $db;

         $sql = "SELECT * FROM `exhibitSubsystems` WHERE `exhibitID` = ?";
         $query = $db->prepare ( $sql );
         $query->bindValue ( 1 , $this->ID , PDO::PARAM_INT );
         $query->execute();

         $html = "<div class='container-fluid'>";
         $html .= "<div class='row'>";
         while ( $system = $query->fetch ( PDO::FETCH_ASSOC ) )
         {
            $system = new subsystem ( $system [ 'subsystemID' ] );
            $html .= "<div class='col-md-3'>".$system->label()."</div>";
         }
         $html .= "</div>";	// End row
         $html .= "</div>";	// End container

         return $html;
      }

      function addSubsystem ( $system , $label )
      {
         global $db;

         $sql = "INSERT INTO `exhibitSubsystems` ( `exhibitID` , `subsystemID` , `systemLabel` ) VALUES ( :exhibit , :subsystem , :label )";
         $query = $db->prepare ( $sql );
         $query->bindValue ( ":exhibit" , $this->ID , PDO::PARAM_INT );
         $query->bindValue ( ":subsystem" , $system , PDO::PARAM_INT );
         $query->bindValue ( ":label" , $label , PDO::PARAM_INT );
         $query->execute();

         $this->subsystemLabels();
      }
   }

   class subsystem
   {
      function __construct ( $id )
      {
         global $db;

         $sql = "SELECT * FROM `systemTypes` WHERE `ID` = ?";
         $query = $db->prepare ( $sql );
         $query->bindValue ( 1 , $id , PDO::PARAM_INT );
         $query->execute();
         $this->system = $query->fetch ( PDO::FETCH_ASSOC );
      }

      function icon ( $status = null , $flash = False )
      {
         $html = "<div class='label ";
         if ( $status != null ) $html .= $status;
         else $html .= "label-default ";
         if ( $flash == True ) $html .= "flash";
         $html .= "'><span class='glyphicon ".$this->system [ 'systemIcon' ]."'></span></div>";
         return $html;
      }

      function label ( $status = null , $flash = False )
      {
         $html = "<div class='label ";
         if ( $status != null ) $html .= $status;
         else $html .= "label-default ";
         if ( $flash == True ) $html .= "flash";
         $html .= "'><span class='glyphicon ".$this->system [ 'systemIcon' ]."'></span> ".$this->system [ 'systemName' ]."</div>";
         return $html;
      }

   }

   class web
   {
      function __construct ( $db )
      {
         $this->db = $db;

         switch ( $_POST [ 'action' ] )
         {
            case "ADMIN":$this->admin();break;
            case "SUBSYSTEMS":$this->subsystems();break;
            case "NEWEXHIBIT":$this->newExhibit();break;
            case "PROCESSNEWEXHIBIT":$this->processNewExhibit();break;
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

      function admin()
      {
         $sql = "SELECT * FROM `exhibits` ORDER BY `Gallery`";
         $response = $this->db->query ( $sql );

         $oldGallery = null;
         $html = "<button class='btn btn-primary' onclick='newExhibit()'>";
         $html .= "<span class='glyphicon glyphicon-plus'></span> New Exhibit";
         $html .= "</button>";
         $html .= "<div class='container-fluid'>";
         while ( $exhibit = $response->fetch ( PDO::FETCH_ASSOC ) )
         {
            $sql = "SELECT * FROM `processes` WHERE `ID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $exhibit [ 'ID' ] , PDO::PARAM_INT );
            $query->execute();

            $monitor = "";
            while ( $process = $query->fetch ( PDO::FETCH_ASSOC ) )
            {
               $monitor .= $process [ 'label' ]."<br>";
            }

            $sql = "SELECT * FROM `pings` WHERE `ID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $exhibit [ 'ID' ] , PDO::PARAM_INT );
            $query->execute();

            $addresses = "";
            while ( $device = $query->fetch ( PDO::FETCH_ASSOC ) )
            {
               $addresses .= $device [ 'computerName' ]." (".$device [ 'address' ].")<br>";
            }

            if ( $oldGallery != $exhibit [ 'Gallery' ] )
            {
               $html .= "<h3>".$exhibit [ 'Gallery' ]."</h3>";
               $oldGallery = $exhibit [ 'Gallery' ];

               $html .= "<div class='row'>";
               $html .= "<div class='col-md-2' style='font-weight:bold;'>Exhibit</div>";
               $html .= "<div class='col-md-2' style='font-weight:bold;'>Location</div>";
               $html .= "<div class='col-md-1' style='font-weight:bold;'>Inspection Frequency</div>";
               $html .= "<div class='col-md-2' style='font-weight:bold;'>Process Monitors</div>";
               $html .= "<div class='col-md-2' style='font-weight:bold;'>IP Addresses</div>";
               $html .= "<div class='col-md-2' style='font-weight:bold;'>Subsystems</div>";
               $html .= "</div>";	// End Row
            }

            $menu = <<<menu
<div class="btn-group">
  <button type="button" class="btn btn-primary btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    Edit <span class="caret"></span>
  </button>
  <ul class="dropdown-menu">
    <li><a href="#">Exhibit Details</a></li>
    <li><a href="#">Processes</a></li>
    <li><a href="#">IP Addresses</a></li>
    <li><a href="#" onclick='subsystems ( {$exhibit [ 'ID' ]} )'>Subsystems</a></li>
    <li role="separator" class="divider"></li>
    <li><a href="#">Delete Exhibit</a></li>
  </ul>
</div>
menu;

            switch ( $exhibit [ 'InspectionFrequency' ] )
            {
               case "1": $frequency = "Daily";break;
               case "7": $frequency = "Weekly";break;
               default: $frequency = $exhibit [ 'InspectionFrequency' ]." days";break;
            }
            $html .= "<div class='row'>";
            $html .= "<div class='col-md-2'>".$exhibit [ 'Name' ]."</div>";
            $html .= "<div class='col-md-2'>".$exhibit [ 'Location' ]."</div>";
            $html .= "<div class='col-md-1'>".$frequency."</div>";
            $html .= "<div class='col-md-2'>".$monitor."</div>";
            $html .= "<div class='col-md-2'>".$addresses."</div>";
            $html .= "<div class='col-md-2'></div>";
            $html .= "<div class='col-md-1'>".$menu."</div>";
            $html .= "</div>";	// End Row
         }
         $html .= "</div>";	// End Column

         $this->responseHTML ( "response" , $html );
         $this->send();
      }

      function subsystems()
      {
         $exhibit = new exhibit ( $_POST [ 'exhibitID' ] );

         $header = "<h4>".$exhibit->name." Subsystems</h4>";
         $html = $exhibit->subsystemLabels();

         $sql = "SELECT * FROM `systemTypes`";
         $response = $this->db->query ( $sql );

         $systemSelector = "<select id='systemSelector'>";
         while ( $system = $response->fetch ( PDO::FETCH_ASSOC ) )
         {
            $systemSelector .= "<option value='".$system [ 'ID' ]."'>".$system [ 'systemName' ]."</option>";
         }
         $systemSelector .= "</select>";

         $html .= "<div>";
         $html .= "<div style='font-weight:bold;'>Add a Subsystem</div>";
         $html .= $systemSelector;
         $html .= "</div>";

         $this->responseHTML ( "modalTitle" , $header );
         $this->responseHTML ( "modalBody" , $html );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" )" );
         $this->send();
      }

      function newExhibit()
      {
         $sql = "SELECT DISTINCT `Gallery` FROM `exhibits` ORDER BY `Gallery` ASC";
         $result = $this->db->query ( $sql );

         $gallerySelector = "<select id='gallery' class='form-control' onchange='checkGallery ( this.id , this.value )'>";
         $gallerySelector .= "<option disabled selected>Please choose a gallery or create a new one</option>";
         $gallerySelector .= "<option value='NEW'>New Gallery...</option>";

         while ( $gallery = $result->fetch ( PDO::FETCH_ASSOC ) )
         {
            $gallerySelector .= "<option value='".$gallery [ 'Gallery' ]."'>".$gallery [ 'Gallery' ]."</option>";
         }
         $gallerySelector .= "</select>";

         $html = <<<html
            <div class='container-fluid'>
               <div class='row'>
                  <div class='col-md-6' style='font-weight:bold;'>Exhibit Name</div>
                  <div class='col-md-6'><input type='text' class='form-control' id='exhibitName'></div>
               </div>
               <div class='row'>
                  <div class='col-md-6' style='font-weight:bold;'>Gallery</div>
                  <div class='col-md-6'>{$gallerySelector}</div>
               </div>
               <div class='row'>
                  <div class='col-md-6' style='font-weight:bold;'>Location</div>
                  <div class='col-md-6'><input type='text' class='form-control' id='location'></div>
               </div>
               <div class='row'>
                  <div class='col-md-6' style='font-weight:bold;'>Inspection Frequency (days)</div>
                  <div class='col-md-6'><input type='integer' class='form-control' id='frequency'></div>
               </div>
               <div class='row'>
                  <div class='col-md-6' style='font-weight:bold;'>Last Inspection</div>
                  <div class='col-md-6'><input type='date' class='form-control' id='lastInsepction'></div>
               </div>

            </div>
html;

         $buttons = "<input type='button' class='btn btn-danger' value='Close' onclick='$ ( \"#modal\" ).modal ( \"hide\" );'>";
         $buttons .= "<input type='button' class='btn btn-success' value='Create Exhibit' onclick='processNewExhibit()'>";
         $this->responseHTML ( "modalTitle" , "<h4>New Exhibit</h4>" );
         $this->responseHTML ( "modalBody" , $html );
         $this->responseHTML ( "modalFooter" , $buttons );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" )" );
         $this->send();
      }

      function processNewExhibit()
      {
         $sql = "INSERT INTO `exhibits` ( `Name` , `Gallery` , `Location` , `InspectionFrequency` , `LastInspection` ) VALUES ( :name , :gallery , :location , :frequency , :lastInspection )";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":name" , $_POST [ 'name' ] , PDO::PARAM_STR );
         $query->bindValue ( ":gallery" , $_POST [ 'gallery' ] , PDO::PARAM_STR );
         $query->bindValue ( ":location" , $_POST [ 'location' ] , PDO::PARAM_STR );
         $query->bindValue ( ":frequency" , $_POST [ 'frequency' ] , PDO::PARAM_STR );
         $query->bindValue ( ":lastInspection" , $_POST [ 'lastInspection' ] , PDO::PARAM_STR );
         $query->execute();
      }

      function exhibitDetails()
      {
         
      }
   }

   new web ( $db );

?>
