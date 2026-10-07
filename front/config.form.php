<?php

use GlpiPlugin\Os\Config as OsConfig;

Session::checkLoginUser();
Session::checkRight('plugin_os', READ);

if (isset($_POST['update'])) {
    Session::checkRight('plugin_os', UPDATE);
    OsConfig::save($_POST);
    Session::addMessageAfterRedirect(__('Configuração salva.', 'os'));
    Html::back();
}

Html::header(__('Ordem de Serviço', 'os'), $_SERVER['PHP_SELF'], 'config', 'plugins');
OsConfig::showForm();
Html::footer();
