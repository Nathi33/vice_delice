<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fix-product-references',
    description: 'Recopie le slug dans la référence fournisseur des produits existants'
)]
class FixProductReferencesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        ini_set('memory_limit', '512M');

        $batchSize = 100;
        $count = 0;

        while (true) {

            $products = $this->em
                ->getRepository(Product::class)
                ->createQueryBuilder('p')
                ->where('p.supplierReference IS NULL')
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult();

            if (!$products) {
                break;
            }

            foreach ($products as $product) {

                $slug = $product->getSlug();

                if ($slug !== '') {
                    $product->setSupplierReference($slug);
                    $count++;
                }
            }

            $this->em->flush();
            $this->em->clear();

            $output->writeln(
                "<info>$count références corrigées...</info>"
            );
        }

        $output->writeln('');
        $output->writeln(
            "<info>Correction terminée : $count références corrigées.</info>"
        );

        return Command::SUCCESS;
    }
}