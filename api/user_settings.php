<?php

require_once '../admin/users/init.php';

header('Content-Type: application/json;charset=utf-8');

if (!$user->canServed()) {
    http_response_code(401);
    die();
}

$db = DB::getInstance();
$settings = $db->query("SELECT * FROM settings")->first();
$response = new stdClass();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $user->data()->id;
    $validation = new Validate();
    $userdetails = $user->data();

    $token = $_POST['csrf'];
    if (!Token::check($token)) {
        $response->error = 'token';
        echo json($response);
        exit();
    } else {
        $validationFields = new stdClass();
        $fields = [];

        $un = Input::get('username');
        if ($userdetails->username != $un && ($settings->change_un == 1 || (($settings->change_un == 2) && ($user->data()->un_changed == 0)))) {
            $fields->username = $un;
            $fields['un_changed'] = 1;

            $validationFields->username = [
                'display' => lang('GEN_UNAME'),
                'required' => true,
                'unique_update' => 'users,'.$userId,
                'min' => $settings->min_un,
                'max' => $settings->max_un,
            ];
        } else if ($userdetails->username != $un) {
            $response->error = $settings->change_un == 1 ? 'cannotchangeun' : 'unonlyonce';
            echo json($response);
            exit();
        }

        $fname = ucfirst(Input::get('fname'));
        if ($userdetails->fname != $fname) {
            $fields['fname'] = $fname;

            $validationFields->fname = [
                'display' => lang('GEN_FNAME'),
                'required' => true,
                'min' => 1,
                'max' => 100,
            ];
        }

        $lname = ucfirst(Input::get('lname'));
        if ($userdetails->lname != $lname) {
            $fields['lname'] = $lname;

            $validationFields->lname = [
                'display' => lang('GEN_LNAME'),
                'required' => true,
                'min' => 1,
                'max' => 100,
            ];
        }

        if (!empty($_POST['password'])) {
            $validationFields->password = [
                'display' => lang('NEW_PW'),
                'required' => true,
                'min' => $settings->min_pw,
                'max' => $settings->max_pw,
            ];
            $validationFields->confirm = [
                'display' => lang('PW_CONF'),
                'required' => true,
                'matches' => 'password',
            ];
        }

        $validation->check($_POST, $validationFields);
        if ($validation->passed()) {
            $db->update('users', $userId, $fields);

            if (!empty($_POST['password'])) {
                $new_password_hash = password_hash(Input::get('password'), PASSWORD_BCRYPT, ['cost' => 12]);
                $user->update(['password' => $new_password_hash, 'force_pr' => 0, 'vericode' => randomstring(15)], $user->data()->id);
                logger($user->data()->id, 'User', 'Updated password.');

                if ($settings->session_manager == 1) {
                    $passwordResetKillSessions = passwordResetKillSessions();
                }
            }
        } else {
            if ($validation->unique_un()) {
                $response->error = 'username';
            } else if ($validation->not_email_error()) {
                $response->error = 'notemail';
            } else {
                $response->error = 'input';
            }
            echo json($response);
            exit();
        }

        $response->error = '';
        echo json($response);
        exit();
    }
} else {
    $response->token = Token::generate();
    $response->recaptcha = $settings->recaptcha != 0 ? $settings->recap_public : null;
    $response->canChangeUn = $settings->change_un == 1;
    $response->minUserLength = $settings->min_un;
    $response->maxUserLength = $settings->max_un;
    $response->minPwLength = $settings->min_pw;
    $response->maxPwLength = $settings->max_pw;
    echo json($response);
    exit();
}

// CHANGE UN -> ONLY ONCE!!!!!!

?>