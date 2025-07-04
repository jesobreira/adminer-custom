<?php

class CustomAdmin extends \AdminNeo\Admin
{
    public function getServiceTitle()
    {
        return "DB";
    }
}

// Define configuration.
$config = [
    "colorVariant" => "green",
    "navigationMode" => "dual",
    "preferSelection" => true,
    "recordsPerPage" => 70,
];

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