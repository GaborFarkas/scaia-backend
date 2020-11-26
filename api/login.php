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
ini_set("allow_url_fopen", 1);
if(isset($_SESSION)){session_destroy();}
require_once '../admin/users/init.php';
$db = DB::getInstance();
$settings = $db->query("SELECT * FROM settings")->first();
$response = new stdClass();
?>
<?php
if(ipCheckBan()){
  $response->banned = true;
  echo json_encode($response);
  exit();
} else {
  $response->banned = false;
}
$response->error = null;
$reCaptchaValid=FALSE;
if($user->isLoggedIn()) {
  $response->userData = $user->apiData();
  echo json_encode($response);
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = Input::get('csrf');
  if(!Token::check($token)){
    $response->error = 'token';
    echo json_encode($response);
    exit();
  }

  //Check to see if recaptcha is enabled
  if($settings->recaptcha == 1){
    if(!function_exists('post_captcha')){
      function post_captcha($user_response) {
        global $settings;
        $fields_string = '';
        $fields = array(
            'secret' => $settings->recap_private,
            'response' => $user_response
        );
        foreach($fields as $key=>$value)
        $fields_string .= $key . '=' . $value . '&';
        $fields_string = rtrim($fields_string, '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);

        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
      }
    }

    // Call the function post_captcha
    $res = post_captcha($_POST['g-recaptcha-response']);

    if (!$res['success']) {
      // What happens when the reCAPTCHA is not properly set up
      $response->error = 'recaptcha';
      echo json_encode($response);
      exit();
    }else{
      $reCaptchaValid=TRUE;
    }
  }
  if($reCaptchaValid || $settings->recaptcha == 0 || $settings->recaptcha == 2 ){ //if recaptcha valid or recaptcha disabled

    $validate = new Validate();
    $validation = $validate->check($_POST, array(
      'username' => array('display' => 'Username','required' => true),
      'password' => array('display' => 'Password', 'required' => true)));
    if ($validation->passed()) {
      //Log user in
      $remember = false;
      $user = new User();
      $login = $user->loginEmail(Input::get('username'), trim(Input::get('password')), $remember);
      if ($login) {
        $response->userData = $user->data();
        echo json_encode($response);
        exit();
      } else {
        logger("0","Login Fail","A failed login on login.php");
        $response->error = 'input';
        echo json_encode($response);
        exit();
      }
    }
  }
} else {
  $response->token = Token::generate();
  echo json_encode($response);
  exit();
}
