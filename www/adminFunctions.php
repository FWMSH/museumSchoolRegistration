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
         $html .= "<div class='col-md-1 header-angle'>Class Type</div>";
         $html .= "<div class='col-md-1 header-angle'>Class Name</div>";
         $html .= "<div class='col-md-1 header-angle'>Age</div>";
         $html .= "<div class='col-md-1 header-angle'>Meeting Days</div>";
         $html .= "<div class='col-md-2 header-angle'>Start Date<br>Start Time</div>";
         $html .= "<div class='col-md-2 header-angle'>End Date<br>End Time</div>";
         $html .= "<div class='col-md-1 header-angle'>Cost</div>";
         $html .= "<div class='col-md-1 header-angle'>Max Size</div>";
         $html .= "<div class='col-md-1 header-angle'>Current<br>Enrollment</div>";
         $html .= "<div class='col-md-1 header-angle'>Status</div>";
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

            $html .= "<div class='row'>";
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
   }

   new web ( $db );

?>
