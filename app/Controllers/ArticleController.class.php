<?php

namespace kivweb\Controllers;

use kivweb\Models\DatabaseConnection;
use kivweb\Others\MyLogin;

class ArticleController implements IController
{
    private DatabaseConnection $connection;
    private MyLogin $login;

    public function __construct(DatabaseConnection $connection, MyLogin $login)
    {
        $this->connection = $connection;
        $this->login = $login;
    }

    public function showView(): void
    {
        if(!isset($_GET["id"])) {
            $controller = new ResultController();
            $controller->showView(false, "Nebylo nastaveno id článku k zobrazení");
            return;
        }

        $article = $this->connection->getArticleDetails($_GET['id']);

        if(!is_array($article))
        {
            $controller = new ResultController();
            $controller->showView(false, $article);
            return;
        }

        if(empty($article))
        {
            $controller = new ResultController();
            $controller->showView(false, "Článek s předaným id neexistuje");
            return;
        }


        //Kontrola práv u neschváleného článku
        if($article['schvaleno'] == 0)
        {
            if(!$this->login->isUserLogged())
            {
                $controller = new ResultController();
                $controller->showView(false, "Nelze zobrazit zatím neschválený článek bez přihlášení a role recenzent nebo vyšší.");
                return;
            }

            $userInformation = $this->connection->getUserInformationWithEmail($this->login->getUserEmail());
            if(empty($userInformation))
            {
                $controller = new ResultController();
                $controller->showView(false, "Nelze ověřit zda-li máte dostatečná práva k zobrazení tohoto neschváleného článku.");
                return;
            }

            if($userInformation['group'] < 1 || $userInformation['approved'] == 0) {
                $controller = new ResultController();
                $controller->showView(false, "Nemáte dostatečná práva k zobrazení tohoto neschváleného článku.");
                return;
            }




            if(isset($_GET['akce'])) {

                if($_GET['akce'] == 'schvalit')
                {
                    if(!isset($_POST['kvalitaTextu']) || !is_numeric($_POST['kvalitaTextu'])) {
                        $controller = new ResultController();
                        $controller->showView(false, "Nebyla ohodnocena kvalita textu");
                        return;
                    }

                    if(!isset($_POST['kvalitaGrafiky']) || !is_numeric($_POST['kvalitaGrafiky'])) {
                        $controller = new ResultController();
                        $controller->showView(false, "Nebyla ohodnocena kvalita grafiky");
                        return;
                    }

                    if(!isset($_POST['kvalitaPriloh']) || !is_numeric($_POST['kvalitaPriloh'])) {
                        $controller = new ResultController();
                        $controller->showView(false, "Nebyla ohodnocena kvalita příloh");
                        return;
                    }


                    $this->connection->acceptArticle($_GET['id'], $_POST['kvalitaTextu'], $_POST['kvalitaGrafiky'], $_POST['kvalitaPriloh']);
                    $controller = new ResultController();
                    $controller->showView(true, "Článek byl schválen");
                    return;
                }

                if($_GET['akce'] == 'zamitnout') {
                    $this->connection->rejectArticle($_GET['id']);
                    $controller = new ResultController();
                    $controller->showView(false, "Článek byl zamítnut");
                    return;
                }
            }
        }


        $template = 'article_details.twig';

        $loader = new \Twig\Loader\FilesystemLoader('app/Templates');
        $twig = new \Twig\Environment($loader);

        echo $twig->render($template, $article);
    }
}