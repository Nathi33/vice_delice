<?php

namespace App\Command;

use App\Repository\OrderRepository;
use App\Service\InvoiceGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-invoice',
    description: 'Génère un PDF de test pour une commande existante'
)]
class TestInvoiceCommand extends Command
{
    public function __construct(
        private OrderRepository $orderRepository,
        private InvoiceGenerator $invoiceGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'orderId',
            InputArgument::REQUIRED,
            'Identifiant de la commande à tester'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $orderId = (int) $input->getArgument('orderId');

        if ($orderId <= 0) {
            $io->error('L’identifiant de commande doit être un nombre positif.');
            return Command::FAILURE;
        }

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            $io->error('Aucune commande trouvée avec cet identifiant.');
            return Command::FAILURE;
        }

        try {
            $pdfContent = $this->invoiceGenerator->generate($order);

            $directory = dirname(__DIR__, 2) . '/var/invoices';

            if (!is_dir($directory)
                && !mkdir($directory, 0775, true)
                && !is_dir($directory)
            ) {
                throw new \RuntimeException(
                    'Impossible de créer le dossier des PDF.'
                );
            }

            $filePath = $directory
                . '/test-commande-' . $orderId . '.pdf';

            $writtenBytes = file_put_contents($filePath, $pdfContent);

            if ($writtenBytes === false || $writtenBytes === 0) {
                throw new \RuntimeException(
                    'Impossible d’écrire le fichier PDF.'
                );
            }

            $io->success('PDF de test généré avec succès.');
            $io->writeln('Fichier : ' . $filePath);
            $io->writeln('Taille : ' . $writtenBytes . ' octets');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Erreur lors de la génération du PDF : ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
