<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508102747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des contraintes FK + index performance catalogue produits';
    }

    public function up(Schema $schema): void
    {
        // =========================
        // FOREIGN KEYS (EXISTANTES)
        // =========================

        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');

        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id)');

        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT FK_64617F034584665A FOREIGN KEY (product_id) REFERENCES product (id)');

        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');

        // =========================
        // INDEX PERFORMANCE CATALOGUE
        // =========================

        $this->addSql('CREATE INDEX idx_product_active ON product (is_active)');
        $this->addSql('CREATE INDEX idx_product_slug ON product (slug)');
        $this->addSql('CREATE INDEX idx_product_price ON product (price)');
        $this->addSql('CREATE INDEX idx_product_category ON product (category_id)');
        $this->addSql('CREATE INDEX idx_category_slug ON category (slug)');
    }

    public function down(Schema $schema): void
    {
        // =========================
        // DROP INDEX
        // =========================

        $this->addSql('DROP INDEX idx_product_active ON product');
        $this->addSql('DROP INDEX idx_product_slug ON product');
        $this->addSql('DROP INDEX idx_product_price ON product');
        $this->addSql('DROP INDEX idx_product_category ON product');
        $this->addSql('DROP INDEX idx_category_slug ON category');

        // =========================
        // DROP FOREIGN KEYS
        // =========================

        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');

        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');

        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');

        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD44F5D008');

        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY FK_64617F034584665A');

        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41D4584665A');
    }
}