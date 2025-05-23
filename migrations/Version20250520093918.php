<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250520093918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE appointement (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, vehicule_id INT DEFAULT NULL, dealership_id INT DEFAULT NULL, date DATETIME NOT NULL, INDEX IDX_BD9991CD19EB6921 (client_id), INDEX IDX_BD9991CD4A4A3511 (vehicule_id), INDEX IDX_BD9991CD8CF5FC51 (dealership_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE appointement_service (appointement_id INT NOT NULL, service_id INT NOT NULL, INDEX IDX_EFF7DFEF1EBF5025 (appointement_id), INDEX IDX_EFF7DFEFED5CA9E6 (service_id), PRIMARY KEY(appointement_id, service_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, civil_title VARCHAR(10) NOT NULL, last_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, address LONGTEXT NOT NULL, zip_code VARCHAR(5) NOT NULL, phone VARCHAR(10) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE dealership (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, zip_code VARCHAR(5) NOT NULL, longitude NUMERIC(9, 6) NOT NULL, latitude NUMERIC(9, 6) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, help LONGTEXT NOT NULL, commentary LONGTEXT NOT NULL, time INT NOT NULL, price INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D64919EB6921 (client_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE vehicule (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, brand VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, registration VARCHAR(255) NOT NULL, vin VARCHAR(255) NOT NULL, circulation_date DATE NOT NULL, mileage INT NOT NULL, driver TINYINT(1) NOT NULL, driver_last_name VARCHAR(255) DEFAULT NULL, driver_first_name VARCHAR(255) DEFAULT NULL, driver_phone VARCHAR(10) DEFAULT NULL, INDEX IDX_292FFF1D19EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement ADD CONSTRAINT FK_BD9991CD19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement ADD CONSTRAINT FK_BD9991CD4A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement ADD CONSTRAINT FK_BD9991CD8CF5FC51 FOREIGN KEY (dealership_id) REFERENCES dealership (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement_service ADD CONSTRAINT FK_EFF7DFEF1EBF5025 FOREIGN KEY (appointement_id) REFERENCES appointement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement_service ADD CONSTRAINT FK_EFF7DFEFED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64919EB6921 FOREIGN KEY (client_id) REFERENCES client (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1D19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement DROP FOREIGN KEY FK_BD9991CD19EB6921
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement DROP FOREIGN KEY FK_BD9991CD4A4A3511
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement DROP FOREIGN KEY FK_BD9991CD8CF5FC51
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement_service DROP FOREIGN KEY FK_EFF7DFEF1EBF5025
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointement_service DROP FOREIGN KEY FK_EFF7DFEFED5CA9E6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64919EB6921
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1D19EB6921
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE appointement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE appointement_service
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE client
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE dealership
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE service
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `user`
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE vehicule
        SQL);
    }
}
