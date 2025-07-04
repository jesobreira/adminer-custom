<?php

$config = include("./config.php");

class CustomAdmin extends \AdminNeo\Admin
{
    public function getServiceTitle()
    {
        global $config;
        return $config['name'] ?? 'DB';
    }
}

if (isset($config['colorMode'])) {
    $colormode = $config['colorMode'];

    if ($colormode === 'dark') {
        if (file_exists("./adminneo-light.css")) {
            unlink("./adminneo-light.css");
        }
        if (!file_exists("./adminneo-dark.css")) {
            file_put_contents("./adminneo-dark.css", "");
        }
    }
    else if ($colormode === "light") {
        if (file_exists("./adminneo-dark.css")) {
            unlink("./adminneo-dark.css");
        }
        if (!file_exists("./adminneo-light.css")) {
            file_put_contents("./adminneo-light.css", "");
        }
    }
    else {
        // auto
        if (file_exists("./adminneo-light.css")) {
            unlink("./adminneo-light.css");
        }
        if (file_exists("./adminneo-dark.css")) {
            unlink("./adminneo-dark.css");
        }
    }
} else {
    // auto
    if (file_exists("./adminneo-light.css")) {
        unlink("./adminneo-light.css");
    }
    if (file_exists("./adminneo-dark.css")) {
        unlink("./adminneo-dark.css");
    }
}

// Enable plugins.
$plugins = [
    new \AdminNeo\JsonPreviewPlugin(),
    new \AdminNeo\XmlDumpPlugin(),
    new \AdminNeo\FileUploadPlugin("data/"),
    new \AdminNeo\Bz2OutputPlugin(),
    new \AdminNeo\ZipOutputPlugin(),
    new \AdminNeo\ForeignEditPlugin(),
    new \AdminNeo\FrameSupportPlugin(),
    new \AdminNeo\JsonDumpPlugin(),
    new \AdminNeo\JsonPreviewPlugin(),
    new \AdminNeo\SlugifyEditPlugin(),
    new \AdminNeo\SqlLogPlugin(__DIR__ . '/logs/' . date("Y/m/d/H") . ".txt"),
    new \AdminNeo\SystemForeignKeysPlugin(),
    new \AdminNeo\TinyMcePlugin(),
    new \AdminNeo\DisplayForeignKeyNamePlugin()
];

// Use factory method to create CustomAdmin instance.
return CustomAdmin::create($config, $plugins);