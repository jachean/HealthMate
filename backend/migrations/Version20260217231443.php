<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217231443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review ADD appointment_id INT DEFAULT NULL, ADD author_name VARCHAR(255) DEFAULT NULL, CHANGE rating rating NUMERIC(3, 1) NOT NULL, CHANGE comment comment VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_794381C6E5B533F9 ON review (appointment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6E5B533F9');
        $this->addSql('DROP INDEX UNIQ_794381C6E5B533F9 ON review');
        $this->addSql('ALTER TABLE review DROP appointment_id, DROP author_name, CHANGE rating rating INT NOT NULL, CHANGE comment comment VARCHAR(255) DEFAULT NULL');
    }
}
