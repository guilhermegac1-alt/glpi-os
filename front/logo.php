<?php

use GlpiPlugin\Os\Config as OsConfig;

Session::checkLoginUser();
Session::checkRight('plugin_os', UPDATE);

if (isset($_POST['upload'])) {
    if (OsConfig::saveLogo($_FILES['logo'] ?? [])) {
        Session::addMessageAfterRedirect(__('Logotipo atualizado.', 'os'));
    } else {
        Session::addMessageAfterRedirect(
            __('Não foi possível usar essa imagem. Envie PNG, JPG ou GIF de até 2 MB.', 'os'),
            false,
            ERROR
        );
    }
} elseif (isset($_POST['delete'])) {
    OsConfig::deleteLogo();
    Session::addMessageAfterRedirect(__('Logotipo padrão restaurado.', 'os'));
}

Html::back();
