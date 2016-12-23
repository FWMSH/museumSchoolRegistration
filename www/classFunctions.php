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

         $title = "<h4>New Family Member</h4>";
         $html = "<div class='container-fluid'>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-2' style='text-align:right;'>Child's Name</div>";
         $html .= "<div class='col-md-4'><input type='text' class='form-control' id='childName'></div>";
         $html .= "</div>";	// End Row;

         $html .= "<div class='row'>";
         $html .= "<div class='col-md-2' style='text-align:right;'>Gender</div>";
         $html .= "<div class='col-md-4'>";
         $html .= "<input type='radio' name='gender' id='childMale' value='MALE'>Male";
         $html .= "<input type='radio' name='gender' id='childFemale' value='FEMALE'>Female";
         $html .= "</div>";	// End column;
         $html .= "</div>";	// End Row;

         $html .= "<div class='row'>";
         $html .= "<div class='col-md-2' style='text-align:right;'>Birthday</div>";
         $html .= "<div class='col-md-4'><input type='date' class='form-control' id='childBirthday'></div>";
         $html .= "</div>";	// End Row;

         $html .= "</div>";	// End Container;

         $buttons = "<button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Cancel</button>";
         $buttons .= "<button type=\"button\" class=\"btn btn-primary\" onclick='processAddFamily()'>Add Family Member</button>";

         $this->responseHTML ( "modalBody" , $html );
         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalFooter" , $buttons );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" );" );
         $this->send();
      }

      function processAddFamily()
      {
         $this->checkLogin();

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

            $catalog .= "<div class='row' onclick='classDetails ( ".$class [ 'ID' ]." )'>";
            $catalog .= "<div class='col-md-2'>";
            $catalog .= "<span class='glyphicon glyphicon-menu-right'></span> ";
            $catalog .= $class [ 'ClassName' ]."<br>Click for more info";
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

         $title = "<h4>".$class [ 'ClassName' ]."</h4>";
         $html = "<div class='container-fluid;'>";
         $html .= "<div class='row'>";
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

         $html .= "</div>";	// End Container

         $buttons = "";
         foreach ( $eligible AS $key=>$student )
         {
            $buttons .= "<button type='button' class='btn btn-success' onclick='enroll ( ".$class [ 'ID' ]." , ".$student [ 'childID' ]." )'>Enroll ".$student [ 'name' ]."</button>";
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
