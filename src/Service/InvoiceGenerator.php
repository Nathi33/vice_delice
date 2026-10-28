<?php

namespace App\Service;

use App\Entity\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class InvoiceGenerator
{
    public function __construct(
        private Environment $twig,
        private array $invoiceSeller,
    ) {
    }

    public function generate(Order $order): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);

        $html = $this->twig->render('invoice/pdf.html.twig', [
            'order' => $order,
            'seller' => $this->invoiceSeller,
        ]);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
