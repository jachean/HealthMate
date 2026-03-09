<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260307225124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE doctor_unavailability (id INT AUTO_INCREMENT NOT NULL, doctor_id INT NOT NULL, date_from DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', date_to DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', reason VARCHAR(255) DEFAULT NULL, INDEX IDX_805ECCE687F4FB17 (doctor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE doctor_unavailability ADD CONSTRAINT FK_805ECCE687F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)');
        $this->addSql('ALTER TABLE doctor CHANGE start_hour start_hour SMALLINT NOT NULL, CHANGE end_hour end_hour SMALLINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE doctor_unavailability DROP FOREIGN KEY FK_805ECCE687F4FB17');
        $this->addSql('DROP TABLE doctor_unavailability');
        $this->addSql('ALTER TABLE doctor CHANGE start_hour start_hour SMALLINT DEFAULT 9 NOT NULL, CHANGE end_hour end_hour SMALLINT DEFAULT 17 NOT NULL');
    }
}
