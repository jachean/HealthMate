<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216190802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE doctor_service (id INT AUTO_INCREMENT NOT NULL, doctor_id INT NOT NULL, medical_service_id INT NOT NULL, price NUMERIC(8, 2) NOT NULL, duration_minutes INT NOT NULL, INDEX IDX_7230F97F87F4FB17 (doctor_id), INDEX IDX_7230F97FC61D802A (medical_service_id), UNIQUE INDEX unique_doctor_service (doctor_id, medical_service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE medical_service (id INT AUTO_INCREMENT NOT NULL, specialty_id INT DEFAULT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, INDEX IDX_A79F7A1C9A353316 (specialty_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE doctor_service ADD CONSTRAINT FK_7230F97F87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)');
        $this->addSql('ALTER TABLE doctor_service ADD CONSTRAINT FK_7230F97FC61D802A FOREIGN KEY (medical_service_id) REFERENCES medical_service (id)');
        $this->addSql('ALTER TABLE medical_service ADD CONSTRAINT FK_A79F7A1C9A353316 FOREIGN KEY (specialty_id) REFERENCES specialty (id)');
        $this->addSql('ALTER TABLE appointment ADD doctor_service_id INT NOT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844DA4B8077 FOREIGN KEY (doctor_service_id) REFERENCES doctor_service (id)');
        $this->addSql('CREATE INDEX IDX_FE38F844DA4B8077 ON appointment (doctor_service_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844DA4B8077');
        $this->addSql('ALTER TABLE doctor_service DROP FOREIGN KEY FK_7230F97F87F4FB17');
        $this->addSql('ALTER TABLE doctor_service DROP FOREIGN KEY FK_7230F97FC61D802A');
        $this->addSql('ALTER TABLE medical_service DROP FOREIGN KEY FK_A79F7A1C9A353316');
        $this->addSql('DROP TABLE doctor_service');
        $this->addSql('DROP TABLE medical_service');
        $this->addSql('DROP INDEX IDX_FE38F844DA4B8077 ON appointment');
        $this->addSql('ALTER TABLE appointment DROP doctor_service_id');
    }
}
