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
header('Content-Type: application/json;charset=utf-8');
$db = DB::getInstance();
$settings = $db->query("SELECT * FROM settings")->first();

if (ipCheckBan()) {
  die();
}

$response = new stdClass();

$error_message = null;
$errors = array();
$reset_password_success=FALSE;
$password_change_form=FALSE;
$ruser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = Input::get('csrf');
	if(!Token::check($token)) {
		$response->error = 'token';
		echo json($response);
		exit();
	}

	if ($user->isLoggedIn()) {
		if ($user->isBanned()) {
			die();
		}
		$ruser = $user;
	} else {
		$email = Input::get('email');
		$vericode = Input::get('vericode');
		$ruser = new User($email);

		if (!$ruser->exists()) {
			$response->error = 'server';
			echo json($response);
			exit();
		}

		if ($ruser->data()->vericode != $vericode || (strtotime($ruser->data()->vericode_expiry) - strtotime(date("Y-m-d H:i:s")) <= 0)) {
			$response->error = 'expired';
			echo json($response);
			exit();
		}
	}

	$validate = new Validate();
		$validation = $validate->check($_POST,array(
		'password' => array(
		  'display' => $newPw,
		  'required' => true,
		  'min' => $settings->min_pw,
		  'max' => $settings->max_pw,
		),
		'confirm' => array(
		  'display' => $confPw,
		  'required' => true,
		  'matches' => 'password',
		),
	));

	if($validation->passed()){
		$ruser->update(array(
		  'password' => password_hash(Input::get('password'), PASSWORD_BCRYPT, array('cost' => 12)),
		  'vericode' => randomstring(15),
			'vericode_expiry' => date("Y-m-d H:i:s"),
			'email_verified' => true,
			'force_pr' => 0,
		),$ruser->data()->id);
		$reset_password_success=TRUE;
		logger($ruser->data()->id,"User","Reset password.");
		if($settings->session_manager==1) {
			$passwordResetKillSessions=passwordResetKillSessions();
		}
	} else {
		$response->error = 'input';
		echo json($response);
		exit();
	}
} else {
	$response->token = Token::generate();
	$response->minPwLength = $settings->min_pw;
    $response->maxPwLength = $settings->max_pw;
	echo json($response);
	exit();
}
