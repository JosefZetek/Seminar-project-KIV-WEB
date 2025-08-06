<?php

namespace kivweb;

use kivweb\Controllers\IController;
use kivweb\Controllers\ResultController;
use kivweb\Controllers\SigninController;
use kivweb\Controllers\UserManagement;
use kivweb\Models\DatabaseConnection;
use kivweb\Others\MyLogin;

/**
 * Vstupni bod webove aplikace.
 */
class ApplicationStart {

    private MyLogin $login;

    public function __construct()
    {
        $this->login = new MyLogin();
    }

    private function documentStart() {
        echo "<!DOCTYPE html>";
        echo '<html lang="cs">';
    }

    private function documentEnd() {
        echo "</html>";
    }

    private function showHeader(string $title) {
        $template = 'header.twig';

        $loader = new \Twig\Loader\FilesystemLoader('app/Templates');
        $twig = new \Twig\Environment($loader);

        $arguments = array("title" => $title);
        echo $twig->render($template, $arguments);
    }

    private function showNavigationBar($userEmail, $userGroup) {
        $template = 'navigation_bar.twig';

        $loader = new \Twig\Loader\FilesystemLoader('app/Templates');
        $twig = new \Twig\Environment($loader);

        $arguments = array();
        if(empty($userEmail)) {
            $arguments = array(
                "role" => $userGroup
            );
        }
        else {
            $arguments = array(
                "email" => $userEmail,
                "role" => $userGroup
            );
        }

        echo $twig->render($template, $arguments);
    }

    private function pageContentStart() {
        echo "<body>";
    }

    private function pageContentEnd() {
        echo "</body>";
    }

    private function getPageKey(): string {
        if(isset($_GET["page"]) && array_key_exists($_GET["page"], WEB_PAGES))
            return $_GET["page"];

        return DEFAULT_WEB_PAGE;
    }

    private function checkUserManagement($connection, $pageKey)
    {
        if($pageKey != "sprava_uzivatelu")
            return $pageKey;

        if(!$this->login->isUserLogged())
            return DEFAULT_WEB_PAGE;

        $userInformation = $connection->getUserInformationWithEmail($this->login->getUserEmail());

        if(!isset($userInformation) || $userInformation['group'] == 0 || $userInformation['approved'] == 0) {
            $pageKey = DEFAULT_WEB_PAGE;
        }

        return $pageKey;
    }

    private function getUserGroup($connection) {

        //Pokud neni uzivatel
        if(!$this->login->isUserLogged())
            return -1;

        $userInformation = $connection->getUserInformationWithEmail($this->login->getUserEmail());

        if(!isset($userInformation) || $userInformation['group'] == 0 || $userInformation['approved'] == 0)
            return 0;

        return $userInformation['group'];
    }


    /**
     * Spusteni webove aplikace.
     */
    public function appStart(): void {

        $connection = new DatabaseConnection(HOST, DB_NAME, USERNAME, PASSWORD);

        //Pokud přišel email a heslo
        if(isset($_POST["email"]) && $_POST["email"] != "" && isset($_POST["password"]) && $_POST["password"] != "") {
            //Pokud je mozne uzivatele prihlasit
            if($connection->signIn($_POST["email"], $_POST["password"])) {
                $this->login->logout();
                $this->login->login($_POST["email"]);
            }
        }

        $pageKey = $this->getPageKey();

        $pageKey = $this->checkUserManagement($connection, $pageKey);

        $controller_class = WEB_PAGES[$pageKey]["controller_class_name"];

        $this->documentStart();
        $this->showHeader(WEB_PAGES[$pageKey]["title"]);
        $this->pageContentStart();


        $this->showNavigationBar(
            $this->login->isUserLogged() ? $this->login->getUserEmail() : null,
            $this->getUserGroup($connection)
        );

        $a = new $controller_class($connection, $this->login);
        $a->showView();

        $this->pageContentEnd();
        $this->documentEnd();


    }
}

?>