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
$query = $db->query("SELECT * FROM email");
$results = $query->first();
$act = $results->email_act;

if (ipCheckBan() || !$user->isLoggedIn() || $act!=1) {
    die();
}

$check = $db->query("SELECT id FROM users WHERE email = ? AND email_verified = 1",[$email])->count();
if (!$user->exists() || $check > 0) {
  $user->logout();
  die();
}

if($settings->allow_language == 0 || !isset($user) || !$user->isLoggedIn()){
	if(!isset($_SESSION['us_lang'])){
	$_SESSION['us_lang'] = $settings->default_language;
}
}else{
	if(isset($user) && $user->isLoggedIn()){
	$_SESSION['us_lang'] = $user->data()->language;
	}else{
	$_SESSION['us_lang'] = $settings->default_language;
}
}

include $abs_us_root.$us_url_root.'users/lang/'.$_SESSION['us_lang'].".php";

$email_sent=FALSE;
$email = $user->data()->email;

$vericode=randomstring(15);
$vericode_expiry=date("Y-m-d H:i:s",strtotime("+$settings->join_vericode_expiry hours",strtotime(date("Y-m-d H:i:s"))));
$db->update('users',$user->data()->id,['vericode' => $vericode,'vericode_expiry' => $vericode_expiry]);
//send the email
$options = array(
  'fname' => $user->data()->fname,
  'email' => rawurlencode($email),
  'vericode' => $vericode,
  'join_vericode_expiry' => $settings->join_vericode_expiry
);
$encoded_email=rawurlencode($email);
$subject = lang("EML_VER");
$body =  email_body('_email_template_verify.php',$options);
$email_sent=email($email,$subject,$body);
logger($user->data()->id,"User","Requested a new verification email.");
if(!$email_sent){
    $errors[] = lang("ERR_EMAIL");
}
