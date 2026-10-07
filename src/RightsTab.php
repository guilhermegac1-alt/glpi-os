<?php

namespace GlpiPlugin\Os;

use CommonGLPI;
use Html;
use Profile;
use Session;

/**
 * Aba "OS" no perfil: define quem pode ver/configurar o plugin.
 */
class RightsTab extends CommonGLPI
{
    public static $rightname = 'profile';

    public static function getTypeName($nb = 0): string
    {
        return __('Ordem de Serviço', 'os');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Profile && $item->getField('interface') === 'central') {
            return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Profile) {
            self::showForProfile((int) $item->getID());
        }
        return true;
    }

    public static function showForProfile(int $profiles_id): void
    {
        $profile = new Profile();
        $profile->getFromDB($profiles_id);
        $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);

        echo "<div class='firstbloc'>";
        if ($canedit) {
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }
        $profile->displayRightsChoiceMatrix([
            [
                'itemtype' => Config::class,
                'label'    => __('Ordem de Serviço', 'os'),
                'field'    => 'plugin_os',
                'rights'   => [
                    READ   => __('Ver / imprimir O.S.', 'os'),
                    UPDATE => __('Configurar plugin', 'os'),
                ],
            ],
        ], [
            'canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => __('Ordem de Serviço', 'os'),
        ]);
        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }
}
