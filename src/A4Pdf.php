<?php

namespace GlpiPlugin\Os;

require_once dirname(__DIR__) . '/lib/fpdf/fpdf.php';

/**
 * Layout A4 da Ordem de Serviço.
 */
class A4Pdf extends \FPDF
{
    private array $d;
    private ?string $qr;

    public function __construct(array $data)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->d  = $data;
        $this->qr = QrImage::toTempFile($data['url']);
        $this->SetMargins(10, 10, 10);
        $this->SetAutoPageBreak(true, 18);
        $this->SetTitle(self::enc('OS ' . $data['id']));
        $this->AliasNbPages();
    }

    public static function enc(string $s): string
    {
        return mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    }

    public static function render(array $data): string
    {
        $pdf = new self($data);
        $pdf->build();
        $out = $pdf->Output('S');
        if ($pdf->qr && is_file($pdf->qr)) {
            @unlink($pdf->qr);
        }
        return $out;
    }

    // ---- Cabeçalho / rodapé --------------------------------------------------

    public function Header(): void
    {
        $c = $this->d['company'];
        if (is_readable($this->d['logo'])) {
            $this->Image($this->d['logo'], 10, 10, 42, 0, 'PNG');
        }
        $this->SetTextColor(0, 0, 0);
        $this->SetXY(55, 10);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(90, 5, self::enc($c['name']), 0, 0, 'C');
        $this->SetXY(55, 16);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(90, 4, self::enc($c['phone']), 0, 0, 'C');
        $this->SetXY(55, 20);
        $this->SetFont('Arial', '', 9);
        $this->Cell(90, 4, self::enc($c['cnpj'] !== '' ? 'CNPJ: ' . $c['cnpj'] : ''), 0, 0, 'C');
        $this->SetXY(55, 24);
        $addr = trim($c['address'] . ($c['city'] !== '' ? ' - ' . $c['city'] : ''), ' -');
        $this->Cell(90, 4, self::enc($addr), 0, 0, 'C');
        $this->SetXY(55, 28);
        $this->SetFont('Arial', '', 8);
        $this->Cell(90, 4, self::enc($c['site']), 0, 0, 'C');

        // Número e data da O.S.
        $this->SetXY(146, 16);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(32, 4, self::enc('OS Nº'), 0, 0, 'C');
        $this->SetXY(146, 20);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(220, 0, 0);
        $this->Cell(32, 5, (string) $this->d['id'], 0, 0, 'C');
        $this->SetTextColor(0, 0, 0);
        $this->SetXY(146, 26);
        $this->SetFont('Arial', '', 9);
        $this->Cell(32, 4, $this->d['date'], 0, 0, 'C');

        if ($this->qr && is_file($this->qr)) {
            $this->Image($this->qr, 180, 10, 22, 22, 'PNG');
        }
        $this->SetY(38);
    }

    public function Footer(): void
    {
        $this->SetY(-14);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, self::enc('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // ---- Conteúdo -----------------------------------------------------------

    private function section(string $title): void
    {
        if ($this->GetY() > 255) {
            $this->AddPage();
        }
        $this->SetDrawColor(75, 75, 85);
        $this->SetFillColor(55, 55, 64);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(190, 5, self::enc($title), 1, 1, 'C', true);
    }

    /** Linha de campos: [[rótulo, valor, largura_rótulo, largura_valor], ...] */
    private function row(array $cells): void
    {
        $lines = 1;
        $this->SetFont('Arial', '', 8);
        foreach ($cells as [$label, $value, $lw, $vw]) {
            $lines = max($lines, $this->nbLines($vw, self::enc($value)));
        }
        $h = 4 * $lines;
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage();
        }
        $x = $this->GetX();
        $y = $this->GetY();
        foreach ($cells as [$label, $value, $lw, $vw]) {
            $this->SetXY($x, $y);
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(255, 255, 255);
            $this->SetFillColor(55, 55, 64);
            $this->Cell($lw, $h, self::enc($label), 1, 0, 'L', true);
            $this->SetTextColor(0, 0, 0);
            $this->Rect($x + $lw, $y, $vw, $h);
            $this->SetXY($x + $lw, $y);
            $this->MultiCell($vw, 4, self::enc($value), 0, 'L');
            $x += $lw + $vw;
        }
        $this->SetXY(10, $y + $h);
    }

    private function nbLines(float $w, string $txt): int
    {
        $cw   = $this->CurrentFont['cw'];
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s    = str_replace("\r", '', $txt);
        $nb   = strlen($s);
        if ($nb > 0 && $s[$nb - 1] === "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c === ' ') {
                $sep = $i;
            }
            $l += $cw[$c] ?? 500;
            if ($l > $wmax) {
                if ($sep === -1) {
                    if ($i === $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    private function build(): void
    {
        $d = $this->d;
        $e = $d['entity'];
        $this->AddPage();

        $this->section('Dados do cliente');
        $this->row([['Cliente', $e['name'], 23, 167]]);
        $this->row([['Solicitante', $d['requester'], 23, 72], ['Telefone', $e['phone'], 20, 75]]);
        $this->row([['Endereço', $e['address'], 23, 72], ['CEP', $e['postcode'], 20, 75]]);
        $this->row([['E-mail', $e['email'], 23, 72], ['CNPJ', $e['cnpj'], 20, 75]]);

        $this->section('Detalhes do chamado');
        $this->row([['Título', $d['title'], 23, 167]]);
        $this->row([['Técnico', $d['technician'], 23, 167]]);
        $this->row([['Início', $d['date_time'], 23, 72], ['Fechamento', $d['solve_time'], 20, 75]]);

        if ($d['items'] !== []) {
            $this->section('Itens vinculados');
            foreach ($d['items'] as $it) {
                $this->row([['Nome', $it['name'] . ($it['type'] !== '' ? ' (' . $it['type'] . ')' : ''), 23, 72],
                            ['Nº de série', $it['serial'], 20, 75]]);
            }
        }

        if ($d['cost'] > 0) {
            $this->section('Custo');
            $this->row([['Custo total', number_format($d['cost'], 2, ',', '.'), 23, 167]]);
        }

        $this->section('Descrição');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0);
        $this->MultiCell(190, 5, self::enc($d['description']), 1, 'J');

        $this->section('Solução');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0);
        if ($d['solution'] === '') {
            if ($this->GetY() > 215) {
                $this->AddPage();
            }
            $y = $this->GetY();
            $this->Rect(10, $y, 190, 45);
            $this->SetXY(11, $y + 1);
            $this->Cell(100, 5, self::enc('Descreva a solução:'), 0, 0, 'L');
            $this->SetXY(10, $y + 45);
        } else {
            $this->MultiCell(190, 5, self::enc($d['solution']), 1, 'J');
        }

        if ($this->GetY() > 225) {
            $this->AddPage();
        }
        $this->section('Assinaturas');
        $y = $this->GetY();
        $this->Rect(10, $y, 190, 40);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 0, 0);
        $line = '_______________________________________';
        $this->SetXY(10, $y + 20);
        $this->Cell(95, 4, $line, 0, 0, 'C');
        $this->Cell(95, 4, $line, 0, 1, 'C');
        $this->SetX(10);
        $this->Cell(95, 4, self::enc($d['technician']), 0, 0, 'C');
        $this->Cell(95, 4, self::enc($d['requester']), 0, 1, 'C');
        $this->SetX(10);
        $this->Cell(95, 4, self::enc($d['company']['name']), 0, 0, 'C');
        $this->Cell(95, 4, self::enc($d['entity']['name']), 0, 1, 'C');
    }
}
