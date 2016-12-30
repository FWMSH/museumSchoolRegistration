<?php
   include "db.php";

   class web
   {
      function __construct ( $db )
      {
         $this->db = $db;

         switch ( $_POST [ 'action' ] )
         {
            case "ADMIN":$this->admin();break;
            case "CLASSES":$this->classes();break;
            case "CLASSDETAILS":$this->classDetails();break;
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
         //$this->checkLogin ( "ADMIN" );

         $html = <<<html
            <div class="btn-group">
               <button type="button" class="btn btn-primary">People</button>
               <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="caret"></span>
                  <span class="sr-only">Toggle Dropdown</span>
               </button>
               <ul class="dropdown-menu">
                  <li><a href="#">Action</a></li>
                  <li><a href="#">Another action</a></li>
                  <li><a href="#">Something else here</a></li>
                  <li role="separator" class="divider"></li>
                  <li><a href="#">Separated link</a></li>
               </ul>
            </div>
            <div class="btn-group">
               <button type="button" class="btn btn-primary" onclick='classes()'>Classes</button>
               <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="caret"></span>
                  <span class="sr-only">Toggle Dropdown</span>
               </button>
               <ul class="dropdown-menu">
                  <li><a href="#">Action</a></li>
                  <li><a href="#">Another action</a></li>
                  <li><a href="#">Something else here</a></li>
                  <li role="separator" class="divider"></li>
                  <li><a href="#">Separated link</a></li>
               </ul>
            </div>

html;

         $this->responseHTML ( "menu" , $html );
         $this->send();
      }

      function classes()
      {
         //$this->checkLogin ( "ADMIN" );

         $sql = "SELECT * FROM `classes` ORDER BY `StartDate`";
         $result = $this->db->query ( $sql );

         $html = "<div class='container-fluid'>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-1 ' style='font-weight:bold;'>Class Type</div>";
         $html .= "<div class='col-md-1 ' style='font-weight:bold;'>Class Name</div>";
         $html .= "<div class='col-md-1 ' style='font-weight:bold;'>Age</div>";
         $html .= "<div class='col-md-1 ' style='font-weight:bold;'>Meeting Days</div>";
         $html .= "<div class='col-md-2 ' style='font-weight:bold;'>Start Date<br>Start Time</div>";
         $html .= "<div class='col-md-2 ' style='font-weight:bold;'>End Date<br>End Time</div>";
         $html .= "<div class='col-md-1 ' style='font-weight:bold;'>Cost</div>";
         $html .= "<div class='col-md-1 ' style='font-weight:bold;'>Max Size</div>";
         $html .= "<div class='col-md-1 ' style='font-weight:bold;'>Current<br>Enrollment</div>";
         $html .= "<div class='col-md-1 ' style='font-weight:bold;'>Status</div>";
         $html .= "</div>";	// End Row
         while ( $class = $result->fetch ( PDO::FETCH_ASSOC ) )
         {
            $sql = "SELECT COUNT(`classID`) AS `count` FROM `registration` WHERE `classID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $class [ 'ID' ] , PDO::PARAM_INT );
            $query->execute();
            $enrolled = $query->fetch ( PDO::FETCH_ASSOC ) [ 'count' ];

            $percent = floatval ( $enrolled ) / floatval ( $class [ 'MaximumSize' ] )*100;

            $enrollment = <<<enrollment
            <div class="progress">
              <div class="progress-bar" role="progressbar" aria-valuenow="2" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: {$percent}%;">
              {$enrolled}
              </div>
            </div>
enrollment;
           
            $startDate = DateTime::createFromFormat ( "Y-m-d" , $class [ 'StartDate' ] )->format ( "n-d-Y" );
            $endDate = DateTIme::createFromFormat ( "Y-m-d" , $class [ 'EndDate' ] )->format ( "n-d-Y" ); 

            $html .= "<div class='row' onclick='classDetails ( ".$class [ 'ID' ]." )'>";
            $html .= "<div class='col-md-1'>".$class [ 'classType' ]."</div>";
            $html .= "<div class='col-md-1'>".$class [ 'ClassName' ]."</div>";
            $html .= "<div class='col-md-1'>".$class [ 'Age' ]."</div>";
            $html .= "<div class='col-md-1'>".$class [ 'MeetingDays' ]."</div>";
            $html .= "<div class='col-md-2'>".$startDate." ".$class [ 'StartTime' ]."</div>";
            $html .= "<div class='col-md-2'>".$endDate." ".$class [ 'EndTime' ]."</div>";
            $html .= "<div class='col-md-1'>$".$class [ 'Cost' ]."</div>";
            $html .= "<div class='col-md-1'>".$class [ 'MaximumSize' ]."</div>";
            $html .= "<div class='col-md-1'>".$enrollment."</div>";
            $html .= "<div class='col-md-1'>Status</div>";
            $html .= "</div>";	// End Row
         }
         $html .= "</div>";	// End Container

         $this->responseHTML ( "response" , $html );
         $this->send();
      }

      function classDetails()
      {
         $sql = "SELECT * FROM `classes` WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'classID' ] , PDO::PARAM_INT );
         $query->execute();
         $class = $query->fetch ( PDO::FETCH_ASSOC );

         $classSelected = "";
         $addonSelected = "";
         if ( $class [ 'classType' ] == "CLASS" ) $classSelected = "CHECKED";
         if ( $class [ 'classType' ] == "ADDON" ) $addonSelected = "CHECKED";

         $days = explode ( "," , $class [ 'MeetingDays' ] );

         $classSunday = "";
         $classMonday = "";
         $classTuesday = "";
         $classWednesday = "";
         $classThursday = "";
         $classFriday = "";
         $classSaturday = "";

         if ( array_search ( "SUNDAY" , $days ) == True ) $classSunday = "CHECKED";
         if ( array_search ( "MONDAY" , $days ) == True ) $classMonday = "CHECKED";
         if ( array_search ( "TUESDAY" , $days ) == True ) $classTuesday = "CHECKED";
         if ( array_search ( "WEDNESDAY" , $days ) == True ) $classWednesday = "CHECKED";
         if ( array_search ( "THURSDAY" , $days ) == True ) $classThursday = "CHECKED";
         if ( array_search ( "FRIDAY" , $days ) == True ) $classFriday = "CHECKED";
         if ( array_search ( "SATURDAY" , $days ) == True ) $classSaturday = "CHECKED";

         $title = "<h4>Class Details -- ".$class [ 'ClassName' ]."</h4>";
         $html = "<div class='container-fluid'>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='font-weight:bold;'>Class Name</div>";
         $html .= "<div class='col-md-8'><input type='text' class='form-control' id='className' value='".$class [ 'ClassName' ]."'></div>";
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4'>";
         $html .= "<b>Class Type</b><br>";
         $html .= "<input type='radio' name='typeClass' id='typeClass' value='CLASS' ".$classSelected."> Class <input type='radio' name='typeClass' id='typeClass' value='ADDON' ".$addonSelected."> Add On";
         $html .= "</div>";	// End Column
         $html .= "<div class='col-md-8'>";
         $html .= "Put Corequisite information here as applicable";
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div style='font-weight:bold;'>Long Description</div>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-12'>";
         $html .= "<textarea class='form-control' id='fullDescription'>".$class [ 'ClassDescription' ]."</textarea>";
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-12'>";
         $html .= "<div style='font-weight:bold;'>Short Description</div>";
         $html .= "<textarea class='form-control' id='shortDescription'>".$class [ 'ShortDescription' ] ."</textarea>";
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4'><b>Age(s)</b></div>";
         $html .= "<div class='col-md-8'>".$class [ 'Age' ]."</div>";
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-12'><b>Dates and Times</b></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'><b>Start Date</b><br><input type='date' id='startDate' value='".$class [ 'StartDate' ]."'></div>";
         $html .= "<div class='col-md-6'><b>End Date</b><br><input type='date' id='endDate' value='".$class [ 'EndDate' ]."'></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'><b>Start Time</b><br><input type='time' id='startTime' value='".$class [ 'StartTime' ]."'></div>";
         $html .= "<div class='col-md-6'><b>End Time</b><br><input type='time' id='endTime' value='".$class [ 'EndTime' ]."'></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'><b>Early Registration Start</b><br><input type='date' id='earlyRegistrationDate' value='".$class [ 'earlyRegistrationStart' ]."'></div>";
         $html .= "<div class='col-md-6'><b>Registration Start</b><br><input type='date' id='registrationDate' value='".$class [ 'registrationStart' ]."'></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'><b>Full Payment Deadline</b><br><input type='date' id='fullPayment' value='".$class [ 'fullPayment' ]."'></div>";
         $html .= "<div class='col-md-6'><b>Age Cutoff</b><br><input type='date' id='ageCutoff' value='".$class [ 'ageCutoff' ]."'></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'><b>No Class Dates</b><br>No Class Fields Here</div>";
         $html .= "<div class='col-md-6'><b>Meeting Days</b><br>";
         $html .= "<input type='checkbox' id='meetSunday' ".$classSunday."> Sunday<br>";
         $html .= "<input type='checkbox' id='meetMonday' ".$classMonday."> Monday<br>";
         $html .= "<input type='checkbox' id='meetTuesday' ".$classTuesday."> Tuesday<br>";
         $html .= "<input type='checkbox' id='meetWednesday' ".$classWednesday."> Wednesday<br>";
         $html .= "<input type='checkbox' id='meetThursday' ".$classThursday."> Thursday<br>";
         $html .= "<input type='checkbox' id='meetFriday' ".$classFriday."> Friday<br>";
         $html .= "<input type='checkbox' id='meetSaturday' ".$classSaturday."> Saturday<br>";
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='font-weight:bold;'>Class Tuition</div>";
         $html .= "<div class='col-md-8'>";
         $html .= "<div class='input-group'>";
         $html .= "<span class='input-group-addon'>$</span>";
         $html .= "<input type='text' class='form-control' value='".$class [ 'Cost' ]."'>";
         $html .= "</div>";	// End Input Group
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4'><b>Maximum Class Size</b></div>";
         $html .= "<div class='col-md-8'><input type='integer' class='form-control' id='maxSize' value='".$class [ 'MaximumSize' ]."'></div>";
         $html .= "</div>";	// End Row
         $html .= "</div>";	// End Container

         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalBody" , $html );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" );" );
         $this->send();

      }
   }

   new web ( $db );

?>
