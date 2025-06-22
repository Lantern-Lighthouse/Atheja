<?php
require('./vendor/autoload.php');
$base = \Base::instance();
$base->config('./app/Configs/config.ini');

$base->set('DB', new \DB\SQL(
    $base->get('db.dsn'),
    $base->get('db.username'),
    $base->get('db.password'),
    [PDO::ATTR_STRINGIFY_FETCHES => false]
));

function JSON_response($message, int $code = 200)
{
    header("Content-Type: application/json");
    http_response_code($code);
    echo json_encode($message);
}

function updateConfigValue($f3, $key, $value, $iniFile = 'app/Configs/config.ini')
{
    $f3->set($key, $value);
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

$base->run();