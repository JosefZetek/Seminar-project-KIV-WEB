<?php

namespace kivweb\Models;

use mysql_xdevapi\Result;
use PDO;
use PDOException;

class DatabaseConnection {
    /**
     * @var PDO $connection Database connection used for queries
     */
    private PDO $connection;

    /**
     * This is a constructor for a database
     * If it cannot connect to database, throws an exception
     * @param $host String Address of a server with MySQL database
     * @param $dbname String Name of a database to connect to
     * @param $username String Username to sign in
     * @param $password String Password to sign in
     */
    public function __construct(string $host, string $dbname, string $username, string $password)
    {
        if(isset($connection)) {
            return;
        }

        // Establish a connection to the database
        $this->connection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

        // Set the PDO error mode to exception
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }

    public function getArticleDetails($id)
    {
        $query = $this->connection->prepare("SELECT id, nazev, popis, clanek, schvaleno FROM clanky WHERE id=:id");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $result = $query->fetch(PDO::FETCH_ASSOC);
        if(!$result || empty($result))
            return "Nepodařilo se získat data o článku";


        $imagesDirectory = "clanky/{$result['id']}/obrazky";

        if (is_dir($imagesDirectory)) {
            $files = scandir($imagesDirectory);
            $files = array_diff($files, array('..', '.'));
            $result['images'] = $files;
        }
        else
            $result['images'] = [];

        $attachmentsDirectory = "clanky/{$result['id']}/prilohy";
        if (is_dir($attachmentsDirectory)) {
            $files = scandir($attachmentsDirectory);
            $files = array_diff($files, array('..', '.'));
            $result['files'] = $files;
        }
        else
            $result['files'] = [];

        $result['rating'] = $this->getArticleRating($id);

        return $result;
    }


    /**
     * Tato metoda vraci seznam schvalenych clanku
     * @return array|false Returns array of articles or false when an error occurs
     */
    public function getArticles()
    {
        $query = $this->connection->prepare("SELECT clanky.id, clanky.nazev, clanky.popis FROM clanky WHERE schvaleno=1");
        $query->execute();


        //Nacist vsechny clanky
        $articles = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($articles as &$article) {
            $articleId = $article['id'];
            $directory = "clanky/{$articleId}/obrazky";

            if (is_dir($directory)) {
                $files = scandir($directory);
                $files = array_diff($files, array('..', '.'));
                $article['images'] = $files;
            }
            else
                $article['images'] = [];
        }

        foreach ($articles as &$item) {
            $result = $this->getArticleRating($item['id']);
            $average = 0;
            foreach ($result as $singleRating) {
               $average += $singleRating;
            }

            $average /= 3;
            $item['rating'] = $average;
        }

        return $articles;
    }

    /**
     * Metoda vraci seznam neohodnocenych clanku
     * @return array Vraci pole neschvalenych clanku
     */
    public function getUnratedArticles()
    {
        $query = $this->connection->prepare("SELECT clanky.id, clanky.nazev, clanky.popis FROM clanky WHERE schvaleno=0");
        $query->execute();


        //Nacist vsechny clanky
        $articles = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($articles as &$article) {
            $articleId = $article['id'];
            $directory = "clanky/{$articleId}/obrazky";

            if (is_dir($directory)) {
                $files = scandir($directory);
                $files = array_diff($files, array('..', '.'));
                $article['images'] = $files;
            }
            else
                $article['images'] = [];
        }

        return $articles;
    }

