<?php
// This is a user-facing page
/*
UserSpice 5
An Open Source PHP User Management System
by the UserSpice Team at http://UserSpice.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
require_once '../admin/users/init.php';
$db = DB::getInstance();
$settings = $db->query("SELECT * FROM settings")->first();

if (ipCheckBan() || $user->isLoggedIn()) {
  die();
}

$response = new stdClass();

$error_message = null;
$errors = array();
$email_sent=FALSE;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = Input::get('csrf');
  if(Input::exists()){
    if(!Token::check($token)){
      $response->error = 'token';
      echo json_encode($response);
      exit();
    }

    $email = Input::get('email');
    //validate the form
    $validate = new Validate();
    $msg1 = lang("GEN_EMAIL");
    $validation = $validate->check($_POST,array('email' => array('display' => $msg1,'valid_email' => true,'required' => true,),));

    if($validation->passed()){
      $fuser = new User($email);
      if($fuser->exists()){
        $vericode=randomstring(15);
        $vericode_expiry=date("Y-m-d H:i:s",strtotime("+$settings->reset_vericode_expiry minutes",strtotime(date("Y-m-d H:i:s"))));
        $db->update('users',$fuser->data()->id,['vericode' => $vericode,'vericode_expiry' => $vericode_expiry]);
          //send the email
          $options = array(
            'fname' => $fuser->data()->fname,
            'email' => rawurlencode($email),
            'vericode' => $vericode,
            'reset_vericode_expiry' => $settings->reset_vericode_expiry
          );
          $subject = lang("PW_RESET");
          $encoded_email=rawurlencode($email);
          $body =  email_body('_email_template_forgot_password.php',$options);
          $email_sent=email($email,$subject,$body);
          logger($fuser->data()->id,"User","Requested password reset.");
          if(!$email_sent){
            $response->error = 'server';
            echo json_encode($response);
            exit();
          }
      }else{
        $response->error = 'user';
        echo json_encode($response);
        exit();
      }
    }else{
        //display the errors
        $response->error = 'input';
        echo json_encode($response);
        exit();
    }
  }

} else {
  $response->token = Token::generate();
  echo json_encode($response);
  exit();
}
