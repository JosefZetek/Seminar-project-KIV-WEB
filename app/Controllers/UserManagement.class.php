<?php

namespace kivweb\Controllers;

use Couchbase\Result;
use kivweb\Models\DatabaseConnection;
use kivweb\Others\MyLogin;

class UserManagement implements IController
{

    private DatabaseConnection $connection;
    private MyLogin $login;

    public function __construct(DatabaseConnection $connection, MyLogin $login)
    {
        $this->connection = $connection;
        $this->login = $login;
    }

    /**
     * Funkce kontroluje, jestli je nedoslo k chybe pri moznem zpracovani akce schvalit.
     * @param string $myGroup Skupina uzivatele
     * @return bool|string Vraci true pokud vse probehlo v poradku, jinak vraci chybovou hlasku
     */
    private function handlerApprove($myGroup) {

        //Pokud neprisel pozadavek na akci typu schvalit
        if(!isset($_GET['akce']) || $_GET['akce'] != "schvalit")
            return true;

        //Pokud prisel pozadavek, ale nebylo poskytnuto id
        if(!isset($_GET['id']) || $_GET['id'] == "")
            return "Nebylo poskytnuto ID uživatele, kterému chcete schválit roli";

        $approvedUsersInfo = $this->connection->getUserInformationWithID($_GET['id']);

        if(!isset($approvedUsersInfo['approved']) || !isset($approvedUsersInfo['group']))
            return "Pokus o schválení registrace uživatele s id " . $myGroup . " se nezdařil. " . $approvedUsersInfo;

        //Uzivatel uz ma schvalenou roli
        if($approvedUsersInfo['approved'])
            return true;

        //Schválení role uživatele, který má vyšší roli
        if($approvedUsersInfo['group'] > $myGroup)
            return "Nemůžete schválit žádost uživatele na skupinu s vyššími právy";

        return $this->connection->approveRegistrationRequest($_GET['id']);
    }

    /**
     * Funkce kontroluje, jestli je nedoslo k chybe pri moznem zpracovani akce zamitnout.
     * @param string $myGroup Skupina uzivatele
     * @return bool|string Vraci true pokud vse probehlo v poradku, jinak vraci chybovou hlasku
     */
    private function handlerDeny($myGroup) {
        //Pokud neprisel pozadavek na akci typu zamitnout
        if(!isset($_GET['akce']) || $_GET['akce'] != "zamitnout")
            return true;

        //Pokud prisel pozadavek, ale nebylo poskytnuto id
        if(!isset($_GET['id']))
            return "Nebylo poskytnuto ID uživatele, kterému chcete zamítnout roli";

        $deniedUsersInfo = $this->connection->getUserInformationWithID($_GET['id']);

        if(!isset($deniedUsersInfo['approved']) || !isset($deniedUsersInfo['group']))
            return "Pokus o zamítnutí registrace uživatele s id " . $myGroup . " se nezdařil. " . $deniedUsersInfo;

        //Uzivatel uz ma schvalenou roli
        if($deniedUsersInfo['approved'])
            return "Uživatel má již schválenou roli.";

        //Zamítnutí role uživatele, který má vyšší roli
        if($deniedUsersInfo['group'] > $myGroup)
            return "Nemůžete zamítnout žádost uživatele na skupinu s vyššími právy";

        return $this->connection->denyRegistrationRequest($_GET['id']);
    }

    private function handlerRemove($myGroup)
    {
        //Pokud neprisel pozadavek na akci typu smazat
        if (!isset($_GET['akce']) || $_GET['akce'] != "smazat")
            return true;

        //Pokud prisel pozadavek, ale nebylo poskytnuto id
        if (!isset($_GET['id']))
            return "Nebylo poskytnuto ID uživatele, kterému chcete smazat účet";

        $deletedUsersInfo = $this->connection->getUserInformationWithID($_GET['id']);

        if(!isset($deletedUsersInfo['approved']) || !isset($deletedUsersInfo['group']))
            return "Pokus o smazání uživatele s id " . $myGroup . " se nezdařil. " . $deletedUsersInfo;

        //Zamítnutí role uživatele, který má vyšší roli
        if ($deletedUsersInfo['group'] > $myGroup)
            return "Nemůžete odstranit uživatele s vyššími právy";

        return $this->connection->removeUser($_GET['id']);
    }

    private function showError($message)
    {
        $controller = new ResultController();
        $controller->showView(false, $message);
    }

    public function showView(): void {
        $loader = new \Twig\Loader\FilesystemLoader('app/Templates');
        $twig = new \Twig\Environment($loader);

        $userEmail = $this->login->getUserEmail();
        $userInformation = $this->connection->getUserInformationWithEmail($userEmail);

        if(!isset($userInformation)) {
            $this->showError("Nepodařilo se načíst skupinu aktuálně přihlášeného uživatele.");
        }

        $handlerResult = $this->handlerApprove($userInformation['group']);
        if(!($handlerResult === true)) {
            $this->showError($handlerResult);
            return;
        }

        $handlerResult = $this->handlerDeny($userInformation['group']);
        if(!($handlerResult === true)) {
            $this->showError($handlerResult);
            return;
        }

        $handlerResult = $this->handlerRemove($userInformation['group']);
        if(!($handlerResult === true)) {
            $this->showError($handlerResult);
            return;
        }

        //Vypsání obsahu stránky
        $template = 'users_table.twig';
        $myGroup = $userInformation["group"];

        //Ziskani skupiny uzivatele
        $registrations = $this->connection->getPendingRegistrations($myGroup);

        //Ziskani vsech uzivatelu s nizsimi pravy a odsouhlasenou roli
        $users = $this->connection->getAllUsers($myGroup);

        echo $twig->render($template, $registrations + $users);
    }
}