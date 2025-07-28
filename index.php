<?php
require('./vendor/autoload.php');
$base = \Base::instance();
$base->config('./app/Configs/config.ini');

switch ($base->get("ATH.DATABASE_CONNECTION_TYPE")) {
    case "sqlite":
        $base->set('DB', new DB\SQL($base->get('db.dsn'), null, null, [PDO::ATTR_STRINGIFY_FETCHES => false]));
        break;
    default:
    case "mysql":
        $base->set('DB', new \DB\SQL(
            $base->get('db.dsn'),
            $base->get('db.username'),
            $base->get('db.password'),
            [PDO::ATTR_STRINGIFY_FETCHES => false]
        ));
        break;
}

function JSON_response($message, int $code = 200)
{
    header("Content-Type: application/json");
    http_response_code($code);
    echo json_encode($message);
}

function updateConfigValue($base, $key, $value, $iniFile = 'app/Configs/config.ini')
{
    $base->set($key, $value);
    $config = [];
    if (file_exists($iniFile))
        $config = parse_ini_file($iniFile, true);
    $parts = explode('.', $key);

    if (count($parts) == 1)
        $config[$key] = $value;
    else if (count($parts) == 2) {
        $section = $parts[0];
        $option = $parts[1];
        if (!isset($config[$section]))
            $config[$section] = [];
        $config[$section][$option] = $value;
    }

    $content = '';
    foreach ($config as $section => $values) {
        if (is_array($values)) {
            $content .= "[$section]\n";
            foreach ($values as $key => $val)
                $content .= "$key = " . (is_numeric($val) ? $val : "\"$val\"") . "\n";
            $content .= "\n";
        } else
            $content .= "$section = " . (is_numeric($values) ? $values : "\"$values\"") . "\n";
    }

    return file_put_contents($iniFile, $content);
}

function VerifyAuth(\Base $base)
{
    $model = new \Models\User();
    $authHeader = $base->get('HEADERS.Authorization');

    if (empty($authHeader)) {
        JSON_response("Authorization required", 401);
        return false;
    }

    $admins = $model->find(['is_admin=1']);
    $validAdmin = false;

    foreach ($admins as $admin) {
        if (password_verify($authHeader, $admin->key)) {
            $validAdmin = true;
            break;
        }
    }

    if (!$validAdmin)
        JSON_response("Feature is disabled", 503);

    return $validAdmin;
}

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