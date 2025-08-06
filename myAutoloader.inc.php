<?php

// zakladni nazev namespace, ktery bude pri registraci nahrazen za vychozi adresar aplikace
// pozn.: lze presunout do settings (zde ponechano pro nazornost)
/** @var string BASE_NAMESPACE_NAME  Zakladni namespace. */
const BASE_NAMESPACE_NAME = "kivweb";
/** @var string BASE_APP_DIR_NAME  Vychozi adresar aplikace. */
const BASE_APP_DIR_NAME = "app";

/** @var array FILE_EXTENSIONS  Dostupne pripony souboru, ktere budou testovany pri nacitani souboru pozadovanych trid. */
const FILE_EXTENSIONS = array(".class.php", ".interface.php");

spl_autoload_register(function ($className) {
    $className = str_replace(BASE_NAMESPACE_NAME, BASE_APP_DIR_NAME, $className);
    $fileName = dirname(__FILE__) ."/". $className;
    $fileName = str_replace("\\", "/", $fileName);

    foreach(FILE_EXTENSIONS as $ext) {
        if (file_exists($fileName . $ext)) {
            $fileName .= $ext;
            break;
        }
    }
    require_once($fileName);
});
?>
