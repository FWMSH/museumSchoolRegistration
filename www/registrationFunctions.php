<?php
   include "db.php";

   class web
   {
      function __construct ( $db )
      {
         $this->db = $db;

         switch ( $_POST [ 'action' ] )
         {
            case "REGISTER":$this->register();break;
            case "CHECKLOGIN":$this->checkLogin();break;
            case "FAMILY":$this->family();break;
            case "ADDFAMILY":$this->addFamily();break;
            //case "PROCESSADDFAMILY":$this->processAddFamily();break;
            case "PROCESSADDFAMILY":
            {
               $this->processAddFamily();
            } break;
            case "PROCESSEDITCHILD":$this->processEditChild();break;
            case "EDITCHILD":$this->editChild();break;
            case "REMOVECHILD":$this->removeChild();break;
            case "PROCESSADDADULT":$this->processAddAdult();break;
            case "REMOVEADULT":$this->removeAdult();break;
            case "ADDADULT":$this->addAdult();break;
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

         if ( isset ( $_SESSION [ 'ID' ] ) )
         {
            $this->responseScript ( "family()" );
            $this->send();
         }
      }

      function editChild()
      {
         $sql = "SELECT * FROM `children` WHERE `ID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'childID' ] , PDO::PARAM_INT );
         $query->execute();

         $child = $query->fetch ( PDO::FETCH_ASSOC );

         $_POST [ 'editID' ] = $child [ 'ID' ];
         $_POST [ 'childName' ] = $child [ 'childName' ];
         $_POST [ 'childLastName' ] = $child [ 'childLastName' ];
         $_POST [ 'gender' ] = $child [ 'gender' ];
         $_POST [ 'birthday' ] = $child [ 'birthday' ];

         $this->addFamily();
      }

      function processEditChild()
      {
         $sql = "UPDATE `children` SET `childName` = ? , `childLastName` = ? , `gender` = ? , `birthday` = ? WHERE `ID` = ? LIMIT 1";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'childName' ] , PDO::PARAM_STR );
         $query->bindValue ( 2 , $_POST [ 'childLast' ] , PDO::PARAM_STR );
         $query->bindValue ( 3 , $_POST [ 'gender' ] , PDO::PARAM_STR );
         $query->bindValue ( 4 , $_POST [ 'birthday' ] , PDO::PARAM_STR );
         $query->bindValue ( 5 , $_POST [ 'childID' ] , PDO::PARAM_INT );
         $query->execute();

         $this->family();
      }

      function processAddFamily()
      {
         $this->checkLogin();
         session_start();

         $message = "<div class='alert alert-info' role='alert'>";
         $message .= "</div>";

         $sql = "INSERT INTO `children` ( `parentID` , `childName` , `childLastName` , `gender` , `birthday` ) VALUES ( :parent , :child , :childLast , :gender , :birthday )";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":parent" , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":child" , $_POST [ 'childName' ] , PDO::PARAM_STR );
         $query->bindValue ( ":childLast" , $_POST [ 'childLast' ] , PDO::PARAM_STR );
         $query->bindValue ( ":gender" , $_POST [ 'gender' ] , PDO::PARAM_STR );
         $query->bindValue ( ":birthday" , $_POST [ 'birthday' ] , PDO::PARAM_STR );
         $query->execute();

         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"hide\" )" );
         $this->family();
      }

      function addFamily()
      {
         $maleSelected = "";
         $femaleSelected = "";

         session_start();
         if ( $_SESSION [ 'ID' ] == 0 )
         {
            $this->login();
         }

         if ( isset ( $_POST [ 'childName' ] ) ) $childName = $_POST [ 'childName' ];
         if ( isset ( $_POST [ 'childLastName' ] ) ) $childLastName = $_POST [ 'childLastName' ];
         if ( isset ( $_POST [ 'gender' ] ) )
         {
            if ( $_POST [ 'gender' ] == "MALE" ) $maleSelected = "checked";
            if ( $_POST [ 'gender' ] == "FEMALE" ) $femaleSelected = "checked";
         }
         if ( isset ( $_POST [ 'birthday' ] ) ) $birthday = $_POST [ 'birthday' ];

         if ( isset ( $_POST [ 'childName' ] ) ) $title = "<h4>Edit Child</h4>";
         else $title = "<h4>New Child</h4>";

         $html = "<div class='well' id='message'>Add each child here.  Once added child info can not be changed.  If this is the first museum school class your child has attended you will be required to show a birth certificate to register.  If you discover any errors please contact the office at [number] to correct them.</div>";
         $html .= "<div class='container-fluid'>";
         $html .= "<div class='row' id='messageDiv'>";

         $html .= "</div>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='text-align:right;'>Child's First Name</div>";
         $html .= "<div class='col-md-6'><input type='text' class='form-control' id='childName' value='".$childName."'></div>";
         $html .= "<div class='col-md-2' id='confirmName'></div>";
         $html .= "</div>";	// End Row;

         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='text-align:right;'>Child's Last Name</div>";
         $html .= "<div class='col-md-6'><input type='text' class='form-control' id='childLastName' value='".$childLastName."'></div>";
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
//         $html .= "<div class='col-md-6'><input type='date' class='form-control' id='childBirthday' value='".$birthday."'></div>";
         $html .= "<div class='col-md-8'>";

         $birthdayParts = explode ( "-" , $_POST [ 'birthday' ] );
         $month = array();
         $day = array();
         $year = array();

         for ( $i = 1 ; $i < 31 ; $i ++ )
         {
            if ( intval ( $birthdayParts [ 2 ] ) == $i ) $day [ $i ] = "selected";
            else $day [ $i ] = "";
         }

         for ( $i = 1 ; $i < 13 ; $i ++ )
         {
            if ( intval ( $birthdayParts [ 1 ] ) == $i ) $month [ $i ] = "selected";
            else $month [ $i ] = "";
         }

         for ( $i = 0 ; $i < 17 ; $i ++ )
         {
            if ( intval ( $birthdayParts [ 0 ] ) == ( 2000 + $i ) ) $year [ ( 2000 + $i ) ] = "selected";
            else $year [ ( 2000 + $i ) ] = "";
         }

         $html .= <<<html
         Month
         <select id='month' onchange='setDays ( this.value )'>
            <option value='1' {$month[1]} >January</option>
            <option value='2' {$month[2]} >February</option>
            <option value='3' {$month[3]} >March</option>
            <option value='4' {$month[4]} >April</option>
            <option value='5' {$month[5]} >May</option>
            <option value='6' {$month[6]} >June</option>
            <option value='7' {$month[7]} >July</option>
            <option value='8' {$month[8]} >August</option>
            <option value='9' {$month[9]} >September</option>
            <option value='10' {$month[10]} >October</option>
            <option value='11' {$month[11]} >November</option>
            <option value='12' {$month[12]} >December</option>
         </select>
         Day
         <select id='day'>
            <option value='1' {$day[1]} >1</option>
            <option value='2' {$day[2]} >2</option>
            <option value='3' {$day[3]} >3</option>
            <option value='4' {$day[4]} >4</option>
            <option value='5' {$day[5]} >5</option>
            <option value='6' {$day[6]} >6</option>
            <option value='7' {$day[7]} >7</option>
            <option value='8' {$day[8]} >8</option>
            <option value='9' {$day[9]} >9</option>
            <option value='10' {$day[10]} >10</option>
            <option value='11' {$day[11]} >11</option>
            <option value='12' {$day[12]} >12</option>
            <option value='13' {$day[13]} >13</option>
            <option value='14' {$day[14]} >14</option>
            <option value='15' {$day[15]} >15</option>
            <option value='16' {$day[16]} >16</option>
            <option value='17' {$day[17]} >17</option>
            <option value='18' {$day[18]} >18</option>
            <option value='19' {$day[19]} >19</option>
            <option value='20' {$day[20]} >20</option>
            <option value='21' {$day[21]} >21</option>
            <option value='22' {$day[22]} >22</option>
            <option value='23' {$day[23]} >23</option>
            <option value='24' {$day[24]} >24</option>
            <option value='25' {$day[25]} >25</option>
            <option value='26' {$day[26]} >26</option>
            <option value='27' {$day[27]} >27</option>
            <option value='28' {$day[28]} >28</option>
            <option id='day29' value='29' {$day[29]} >29</option>
            <option id='day30' value='30' {$day[30]} >30</option>
            <option id='day31' value='31' {$day[31]} >31</option>
         </select>
         Year
         <select id='year'>
            <option value='2000' {$year[2000]} >2000</option>
            <option value='2001' {$year[2001]} >2001</option>
            <option value='2002' {$year[2002]} >2002</option>
            <option value='2003' {$year[2003]} >2003</option>
            <option value='2004' {$year[2004]} >2004</option>
            <option value='2005' {$year[2005]} >2005</option>
            <option value='2006' {$year[2006]} >2006</option>
            <option value='2007' {$year[2007]} >2007</option>
            <option value='2008' {$year[2008]} >2008</option>
            <option value='2009' {$year[2009]} >2009</option>
            <option value='2010' {$year[2010]} >2010</option>
            <option value='2011' {$year[2011]} >2011</option>
            <option value='2012' {$year[2012]} >2012</option>
            <option value='2013' {$year[2013]} >2013</option>
            <option value='2014' {$year[2014]} >2014</option>
            <option value='2015' {$year[2015]} >2015</option>
            <option value='2016' {$year[2016]} >2016</option>
            <option value='2017' {$year[2017]} >2017</option>
         </select>
html;
         $html .= "<div class='col-md-2' id='confirmBirthday'></div>";
         $html .= "</div>";	// End Row;
         $html .= "</div>";	// End Container;

         $buttons = "<button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Cancel</button>";
         if ( isset ( $_POST [ 'childName' ] ) ) $buttons .= "<button type=\"button\" class=\"btn btn-warning\" onclick='processEditChild ( ".$_POST [ 'editID' ]." )' data-dismiss=\"modal\">Update</button>";
         else $buttons .= "<button type=\"button\" id='addFamilyButton' class=\"btn btn-primary\" onclick='processAddFamily()'>Add Child</button>";

         $this->responseHTML ( "modalBody" , $html );
         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalFooter" , $buttons );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" );" );
         $this->send();
      }

      function addAdult()
      {
         session_start();

         $title = "<h4>New Adult</h4>";
         $html = "<div class='well' id='message'>Add adults here.  An adult listed here will be able to pick up your children and talk with the office about your account</div>";
         $html .= "<div class='container-fluid'>";
         $html .= "<div class='row' id='messageDiv'>";

         $html .= "</div>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='text-align:right;'>Adult's Full Name<br></div>";
         $html .= "<div class='col-md-8'><input type='text' class='form-control' id='adultName' value='".$adultName."'></div>";
         $html .= "</div>";	// End Row;

         $html .= "</div>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='text-align:right;'>Family Relationship (Father, Mother, Brother, etc.)<br></div>";
         $html .= "<div class='col-md-8'><input type='text' class='form-control' id='relationship' value='".$adultName."'></div>";
         $html .= "</div>";	// End Row;


         $html .= "</div>";	// End Container;

         $buttons = "<button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Cancel</button>";
         $buttons .= "<button type=\"button\" id='addFamilyButton' class=\"btn btn-primary\" onclick='processAddAdult()'>Add Adult</button>";

         $this->responseHTML ( "modalBody" , $html );
         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalFooter" , $buttons );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" );" );
         $this->send();
      }

      /*
      function addAdult()
      {
         session_start();

         $title = "<h4>New Adult</h4>";
         $html = "<div class='well' id='message'>Add adults here.  An adult listed here will be able to pick up your children and talk with the office about your account</div>";
         $html .= "<div class='container-fluid'>";
         $html .= "<div class='row' id='messageDiv'>";

         $html .= "</div>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='text-align:right;'>Adult's Full Name<br></div>";
         $html .= "<div class='col-md-8'><input type='text' class='form-control' id='adultName' value='".$adultName."'></div>";
         $html .= "</div>";	// End Row;

         $html .= "</div>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-4' style='text-align:right;'>Family relationship<br></div>";
         $html .= "<div class='col-md-8'><input type='text' class='form-control' id='relation' value='".$relation."'></div>";
         $html .= "</div>";	// End Row;

         $buttons = "<button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Cancel</button>";
         $buttons .= "<button type=\"button\" id='addFamilyButton' class=\"btn btn-primary\" onclick='processAddAdult()'>Add Adult</button>";

         $this->responseHTML ( "modalBody" , $html );
         $this->responseHTML ( "modalTitle" , $title );
         $this->responseHTML ( "modalFooter" , $buttons );
         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"show\" );" );
         $this->send();
      }
      */

      function processAddAdult()
      {
         session_start();

         $sql = "INSERT INTO `family` ( `userID` , `name` , `relation` , `primary` ) VALUES ( :user , :name , :relation , 0 )";

         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":user" , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->bindValue ( ":name" , $_POST [ 'name' ] , PDO::PARAM_STR );
         $query->bindValue ( ":relation" , $_POST [ 'relation' ] , PDO::PARAM_STR );
         $query->execute();

         $this->responseScript ( "$ ( \"#modal\" ).modal ( \"hide\" );" );
         $this->family();
      }

      function removeAdult()
      {
         session_start();

         $sql = "DELETE FROM `family` WHERE `ID` = ? AND `userID` = ? LIMIT 1";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'adultID' ] , PDO::PARAM_INT );
         $query->bindValue ( 2 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $this->family();
      }

      function removeChild()
      {
         session_start();

         $sql = "DELETE FROM `children` WHERE `ID` = ? AND `parentID` = ? LIMIT 1";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'childID' ] , PDO::PARAM_INT );
         $query->bindValue ( 2 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $this->family();
      }

      function family()
      {
         session_start();

         if ( isset ( $_SESSION [ 'ID' ] ) ) $_SESSION [ 'familyID' ] = $_SESSION [ 'ID' ];

         $sql = "SELECT * FROM `children` WHERE `parentID` = ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $query->execute();

         $html = "<div class='well'>Add your family info here.  When you're finished click <input type='button' class='btn btn-warning' value='Done' onclick='parent.document.getElementById ( \"frame\" ).src = \"grid.html\"'></div>";
         $html .= "<div class='container-fluid'>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6' id='children'>&nbsp</div>";
         $html .= "<div class='col-md-6' id='adults'>&nbsp;</div>";
         $html .= "</div>";	// End Row
         $html .= "</div>";	// End Container

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
            $family .= "<div class='col-md-3' style='font-weight:bold;'>Gender</div>";
            $family .= "<div class='col-md-3' style='font-weight:bold;'>Age</div>";
            $family .= "<div class='col-md-2' style='font-weight:bold;'>Edit</div>";
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
               $family .= "<div class='col-md-4'>".$child [ 'childName' ]." ".$child [ 'childLastName' ]."</div>";
               $family .= "<div class='col-md-3'>".substr ( $child [ 'gender' ] , 0 , 1 )."</div>";
               $family .= "<div class='col-md-3'>".$age->format ( "%y" )."</div>";
               $family .= "<div class='col-md-2'>";
               $family .= "<input type='button' class='btn btn-warning btn-xs' style='width:100%;' value='Edit ".$child [ 'childName' ]."' onclick='editChild ( ".$child [ 'ID' ]." )'>";
               $family .= "<input type='button' class='btn btn-warning btn-xs' style='width:100%;' value='Remove ".$child [ 'childName' ]."' onclick='removeFamily ( ".$child [ 'ID' ]." )'>";
               $family .= "</div>";	// End Column
               $family .= "</div>";	// End Row
            }
         }
         $family .= "</div>";	// End Container
         $family .= "<button class='btn btn-primary' onclick='addFamily()'><span class='glyphicon glyphicon-plus-sign'></span> Add a Child</button>";

         $sql = "SELECT * FROM `family` WHERE `userID` = ?";
         $familyQuery = $this->db->prepare ( $sql );
         $familyQuery->bindValue ( 1 , $_SESSION [ 'ID' ] , PDO::PARAM_INT );
         $familyQuery->execute();
         
         $adults = "<h4>Adults</h4>";
         $adults .= "<hr>";
         $adults .= "<div class='container-fluid'>";
         $adults .= "<div classs='row'>";
         $adults .= "<div class='col-md-6'><b>Name</b></div>";
         $adults .= "<div class='col-md-4'><b>Family Relation</b></div>";
         $adults .= "<div class='col-md-2'><b>Options</b></div>";
         $adults .= "</div>";	// End Row

         while ( $adult = $familyQuery->fetch ( PDO::FETCH_ASSOC ) )
         {
            $adults .= "<div classs='row'>";
            $adults .= "<div class='col-md-6'>".$adult [ 'name' ]."</div>";
            $adults .= "<div class='col-md-4'>".$adult [ 'relation' ]."</div>";
            if ( $adult [ 'primary' ] == 1 ) $adults .= "<div class='col-md-2'>Account Holder</div>";
            if ( $adult [ 'primary' ] != 1 ) $adults .= "<div class='col-md-2'><button class='btn btn-danger btn-xs' onclick='removeAdult ( ".$adult [ 'ID' ]." )'><span class='glyphicon glyphicon-remove'></span> Remove</button></div>";
            $adults .= "</div>";	// End Row
         }
         $adults .= "</div>";		// End Container
         $adults .= "<button class='btn btn-primary' onclick='addAdult()'><span class='glyphicon glyphicon-plus-sign'></span> Add an Adult</button>";

         $this->responseHTML ( "body" , $html );
         $this->responseHTML ( "children" , $family );
         $this->responseHTML ( "adults" , $adults );
         $this->send();
      }

      function register()
      {
         session_start();

         if ( isset ( $_SESSION [ 'ID' ] ) )
         {
            $this->responseScript ( "alert ( \"Going to Family\" );" );
            $this->responseScript ( "family()" );
            $this->send();
         }

         $sql = "SELECT * FROM `people` WHERE `email` LIKE ?";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( 1 , $_POST [ 'email' ] , PDO::PARAM_STR );
         $query->execute();

         if ( $query->rowCount() > 0 )
         {
            $this->responseScript ( "errorMessage ( \"This e-mail is already associated with an account!\" )" );
            $this->send();
         }

         $sql = "INSERT INTO `people` ( `firstName` , `lastName` , `address` , `city` , `state` , `zip` , `homePhone` , `workPhone` , `cellPhone` , `email` , `password` , `role` , `enabled` ) VALUES ( :first , :last , :address , :city , :state , :zip , :home , :work , :cell , :email , :password , 'FAMILY' , 1 );";

         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":first" , $_POST [ 'firstName' ] , PDO::PARAM_STR );
         $query->bindValue ( ":last" , $_POST [ 'lastName' ] , PDO::PARAM_STR );
         $query->bindValue ( ":email" , $_POST [ 'email' ] , PDO::PARAM_STR );
         $query->bindValue ( ":address" , $_POST [ 'address' ] , PDO::PARAM_STR );
         $query->bindValue ( ":city" , $_POST [ 'city' ] , PDO::PARAM_STR );
         $query->bindValue ( ":state" , $_POST [ 'state' ] , PDO::PARAM_STR );
         $query->bindValue ( ":zip" , $_POST [ 'zip' ] , PDO::PARAM_STR );
         $query->bindValue ( ":home" , $_POST [ 'homePhone' ] , PDO::PARAM_STR );
         $query->bindValue ( ":work" , $_POST [ 'workPhone' ] , PDO::PARAM_STR );
         $query->bindValue ( ":cell" , $_POST [ 'cellPhone' ] , PDO::PARAM_STR );
         $query->bindValue ( ":password" , $_POST [ 'password' ] , PDO::PARAM_STR );
         $query->execute();

         $id = $this->db->lastInsertId();
         $_SESSION [ 'familyID' ] = $id;
         $_SESSION [ 'ID' ] = $id;

         $sql = "INSERT INTO `family` ( `userID` , `name` , `relation` , `primary` ) VALUES ( :user , :name , :relation , :primary )";
         $query = $this->db->prepare ( $sql );
         $query->bindValue ( ":user" , $id , PDO::PARAM_INT );
         $query->bindValue ( ":name" , $_POST [ 'firstName' ]." ".$_POST [ 'lastName' ] , PDO::PARAM_STR );
         $query->bindValue ( ":relation" , $_POST [ 'relationship' ] , PDO::PARAM_STR );
         $query->bindValue ( ":primary" , 1 , PDO::PARAM_INT );
         $query->execute();

         //$html = "<div class='jumbotron'>";
         //$html .= "<h1>Registration Complete!</h1>";
         //$html .= "<h2><a href='index.html'>Please Login</a></h2>";
         //$html .= "</div>";

         $html = "<div class='container-fluid'>";
         $html .= "<div class='row'>";
         $html .= "<div class='col-md-6' id='children'>&nbsp</div>";
         $html .= "<div class='col-md-6' id='adults'>&nbsp;</div>";
         $html .= "</div>";	// End Row
         $html .= "</div>";	// End Container

         //$this->responseScript ( "alert ( \"Registration ID ".$id."\" )" );
         if ( $id == 0 )
         {
            $this->send();
         }

         $this->responseHTML ( "body" , $html );
         $this->family();
         $this->send();
      }
   }

   new web ( $db );

?>
