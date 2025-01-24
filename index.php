<?php
function adminer_object() {
    // required to run any plugin
    include_once "./plugins/plugin.php";
    
    // autoloader
    foreach (glob("plugins/*.php") as $filename) {
        include_once "./$filename";
    }
    
    $adminer = new AdminerPlugin([]);
	
	$adminer->plugins = [// specify enabled plugins here
      new AdminerDumpXml(),
      new AdminerCopy(),
      new AdminerDisplayForeignKeyName(),
      // new AdminerDesigns(),
      // new AdminerThemeSwitcher(),
      new AdminerDumpAlter(),
      new AdminerDumpJson(),
      new AdminerDumpXml(),
      new AdminerDumpZip(),
      new AdminerEditCalendar(),
      new AdminerEditForeign(),
      new AdminerEditTextarea(),
      new AdminerJsonColumn(),
      new AdminerPHPSerializedColumn(),
      new AdminerPrettyJsonColumn($adminer),
      new AdminerSqlLog(),
      new AdminerStructComments()
	];
    
    return $adminer;
}

// include original Adminer or Adminer Editor
include "./adminer.php";