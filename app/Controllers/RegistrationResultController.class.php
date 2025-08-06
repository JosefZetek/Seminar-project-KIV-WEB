<?php

namespace kivweb\Controllers;
use kivweb\Models\DatabaseConnection;
use kivweb\Others\MyLogin;

class RegistrationResultController implements IController
{
    private DatabaseConnection $connection;
    private MyLogin $login;

    public function __construct(DatabaseConnection $connection, MyLogin $login)
    {
        $this->connection = $connection;
        $this->login = $login;
    }

    private function check_input_validity(string $firstName, string $lastName, string $password, string $passwordRepeated, string $email): bool {


        if($firstName == "" || $lastName == "" || $password != $passwordRepeated || strlen($password) < 8) {
            return false;
        }

        return !$this->connection->isEmailUsed($email);
    }
    public function showView(): void
    {
        $firstName = $_POST["firstName"];
        $lastName = $_POST["lastName"];
        $password = $_POST["password"];
        $passwordRepeated = $_POST["passwordRepeated"];
        $email = $_POST["email"];
        $group = $_POST["selectedGroup"];

        $template = 'result_view.twig';

        $loader = new \Twig\Loader\FilesystemLoader('app/Templates');
        $twig = new \Twig\Environment($loader);

        $result = $this->check_input_validity($firstName, $lastName, $password, $passwordRepeated, $email) &&
            $this->connection->registerUser($firstName, $lastName, $email, $password, $group);

        $message = "Váš požadavek na registraci " . ($result ? 'byl úspěšně zpracován.' : 'se nepodařilo zpracovat.');

        $atributes = array(
            "result" => $result,
            "message" => $message
        );

        echo $twig->render($template, $atributes);
    }
}