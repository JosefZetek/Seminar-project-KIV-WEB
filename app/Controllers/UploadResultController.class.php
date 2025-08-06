<?php

namespace kivweb\Controllers;
use kivweb\Models\DatabaseConnection;
use kivweb\Others\MyLogin;
use kivweb\Others\Constants;

class UploadResultController implements IController
{

    private const DEFAULT_DIRECTORY = "clanky/";
    private DatabaseConnection $connection;
    private MyLogin $login;

    public function __construct(DatabaseConnection $connection, MyLogin $login)
    {
        $this->connection = $connection;
        $this->login = $login;
    }

    /**
     * Funkce kontroluje platnost přijatých dat
     * @param string $articleName Název článku
     * @param string $description Popis článku
     * @param string $article Článek
     * @return bool|string Funkce vrací true, pokud je vše v pořádku, v opačném případě vrací string s chybovou hláškou
     */
    private function checkInputValidity(string $articleName, string $description, string $article)
    {
        if(!$this->login->isUserLogged())
            return "Uživatel není přihlášený";

        $userInformation = $this->connection->getUserInformationWithEmail($this->login->getUserEmail());

        if(!isset($userInformation))
            return "Nepodařilo se načíst informace o přihlášeném uživateli";

        if($userInformation['group'] != Constants::AUTHOR_ID)
            return "Uživatel nemá roli Autor";

        if(empty($articleName))
            return "Článek musí mít svůj název";

        if(empty($description))
            return "Článek musí mít svůj popis";

        if(empty($article))
            return "Nebyl dodán článek";

        if(strlen($articleName) > Constants::MAX_ARTICLE_NAME_LENGTH)
            return "Název článku je moc dlouhý";

        if(strlen($description) > Constants::MAX_DESCRIPTION_LENGTH)
            return "Popis článku je moc dlouhý";

        if(strlen($article) > Constants::MAX_ARTICLE_LENGTH)
            return "Článek je moc dlouhý";

        return true;
    }

    /**
     * Metoda zkousi vytvorit slozku v predane ceste
     * @param string $targetDirectory Predana cesta
     * @return bool Vraci true pokud se slozka podari vytvorit, jinak false
     */
    private function createDirectory($targetDirectory): bool
    {
        if (file_exists($targetDirectory) || is_dir($targetDirectory))
            return false;

        return mkdir($targetDirectory, 0777, true);
    }


    /**
     * Funkce presouva soubory do slozek
     * @param string $dataType String popisujici, ktery typ dat presunout images nebo files
     * @param string $targetDirectory Cesta, kam se maji soubory presunout
     * @return true | string True, pokud se vse podari, jinak string s chybou
     */
    private function moveFiles($dataType, $targetDirectory) {

        if (!isset($_FILES[$dataType]) || empty($_FILES[$dataType]['tmp_name'])) {
            return false;
        }

        $imageFiles = $_FILES[$dataType];
        $transfered = true;
        foreach ($imageFiles['tmp_name'] as $index => $tmpName) {

            $imageName = $this->sanitizeFileName($imageFiles['name'][$index]);
            $imageName = $this->normalizeFileName($imageName);

            $errorCode = $imageFiles['error'][$index];

            if ($errorCode !== UPLOAD_ERR_OK) {
                continue;
            }


            $targetPath = $targetDirectory . "/" . $imageName;

            if(!move_uploaded_file($tmpName, $targetPath)) {
                echo '<script>alert("neulozeno");</script>';
                $transfered = false;
            }
        }
        if(!$transfered)
            return "Některé soubory typu " . $dataType . " se nepodařilo uložit. Možná jsou některé soubory pojmenovány stejně.";

        return true;
    }

    private function sanitizeFileName($filename) {
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);
        return $filename;
    }

    private function normalizeFileName($filename) {
        // Separate the extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Remove diacritics and unsafe chars
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);

        // Recombine name and extension
        return $name . '.' . $ext;
    }

    public function showView(): void
    {
        $controller = new ResultController();

        $articleName = $_POST['nazev'];
        $description = $_POST['popis'];
        $article = $_POST['clanek'];

        $validity = $this->checkInputValidity($articleName, $description, $article);

        //Kontrola, jestli clanek splnuje vsechny dulezite nalezitosti
        if(!($validity === true))
        {
            $controller->showView(false, $validity);
            return;
        }


        $publishResult = $this->connection->publishArticle($articleName, $description, $article);
        if($publishResult === false)
        {
            $controller->showView(false, "Článek se nepodařilo odeslat");
            return;
        }


        $articleDirectory = self::DEFAULT_DIRECTORY . $publishResult;


        if(!$this->createDirectory($articleDirectory))
        {
            $controller->showView(false, "Ke článku se nepodařilo zveřejnit přílohy");
            return;
        }



        $attachmentsDirectory = $articleDirectory . "/prilohy";
        $imagesDirectory = $articleDirectory . "/obrazky";

        $attachmentsDirectoryCreated = true;

        if(!$this->createDirectory($attachmentsDirectory))
            $attachmentsDirectoryCreated = "Nepodařilo se vytvořit složku pro přílohy.";
        else
            $this->moveFiles("files", $attachmentsDirectory);

        $imagesDirectoryResult = true;

        if(!$this->createDirectory($imagesDirectory))
            $imagesDirectoryResult = "Nepodařilo se vytvořit složku pro přílohy.";
        else
            $imagesDirectoryResult = $this->moveFiles("images", $imagesDirectory);

        if(!($attachmentsDirectoryCreated === true) && !($imagesDirectoryResult === true)) {
            $controller->showView(false, $imagesDirectoryResult . " " . $attachmentsDirectoryCreated);
            return;
        }

        if(!($attachmentsDirectoryCreated === true)) {
            $controller->showView(false, $attachmentsDirectoryCreated);
            return;
        }

        if(!($imagesDirectoryResult === true)) {
            $controller->showView(false, $imagesDirectoryResult);
            return;
        }


        $controller->showView(true, "Článek se podařilo úspěšně odeslat. Nyní je potřeba počkat na schválení.");
    }
}