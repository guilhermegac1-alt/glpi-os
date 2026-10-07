<?php

namespace GlpiPlugin\Os;

use CommonITILActor;
use Glpi\RichText\RichText;
use ITILSolution;
use Ticket;
use User;

/**
 * Reúne os dados do chamado necessários para montar a O.S.
 * Todas as consultas usam $DB->request() / classes do GLPI (sem SQL concatenado).
 */
class OsData
{
    /** Converte HTML/valor do GLPI em texto puro UTF-8 */
    public static function plain(?string $value, bool $rich = false): string
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }
        if ($rich) {
            try {
                $value = RichText::getTextFromHtml($value, true, false, false, false, true);
            } catch (\Throwable) {
                $value = strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace("/[ \t]+/", ' ', str_replace("\r", '', $value)));
    }

    private static function fmt(?string $date, bool $time = false): string
    {
        if (empty($date)) {
            return '';
        }
        $ts = strtotime($date);
        return $ts ? date($time ? 'd/m/Y H:i' : 'd/m/Y', $ts) : '';
    }

    private static function userName(int $users_id): string
    {
        $user = new User();
        if ($users_id > 0 && $user->getFromDB($users_id)) {
            return trim(($user->fields['firstname'] ?? '') . ' ' . ($user->fields['realname'] ?? ''))
                ?: (string) $user->fields['name'];
        }
        return '';
    }

    /** @return string[] nomes dos usuários do chamado para o tipo de ator informado */
    private static function actorNames(Ticket $ticket, int $type): array
    {
        $names = [];
        foreach ($ticket->getUsers($type) as $row) {
            $name = self::userName((int) $row['users_id']);
            if ($name === '' && !empty($row['alternative_email'])) {
                $name = (string) $row['alternative_email'];
            }
            if ($name !== '') {
                $names[] = $name;
            }
        }
        return $names;
    }

    public static function fromTicket(Ticket $ticket): array
    {
        global $DB, $CFG_GLPI;

        $id     = (int) $ticket->getID();
        $fields = $ticket->fields;

        // Entidade
        $entity = new \Entity();
        $entity->getFromDB((int) $fields['entities_id']);
        $entity_data = [
            'name'     => self::plain($entity->fields['completename'] ?? $entity->fields['name'] ?? ''),
            'address'  => self::plain($entity->fields['address'] ?? ''),
            'postcode' => self::plain($entity->fields['postcode'] ?? ''),
            'email'    => self::plain($entity->fields['email'] ?? ''),
            'phone'    => self::plain($entity->fields['phonenumber'] ?? ''),
            'cnpj'     => EntityData::getCnpj((int) $fields['entities_id']),
        ];
        // Mostra só o nome final da entidade ("Raiz > Cliente" -> "Cliente")
        if (str_contains($entity_data['name'], ' > ')) {
            $parts = explode(' > ', $entity_data['name']);
            $entity_data['name'] = end($parts);
        }

        // Itens vinculados (qualquer tipo de ativo)
        $items = [];
        foreach ($DB->request(['FROM' => 'glpi_items_tickets', 'WHERE' => ['tickets_id' => $id]]) as $row) {
            $item = getItemForItemtype($row['itemtype']);
            if ($item && $item->getFromDB((int) $row['items_id'])) {
                $items[] = [
                    'type'   => self::plain($item::getTypeName(1)),
                    'name'   => self::plain($item->fields['name'] ?? ''),
                    'serial' => self::plain($item->fields['serial'] ?? ''),
                ];
            }
        }

        // Custos
        $cost = 0.0;
        foreach ($DB->request(['FROM' => 'glpi_ticketcosts', 'WHERE' => ['tickets_id' => $id]]) as $row) {
            $cost += (float) $row['cost_material'] + (float) $row['cost_fixed']
                + (float) $row['cost_time'] * ((int) $row['actiontime']) / 3600;
        }

        // Solução (aceita ou aguardando aprovação)
        $solution = '';
        $sol = $DB->request([
            'FROM'  => ITILSolution::getTable(),
            'WHERE' => ['itemtype' => 'Ticket', 'items_id' => $id, 'status' => [2, 3]],
            'ORDER' => 'date_creation DESC',
            'LIMIT' => 1,
        ])->current();
        if (is_array($sol)) {
            $solution = self::plain($sol['content'], true);
        }

        $config = Config::getValues();
        foreach ($config as $k => $v) {
            $config[$k] = self::plain($v);
        }

        $requesters = self::actorNames($ticket, CommonITILActor::REQUESTER);

        return [
            'id'          => $id,
            'title'       => self::plain($fields['name'] ?? ''),
            'description' => self::plain($fields['content'] ?? '', true),
            'solution'    => $solution,
            'date'        => self::fmt($fields['date'] ?? null),
            'date_time'   => self::fmt($fields['date'] ?? null, true),
            'solve_time'  => self::fmt($fields['solvedate'] ?? null, true),
            'requester'   => implode(', ', $requesters),
            'technician'  => implode(', ', self::actorNames($ticket, CommonITILActor::ASSIGN)),
            'entity'      => $entity_data,
            'items'       => $items,
            'cost'        => $cost,
            'company'     => $config,
            'logo'        => Config::getLogoPath(),
            'url'         => Ticket::getFormURLWithID($id, true),
        ];
    }
}
