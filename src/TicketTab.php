<?php

namespace GlpiPlugin\Os;

use CommonGLPI;
use Session;
use Ticket;

/**
 * Aba "Ordem de Serviço" dentro do chamado.
 */
class TicketTab extends CommonGLPI
{
    public static $rightname = 'plugin_os';

    public static function getTypeName($nb = 0): string
    {
        return __('Ordem de Serviço', 'os');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Ticket && $item->getID() > 0 && Session::haveRight(self::$rightname, READ)) {
            return self::createTabEntry(self::getTypeName(), 0, null, 'ti ti-file-invoice');
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Ticket) {
            self::showForTicket($item);
        }
        return true;
    }

    public static function showForTicket(Ticket $ticket): void
    {
        global $CFG_GLPI;

        $id   = (int) $ticket->getID();
        $base = $CFG_GLPI['root_doc'] . '/plugins/os/front/pdf.php?id=' . $id;
        $a4   = htmlspecialchars($base . '&layout=a4', ENT_QUOTES);
        $lbl  = htmlspecialchars($base . '&layout=label', ENT_QUOTES);

        echo "<div class='p-2'>";
        echo "<div class='mb-2'>";
        echo "<span class='me-2'>" . __('Layout', 'os') . ":</span>";
        echo "<a class='btn btn-sm btn-primary me-1' href='$a4' target='os_iframe_$id'>A4</a>";
        echo "<a class='btn btn-sm btn-outline-primary me-3' href='$lbl' target='os_iframe_$id'>" . __('Etiqueta', 'os') . "</a>";
        echo "<a class='btn btn-sm btn-outline-secondary' href='$a4' target='_blank' rel='noopener'>"
            . "<i class='ti ti-external-link'></i> " . __('Abrir em nova aba', 'os') . "</a>";
        echo "</div>";
        echo "<iframe name='os_iframe_$id' src='$a4' style='width:100%;height:75vh;border:1px solid #ccc' title='O.S.'></iframe>";
        echo "</div>";
    }
}
