<?php


use kivweb\Controllers\IntroductionController;
use kivweb\Controllers\RegistrationResultController;
use kivweb\Controllers\SignupController;
use kivweb\Controllers\SigninController;
use kivweb\Controllers\UserManagement;
use kivweb\Controllers\UploadArticle;
use kivweb\Controllers\SignoutController;
use kivweb\Controllers\UploadResultController;
use kivweb\Controllers\ArticleController;
use kivweb\Controllers\ArticleManagementController;


const HOST = "localhost";
const DB_NAME = "konferencni_system";
const USERNAME = "root";
const PASSWORD = "";
const DEFAULT_WEB_PAGE = "uvod";

const WEB_PAGES = array(
    "uvod" => array(
        "title" => "Úvodní stránka",
        "controller_class_name" => IntroductionController::class,
    ),

    "registrace" => array(
        "title" => "Vytvoření účtu",
        "controller_class_name" => SignupController::class,
    ),

    "vysledek_registrace" => array(
        "title" => "Výsledek registrace",
        "controller_class_name" => RegistrationResultController::class
    ),

    "prihlaseni" => array(
        "title" => "Přihlásit se",
        "controller_class_name" => SigninController::class
    ),

    "sprava_uzivatelu" => array(
        "title" => "Správa uživatelů",
        "controller_class_name" => UserManagement::class
    ),

    "vlozeni_clanku" => array(
        "title" => "Vložení článku",
        "controller_class_name" => UploadArticle::class
    ),

    "odhlaseni" => array(
        "title" => "Odhlášení",
        "controller_class_name" => SignoutController::class
    ),

    "vysledek_nahrani" => array(
        "title" => "Zpracování nahraného článku",
        "controller_class_name" => UploadResultController::class
    ),

    "clanek" => array(
        "title" => "Článek",
        "controller_class_name" => ArticleController::class
    ),

    "sprava_clanku" => array(
        "title" => "Správa článků",
        "controller_class_name" => ArticleManagementController::class
    )

);