    private function getArticleRating($articleID)
    {
        $noRating = array(
            "text" => 0,
            "graphics" => 0,
            "attachments" => 0);

        if(!isset($articleID)) {
            return $noRating;
        }

        $query = $this->connection->prepare("SELECT text, grafika, prilohy FROM hodnoceni_clanku WHERE id_clanku=:articleID");
        $query->bindParam(':articleID', $articleID, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if(!is_array($result)) {
            return $noRating;
        }

        return $result;
    }

    /**
     * Funkce schvaluje clanek
     * @param string $id ID clanku
     */
    public function acceptArticle($id, $textQuality, $graphicsQuality, $attachmentsQuality)
    {
        $query = $this->connection->prepare("UPDATE clanky SET SCHVALENO = 1 WHERE id=:id");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $query = $this->connection->prepare("INSERT INTO hodnoceni_clanku (id_clanku, text, grafika, prilohy) VALUES (:id, :text, :grafika, :prilohy)");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->bindParam(':text', $textQuality, PDO::PARAM_INT);
        $query->bindParam(':grafika', $graphicsQuality, PDO::PARAM_INT);
        $query->bindParam(':prilohy', $attachmentsQuality, PDO::PARAM_INT);
        $query->execute();

    }

    public function rejectArticle($id)
    {
        $query = $this->connection->prepare("DELETE FROM hodnoceni_clanku WHERE id_clanku=:id");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();


        $query = $this->connection->prepare("DELETE FROM clanky WHERE id=:id");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $articleDirectory = "clanky/{$id}";

        if (is_dir($articleDirectory)) {
            $this->deleteDirectory($articleDirectory);
        }
    }

    /**
     * Metoda, ktera smaze vsechny soubory uvnitr daneho adresare
     * @param string $dir Cesta ke slozce, kterou chceme vymazat
     * @return bool Vraci true, pokud se to podari, jinak false
     */
    private function deleteDirectory($dir): bool
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * This method checks if an email already exists in the 'uzivatele' table
     * @param string $email The email to check
     * @return bool Returns true if the email exists, false otherwise
     */
    public function isEmailUsed(string $email): bool
    {
        try {
            $query = $this->connection->prepare("SELECT COUNT(*) FROM uzivatele WHERE email = :email");

            $query->bindParam(':email', $email);

            $query->execute();

            $result = $query->fetchColumn();

            return $result > 0;
        } catch (PDOException $e) {
            error_log("Error checking email existence: " . $e->getMessage());
            return false;
        }
    }

    public function registerUser(string $firstName, string $lastName, string $email, string $password, string $group): bool
    {
        $approved = $group === "0";

        $query = $this->connection->prepare("INSERT INTO uzivatele (jmeno, prijmeni, email, heslo, role, potvrzeno) VALUES (:jmeno, :prijmeni, :email, :heslo, :role, :potvrzeno)");

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $query->bindParam(':jmeno', $firstName);
        $query->bindParam(':prijmeni', $lastName);
        $query->bindParam(':email', $email);
        $query->bindParam(':heslo', $hashedPassword);
        $query->bindParam(':role', $group, PDO::PARAM_INT);
        $query->bindParam(':potvrzeno', $approved, PDO::PARAM_BOOL);

        // Execute the query
        try {
            $query->execute();
            return true;
        }
        catch (PDOException $e) {
            return false;
        }
    }

    public function signIn(string $email, $password): bool
    {
        $query = $this->connection->prepare("SELECT heslo FROM uzivatele WHERE (email = :email)");
        $query->bindParam(':email', $email);

        try {
            if ($query->execute()) {
                $result = $query->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    $hashedPasswordFromDB = $result['heslo'];

                    if (password_verify($password, $hashedPasswordFromDB))
                        return true;
                }
            }
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }


        return false;
    }

    public function approveRegistrationRequest($id)
    {
        $query = $this->connection->prepare("UPDATE uzivatele SET potvrzeno = 1 WHERE id = :id");
        $query->bindParam(':id', $id);
        try
        {
            $query->execute();
            return true;
        }
        catch (PDOException $e)
        {
            return $e->getMessage();
        }
    }

    public function denyRegistrationRequest($id)
    {
        $query = $this->connection->prepare("UPDATE uzivatele SET potvrzeno = 1 WHERE id = :id");
        $query->bindParam(':id', $id);
        try
        {
            $query->execute();
        }
        catch (PDOException $e)
        {
            return $e->getMessage();
        }

        $query = $this->connection->prepare("UPDATE uzivatele SET role = 0 WHERE id = :id");
        $query->bindParam(':id', $id);
        try
        {
            $query->execute();
            return true;
        }
        catch (PDOException $e)
        {
            return $e->getMessage();
        }
    }

