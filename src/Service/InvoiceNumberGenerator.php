<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceNumberGenerator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function generate(Order $order): string
    {
        $orderId = $order->getId();

        if ($orderId === null) {
            throw new \RuntimeException(
                'Impossible de générer une facture pour une commande non enregistrée.'
            );
        }

        $connection = $this->entityManager->getConnection();
        $metadata = $this->entityManager->getClassMetadata(Order::class);

        // Récupère les vrais noms des tables et colonnes Doctrine.
        $tableName = trim($metadata->getTableName(), '`');
        $idColumn = $metadata->getIdentifierColumnNames()[0];
        $invoiceNumberColumn = $metadata->getColumnName('invoiceNumber');
        $invoiceIssuedAtColumn = $metadata->getColumnName('invoiceIssuedAt');

        $platform = $connection->getDatabasePlatform();

        $table = $platform->quoteIdentifier($tableName);
        $id = $platform->quoteIdentifier($idColumn);
        $invoiceNumber = $platform->quoteIdentifier($invoiceNumberColumn);
        $invoiceIssuedAt = $platform->quoteIdentifier($invoiceIssuedAtColumn);

        return $connection->transactional(
            function (Connection $connection) use (
                $order,
                $orderId,
                $table,
                $id,
                $invoiceNumber,
                $invoiceIssuedAt
            ): string {
                // Verrouille la commande afin d'éviter une double attribution.
                $row = $connection->fetchAssociative(
                    sprintf(
                        'SELECT %s AS invoice_number, %s AS invoice_issued_at
                         FROM %s
                         WHERE %s = :id
                         FOR UPDATE',
                        $invoiceNumber,
                        $invoiceIssuedAt,
                        $table,
                        $id
                    ),
                    ['id' => $orderId]
                );

                if ($row === false) {
                    throw new \RuntimeException(
                        'Commande introuvable lors de la génération de la facture.'
                    );
                }

                // Si un numéro existe déjà en base, on le conserve.
                if ($row['invoice_number'] !== null) {
                    $existingNumber = (string) $row['invoice_number'];

                    $order->setInvoiceNumber($existingNumber);

                    if ($row['invoice_issued_at'] !== null) {
                        $order->setInvoiceIssuedAt(
                            new \DateTimeImmutable(
                                (string) $row['invoice_issued_at']
                            )
                        );
                    }

                    return $existingNumber;
                }

                $year = (int) (new \DateTimeImmutable())->format('Y');

                // Initialise le compteur annuel s'il n'existe pas.
                $connection->executeStatement(
                    'INSERT IGNORE INTO invoice_counter
                        (invoice_year, last_number)
                     VALUES (:year, 0)',
                    ['year' => $year]
                );

                // Verrouille le compteur pour éviter les numéros identiques.
                $lastNumber = $connection->fetchOne(
                    'SELECT last_number
                     FROM invoice_counter
                     WHERE invoice_year = :year
                     FOR UPDATE',
                    ['year' => $year]
                );

                if ($lastNumber === false) {
                    throw new \RuntimeException(
                        'Impossible de récupérer le compteur de factures.'
                    );
                }

                $nextNumber = (int) $lastNumber + 1;

                $connection->update(
                    'invoice_counter',
                    ['last_number' => $nextNumber],
                    ['invoice_year' => $year]
                );

                $newInvoiceNumber = sprintf(
                    'VD-%04d-%06d',
                    $year,
                    $nextNumber
                );

                $issuedAt = new \DateTimeImmutable();

                // Enregistre le numéro et la date dans la même transaction.
                $connection->executeStatement(
                    sprintf(
                        'UPDATE %s
                         SET %s = :invoice_number,
                             %s = :issued_at
                         WHERE %s = :id',
                        $table,
                        $invoiceNumber,
                        $invoiceIssuedAt,
                        $id
                    ),
                    [
                        'invoice_number' => $newInvoiceNumber,
                        'issued_at' => $issuedAt->format('Y-m-d H:i:s'),
                        'id' => $orderId,
                    ]
                );

                // Synchronise l'objet en mémoire sans flush global.
                $order->setInvoiceNumber($newInvoiceNumber);
                $order->setInvoiceIssuedAt($issuedAt);

                return $newInvoiceNumber;
            }
        );
    }
}
