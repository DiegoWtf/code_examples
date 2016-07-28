<?php
/**
 *
 * Just send a email
 *
 */

include_once('project_folder/core/params.php');

$worker = new GearmanWorker();
$worker->addServer('127.0.0.1', 4730);
$worker->addFunction('send_email', 'send_email_fn');

function send_email_fn(GearmanJob $job){
    global $twig_settings;

    mb_internal_encoding('UTF-8');
    $headers =
        'From: '.mb_encode_mimeheader('Sender', 'UTF-8').' <sender@mail.ru>' . "\r\n" .
        'Reply-To: sender@mail.ru' . "\r\n" .
        'MIME-Version: 1.0' . "\r\n" .
        'Content-type: text/html; charset="utf-8"';

    $workload = json_decode($job->workload());

    require_once(VENDOR_PATH.'/twig/twig/lib/Twig/Autoloader.php');
    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem($twig_settings['template_path'].'/emails/system');
    $twig = new Twig_Environment($loader, [
        $twig_settings['twig']
    ]);
    // оборачиваем тело в общий шаблон
    $template_main = $twig->loadTemplate('system_main.twig');
    $bodyHtml = $template_main->renderBlock('body_html', ['body'=>$workload->body]);

    mail($workload->email, mb_encode_mimeheader($workload->subject, 'UTF-8'), $bodyHtml, $headers);
    // Передаем статус завершения
    $job->sendComplete('');
}
$worker->work();
sleep(1);