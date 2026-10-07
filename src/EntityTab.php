<?php

namespace GlpiPlugin\Os;

use CommonGLPI;
use Entity;
use Html;
use Session;

/**
 * Aba "Dados para O.S." na entidade (CNPJ do cliente).
 */
class EntityTab extends CommonGLPI
{
    public static $rightname = 'plugin_os';

    public static function getTypeName($nb = 0): string
    {
        return __('Dados para O.S.', 'os');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Entity && $item->getID() >= 0 && Session::haveRight(self::$rightname, READ)) {
            return self::createTabEntry(self::getTypeName(), 0, null, 'ti ti-file-invoice');
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Entity) {
            self::showForEntity($item);
        }
        return true;
    }

    public static function showForEntity(Entity $entity): void
    {
        global $CFG_GLPI;

        $id      = (int) $entity->getID();
        $canedit = Session::haveRight(self::$rightname, UPDATE) && $entity->canUpdateItem();
        $action  = $CFG_GLPI['root_doc'] . '/plugins/os/front/entity.form.php';

        echo "<div class='p-3'>";
        echo "<form method='post' action='" . htmlspecialchars($action, ENT_QUOTES) . "'>";
        echo "<div class='mb-3' style='max-width:320px'><label class='form-label' for='os_cnpj'>" . __('CNPJ', 'os') . "</label>";
        echo "<input type='text' class='form-control' id='os_cnpj' name='cnpj' maxlength='18' inputmode='numeric' value='"
            . htmlspecialchars(EntityData::getCnpj($id), ENT_QUOTES) . "'" . ($canedit ? '' : ' disabled') . ">";
        echo "</div>";
        echo Html::hidden('entities_id', ['value' => $id]);
        if ($canedit) {
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
        }
        Html::closeForm();
        echo "</div>";
    }
}
