<?php
if(!isset($_COOKIE['some_cookie1'],$_COOKIE['some_cookie2'])) {
    header('HTTP/1.1 500 Internal Server Booboo');
    header('Content-Type: application/json; charset=UTF-8');
    die(json_encode(['message' => 'cookie_die', 'descr' => 'Внимание! Вам необходимо авторизоваться']));
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if($action !== '') {
    // форма добавления затрат
    if($action === 'show_addform_charges') {
        include_once 'project_folder/core/params.php';
        include_once CORE_PATH.'db.class.php';
        $db = new DB();

        $date_add = isset($_POST['date_add']) ? $_POST['date_add'] : '';
        $type = isset($_POST['type']) ? $_POST['type'] : 0;
        $summ = isset($_POST['summ']) ? preg_replace('/,/','.',$_POST['summ']) : 0;

        $db->query('INSERT INTO some_table (some_rows) VALUES (some_values) ON DUPLICATE KEY UPDATE value = :value',
            [some_values]);

        echo json_encode([
            'type' => 'success',
            'mess' => 'Успешно добавлено'
        ]);
    }
} else {
    echo json_encode([
        'type' => 'error',
        'mess' => 'Указаны не все необходимые данные'
    ]);
}
