<?php

namespace App\Command;

use App\Repository\OrderRepository;
use App\Service\InvoiceGenerator;
use App\Service\OrderMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-invoice-email',
    description: 'Envoie un PDF de test à une adresse e-mail choisie'
)]
class TestInvoiceEmailCommand extends Command
{
    public function __construct(
        private OrderRepository $orderRepository,
        private InvoiceGenerator $invoiceGenerator,
        private OrderMailer $orderMailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'orderId',
                InputArgument::REQUIRED,
                'Identifiant de la commande à tester'
            )
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'Adresse e-mail de test que tu contrôles'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $orderId = filter_var(
            $input->getArgument('orderId'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $recipientEmail = filter_var(
            $input->getArgument('email'),
            FILTER_VALIDATE_EMAIL
        );

        if ($orderId === false) {
            $io->error('L’identifiant de commande doit être un entier positif.');
            return Command::FAILURE;
        }

        if ($recipientEmail === false) {
            $io->error('L’adresse e-mail fournie est invalide.');
            return Command::FAILURE;
        }

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            $io->error('Aucune commande trouvée avec cet identifiant.');
            return Command::FAILURE;
        }

        try {
            $pdfContent = $this->invoiceGenerator->generate($order);

            $this->orderMailer->sendTestPdf(
                $order,
                $pdfContent,
                $recipientEmail
            );

            $io->success('E-mail de test envoyé à ' . $recipientEmail);
            $io->note(
                'La pièce jointe est un document de test, pas une facture valable.'
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error(
                'Échec de la génération ou de l’envoi : ' . $e->getMessage()
            );

            return Command::FAILURE;
        }
    }
}
