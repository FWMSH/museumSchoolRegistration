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

      function register()
      {
         // TODO -- Check for existing e-mail before creating new account
         $sql = "INSERT INTO `people` ( `firstName` , `lastName` , `address` , `city` , `state` , `zip` , `homePhone` , `workPhone` , `cellPhone` , `textOK` , `email` , `password` , `role` , `enabled` ) VALUES ( :first , :last , :address , :city , :state , :zip , :home , :work , :cell , :text , :email , :password , 'FAMILY' , 1 );";

         if ( $_POST [ 'text' ] == '1' ) $text = '1';
         else $text = '0';

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
         $query->bindValue ( ":text" , $text , PDO::PARAM_STR );
         $query->bindValue ( ":password" , $_POST [ 'password' ] , PDO::PARAM_STR );
         $query->execute();

         $html = "<div class='jumbotron'>";
         $html .= "<h1>Registration Complete!</h1>";
         $html .= "<h2><a href='index.html'>Please Login</a></h2>";
         $html .= "</div>";

         $this->responseHTML ( "body" , $html );
         $this->send();
      }
   }

   new web ( $db );

?>
