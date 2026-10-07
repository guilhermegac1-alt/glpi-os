<?php

/**
 * Plugin OS (Ordem de Serviço) - port para GLPI 11
 *
 * Baseado no plugin original de Junior Marcati (https://github.com/juniormarcati/os)
 * Licença: GPLv3+
 */

use GlpiPlugin\Os\EntityTab;
use GlpiPlugin\Os\RightsTab;
use GlpiPlugin\Os\TicketTab;

define('PLUGIN_OS_VERSION', '1.0.0');
define('PLUGIN_OS_MIN_GLPI', '11.0.0');
define('PLUGIN_OS_MAX_GLPI', '11.1.0'); // exclusivo

/**
 * Inicialização do plugin
 */
function plugin_init_os(): void
{
    global $PLUGIN_HOOKS;

    $plugin = new Plugin();
    if (!$plugin->isActivated('os')) {
        return;
    }

    // Abas em Chamados, Entidades e Perfis
    Plugin::registerClass(TicketTab::class, ['addtabon' => ['Ticket']]);
    Plugin::registerClass(EntityTab::class, ['addtabon' => ['Entity']]);
    Plugin::registerClass(RightsTab::class, ['addtabon' => ['Profile']]);

    // Página de configuração (Configurar > Plugins > OS)
    if (Session::haveRight('plugin_os', READ)) {
        $PLUGIN_HOOKS['config_page']['os'] = 'front/config.form.php';
    }
}

/**
 * Metadados do plugin
 */
function plugin_version_os(): array
{
    return [
        'name'         => 'OS - Ordem de Serviço',
        'version'      => PLUGIN_OS_VERSION,
        'author'       => 'Junior Marcati (port para GLPI 11)',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://github.com/guilhermegac1-alt/glpi-os',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_OS_MIN_GLPI,
                'max' => PLUGIN_OS_MAX_GLPI,
            ],
            'php'  => [
                'min' => '8.2',
                'exts' => [
                    'gd'       => ['required' => true],
                    'mbstring' => ['required' => true],
                ],
            ],
        ],
    ];
}
