<?php

/**
 * Instalação / desinstalação do plugin OS (GLPI 11)
 */

use GlpiPlugin\Os\Config as OsConfig;
use GlpiPlugin\Os\EntityData;

function plugin_os_install(): bool
{
    /** @var DBmysql $DB */
    global $DB;

    $migration = new Migration(PLUGIN_OS_VERSION);

    $charset   = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $sign      = DBConnection::getDefaultPrimaryKeySignOption();

    // --- Tabela de dados por entidade (CNPJ) --------------------------------
    $table = EntityData::getTable();
    if (!$DB->tableExists($table)) {
        $DB->doQuery("CREATE TABLE `$table` (
            `id` int {$sign} NOT NULL AUTO_INCREMENT,
            `entities_id` int {$sign} NOT NULL DEFAULT '0',
            `cnpj` varchar(32) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            UNIQUE KEY `entities_id` (`entities_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC");
    }

    // --- Migração de instalações antigas (plugin v0.x) ----------------------
    if ($DB->tableExists('glpi_plugin_os_rn')) {
        foreach ($DB->request(['FROM' => 'glpi_plugin_os_rn']) as $row) {
            if (isset($row['entities_id'], $row['rn'])) {
                EntityData::setCnpj((int) $row['entities_id'], (string) $row['rn']);
            }
        }
        $migration->dropTable('glpi_plugin_os_rn');
    }

    $legacy_values = [];
    if ($DB->tableExists('glpi_plugin_os_config')) {
        $row = $DB->request(['FROM' => 'glpi_plugin_os_config', 'LIMIT' => 1])->current();
        if (is_array($row)) {
            foreach (array_keys(OsConfig::getDefaults()) as $key) {
                if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== '0') {
                    $legacy_values[$key] = (string) $row[$key];
                }
            }
        }
        $migration->dropTable('glpi_plugin_os_config');
    }
    if ($DB->tableExists('glpi_plugin_os_profiles')) {
        $migration->dropTable('glpi_plugin_os_profiles');
    }

    // --- Configuração (glpi_configs, contexto plugin:os) --------------------
    $current = Config::getConfigurationValues(OsConfig::CONTEXT);
    $missing = array_diff_key(OsConfig::getDefaults(), $current);
    if ($missing !== [] || $legacy_values !== []) {
        Config::setConfigurationValues(OsConfig::CONTEXT, array_merge($missing, $legacy_values));
    }

    $migration->executeMigration();

    // --- Direitos -----------------------------------------------------------
    ProfileRight::addProfileRights(['plugin_os']);

    // Quem pode ver chamados pode imprimir a O.S.; quem pode alterar a
    // configuração do GLPI também pode configurar o plugin.
    $profiles = [];
    foreach ($DB->request(['FROM' => 'glpi_profilerights', 'WHERE' => ['name' => ['ticket', 'config']]]) as $r) {
        $profiles[(int) $r['profiles_id']][$r['name']] = (int) $r['rights'];
    }
    foreach ($profiles as $profiles_id => $r) {
        $existing = countElementsInTable('glpi_profilerights', ['profiles_id' => $profiles_id, 'name' => 'plugin_os', 'rights' => ['>', 0]]);
        if ($existing > 0) {
            continue;
        }
        $rights = 0;
        if (($r['ticket'] ?? 0) & READ) {
            $rights |= READ;
        }
        if (($r['config'] ?? 0) & UPDATE) {
            $rights |= READ | UPDATE;
        }
        if ($rights > 0) {
            ProfileRight::updateProfileRights($profiles_id, ['plugin_os' => $rights]);
        }
    }

    return true;
}

function plugin_os_uninstall(): bool
{
    /** @var DBmysql $DB */
    global $DB;

    $migration = new Migration(PLUGIN_OS_VERSION);
    foreach ([EntityData::getTable(), 'glpi_plugin_os_rn', 'glpi_plugin_os_config', 'glpi_plugin_os_profiles'] as $table) {
        $migration->dropTable($table);
    }
    $migration->executeMigration();

    Config::deleteConfigurationValues(OsConfig::CONTEXT, array_keys(OsConfig::getDefaults()));
    ProfileRight::deleteProfileRights(['plugin_os']);

    $logo = OsConfig::getCustomLogoPath();
    if (is_file($logo)) {
        @unlink($logo);
        @rmdir(dirname($logo));
    }

    return true;
}
