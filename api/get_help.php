<?php

require_once '../admin/users/init.php';

header('Content-Type: application/json;charset=utf-8');

$db = DB::getInstance();

if (!$user->canServed()) {
    http_response_code(401);
    die();
}

$cat = Input::get('category');
$response = [];

if ($cat) {
    $cards = fetchHelpCardsForCat($cat);
    foreach($cards as $card) {
        $cardObj = new stdClass();
        $cardObj->type = 'content';
        $cardObj->name = $card->title;
        $cardObj->content = $card->body;
        $cardObj->icon = 'fas fa-question-circle';

        $response[] = $cardObj;
    }

    echo json($response);
    exit();
}
?>