    public function removeUser($id)
    {
        $query = $this->connection->prepare("DELETE FROM uzivatele WHERE id = :id");
        $query->bindParam(':id', $id);
        try
        {
            if(!$query->execute())
                return "Žádný takový uživatel neexistuje.";
            return true;
        }
        catch (PDOException $e)
        {
            return $e->getMessage();
        }
    }

    public function getUserInformationWithID($id)
    {
        $query = $this->connection->prepare("SELECT role, potvrzeno FROM uzivatele WHERE (id = :id)");
        $query->bindParam(':id', $id);

        try {
            if ($query->execute()) {
                $result = $query->fetch(PDO::FETCH_ASSOC);
                if(!$result)
                    return "Žádný takový uživatel neexistuje.";

                return array(
                    "group" => $result["role"],
                    "approved" => $result["potvrzeno"]
                );
            }
        }
        catch (PDOException $e) {
            return $e->getMessage();
        }
        return null;
    }

    /**
     * Metoda ziskava roli uzivatele a priznakovy bit, jestli je role potvrzena
     * @param string $email
     * @return null | array Pokud se podari zjistit informace o uzivateli, metoda vraci pole. V opacnem pripade vraci null
     */
    public function getUserInformationWithEmail(string $email)
    {
        $query = $this->connection->prepare("SELECT role, potvrzeno FROM uzivatele WHERE (email = :email)");
        $query->bindParam(':email', $email);

        try {
            if ($query->execute()) {
                $result = $query->fetch(PDO::FETCH_ASSOC);
                return array(
                    "group" => $result["role"],
                    "approved" => $result["potvrzeno"]
                );
            }
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return null;
        }
        return null;
    }

    /**
     * Funkce zkusi publikovat clanek. Pokud se ji to podari, vrati id clanku, v opacnem pripade vrati false
     * @param string $articleName Nazev clanku
     * @param string $description Popis clanku
     * @param string $article Obsah clanku
     * @return int | false Vrati id clanku nebo false, pokud se clanek nepodari zverejnit.
     */
    public function publishArticle($articleName, $description, $article)
    {
        $query = $this->connection->prepare("INSERT INTO clanky (nazev, popis, clanek) VALUES (:articleName, :description, :article)");

        // Bind parametry
        $query->bindParam(":articleName", $articleName);
        $query->bindParam(":description", $description);
        $query->bindParam(":article", $article);

        // Pokus o provedení dotazu
        if ($query->execute())
            return $this->connection->lastInsertId();
        else
            return false;
    }

    /**
     * Metoda ziska cekajici registrace pro potvrzeni a tyto registrace muze odsouhlasit uzivatel s roli predanou jako parametr
     * @param int $myGroup Role uzivatele, ktery teoreticky bude moci schvalovat zadosti o registraci
     * @return array|null Null nebo cekajici registrace
     */
    public function getPendingRegistrations($myGroup) {
        $query = $this->connection->prepare("SELECT id, jmeno, prijmeni, email, role FROM uzivatele WHERE (role <= :myGroup) AND (potvrzeno = 0)");
        $query->bindParam(':myGroup', $myGroup);
        try {
            if ($query->execute())
                return array("pendingRegistrations" => $query->fetchAll(PDO::FETCH_ASSOC));
        }
        catch(PDOException $e) {
            echo $e->getMessage();
            return null;
        }
        return null;
    }

    /**
     * Metoda ziska vsechny uzivatele s nizsi uzivatelskou urovni nez predana hodnota, co jsou potvrzeny
     * @param int $myGroup Cislo uzivatelske skupiny
     * @return array|null Seznam uzivatelu s nizsi uzivatelskou skupinou nez myGroup, co maji skupinu potvrzenou
     */
    public function getAllUsers($myGroup) {
        $query = $this->connection->prepare("SELECT id, jmeno, prijmeni, email, role FROM uzivatele WHERE (role < :myGroup) AND (potvrzeno = 1)");
        $query->bindParam(':myGroup', $myGroup);
        try {
            if ($query->execute())
                return array("users" => $query->fetchAll(PDO::FETCH_ASSOC));
        }
        catch(PDOException $e) {
            echo $e->getMessage();
            return null;
        }
        return null;
    }
}