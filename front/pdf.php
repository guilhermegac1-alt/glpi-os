<?php

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Os\A4Pdf;
use GlpiPlugin\Os\LabelPdf;
use GlpiPlugin\Os\OsData;
use Symfony\Component\HttpFoundation\Response;

Session::checkLoginUser();
Session::checkRight('plugin_os', READ);

$id     = (int) ($_GET['id'] ?? 0);
$ticket = new Ticket();
if ($id <= 0 || !$ticket->getFromDB($id)) {
    throw new NotFoundHttpException();
}
if (!$ticket->canViewItem()) {
    throw new AccessDeniedHttpException();
}

$data   = OsData::fromTicket($ticket);
$layout = ($_GET['layout'] ?? 'a4') === 'label' ? 'label' : 'a4';
$binary = $layout === 'label' ? LabelPdf::render($data) : A4Pdf::render($data);

return new Response($binary, 200, [
    'Content-Type'           => 'application/pdf',
    'Content-Disposition'    => 'inline; filename="OS-' . $id . '.pdf"',
    'X-Content-Type-Options' => 'nosniff',
    'Cache-Control'          => 'private, no-store',
]);
