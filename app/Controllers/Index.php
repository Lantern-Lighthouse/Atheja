<?php

namespace Controllers;

class Index
{
    public function getStatus(\Base $base)
    {
        JSON_response(['message' => 'API is running', 'status' => 'ok']);
    }

    function updateConfigValue($f3, $key, $value, $iniFile = 'app/Configs/config.ini')
    {
        // Update the value in F3's hive
        $f3->set($key, $value);

        // Get the full configuration array
        $config = [];

        // Read the existing .ini file
        if (file_exists($iniFile)) {
            $config = parse_ini_file($iniFile, true);
        }

        // Update the value in the array (handle nested keys with dots)
        $parts = explode('.', $key);
        if (count($parts) == 1) {
            // Top-level key
            $config[$key] = $value;
        } else if (count($parts) == 2) {
            // Section and key
            $section = $parts[0];
            $option = $parts[1];
            if (!isset($config[$section])) {
                $config[$section] = [];
            }
            $config[$section][$option] = $value;
        }

        // Write the configuration back to the .ini file
        $content = '';
        foreach ($config as $section => $values) {
            // Check if this is a section or a direct key-value pair
            if (is_array($values)) {
                $content .= "[$section]\n";
                foreach ($values as $key => $val) {
                    $content .= "$key = " . (is_numeric($val) ? $val : "\"$val\"") . "\n";
                }
                $content .= "\n";
            } else {
                // Direct key-value pair without section
                $content .= "$section = " . (is_numeric($values) ? $values : "\"$values\"") . "\n";
            }
        }

        // Save the content back to the file
        return file_put_contents($iniFile, $content);
    }
}
