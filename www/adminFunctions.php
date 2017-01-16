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
            case "UPDATEFIELD":$this->updateField();break;
            case "ADDNOCLASS":$this->addNoClass();break;
            case "REMOVENOCLASS":$this->removeNoClass();break;
            case "NEWCLASS":$this->newClass();break;
            case "PEOPLE":$this->people();break;
            case "PAYMENTS":$this->payments();break;
            case "CHILDDETAIL":$this->childDetail();break;
            case "VERIFY":$this->verify();break;
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
               <button type="button" class="btn btn-primary" onclick='people()'>People</button>
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
                  <li><a href="#" onclick='newClass()'>New Class</a></li>
                  <li role="separator" class="divider"></li>
                  <li><a href="#">Separated link</a></li>
               </ul>
            </div>
            <div class="btn-group">
               <button type="button" class="btn btn-primary" onclick='payments()'>Payments</button>
               <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="caret"></span>
                  <span class="sr-only">Toggle Dropdown</span>
               </button>
               <ul class="dropdown-menu">
                  <li><a href="#" onclick='newClass()'>New Class</a></li>
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
          
            if ( $class [ 'StartDate' ] == "0000-00-00" ) $startDate = DateTime::createFromFormat ( "Y-m-d" , $class [ 'StartDate' ] )->format ( "n-d-Y" );
            else $startDate = date ( "Y-m-d" );
            if ( $class [ 'EndDate' ] == "0000-00-00" ) $endDate = DateTIme::createFromFormat ( "Y-m-d" , $class [ 'EndDate' ] )->format ( "n-d-Y" ); 
            else $endDate = date ( "Y-m-d" );

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

         if ( array_search ( "SUNDAY" , $days ) !== False ) $classSunday = "CHECKED";
         if ( array_search ( "MONDAY" , $days ) !== False ) $classMonday = "CHECKED";
         if ( array_search ( "TUESDAY" , $days ) !== False ) $classTuesday = "CHECKED";
         if ( array_search ( "WEDNESDAY" , $days ) !== False ) $classWednesday = "CHECKED";
         if ( array_search ( "THURSDAY" , $days ) !== False ) $classThursday = "CHECKED";
         if ( array_search ( "FRIDAY" , $days ) !== False ) $classFriday = "CHECKED";
         if ( array_search ( "SATURDAY" , $days ) !== False ) $classSaturday = "CHECKED";

         $noClass = explode ( "\n" , $class [ 'noClassDates' ] );
         $noClassHTML = "";

         foreach ( $noClass AS $key=>$classDay )
         {
            $dtParts = explode ( "/" , $classDay );
            $month = intval ( $dtParts [ 0 ] );
            $day = intval ( $dtParts [ 1 ] );
            $year = $dtParts [ 2 ];

            if ( strlen ( trim ( $year ) ) == 2 ) $year = "20".$year;
            
            $dt = new DateTime();
            $dt->setDate ( intval ( $year ) , $month , $day );

            $noClassHTML .= "<input type='button' class='btn btn-xs btn-danger' onclick='removeNoClass ( ".$class [ 'ID' ]." , ".$key." )' value='X'> ";
            $noClassHTML .= $dt->format ( "F jS, Y" )."<br>";
         }
         $noClassHTML .= "<b>Add a no-class day</b><br>";
         $noClassHTML .= "<input type='date' id='newNoClass'><br>";
         $noClassHTML .= "<input type='button' class='btn btn-xs btn-primary' value='Add No Class Date' onclick='addNoClass ( ".$class [ 'ID' ]." )'>";

         $title = "<h4>Class Details -- ".$class [ 'ClassName' ]."</h4>";
         $html = "<div class='container-fluid'>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='font-weight:bold;'>Class Name</div>";
         $html .= "<div class='col-md-8'><input type='text' class='form-control' id='className' value='".$class [ 'ClassName' ]."' onchange='updateField ( ".$class [ 'ID' ]." , 3 , this.value )'></div>";
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4'>";
         $html .= "<b>Class Type</b><br>";
         $html .= "<input type='radio' name='typeClass' id='typeClass' value='CLASS' ".$classSelected." onchange='updateField ( ".$class [ 'ID' ]." , 4 , this.value )'> Class <input type='radio' name='typeClass' id='typeClass' value='ADDON' ".$addonSelected." onchange='updateField ( ".$class [ 'ID' ]." , 4 , this.value )'> Add On";
         $html .= "</div>";	// End Column
         $html .= "<div class='col-md-8'>";
         $html .= "Put Corequisite information here as applicable";
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div style='font-weight:bold;'>Long Description</div>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-12'>";
         $html .= "<textarea class='form-control' id='fullDescription' onchange='updateField ( ".$class [ 'ID' ]." , 2 , this.value )'>".$class [ 'ClassDescription' ]."</textarea>";
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-12'>";
         $html .= "<div style='font-weight:bold;'>Short Description</div>";
         $html .= "<textarea class='form-control' id='shortDescription' onchange='updateField ( ".$class [ 'ID' ]." , 16 , this.value )'>".$class [ 'ShortDescription' ] ."</textarea>";
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
         $html .= "<div class='col-md-6'><b>Start Date</b><br><input type='date' id='startDate' value='".$class [ 'StartDate' ]."' onchange='updateField ( ".$class [ 'ID' ]." , 17 , this.value )'></div>";
         $html .= "<div class='col-md-6'><b>End Date</b><br><input type='date' id='endDate' value='".$class [ 'EndDate' ]."' onchange='updateField ( ".$class [ 'ID' ]." , 9 , this.value )'></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'><b>Start Time</b><br><input type='time' id='startTime' value='".$class [ 'StartTime' ]."' onchange='updateField ( ".$class [ 'ID' ]." , 18 , this.value )'></div>";
         $html .= "<div class='col-md-6'><b>End Time</b><br><input type='time' id='endTime' value='".$class [ 'EndTime' ]."' onchange='updateField ( ".$class [ 'ID' ]." , 10 , this.value )'></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'><b>Early Registration Start</b><br><input type='datetime-local' id='earlyRegistrationDate' value='".str_replace ( " " , "T" , $class [ 'earlyRegistrationStart' ] )."' onchange='updateField ( ".$class [ 'ID' ]." , 8 , this.value )'></div>";
         $html .= "<div class='col-md-6'><b>Registration Start</b><br><input type='datetime-local' id='registrationDate' value='".str_replace ( " " , "T" , $class [ 'registrationStart' ] )."' onchange='updateField ( ".$class [ 'ID' ]." , 15 , this.value )'></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'><b>Full Payment Deadline</b><br><input type='datetime-local' id='fullPaymentDeadline' value='".str_replace ( " " , "T" , $class [ 'fullPaymentDeadline' ] )."' onchange='updateField ( ".$class [ 'ID' ]." , 11 , this.value )'></div>";
         $html .= "<div class='col-md-6'><b>Age Cutoff</b><br><input type='date' id='ageCutoff' value='".$class [ 'ageCutoff' ]."' onchange='updateField ( ".$class [ 'ID' ]." , 1 , this.value )'></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'><b>No Class Dates</b><br>".$noClassHTML."</div>";
         $html .= "<div class='col-md-6'><b>Meeting Days</b><br>";
         $html .= "<input type='checkbox' id='meetSunday' onchange='updateField ( ".$class [ 'ID' ]." , 20 , \"SUNDAY\" )'".$classSunday."> Sunday<br>";
         $html .= "<input type='checkbox' id='meetMonday' onchange='updateField ( ".$class [ 'ID' ]." , 20 , \"MONDAY\" )'".$classMonday."> Monday<br>";
         $html .= "<input type='checkbox' id='meetTuesday' onchange='updateField ( ".$class [ 'ID' ]." , 20 , \"TUESDAY\" )'".$classTuesday."> Tuesday<br>";
         $html .= "<input type='checkbox' id='meetWednesday' onchange='updateField ( ".$class [ 'ID' ]." , 20 , \"WEDNESDAY\" )'".$classWednesday."> Wednesday<br>";
         $html .= "<input type='checkbox' id='meetThursday' onchange='updateField ( ".$class [ 'ID' ]." , 20 , \"THURSDAY\" )'".$classThursday."> Thursday<br>";
         $html .= "<input type='checkbox' id='meetFriday' onchange='updateField ( ".$class [ 'ID' ]." , 20 , \"FRIDAY\" )'".$classFriday."> Friday<br>";
         $html .= "<input type='checkbox' id='meetSaturday' onchange='updateField ( ".$class [ 'ID' ]." , 20 , \"SATURDAY\" )'".$classSaturday."> Saturday<br>";
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='font-weight:bold;'>Class Tuition</div>";
         $html .= "<div class='col-md-8'>";
         $html .= "<div class='input-group'>";
         $html .= "<span class='input-group-addon'>$</span>";
         $html .= "<input type='text' class='form-control' value='".$class [ 'Cost' ]."' onchange='updateField ( ".$class [ 'ID' ]." , 6 , this.value )'>";
         $html .= "</div>";	// End Input Group
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "<hr>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4'><b>Maximum Class Size</b></div>";
         $html .= "<div class='col-md-8'><input type='integer' class='form-control' id='maxSize' value='".$class [ 'MaximumSize' ]."' onchange='updateField ( ".$class [ 'ID' ]." , 12 , this.value )'></div>";
         $html .= "</div>";	// End Row
         $html .= "</div>";	// End Container

         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalBody" , $html );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" );" );
         $this->send();

      }

      function addNoClass()
      {
         $sql = "UPDATE `classes` SET `noClassDates` = CONCAT ( `noClassDates` , '\n' , ? ) WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );

         $dt = DateTime::createFromFormat ( "Y-m-d" , $_POST [ 'date' ] );

         $query->bindValue ( 1 , $dt->format ( "m/d/Y" ) , PDO::PARAM_STR );
         $query->bindValue ( 2 , $_POST [ 'class' ] , PDO::PARAM_STR );
         $query->execute();

         $this->responseScript ( "classDetails ( ".$_POST [ 'class' ]." )" );
         $this->send();
      }

      function removeNoClass()
      {
         $sql = "SELECT * FROM `classes` WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'class' ] , PDO::PARAM_INT );
         $query->execute();
         $class = $query->fetch ( PDO::FETCH_ASSOC );

         $classDays = explode ( "\n" , $class [ 'noClassDates' ] );
         unset ( $classDays [ $_POST [ 'key' ] ] );
         $value = implode ( "\n" , $classDays );

         $sql = "UPDATE `classes` SET `noClassDates`=? WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $value , PDO::PARAM_STR );
         $query->bindValue ( 2 , $_POST [ 'class' ] , PDO::PARAM_INT );
         $query->execute();

         $this->responseScript ( "classDetails ( ".$_POST [ 'class' ]." )" );
         $this->send();
      }

      function updateField()
      {
         $fields = array();
         $fields [ 0 ] = 'Age';
         $fields [ 1 ] = 'ageCutoff';
         $fields [ 2 ] = 'ClassDescription';
         $fields [ 3 ] = 'ClassName';
         $fields [ 4 ] = 'classType';
         $fields [ 5 ] = 'coRequisites';
         $fields [ 6 ] = 'Cost';
         $fields [ 7 ] = 'CreatedBy';
         $fields [ 8 ] = 'earlyRegistrationStart';
         $fields [ 9 ] = 'EndDate';
         $fields [ 10 ] = 'EndTime';
         $fields [ 11 ] = 'fullPaymentDeadline';
         $fields [ 12 ] = 'MaximumSize';
         $fields [ 13 ] = 'MeetingDays';
         $fields [ 14 ] = 'noClassDates';
         $fields [ 15 ] = 'registrationStart';
         $fields [ 16 ] = 'ShortDescription';
         $fields [ 17 ] = 'StartDate';
         $fields [ 18 ] = 'StartTime';
         $fields [ 19 ] = 'Teacher';
         $fields [ 20 ] = 'MeetingDays';

         switch ( $_POST [ 'field' ] )
         {
            case 8: $value = str_replace ( "T" , " " , $_POST [ 'value' ] ).":00";break;
            case 11: $value = str_replace ( "T" , " " , $_POST [ 'value' ] ).":00";break;
            case 15: $value = str_replace ( "T" , " " , $_POST [ 'value' ] ).":00";break;
            case 20:
            {
               $sql = "SELECT * FROM `classes` WHERE `ID` = ?";
               $query = $this->db->prepare ( $sql );
               $query->bindValue ( 1 , $_POST [ 'classID' ] , PDO::PARAM_INT );
               $query->execute();
               $class = $query->fetch ( PDO::FETCH_ASSOC );

               if ( $class [ 'MeetingDays' ] == "" ) $value = $_POST [ 'value' ];
               else
               {
                  $classDays = explode ( "," , $class [ 'MeetingDays' ] );
                  $result = array_search ( $_POST [ 'value' ] , $classDays );
                  if ( $result === False )
                  {
                     $classDays[] = $_POST [ 'value' ];
                  }
                  else
                  {
                     unset ( $classDays [ $result ] );
                  }
   
                  $value = implode ( "," , $classDays );
               }
            };break;
            default: $value = $_POST [ 'value' ];
         }

         $sql = "UPDATE `classes` SET `".$fields [ $_POST [ 'field' ] ]."` = ? WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $value , PDO::PARAM_STR );
         $query->bindValue ( 2 , $_POST [ 'classID' ] , PDO::PARAM_INT );
         $query->execute();

         $this->responseScript ( "classDetails ( ".$_POST [ 'classID' ]." )" );
         $this->send();
      }

      function newClass()
      {
         $sql = "INSERT INTO `classes` ( `ClassName` ) VALUES ( ' ' )";
         $this->db->query ( $sql );
         $classID = $this->db->lastInsertId();

         $this->responseScript ( "classDetails ( ".$classID." )" );
         $this->send();
      }

      function people()
      {
         $sql = "SELECT * FROM `people` ORDER BY `lastName` ASC, `firstName` ASC";
         $result = $this->db->query ( $sql );

         $html = "<div class='container-fluid'>";
         while ( $person = $result->fetch ( PDO::FETCH_ASSOC ) )
         {
            if ( $person [ 'enabled' ] == 1 ) $enabled = "<div class='label label-success'>Yes</div>";
            else $enabled = "<div class='label label-danger'>No</div>";

            if ( $person [ 'textOK' ] == 1 ) $text = "<div class='label label-success'>Texts OK</div>";
            else $text = "<div class='label label-danger'>No texts</div>";

            $sql = "SELECT * FROM `children` WHERE `parentID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $person [ 'ID' ] , PDO::PARAM_INT );
            $query->execute();

            $childHTML = "";
            while ( $child = $query->fetch ( PDO::FETCH_ASSOC ) )
            {
               $bday = DateTime::createFromFormat ( "Y-m-d" , $child [ 'birthday' ] );
               $age = $bday->diff ( new DateTime ( "now" ) , True );
               if ( $age->format ( "%y" ) == "0" ) $ageString = $age->format ( "%m" )." months";
               else $ageString = $age->format ( "%y" );

               $childHTML .= "<a href='#' onclick='childDetail ( ".$child [ 'ID' ]." )'>".$child [ 'childName' ]." - ".substr ( $child [ 'gender' ] , 0 , 1 )." - ".$ageString."</a><br>";
            }

            $html .= "<div class='row'>";
            $html .= "<div class='col-md-1'>".$person [ 'role' ]."</div>";
            $html .= "<div class='col-md-2'>".$person [ 'lastName' ].", ".$person [ 'firstName' ]."</div>";
            $html .= "<div class='col-md-2'>".$childHTML."</div>";
            $html .= "<div class='col-md-3'>".$person [ 'address' ].", ".$person [ 'city' ].", ".$person [ 'state' ].", ".$person [ 'zip' ]."</div>";
            $html .= "<div class='col-md-1'>".$person [ 'homePhone' ]."</div>";
            $html .= "<div class='col-md-1'>".$person [ 'workPhone' ]."</div>";
            $html .= "<div class='col-md-1'>".$person [ 'cellPhone' ]."<br>".$text."</div>";
            $html .= "<div class='col-md-1'>".$enabled."</div>";
            $html .= "</div>";	// End Row
         }
         $html .= "</div>";	// End Container

         $this->responseHTML ( "response" , $html );
         $this->send();
      }

      function childDetail()
      {
         $sql = "SELECT * FROM `children` WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'childID' ] , PDO::PARAM_INT );
         $query->execute();
         $child = $query->fetch ( PDO::FETCH_ASSOC );

         if ( $child [ 'verified' ] == 1 )
         {
            $verified = "<div class='col-md-4'>";
            $verified .= "<div class='label label-success'>Age Verified</div>";
            $verified .= "</div>";
            $verified .= "<div class='col-md-4'><input type='button' class='btn btn-danger btn-xs' value='Unverify Birthday' onclick='verify ( ".$child [ 'ID' ]." , 0 )'></div>";
         }
         else
         {
            $verified = "<div class='col-md-4'>";
            $verified .= "<div class='label label-danger'>Age Not Verified</div>";
            $verified .= "</div>";
            $verified .= "<div class='col-md-4'><input type='button' class='btn btn-success btn-xs' value='Verify Birthday' onclick='verify ( ".$child [ 'ID' ]." , 1 )'></div>";
         }
 
         $title = "<h4>".$child [ 'childName' ]."</h4>";
         $html = "<div class='container-fluid'>";
         $bday = DateTime::createFromFormat ( "Y-m-d" , $child [ 'birthday' ] );
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4'>Birthday</div>";
         $html .= "<div class='col-md-4'>".$bday->format ( "F jS, Y" )."</div>";
         $html .= "<div class='col-md-4'><input type='button' class='btn btn-primary btn-xs' value='Change Birthday'></div>";
         $html .= "</div>";	// End Row
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4'>Verified</div>";
         $html .= $verified;
         $html .= "</div>";	// End Row

         $html .= "<h4>Classes</h4>";

         $sql = "SELECT * FROM `registration` WHERE `childID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $child [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         while ( $registration = $query->fetch ( PDO::FETCH_ASSOC ) )
         {
            $sql = "SELECT * FROM `classes` WHERE `ID` = ?";
            $classQuery = $this->db->prepare ( $sql );
            $classQuery->bindValue ( 1 , $registration [ 'classID' ] , PDO::PARAM_INT );
            $classQuery->execute();
            $class = $classQuery->fetch ( PDO::FETCH_ASSOC );

            $sql = "SELECT * FROM `paymentItems` WHERE `registrationID` = ?";
            $payQuery = $this->db->prepare ( $sql );
            $payQuery->bindValue ( 1 , $registration [ 'ID' ] , PDO::PARAM_INT );
            $payQuery->execute();
            $payment = $payQuery->fetch ( PDO::FETCH_ASSOC );

            $startTime = DateTime::createFromFormat ( "H:i:s" , $class [ 'StartTime' ] );
            $endTime = DateTime::createFromFormat ( "H:i:s" , $class [ 'EndTime' ] );
            $times = $startTime->format ( "g:i A" )." - ".$endTime->format ( "g:i A" );

            $html .= "<div class='row'>";
            $html .= "<div class='col-md-2'>".$class [ 'ClassName' ]."</div>";
            $html .= "<div class='col-md-2'>".str_replace ( "," , "<br>" , $class [ 'MeetingDays' ] )."</div>";
            $html .= "<div class='col-md-3'>".$times."</div>";
            $html .= "<div class='col-md-2'>".$payment [ 'status' ]."</div>";
            $html .= "<div class='col-md-2'><input type='button' class='btn btn-danger btn-xs' value='Unenroll'></div>";
            $html .= "</div>";
         }


         $html .= "</div>";	// End Container

         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalBody" , $html );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" )" );
         $this->send();
      }

      function verify()
      {
         $sql = "UPDATE `children` SET `verified` = ? WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'verified' ] , PDO::PARAM_INT );
         $query->bindValue ( 2 , $_POST [ 'childID' ] , PDO::PARAM_INT );
         $query->execute();

         $this->responseScript ( "childDetail ( ".$_POST [ 'childID' ]." )" );
         $this->send();
      }

      function payments()
      {
         $sql = "SELECT * FROM `paymentItems` ORDER BY `userID`";
         $response = $this->db->query ( $sql );

         $oldName = "";
         $html = "<div class='container-fluid'>";
         while ( $payment = $response->fetch ( PDO::FETCH_ASSOC ) )
         {
            $sql = "SELECT * FROM `people` WHERE `ID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $payment [ 'userID' ] , PDO::PARAM_INT );
            $query->execute();
            $person = $query->fetch ( PDO::FETCH_ASSOC );

            $sql = "SELECT * FROM `registration` WHERE `ID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $payment [ 'registrationID' ] , PDO::PARAM_INT );
            $query->execute();
            $registration = $query->fetch ( PDO::FETCH_ASSOC );

            $sql = "SELECT * FROM `classes` WHERE `ID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $registration [ 'classID' ] , PDO::PARAM_INT );
            $query->execute();
            $class = $query->fetch ( PDO::FETCH_ASSOC );

            if ( $oldName != $person [ 'lastName' ].", ".$person [ 'firstName' ] )
            {
               $oldName = $person [ 'lastName' ].", ".$person [ 'firstName' ];
               $html .= "<h4>".$oldName."</h4>";
            }
            $html .= "<div class='row'>";
            $html .= "<div class='col-md-2'>".$class [ 'ClassName' ]."</div>";
            $html .= "<div class='col-md-1'>".$payment [ 'type' ]."</div>";
            $html .= "<div class='col-md-1'>$".$payment [ 'amount' ]."</div>";
            $html .= "</div>";	// End Row
         }
         $html .= "</div>";	// End Container

         $this->responseHTML ( "response" , $html );
         $this->send();
      }
   }

   new web ( $db );

?>
