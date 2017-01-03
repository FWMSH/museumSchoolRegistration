<?php
   include "db.php";

   class web
   {
      function __construct ( $db )
      {
         $this->db = $db;

         switch ( $_POST [ 'action' ] )
         {
            case "LOGIN":$this->login();break;
            case "HOME":$this->home();break;
            case "ADDFAMILY":$this->addFamily();break;
            case "PROCESSADDFAMILY":$this->processAddFamily();break;
            case "CHANGEFILTER":$this->changeFilter();break;
            case "CLASSDETAILS":$this->classDetails();break;
            case "ENROLL":$this->enroll();break;
            case "DROP":$this->dropClass();break;
            case "CHECKOUT":$this->checkout();break;
            case "SUCCESFULPAYMENT":$this->succesfulPayment();break;
            case "RECALCULATETOTAL":$this->recalculateTotal();break;
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

      function checkLogin()
      {
         session_start();
         if ( !isset ( $_SESSION [ 'ID' ] ) )
         {
            $this->responseScript ( "window.location=\"index.html\"" );
            $this->send();
         }
      }

      function home()
      {
         $this->checkLogin();

         $sql = "SELECT * FROM `children` WHERE `parentID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $family = "<h4>Children</h4>";
         $family .= "<hr>";
         if ( $query->rowCount() == 0 )
         {
            $family .= "<div class='container-fluid'>";
            $family .= "<div class='row'>";
            $family .= "<div class='col-md-12' style='font-weight:bold;'>(No children added yet)</div>";
            $family .= "</div>";
         }
         else
         {
            $family .= "<div class='container-fluid'>";
            $family .= "<div class='row'>";
            $family .= "<div class='col-md-4' style='font-weight:bold;'>Name</div>";
            $family .= "<div class='col-md-1' style='font-weight:bold;'>Gender</div>";
            $family .= "<div class='col-md-1' style='font-weight:bold;'>Age</div>";
            $family .= "</div>";
            while ( $child = $query->fetch ( PDO::FETCH_ASSOC ) )
            {
               $bday = DateTime::createFromFormat ( "Y-m-d" , $child [ 'birthday' ] );
               $today = new DateTime ( "now" );
               $age = $today->diff ( $bday );

               if ( intval ( $age->format ( "%y" ) ) == 0 ) $ageString = $age->format ( "%m" )." months";
               else if ( intval ( $age->format ( "%y" ) ) == 1 ) $ageString = "1 year"; 
               else $ageString = $age->format ( "%y" )." years";

               $family .= "<div class='row'>";
               $family .= "<div class='col-md-4'>".$child [ 'childName' ]."</div>";
               $family .= "<div class='col-md-1'>".substr ( $child [ 'gender' ] , 0 , 1 )."</div>";
               $family .= "<div class='col-md-2'>".$ageString."</div>";
               $family .= "</div>";	// End Row
            }
         }
         $family .= "</div>";	// End Container
         $family .= "<button class='btn btn-primary' onclick='addFamily()'><span class='glyphicon glyphicon-plus-sign'></span> Add a Child</button>";

         $myClasses = "<h4>My Classes ";
         $myClasses .= "<span id='clock' style='color:blue;'></span>";
         $myClasses .= "</h4>";
         $myClasses .= "<hr>";
         $myClasses .= "<div class='container-fluid'>";
         $myClasses .= "<div class='row'>";
         $myClasses .= "<div class='col-md-3' style='font-weight:bold;'>Student</div>";
         $myClasses .= "<div class='col-md-3' style='font-weight:bold;'>Class</div>";
         $myClasses .= "<div class='col-md-3' style='font-weight:bold;'>Status</div>";
         $myClasses .= "<div class='col-md-3' style='font-weight:bold;'>Options</div>";
         $myClasses .= "</div>";	// End Row
         $myClasses .= $this->myClasses();
         $myClasses .= "</div>";	// End Container

         $html = "<h1>FWMSH Museum School Class Registration</h1>";
         $html .= "<div class='container-fluid'>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6'>";
         $html .= $family;
         $html .= "</div>";	// End Column
         $html .= "<div class='col-md-6'>";
         $html .= $myClasses;
         $html .= "</div>";	// End Column
         $html .= "</div>";	// End Row
         $html .= "</div>";	// End Container

         $html .= $this->catalog();

         $this->responseHTML ( "response" , $html );
         $this->send();
      }

      function addFamily()
      {
         $this->checkLogin();

         if ( isset ( $_POST [ 'childName' ] ) ) $childName = $_POST [ 'childName' ];
         if ( isset ( $_POST [ 'gender' ] ) )
         {
            if ( $_POST [ 'gender' ] = "MALE" ) $maleSelected = "selected";
            if ( $_POST [ 'gender' ] = "FEMALE" ) $femaleSelected = "selected";
         }
         if ( isset ( $_POST [ 'birthday' ] ) ) $birthday = $_POST [ 'birthday' ];

         $title = "<h4>New Family Member</h4>";
         $html = "<div class='well' id='message'>Add each child here.  Once added child info can not be changed.  If this is the first museum school class your child has attended you will be required to show a birth certificate to register.  If you discover any errors please contact the office at [number] to correct them.</div>";
         $html .= "<div class='container-fluid'>";
         $html .= "<div class='row' id='messageDiv'>";

         $html .= "</div>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='text-align:right;'>Child's Name<br>(include last name if different)</div>";
         $html .= "<div class='col-md-6'><input type='text' class='form-control' id='childName' value='".$childName."'></div>";
         $html .= "<div class='col-md-2' id='confirmName'></div>";
         $html .= "</div>";	// End Row;

         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='text-align:right;'>Gender</div>";
         $html .= "<div class='col-md-6'>";
         $html .= "<input type='radio' name='gender' id='childMale' value='MALE' ".$maleSelected.">Male ";
         $html .= "<input type='radio' name='gender' id='childFemale' value='FEMALE' ".$femaleSelected.">Female";
         $html .= "</div>";	// End column;
         $html .= "<div class='col-md-2' id='confirmGender'></div>";
         $html .= "</div>";	// End Row;

         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='text-align:right;'>Birthday</div>";
         $html .= "<div class='col-md-6'><input type='date' class='form-control' id='childBirthday' value='".$birthday."'></div>";
         $html .= "<div class='col-md-2' id='confirmBirthday'></div>";
         $html .= "</div>";	// End Row;
         $html .= "</div>";	// End Container;

         $buttons = "<button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Cancel</button>";
         $buttons .= "<button type=\"button\" id='addFamilyButton' class=\"btn btn-primary\" onclick='processAddFamily()'>Add Family Member</button>";

         $this->responseHTML ( "modalBody" , $html );
         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalFooter" , $buttons );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" );" );
         $this->send();
      }

      function processAddFamily()
      {
         $this->checkLogin();

         $message = "<div class='alert alert-info' role='alert'>";
         $message .= "  Please double check all information and check all 3 correct boxes.  Once submitted child info can not be changed.";
         $message .= "</div>";

         if ( !isset ( $_POST [ 'confirmed' ] ) )
         {
            $this->responseHTML ( "message" , $message );
            $this->responseHTML ( "confirmName" , "<input type='checkbox' id='checkConfirmName' onclick='checkCorrect()'> Correct" );
            $this->responseHTML ( "confirmGender" , "<input type='checkbox' id='checkConfirmGender' onclick='checkCorrect()'> Correct" );
            $this->responseHTML ( "confirmBirthday" , "<input type='checkbox' id='checkConfirmBirthday' onclick='checkCorrect()'> Correct" );
            $this->send();
         }
 
         $sql = "INSERT INTO `children` ( `parentID` , `childName` , `gender` , `birthday` ) VALUES ( :parent , :child , :gender , :birthday )";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":parent" , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":child" , $_POST [ 'childName' ] , PDO::PARAM_STR );
         $query->bindValue ( ":gender" , $_POST [ 'gender' ] , PDO::PARAM_STR );
         $query->bindValue ( ":birthday" , $_POST [ 'birthday' ] , PDO::PARAM_STR );
         $query->execute();

         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"hide\" );" );
         $this->home();
      }

      function catalog()
      {
         $this->checkLogin();
         unset ( $_SESSION [ 'filter' ] );

         if ( !isset ( $_SESSION [ 'filter' ] ) )
         {
            $sql = "SELECT * FROM `children` WHERE `parentID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
            $query->execute();

            while ( $child = $query->fetch ( PDO::FETCH_ASSOC ) )
            {
               $bday = DateTime::createFromFormat ( "Y-m-d" , $child [ 'birthday' ] );
               $today = new DateTime ( "now" );
               $age = $today->diff ( $bday );

               $_SESSION [ 'filter' ][] = array ( "name"=>$child [ 'childName' ] , "bday"=>$bday , "childID"=>$child [ 'ID' ] , "selected"=>true );
            }
         }

         $sql = "SELECT * FROM `classes` WHERE `classType` = 'CLASS'";
         $first = true;
         $catalog = "<h4>Class Catalog</h4>";
         $catalog .= "<div class='container-fluid'>";

         /*
         foreach ( $_SESSION [ 'filter' ] AS $key=>$child )
         {
            $catalog .= "<div class='col-md-2'>";
            $catalog .= "<div>";
            $catalog .= "<input type='checkbox' onclick='changeFilter ( ".$key." , this.checked )' ";
            if ( $child [ 'selected' ] == true ) $catalog .= "checked";
            $catalog .= "> ".$child [ 'name' ];
            $catalog .= "</div>";	// End Checkbox
            $catalog .= "</div>";	// End Column
         }
         $catalog .= "</div>";	// End Row
         */

         $catalog .= "<div class='row'>";
         $catalog .= "<div class='col-md-2' style='font-weight:bold;'>Class</div>";
         $catalog .= "<div class='col-md-1' style='font-weight:bold;'>Age</div>";
         $catalog .= "<div class='col-md-2' style='font-weight:bold;'>Start Date / Time</div>";
         $catalog .= "<div class='col-md-2' style='font-weight:bold;'>End Date / Time</div>";
         $catalog .= "<div class='col-md-2' style='font-weight:bold;'>Meeting Days</div>";
         $catalog .= "<div class='col-md-1' style='font-weight:bold;'>Cost</div>";
         $catalog .= "<div class='col-md-2' style='font-weight:bold;'>Eligible Students</div>";
         $catalog .= "</div>";	// End Row

         $result = $this->db->query ( $sql );

         while ( $class = $result->fetch ( PDO::FETCH_ASSOC ) )
         {
            //TODO -- Check attendance and label full classes

            $showClass = false;
            $allAges = explode ( "," , $class [ 'Age' ] );
            $eligible = array();

            foreach ( $allAges AS $key=>$age )
            {
               $allAges [ $key ] = intval ( $age );
            }

            foreach ( $_SESSION [ 'filter' ] AS $key=>$child )
            {
               $cutoff = DateTime::createFromFormat ( "Y-m-d" , $class [ 'ageCutoff' ] );
               $age = abs ( intval ( $cutoff->diff ( $child [ 'bday' ] )->format ( "%y" ) ) );

               if ( in_array ( $age , $allAges ) == true )
               {
                  $showClass = true;
                  if ( !in_array ( $child , $eligible ) ) $eligible[] = $child [ 'name' ];
               }
            }
            if ( $showClass == false ) continue;

            $sql = "SELECT * FROM `registration` WHERE `classID` = ? AND `status` = 'NOT PAID' AND `expirationTime` > NOW()";
            $spaceQuery = $this->db->prepare ( $sql );
            $spaceQuery->bindValue ( 1 , $class [ 'ID' ] , PDO::PARAM_INT );
            $spaceQuery->execute();
            $pending = $spaceQuery->rowCount();

            $sql = "SELECT * FROM `registration` WHERE `classID` = ? AND `status` = 'PAID'";
            $spaceQuery = $this->db->prepare ( $sql );
            $spaceQuery->bindValue ( 1 , $class [ 'ID' ] , PDO::PARAM_INT );
            $spaceQuery->execute();
            $paid = $spaceQuery->rowCount();

            $sql = "SELECT * FROM `registration` WHERE `classID` = ? AND `status` = 'PAIDHALF'";
            $spaceQuery = $this->db->prepare ( $sql );
            $spaceQuery->bindValue ( 1 , $class [ 'ID' ] , PDO::PARAM_INT );
            $spaceQuery->execute();
            $paidHalf = $spaceQuery->rowCount();

            $seatsLeft = intval ( $class [ 'MaximumSize' ] ) - $pending - $paid - $paidHalf;

            if ( $seatsLeft == 0 ) $catalog .= "<div class='row bg-danger'>";
            else $catalog .= "<div class='row row-striped' onclick='classDetails ( ".$class [ 'ID' ]." )'>";

            $catalog .= "<div class='col-md-2'>";
            $catalog .= "<span class='glyphicon glyphicon-menu-right'></span> ";
            if ( $seatsLeft != 0 ) $catalog .= $class [ 'ClassName' ]."<br>Click for more info";
            else $catalog .= $class [ 'ClassName' ]." <b>(Full)</b>";
            $catalog .= "</div>";
            $catalog .= "<div class='col-md-1'>".$class [ 'Age' ]."</div>";
            $catalog .= "<div class='col-md-2'>".$class [ 'StartDate' ]."<br>".$class [ 'StartTime' ]."</div>";
            $catalog .= "<div class='col-md-2'>".$class [ 'EndDate' ]."<br>".$class [ 'EndTime' ]."</div>";
            $catalog .= "<div class='col-md-2'>".$class [ 'MeetingDays' ]."</div>";
            $catalog .= "<div class='col-md-1'>$".$class [ 'Cost' ]."</div>";
            $catalog .= "<div class='col-md-2'>".implode ( "<br>" , $eligible )."</div>";
            $catalog .= "</div>";	// End Row
         }
         return $catalog;
      }

      function classDetails()
      {
         $this->checkLogin();

         $sql = "SELECT * FROM `classes` WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'classID' ] , PDO::PARAM_INT );
         $query->execute();
         $class = $query->fetch ( PDO::FETCH_ASSOC );

         $allAges = explode ( "," , $class [ 'Age' ] );
         $eligible = array();

         foreach ( $allAges AS $key=>$age )
         {
            $allAges [ $key ] = intval ( $age );
         }

         if ( $class [ 'classType' ] == "CLASS" )
         {
            foreach ( $_SESSION [ 'filter' ] AS $key=>$child )
            {
               $cutoff = DateTime::createFromFormat ( "Y-m-d" , $class [ 'ageCutoff' ] );
               $age = abs ( intval ( $cutoff->diff ( $child [ 'bday' ] )->format ( "%y" ) ) );

               if ( in_array ( $age , $allAges ) == true )
               {
                  $showClass = true;
                  if ( !in_array ( $child , $eligible ) ) $eligible[] = $child;
               }
            }
         }
         else if ( $class [ 'classType' ] == "ADDON" )
         {
            $html .= "ADDON";
            $corequisites = explode ( "," , $class [ 'coRequisites' ] );
            $sql = "SELECT * FROM `registration` WHERE `userID` = ? AND `classID` = ?";
            $classQuery = $this->db->prepare ( $sql );

            foreach ( $corequisites AS $co )
            {
               $classQuery->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
               $classQuery->bindValue ( 2 , $co , PDO::PARAM_INT );
               $classQuery->execute();
               while ( $registration = $classQuery->fetch ( PDO::FETCH_ASSOC ) )
               {
                  $sql = "SELECT * FROM `children` WHERE `ID` = ?";
                  $childQuery = $this->db->prepare ( $sql );
                  $childQuery->bindValue ( 1 , $registration [ 'childID' ] , PDO::PARAM_INT );
                  $childQuery->execute();

                  $child = $childQuery->fetch ( PDO::FETCH_ASSOC );
                  $eligible[] = array ( "childID"=>$child [ 'ID' ] , "name"=>$child [ 'childName' ] );
               }
            }
         } 

         //$sql = "SELECT * FROM `registration` WHERE `userID` = ? AND `classID` = ? AND ( `expirationTime` > NOW() OR `status` = 'PAID' OR `status` = 'PAIDHALF' )";
         $sql = "SELECT * FROM `registration` WHERE `userID` = ? AND `classID` = ?";

         $enrolledQuery = $this->db->prepare ( $sql );
         $enrolledQuery->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $enrolledQuery->bindValue ( 2 , $_POST [ 'classID' ] , PDO::PARAM_INT );
         $enrolledQuery->execute();

         $enrolled = False;
         $children = "";
         if ( $enrolledQuery->rowCount() > 0 )
         {
            $childProcessed = array();
            while ( $registration = $enrolledQuery->fetch ( PDO::FETCH_ASSOC ) )
            {
               if ( array_search ( $registration [ 'childID' ] , $childProcessed  ) == True ) continue;
               $childProcessed[] = $registration [ 'childID' ];
               
               $sql = "SELECT * FROM `children` WHERE `ID` = ?";
               $childQuery = $this->db->prepare ( $sql );
               $childQuery->bindValue ( 1 , $registration [ 'childID' ] , PDO::PARAM_INT );
               $childQuery->execute();
               $child = $childQuery->fetch ( PDO::FETCH_ASSOC );

               $paid = "";
              
               $expiration = DateTime::createFromFormat ( "Y-m-d H:i:s" , $registration [ 'expirationTime' ] );
               $now = new DateTime ( "now" );
               if ( $now > $expiration && $registration [ 'status' ] == "NOT PAID" )
               {
                  continue;
                  $children .= "<div class='alert alert-warning alert-dismissible' role='alert'>";
                  $children .= "  <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>";
                  $children .= "  This class was selected for ".$child [ 'childName' ]." but was not paid for within the 30 minute window.  If there is still room you can enroll again.";
                  $children .= "</div>";
               }
               else if ( $registration [ 'status' ] == "PAID" )
               {
                  $enrolled = True;
                  $children .= "<div class='alert alert-success alert-dismissible' role='alert'>";
                  $children .= "  <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>";
                  $children .= "  ".$child [ 'childName' ]." is enrolled in this class!".$paid;
                  $children .= "</div>";
               }
               else if ( $registration [ 'status' ] == "NOT PAID" )
               {
                  $enrolled = True;
                  $children .= "<div class='alert alert-info alert-dismissible' role='alert'>";
                  $children .= "  <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>";
                  $children .= "  This class is pending for ".$child [ 'childName' ]." but not paid yet!".$paid;
                  $children .= "</div>";
               }
            }
         }

         $sql = "SELECT * FROM `registration` WHERE `classID` = ? AND `status` = 'NOT PAID' AND `expirationTime` > NOW()";
         $spaceQuery = $this->db->prepare ( $sql );
         $spaceQuery->bindValue ( 1 , $class [ 'ID' ] , PDO::PARAM_INT );
         $spaceQuery->execute();
         $pending = $spaceQuery->rowCount();

         $sql = "SELECT * FROM `registration` WHERE `classID` = ? AND `status` = 'PAID'";
         $spaceQuery = $this->db->prepare ( $sql );
         $spaceQuery->bindValue ( 1 , $class [ 'ID' ] , PDO::PARAM_INT );
         $spaceQuery->execute();
         $paid = $spaceQuery->rowCount();

         $sql = "SELECT * FROM `registration` WHERE `classID` = ? AND `status` = 'PAIDHALF'";
         $spaceQuery = $this->db->prepare ( $sql );
         $spaceQuery->bindValue ( 1 , $class [ 'ID' ] , PDO::PARAM_INT );
         $spaceQuery->execute();
         $paidHalf = $spaceQuery->rowCount();

         $seatsLeft = intval ( $class [ 'MaximumSize' ] ) - $pending - $paid - $paidHalf;

         $title = "<h4>".$class [ 'ClassName' ]."</h4>";
         $html = $children;
         $html .= "<div class='container-fluid;'>";
         $html .= "<div class='row' style='padding-bottom:10px;'>";
         $html .= "<div class='col-md-12'>".$class [ 'ClassDescription' ]."</div>";
         $html .= "</div>";	// End Row

         $start = DateTime::createFromFormat ( "Y-m-d" , $class [ 'StartDate' ] );
         $startDate = $start->format ( "l, F jS, Y" );

         $end = DateTime::createFromFormat ( "Y-m-d" , $class [ 'EndDate' ] );
         $endDate = $end->format ( "l, F jS, Y" );

         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='font-weight:bold;'>Dates</div>";
         $html .= "<div class='col-md-8'>".$startDate." to ".$endDate."</div>";
         $html .= "</div>";	// End Row

         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='font-weight:bold;'>Class will not meet on</div>";
         $html .= "<div class='col-md-8'>".str_replace ( "\n" , "<br>" , $class [ 'noClassDates' ] )."</div>";
         $html .= "</div>";	// End Row

         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='font-weight:bold;'>Times</div>";
         $html .= "<div class='col-md-8'>".$class [ 'StartTime' ]." to ".$class [ 'EndTime' ]."</div>";
         $html .= "</div>";	// End Row

         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='font-weight:bold;'>Cost</div>";
         $html .= "<div class='col-md-8'>$".$class [ 'Cost' ]."</div>";
         $html .= "</div>";	// End Row

         if ( $enrolled == False )
         {
            $html .= "<div class='row'>";
            $html .= "<div class='col-md-4' style='font-weight:bold;'>Seats Left</div>";
            $html .= "<div class='col-md-8'>".$seatsLeft."</div>";
            $html .= "</div>";	// End Row
         }
         $html .= "</div>";	// End Container

         $buttons = "";

         if ( $seatsLeft > 0 && $enrolled == False )
         {
            foreach ( $eligible AS $key=>$student )
            {
               $buttons .= "<button type='button' class='btn btn-success' onclick='enroll ( ".$class [ 'ID' ]." , ".$student [ 'childID' ]." )'>Enroll ".$student [ 'name' ]."</button>";
            }
         }
         $buttons .= "<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>";

         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalBody" , $html );
         $this->responseHTML ( "modalFooter" , $buttons );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" );" );
         $this->send();
      }

      function changeFilter()
      {
         $this->checkLogin();

         if ( $_POST [ 'selected' ] == "false" ) $_SESSION [ 'filter' ] [ $_POST [ 'filterID' ] ] [ 'selected' ] = false;
         else $_SESSION [ 'filter' ] [ $_POST [ 'filterID' ] ] [ 'selected' ] = true;

         $this->home();
      }

      function enroll()
      {
         $this->checkLogin();

         $expiration = new DateTime ( "now" );
         $expiration->add ( new DateInterval ( "PT30M" ) );

         $sql = "SELECT * FROM `registration` WHERE `userID` = :user AND `childID` = :child AND `classID` = :class AND ( `expirationTime` > NOW() OR `status` = 'PAID' OR `status` = 'PAIDHALF' )";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":user" , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":child" , $_POST [ 'childID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":class" , $_POST [ 'classID' ] , PDO::PARAM_INT );
         $query->execute();

         if ( $query->rowCount() != 0 )
         {
            $sql = "SELECT * FROM `children` WHERE `ID` = ?";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $_POST [ 'childID' ] , PDO::PARAM_INT );
            $query->execute();
            $child = $query->fetch ( PDO::FETCH_ASSOC );

            $html = "<div class='alert alert-warning alert-dismissible' role='alert'>";
            $html .= "  <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>";
            $html .= "  <strong>Already selected!</strong> This class is already in your cart for ".$child [ 'childName' ]."!";
            $html .= "</div>";

            $this->responseScript ( "$ ( '#modalBody' ).prepend ( \"".$html."\" )" );
            $this->send();
         }

         $sql = "INSERT INTO `registration` ( `userID` , `childID` , `classID` , `status` , `expirationTime` ) VALUES ( :user , :child , :class , :status , :expiration )";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":user" , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":child" , $_POST [ 'childID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":class" , $_POST [ 'classID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":status" , "NOT PAID" , PDO::PARAM_STR );
         $query->bindValue ( ":expiration" , $expiration->format ( "Y-m-d H:i:s" ) , PDO::PARAM_STR );
         $query->execute();
         $registrationID = $this->db->lastInsertId();

         $sql = "SELECT * FROM `classes` WHERE `ID` = ?";
         $classQuery = $this->db->prepare ( $sql );
         $classQuery->bindValue ( 1 , $_POST [ 'classID' ] , PDO::PARAM_INT );
         $classQuery->execute();
         $class = $classQuery->fetch ( PDO::FETCH_ASSOC );

         $sql = "INSERT INTO `paymentItems` ( `registrationID` , `userID` , `amount` , `type` , `status` ) VALUES ( :registration , :user , :amount , :type , :status )";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":registration" , $registrationID , PDO::PARAM_INT );
         $query->bindValue ( ":user" , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":amount" , $class [ 'Cost' ] , PDO::PARAM_INT );
         $query->bindValue ( ":type" , $class [ 'classType' ] , PDO::PARAM_INT );
         $query->bindValue ( ":status" , "NOT PAID" , PDO::PARAM_STR );
         $query->execute();

         $sql = "UPDATE `registration` SET `expirationTime` = ? WHERE `status` = 'NOT PAID' AND `userID` = ? AND `expirationTime` > NOW();";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $expiration->format ( "Y-m-d H:i:s" ) , PDO::PARAM_STR );
         $query->bindValue ( 2 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $sql = "SELECT * FROM `children` WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'childID' ] , PDO::PARAM_INT );
         $query->execute();
         $child = $query->fetch ( PDO::FETCH_ASSOC );

         $html = "<div class='alert alert-success alert-dismissible' role='alert'>";
         $html .= "  <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>";

         $html .= "  <strong>Success!</strong> This class has been added to your cart for ".$child [ 'childName' ]."!";
         $html .= "</div>";

         $this->responseScript ( "$ ( \"#modalBody\" ).prepend ( \"".$html."\" )" );
         $this->responseScript ( "startClock ( 30 )" );
         $this->home();
      }

      function myClasses()
      {
         $this->checkLogin();

         $sql = "SELECT * FROM `registration` WHERE `userID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $once = False;
         $html = "";

         if ( $query->rowCount() == 0 )
         {
            $this->responseScript ( "stopClock()" );
            return "<div class='col-md-12'>(No classes selected yet)</div>";
         }

         $total = 0;
         $clockRun = False;
         while ( $cart = $query->fetch ( PDO::FETCH_ASSOC ) )
         {
            $sql = "SELECT * FROM `children` WHERE `ID` = ?";
            $childQuery = $this->db->prepare ( $sql );
            $childQuery->bindValue ( 1 , $cart [ 'childID' ] ,  PDO::PARAM_INT );
            $childQuery->execute();
            $child = $childQuery->fetch ( PDO::FETCH_ASSOC );

            $sql = "SELECT * FROM `classes` WHERE `ID` = ?";
            $classQuery = $this->db->prepare ( $sql );
            $classQuery->bindValue ( 1 , $cart [ 'classID' ] ,  PDO::PARAM_INT );
            $classQuery->execute();
            $class = $classQuery->fetch ( PDO::FETCH_ASSOC );

            $cartExpiration = DateTime::createFromFormat ( "Y-m-d H:i:s" , $cart [ 'expirationTime' ] );
            $now = new DateTime ( "now" );

            if ( $now > $cartExpiration && $cart [ 'status' ] != "PAID" && $cart [ 'status' ] != "PAIDHALF" )
            {
               // Registration has expired
               $startTag = "<s>";
               $endTag = "</s>";
               $status = "EXPIRED";
            }
            else if ( $now < $cartExpiration && $cart [ 'status' ] != "PAID" && $cart [ 'status' ] != "PAIDHALF" )
            {
               // Not expired, not paid
               $startTag = "";
               $endTag = "";
               $status = $cart [ 'status' ];
               //$total += intval ( $class [ 'Cost' ] );

               $timeLeft = $now->diff ( $cartExpiration , True );
               $clockRun = True;
            }
            else
            {
               $startTag = "";
               $endTag = "";
               $status = $cart [ 'status' ];
            }

            if ( $clockRun == True ) $this->responseScript ( "startClock ( ".$timeLeft->format ( "%i" )." , ".$timeLeft->format ( "%s" )." )" );
            else $this->responseScript ( "stopClock()" );

            $sql = "SELECT * FROM `classes` WHERE `coRequisites` LIKE '%".$cart [ 'classID' ]."%'";
            $result = $this->db->query ( $sql );

            //$coQuery = $this->db->prepare ( $sql );
            //$coQuery->bindValue ( 1 , strval ( $cart [ 'classID' ] ) , PDO::PARAM_STR );
            //$coQuery->execute();
            //$rowBG = "ROWBG";
            $addonText = "";

            $rowBG = "";
            if ( $result->rowCount() > 0 )
            {
               $addon = $result->fetch ( PDO::FETCH_ASSOC );

               $rowBG = "bg-info";
               $addonText = "<button class='btn btn-xs btn-info' onclick='event.stopPropagation();classDetails ( ".$addon [ 'ID' ]." )'><span class='glyphicon glyphicon-circle-arrow-up'></span> Add-Ons Available</button>";
            }

            $sql = "SELECT * FROM `paymentItems` WHERE `registrationID` = ?";
            $payQuery = $this->db->prepare ( $sql );
            $payQuery->bindValue ( 1 , $cart [ 'classID' ] , PDO::PARAM_INT );
            $payQuery->execute();
            if ( $payQuery->rowCount() > 0 )
            {
               $payment = $payQuery->fetch ( PDO::FETCH_ASSOC );
               if ( $payment [ 'status' ] == "PAIDHALF" )
               {
                  $total += intval ( $payment [ 'half2amount' ] );
               }
            }

            if ( $status == "EXPIRED" ) $html .= "<div class='row'>";
            else $html .= "<div class='row ".$rowBG."' onclick='classDetails ( ".$cart [ 'classID' ]." )'>";
            $html .= "<div class='col-md-3'>".$startTag.$child [ 'childName' ].$endTag."<br>".$addonText."</div>";
            $html .= "<div class='col-md-3'>".$startTag.$class [ 'ClassName' ]." (".$class [ 'MeetingDays' ].")".$endTag."</div>";
            $html .= "<div class='col-md-3'>".$status."</div>";
            $html .= "<div class='col-md-3'><button class='btn btn-danger btn-xs' onclick='drop ( ".$cart [ 'ID' ]." )'><span class='glyphicon glyphicon-download'></span> Drop Class</button></div>";
            $html .= "</div>";	// End Row
         }
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-offset-5 col-md-4' style='font-weight:bold;'>Unpaid total $".$total."</div>";
         //if ( $total != 0 ) $html .= "<div class='col-md-2'><button class='btn btn-primary' onclick='checkout ( ".$total." )'><span class='glyphicon glyphicon-log-out'></span> Checkout</button></div>";
         $html .= "<div class='col-md-2'><button class='btn btn-primary' onclick='checkout ( ".$total." )'><span class='glyphicon glyphicon-log-out'></span> Checkout</button></div>";

         $html .= "</div>";	// End Row

         return $html;
      }

      function dropClass()
      {
         $this->checkLogin();

         $sql = "SELECT * FROM `registration` WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'reservationID' ] , PDO::PARAM_INT );
         $query->execute();
         $reservation = $query->fetch ( PDO::FETCH_ASSOC );

         if ( $reservation [ 'userID' ] == $_SESSION [ 'ID' ] )
         {
            if ( $_POST [ 'confirm' ] == 0 )
            {
               if ( $reservation [ 'status' ] == "PAID" || $reservation [ 'status' ] == "PAIDHALF" && $_POST [ 'confirm' ] == 0 )
               {
                  $title = "<h4>Drop Class</h4>";
                  $html = "<h1>Refund policy goes here</h1>";
                  $buttons = "<button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Cancel</button>";
                  $buttons .= "<button type=\"button\" class=\"btn btn-danger\" onclick='drop ( ".$reservation [ 'ID' ]." , true )'>Drop Class</button>";

                  $this->responseHTML ( "modalTitle" , $title );
                  $this->responseHTML ( "modalBody" , $html );
                  $this->responseHTML ( "modalFooter" , $buttons );
                  $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" )" );
                  $this->send(); 
               }
            }

            $this->responseScript ( "$ ( \"#modal\" ).modal ( \"hide\" )" );
            $sql = "DELETE FROM `registration` WHERE `ID` = ? LIMIT 1";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $_POST [ 'reservationID' ] , PDO::PARAM_INT );
            $query->execute();
         }

         $this->home();
      }

      function checkout()
      {
         $this->checkLogin();

         $sql = "SELECT * FROM `paymentItems` WHERE `userID` = ? ORDER BY `registrationID` ASC";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $title = "<h4>Payments</h4>";
         $total = 0;
         $paymentTotal = 0;

         $html = "<div class='container-fluid'>";
         $_SESSION [ 'checkoutItems' ] = array();

         while ( $item = $query->fetch ( PDO::FETCH_ASSOC ) )
         {
            if ( $item [ 'type' ] == "CLASS" || $item [ 'type' ] == "ADDON" ) $total += intval ( $item [ 'amount' ] );
            else if ( $item [ 'type' ] == "DEPOSIT" || $item [ 'type' ] == "FULLPAY" ) $total -= intval ( $item [ 'amount' ] );

            $sql = "SELECT * FROM `registration` WHERE `ID` = ?";
            $registrationQuery = $this->db->prepare ( $sql );
            $registrationQuery->bindValue ( 1 , $item [ 'registrationID' ] , PDO::PARAM_INT );
            $registrationQuery->execute();
            $registration = $registrationQuery->fetch ( PDO::FETCH_ASSOC );

            $sql = "SELECT * FROM `classes` WHERE `ID` = ?";
            $classQuery = $this->db->prepare ( $sql );
            $classQuery->bindValue ( 1 , $registration [ 'classID' ] , PDO::PARAM_INT );
            $classQuery->execute();
            $class = $classQuery->fetch ( PDO::FETCH_ASSOC );

            $sql = "SELECT * FROM `children` WHERE `ID` = ?";
            $childQuery = $this->db->prepare ( $sql );
            $childQuery->bindValue ( 1 , $registration [ 'childID' ] , PDO::PARAM_INT );
            $childQuery->execute();
            $child = $childQuery->fetch ( PDO::FETCH_ASSOC );

            $html .= "<div class='row'>";
            if ( $item [ 'type' ] == "CLASS" || $item [ 'type' ] == "ADDON" ) $html .= "<div class='col-md-5'>".$class [ 'ClassName' ]." for ".$child [ 'childName' ]."</div>";
            if ( $item [ 'type' ] == "DEPOSIT" || $item [ 'type' ] == "FULLPAY" ) $html .= "<div class='col-md-5'>Payment -- Thank you!</div>";
            $html .= "<div class='col-md-1'>".$item [ 'type' ]."</div>";
            $html .= "<div class='col-md-1'>$".$item [ 'amount' ]."</div>";
            if ( $item [ 'status' ] == "DEPOSIT" )
            {
               $html .= "<div class='col-md-2'><input type='radio' name='payment".$item [ 'registrationID' ]."' value='". ( $item [ 'amount' ] - 100 ) ."' onchange='recalculateTotal ( this , \"FULLPAY\" )' CHECKED>Pay Balance</div>";
               $html .= "<div class='col-md-2'><input type='radio' name='payment".$item [ 'registrationID' ]."' value='0' onchange='recalculateTotal ( this , \"NOPAY\" )'>Do not pay now</div>";
               $_SESSION [ 'checkoutItems' ] [ $item [ 'registrationID' ] ] = array ( intval ( $item [ 'amount' ] - 100 ) , "FULLPAY" );
               $paymentTotal += intval ( $item [ 'amount' ] - 100 );
            }
            else if ( $item [ 'status' ] != "PAID" )
            {
               $html .= "<div class='col-md-2'><input type='radio' name='payment".$item [ 'registrationID' ]."' value='".$item [ 'amount' ]."' onchange='recalculateTotal ( this , \"FULLPAY\" )' CHECKED>Fulll Payment</div>";
               $html .= "<div class='col-md-2'><input type='radio' name='payment".$item [ 'registrationID' ]."' value='100' onchange='recalculateTotal ( this , \"DEPOSIT\" )'>$100 Deposit</div>";
               $_SESSION [ 'checkoutItems' ] [ $item [ 'registrationID' ] ] = array ( intval ( $item [ 'amount' ] ) , "FULLPAY" );
               $paymentTotal += intval ( $item [ 'amount' ] );
            }
            $html .= "</div>";	// End Row
         } 
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-offset-7 col-md-5' id='totalField'><h4>Unpaid total $".$total."</h4></div>";
         $html .= "</div>";	// End Row
         $html .= "</div>";	// End Container

         $buttons = "<button type='button' class='btn btn-default btn-success' data-dismiss='modal' id='payButton' onclick='succesfulPayment ( ".$total." )'>Pay $".$total."</button>";
         $buttons .= "<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>";

         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalBody" , $html );
         $this->responseHTML ( "modalFooter" , $buttons );

         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" )" );
         $this->send();

      }

      function recalculateTotal()
      {
         $this->checkLogin();

         $id = intval ( substr ( $_POST [ 'name' ] , 7 ) );
         $_SESSION [ 'checkoutItems' ] [ $id ] = array ( intval ( $_POST [ 'value' ] ) , $_POST [ 'type' ] );

         $paymentTotal = 0;
         foreach ( $_SESSION [ 'checkoutItems' ] AS $value )
         {
            $paymentTotal += $value [ 0 ];
         }

         $buttons = "<button type='button' class='btn btn-default btn-success' data-dismiss='modal' id='payButton' onclick='succesfulPayment ( ".$paymentTotal." )'>Pay $".$paymentTotal."</button>";
         $buttons .= "<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>";

         $this->responseHTML ( "totalField" , "<h4>Unpaid total $".$paymentTotal."</h4>" );
         $this->responseHTML ( "modalFooter" , $buttons );
         $this->send();
      }

      /*
      function checkout()
      {
         $this->checkLogin();

         $sql = "SELECT * FROM `paymentItems` WHERE `userID` = ? AND `fullPaymentID` is NULL AND `half1paymentID` is NULL";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $total = 0;
         while ( $item = $query->fetch ( PDO::FETCH_ASSOC ) )
         {
            $total += intval ( $item [ 'amountDue' ] );
         }

         if ( $total % 2 == 0 ) $half = $total / 2;
         else $half = ( $total + 1 ) / 2 ;

         $sql = "SELECT * FROM `paymentItems` WHERE `userID` = ? AND `half1paymentID` IS NOT NULL AND `half2paymentID` IS NULL";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $halfTotal = 0;
         $_SESSION [ 'halfIDs' ] = array();
         while ( $unpaid = $query->fetch ( PDO::FETCH_ASSOC ) )
         {
            $halfTotal += intval ( $unpaid [ 'amountDue' ] ) - intval ( $unpaid [ 'half1amount' ] );
            $_SESSION [ 'halfIDs' ][] = $unpaid [ 'registrationID' ];
         }

         $title = "<h4>Checkout</h4>";

         if ( $halfTotal != 0 )
         {
            $html = "<h3>Half Payments</h3>";
            $html .= "Complete payment of $".$halfTotal." <button class='btn btn-success btn-large' onclick='succesfulPayment ( ".$halfTotal." , \"HALF2\" )'>Pay $".$halfTotal."</button>";
         }

         $html .= "<h1>Authorize.net interface goes here</h1>";
         $html .= "<button class='btn btn-success btn-large' onclick='succesfulPayment ( ".$total." , \"PAID\" )'>Pay $".$total."</button>";
         $html .= "<button class='btn btn-success btn-large' onclick='succesfulPayment ( ".$half." , \"PAIDHALF\" )'>Pay Half $".$half."</button>";

         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalBody" , $html );
         $this->responseHTML ( "modalFooter" , "<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>" );

         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" )" );
         $this->send();
      }
      */

      function succesfulPayment()
      {
         $this->checkLogin();

         $sql = "INSERT INTO `payments` ( `userID` , `amount` , `status` ) VALUES ( :user , :amount , :status )";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":user" , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":amount" , $_POST [ 'amount' ] , PDO::PARAM_INT );
         $query->bindValue ( ":status" , $_POST [ 'status' ] , PDO::PARAM_STR );
         $query->execute();         
         $paymentID = $this->db->lastInsertId();

         foreach ( $_SESSION [ 'checkoutItems' ] AS $registrationID => $item )
         {
            $sql = "INSERT INTO `paymentItems` ( `registrationID` , `userID` , `type` , `status` , `amount` ) VALUES ( :registration , :user , :type , :status , :amount )";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( ":registration" , $registrationID , PDO::PARAM_INT );
            $query->bindValue ( ":user" , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
            $query->bindValue ( ":type" , $item [ 1 ] , PDO::PARAM_STR );
            $query->bindValue ( ":status" , "PAID" , PDO::PARAM_STR );
            $query->bindValue ( ":amount" , $item [ 0 ] , PDO::PARAM_INT );
            $query->execute();

            if ( $item [ 1 ] == "FULLPAY" ) $status = "PAID";
            if ( $item [ 1 ] == "DEPOSIT" ) $status = "DEPOSIT";

            $sql = "UPDATE `paymentItems` SET `status` = ? WHERE `registrationID` = ? AND `type` = 'CLASS' OR `status` = 'ADDON'";
            $query = $this->db->prepare ( $sql );
            $query->bindValue ( 1 , $status , PDO::PARAM_STR );
            $query->bindValue ( 2 , $registrationID , PDO::PARAM_INT );
            $query->execute();
         }

         $sql = "UPDATE `registration` SET `status` = ? , `paymentID` = ? WHERE `userID` = ? AND `status` = 'NOT PAID' AND `expirationTime` > NOW()";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'status' ] , PDO::PARAM_STR );
         $query->bindValue ( 2 , $paymentID , PDO::PARAM_INT );
         $query->bindValue ( 3 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"hide\" )" );
         $this->home(); 
      }

      function login()
      {
         $sql = "SELECT * FROM `people` WHERE `email` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'email' ] , PDO::PARAM_STR );
         $query->execute();
         $user = $query->fetch ( PDO::FETCH_ASSOC );

         $hash = md5 ( $user [ 'email' ].$user [ 'password' ] );
         if ( $hash == $_POST [ 'hash' ] )
         {
            session_start();
            $_SESSION [ 'ID' ] = $user [ 'ID' ];
            $_SESSION [ 'firstName' ] = $user [ 'firstName' ];
            $_SESSION [ 'lastName' ] = $user [ 'lastName' ];
            $_SESSION [ 'type' ] = $user [ 'role' ];

            $this->responseScript ( "window.location = \"home.html\"" );
            $this->send();
         }
      }

   }

   new web ( $db );

?>
