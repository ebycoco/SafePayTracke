<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240509183445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, users_id INT DEFAULT NULL, payment_verification_id INT DEFAULT NULL, montant_apayer INT NOT NULL, recu_de_paiement VARCHAR(255) NOT NULL, montant_restant INT DEFAULT NULL, status VARCHAR(255) NOT NULL, is_visibilite TINYINT(1) NOT NULL, INDEX IDX_6D28840D67B3B43D (users_id), UNIQUE INDEX UNIQ_6D28840D11AB27A9 (payment_verification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_verification (id INT AUTO_INCREMENT NOT NULL, payment_id INT DEFAULT NULL, montant_prevu INT NOT NULL, montant_recu INT NOT NULL, type_paiement VARCHAR(255) NOT NULL, INDEX IDX_3C0172114C3A3BB (payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D67B3B43D FOREIGN KEY (users_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D11AB27A9 FOREIGN KEY (payment_verification_id) REFERENCES payment_verification (id)');
        $this->addSql('ALTER TABLE payment_verification ADD CONSTRAINT FK_3C0172114C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D67B3B43D');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D11AB27A9');
        $this->addSql('ALTER TABLE payment_verification DROP FOREIGN KEY FK_3C0172114C3A3BB');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE payment_verification');
    }
}
