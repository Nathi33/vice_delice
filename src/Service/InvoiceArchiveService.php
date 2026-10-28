<?php

namespace App\Service;

use App\Entity\Order;

class InvoiceArchiveService
{
    public function __construct(
        private InvoiceGenerator $invoiceGenerator,
        private InvoiceNumberGenerator $invoiceNumberGenerator,
        private string $projectDir,
    ) {
    }

    public function archive(Order $order): string
    {
        // Conserve le numéro déjà attribué, y compris après un échec PDF.
        $invoiceNumber = $order->getInvoiceNumber();

        if ($invoiceNumber === null) {
            $invoiceNumber = $this->invoiceNumberGenerator->generate($order);
        }

        // Sécurise le nom de fichier.
        if (!preg_match('/^VD-\d{4}-\d{6}$/', $invoiceNumber)) {
            throw new \RuntimeException(
                'Le numéro de facture ne respecte pas le format attendu.'
            );
        }

        $directory = $this->projectDir . '/var/invoices';

        if (
            !is_dir($directory)
            && !mkdir($directory, 0775, true)
            && !is_dir($directory)
        ) {
            throw new \RuntimeException(
                'Impossible de créer le dossier des factures.'
            );
        }

        $filePath = $directory . '/' . $invoiceNumber . '.pdf';

        // Un PDF existant et valide est conservé : l'opération est idempotente.
        if (file_exists($filePath)) {
            if ($this->isValidPdf($filePath)) {
                return $filePath;
            }

            throw new \RuntimeException(
                sprintf(
                    'Un fichier existe déjà pour %s, mais il ne semble pas être un PDF valide. Il n’a pas été remplacé.',
                    $invoiceNumber
                )
            );
        }

        $pdfContent = $this->invoiceGenerator->generate($order);

        if (!str_starts_with($pdfContent, '%PDF-')) {
            throw new \RuntimeException(
                'Le générateur n’a pas produit un contenu PDF valide.'
            );
        }

        // Le mode "x" crée le fichier uniquement s'il n'existe pas.
        $handle = @fopen($filePath, 'xb');

        if ($handle === false) {
            // Un autre traitement a peut-être créé le fichier entre-temps.
            if (file_exists($filePath) && $this->isValidPdf($filePath)) {
                return $filePath;
            }

            throw new \RuntimeException(
                'Impossible de créer le fichier d’archive. Aucun fichier existant n’a été écrasé.'
            );
        }

        $success = false;

        try {
            $length = strlen($pdfContent);
            $offset = 0;

            while ($offset < $length) {
                $written = fwrite(
                    $handle,
                    substr($pdfContent, $offset)
                );

                if ($written === false || $written === 0) {
                    throw new \RuntimeException(
                        'Échec lors de l’écriture du PDF.'
                    );
                }

                $offset += $written;
            }

            if (!fflush($handle)) {
                throw new \RuntimeException(
                    'Impossible de finaliser l’écriture du PDF.'
                );
            }

            $success = true;
        } finally {
            fclose($handle);

            // Supprime seulement le fichier incomplet créé par cette tentative.
            if (!$success && is_file($filePath)) {
                @unlink($filePath);
            }
        }

        return $filePath;
    }

    private function isValidPdf(string $filePath): bool
    {
        if (!is_file($filePath) || filesize($filePath) === 0) {
            return false;
        }

        $handle = @fopen($filePath, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            return fread($handle, 5) === '%PDF-';
        } finally {
            fclose($handle);
        }
    }
}
