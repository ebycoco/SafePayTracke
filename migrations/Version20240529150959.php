<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240529150959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `Payment` (id INT AUTO_INCREMENT NOT NULL, users_id INT DEFAULT NULL, payment_verification_id INT DEFAULT NULL, montant_apayer INT NOT NULL, montant_saisir INT NOT NULL, total_montant_payer INT DEFAULT NULL, recu_de_paiement VARCHAR(255) DEFAULT NULL, montant_restant INT DEFAULT NULL, status VARCHAR(255) NOT NULL, is_visibilite TINYINT(1) NOT NULL, is_verifier TINYINT(1) NOT NULL, date_paiement DATE NOT NULL, solde INT DEFAULT NULL, montant_prevu INT DEFAULT NULL, type_paiement VARCHAR(255) NOT NULL, avance_paiement INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A295BD9167B3B43D (users_id), UNIQUE INDEX UNIQ_A295BD9111AB27A9 (payment_verification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `payment_verification` (id INT AUTO_INCREMENT NOT NULL, payment_id INT DEFAULT NULL, montant_prevu INT NOT NULL, montant_recu INT NOT NULL, type_paiement VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3C0172114C3A3BB (payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, numero VARCHAR(255) NOT NULL, nom_de_societe VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, reset_token VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `Payment` ADD CONSTRAINT FK_A295BD9167B3B43D FOREIGN KEY (users_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `Payment` ADD CONSTRAINT FK_A295BD9111AB27A9 FOREIGN KEY (payment_verification_id) REFERENCES `payment_verification` (id)');
        $this->addSql('ALTER TABLE `payment_verification` ADD CONSTRAINT FK_3C0172114C3A3BB FOREIGN KEY (payment_id) REFERENCES `Payment` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `Payment` DROP FOREIGN KEY FK_A295BD9167B3B43D');
        $this->addSql('ALTER TABLE `Payment` DROP FOREIGN KEY FK_A295BD9111AB27A9');
        $this->addSql('ALTER TABLE `payment_verification` DROP FOREIGN KEY FK_3C0172114C3A3BB');
        $this->addSql('DROP TABLE `Payment`');
        $this->addSql('DROP TABLE `payment_verification`');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
