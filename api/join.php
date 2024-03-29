<?php
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
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
ini_set('allow_url_fopen', 1);
require_once '../admin/users/init.php';
header('Content-Type: application/json;charset=utf-8');
$db = DB::getInstance();
$settings = $db->query("SELECT * FROM settings")->first();

if (ipCheckBan() || $user->isLoggedIn()) {
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

$response = new stdClass();

if ($settings->registration == 0) {
    $response->registrationDisabled = true;
    echo json($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vericode = randomstring(15);

    $form_valid = false;

    //Decide whether or not to use email activation
    $query = $db->query('SELECT * FROM email');
    $results = $query->first();
    $act = $results->email_act;

    //Opposite Day for Pre-Activation - Basically if you say in email
    //settings that you do NOT want email activation, this lists new
    //users as active in the database, otherwise they will become
    //active after verifying their email.
    if ($act == 1) {
        $pre = 0;
    } else {
        $pre = 1;
    }

    $reCaptchaValid = false;

    if (Input::exists()) {
        $token = $_POST['csrf'];
        if (!Token::check($token)) {
            $response->error = 'token';
            echo json($response);
            exit();
        }
    
        $fname = Input::get('fname');
        $lname = Input::get('lname');
        $email = Input::get('email');
        $username = Input::get('username');
    
        $validation = new Validate();
        if (pluginActive('userInfo', true)) {
            $is_not_email = false;
        } else {
            $is_not_email = true;
        }
        $validation->check($_POST, [
            'username' => [
                'display' => lang('GEN_UNAME'),
                'is_not_email' => $is_not_email,
                'required' => true,
                'min' => $settings->min_un,
                'max' => $settings->max_un,
                'unique' => 'users',
            ],
            'fname' => [
                'display' => lang('GEN_FNAME'),
                'required' => true,
                'min' => 1,
                'max' => 100,
            ],
            'lname' => [
                'display' => lang('GEN_LNAME'),
                'required' => true,
                'min' => 1,
                'max' => 100,
            ],
            'email' => [
                'display' => lang('GEN_EMAIL'),
                'required' => true,
                'valid_email' => true,
                'unique' => 'users',
            ],
            'password' => [
                'display' => lang('GEN_PASS'),
                'required' => true,
                'min' => $settings->min_pw,
                'max' => $settings->max_pw,
            ],
            'confirm' => [
                'display' => lang('PW_CONF'),
                'required' => true,
                'matches' => 'password',
            ],
        ]);
    
        if ($validation->passed()) {
            //Logic if ReCAPTCHA is turned ON
            if ($settings->recaptcha > 0) {
                if (!function_exists('post_captcha')) {
                    function post_captcha($user_response)
                    {
                        global $settings;
                        $fields_string = '';
                        $fields = [
                      'secret' => $settings->recap_private,
                      'response' => $user_response,
                  ];
                        foreach ($fields as $key => $value) {
                            $fields_string .= $key.'='.$value.'&';
                        }
                        $fields_string = rtrim($fields_string, '&');
    
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
                        curl_setopt($ch, CURLOPT_POST, count($fields));
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
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
                    echo json($response);
                    exit();
                } else {
                    $reCaptchaValid = true;
                    $form_valid = true;
                }
            } //else for recaptcha
    
            if ($reCaptchaValid || $settings->recaptcha == 0) {
                $form_valid = true;
                //add user to the database
                $user = new User();
                $join_date = date('Y-m-d H:i:s');
                $params = [
                    'fname' => Input::get('fname'),
                    'email' => $email,
                    'username' => $username,
                    'vericode' => $vericode,
                    'join_vericode_expiry' => $settings->join_vericode_expiry,
                ];
                $vericode_expiry = date('Y-m-d H:i:s');
                if ($act == 1) {
                    //Verify email address settings
                    $to = rawurlencode($email);
                    $subject = html_entity_decode($settings->site_name, ENT_QUOTES);
                    $body = email_body('_email_template_verify.php', $params);
                    email($to, $subject, $body);
                    $vericode_expiry = date('Y-m-d H:i:s', strtotime("+$settings->join_vericode_expiry hours", strtotime(date('Y-m-d H:i:s'))));
                }
                try {
                    // echo "Trying to create user";
                    $fields = [
                        'username' => $username,
                        'fname' => ucfirst(Input::get('fname')),
                        'lname' => ucfirst(Input::get('lname')),
                        'email' => Input::get('email'),
                        'password' => password_hash(Input::get('password', true), PASSWORD_BCRYPT, ['cost' => 12]),
                        'permissions' => 1,
                        'join_date' => $join_date,
                        'email_verified' => $pre,
                        'vericode' => $vericode,
                        'vericode_expiry' => $vericode_expiry,
                        'oauth_tos_accepted' => true,
                    ];
                    $activeCheck = $db->query('SELECT active FROM users');
                    if (!$activeCheck->error()) {
                        $fields['active'] = 1;
                    }
                    $theNewId = $user->create($fields);
                } catch (Exception $e) {
                    $response->error = 'server';
                    echo json($response);
                    die();
                }
                if ($form_valid == true) { //this allows the plugin hook to kill the post but it must delete the created user
                    if ($act == 1) {
                        logger($theNewId, 'User', 'Registration completed and verification email sent.');
                        $query = $db->query('SELECT * FROM email');
                        $results = $query->first();
                        $act = $results->email_act;
                    } else {
                        logger($theNewId, 'User', 'Registration completed.');
                    }

                    $response->error = '';
                    echo json($response);
                    exit();
                }
            }
        } else {
            if ($validation->unique_un()) {
                $response->error = 'username';
            } else if ($validation->unique_email()) {
                $response->error = 'emailexists';
            } else if ($validation->invalid_email()) {
                $response->error = 'email';
            } else if ($validation->not_email_error()) {
                $response->error = 'notemail';
            } else {
                $response->error = 'input';
            }
            echo json($response);
            exit();
        }
    } //Input exists
} else {
    $response->token = Token::generate();
    $response->recaptcha = $settings->recaptcha != 0 ? $settings->recap_public : null;
    $response->minUserLength = $settings->min_un;
    $response->maxUserLength = $settings->max_un;
    $response->minPwLength = $settings->min_pw;
    $response->maxPwLength = $settings->max_pw;
    echo json($response);
    exit();
}
