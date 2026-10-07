<?php

use GlpiPlugin\Os\EntityData;

Session::checkLoginUser();
Session::checkRight('plugin_os', UPDATE);

if (isset($_POST['update'])) {
    $entities_id = (int) ($_POST['entities_id'] ?? -1);
    $cnpj        = trim((string) ($_POST['cnpj'] ?? ''));

    $entity = new Entity();
    if ($entities_id < 0 || !$entity->getFromDB($entities_id) || !$entity->canUpdateItem()) {
        throw new \Glpi\Exception\Http\AccessDeniedHttpException();
    }

    if (!EntityData::isValidCnpj($cnpj)) {
        Session::addMessageAfterRedirect(__('CNPJ inválido: use apenas números, ponto, barra e hífen.', 'os'), false, ERROR);
    } else {
        EntityData::setCnpj($entities_id, $cnpj);
        Session::addMessageAfterRedirect(__('Dados salvos.', 'os'));
    }
}

Html::back();
