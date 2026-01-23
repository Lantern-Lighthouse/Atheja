<?php
spl_autoload_register(function ($className) {
    $classFile = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . $classFile;

    if (file_exists($filePath)) {
        require_once $filePath;
        return true;
    }
    return false;
});

require('./vendor/autoload.php');
$base = \Base::instance();
$base->config('./app/Configs/config.ini');

switch ($base->get("ATH.DATABASE_CONNECTION_TYPE")) {
    case "sqlite":
        if (!file_exists(substr($base->get('db.dsn'), 7))) {
            \Responsivity\Responsivity::respond('Database file not found', \Responsivity\Responsivity::HTTP_Internal_Error);
        }
        $base->set('DB', new DB\SQL($base->get('db.dsn'), null, null, [PDO::ATTR_STRINGIFY_FETCHES => false]));
        if (filesize(substr($base->get('db.dsn'), 7)) == 0)
            (new \Controllers\Index)->getDBInit($base);
        break;
    default:
    case "mysql":
    case "pgsql":
        $base->set('DB', new \DB\SQL(
            $base->get('db.dsn'),
            $base->get('db.username'),
            $base->get('db.password'),
            [PDO::ATTR_STRINGIFY_FETCHES => false]
        ));
        break;
}

$base->set('ONERROR', function ($base) {
    if ($base->get('DEBUG') >= 3){
        $Tracer = explode("\n", $base->get('ERROR.trace'));
        $Tracer = array_filter($Tracer);
    }

    $BobTheBuilder = [
        //...(condition ? ['index' => $value] : []),
        ...($base->get('DEBUG') <= 1 ? ['status' => $base->get('ERROR.code') . ' ' . $base->get('ERROR.status')] : []),
        ...($base->get('DEBUG') >= 2 ? ['text' => $base->get('ERROR.text')] : []),
        ...($base->get('DEBUG') >= 3 ? ['trace' => $Tracer] : []),
    ];

    \Responsivity\Responsivity::respond($BobTheBuilder, $base->get('ERROR.code'));
});


function VerifySessionToken(\Base $base)
{
    $authHeader = $base->get('HEADERS.Authorization');
    if (!$authHeader)
        return false;

    if (!preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches))
        return false;

    $token = $matches[1];

    $sessionModel = new \Models\Sessions();
    $sessions = $sessionModel->find(['expires_at > ?', date('Y-m-d H:i:s')]);
    if ($sessions == false)
        return false;

    foreach ($sessions as $session) {
        if (password_verify($token, $session->key)) {
            $session->last_used_at = date('Y-m-d H:i:s');
            $session->save();

            return $session->user;
        }
    }

    return false;
}

$base->run();
