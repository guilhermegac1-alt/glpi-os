<?php

namespace GlpiPlugin\Os;

require_once dirname(__DIR__) . '/lib/fpdf/fpdf.php';

/**
 * Layout etiqueta (70 x 95 mm, retrato - mesmo formato do plugin original) da Ordem de Serviço.
 */
class LabelPdf extends \FPDF
{
    public static function render(array $d): string
    {
        $pdf = new self('P', 'mm', [95, 70]);
        $pdf->SetMargins(4, 3, 4);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetTitle(A4Pdf::enc('OS ' . $d['id']));
        $pdf->AddPage();

        $c    = $d['company'];
        $left = 4;
        $w    = 62; // largura útil (página de 70 mm com margens de 4 mm)

        if (is_readable($d['logo'])) {
            $pdf->Image($d['logo'], $left, 2, 28, 0, 'PNG');
        }
        $pdf->SetXY($left, 12);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($w, 5, A4Pdf::enc('OS Nº ' . $d['id']), 0, 1, 'L');

        $line = function (string $text, string $style = '', float $size = 7) use ($pdf, $left, $w): void {
            $pdf->SetX($left);
            $pdf->SetFont('Arial', $style, $size);
            $text = A4Pdf::enc($text);
            while ($text !== '' && $pdf->GetStringWidth($text) > $w - 1) {
                $text = substr($text, 0, -1);
            }
            $pdf->Cell($w, 3, $text, 0, 1, 'L');
        };
        $sep = fn() => $line(str_repeat('-', 52), '', 6);

        $line($c['name'], 'B', 8);
        if ($c['cnpj'] !== '') {
            $line('CNPJ: ' . $c['cnpj']);
        }
        if ($c['phone'] !== '') {
            $line('FONE: ' . $c['phone']);
        }
        $addr = trim($c['address'] . ($c['city'] !== '' ? ' - ' . $c['city'] : ''), ' -');
        if ($addr !== '') {
            $line($addr);
        }
        if ($d['technician'] !== '') {
            $line('RESPONSÁVEL: ' . $d['technician']);
        }
        $sep();
        $line('CLIENTE: ' . $d['entity']['name']);
        $line('SOLICITANTE: ' . $d['requester']);
        $line('DATA: ' . $d['date']);
        $sep();
        $line('ITENS', 'B');
        foreach (array_slice($d['items'], 0, 3) as $it) {
            $line(trim($it['name'] . ' - ' . $it['serial'], ' -'));
        }
        $sep();
        $line($c['site']);

        $qr = QrImage::toTempFile($d['url']);
        if ($qr) {
            $pdf->Image($qr, 21, 64, 28, 28, 'PNG');
            @unlink($qr);
        }
        return $pdf->Output('S');
    }
}
