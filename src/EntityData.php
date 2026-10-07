<?php

namespace GlpiPlugin\Os;

/**
 * CNPJ por entidade (tabela glpi_plugin_os_entities).
 */
class EntityData
{
    public static function getTable(): string
    {
        return 'glpi_plugin_os_entities';
    }

    public static function getCnpj(int $entities_id): string
    {
        global $DB;
        $row = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['entities_id' => $entities_id],
            'LIMIT' => 1,
        ])->current();
        return is_array($row) ? (string) $row['cnpj'] : '';
    }

    public static function isValidCnpj(string $cnpj): bool
    {
        return $cnpj === '' || preg_match('/^[0-9.\/\-]{1,18}$/', $cnpj) === 1;
    }

    public static function setCnpj(int $entities_id, string $cnpj): void
    {
        global $DB;
        $cnpj = trim($cnpj);
        $exists = countElementsInTable(self::getTable(), ['entities_id' => $entities_id]) > 0;
        if ($exists) {
            $DB->update(self::getTable(), ['cnpj' => $cnpj], ['entities_id' => $entities_id]);
        } else {
            $DB->insert(self::getTable(), ['entities_id' => $entities_id, 'cnpj' => $cnpj]);
        }
    }
}
