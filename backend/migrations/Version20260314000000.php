<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add doctor.user_id FK and appointment consultation fields (started_at, completed_at, delay_minutes)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE doctor ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE doctor ADD UNIQUE INDEX UNIQ_1FC0F36AA76ED395 (user_id)');
        $this->addSql('ALTER TABLE doctor ADD CONSTRAINT FK_DOCTOR_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE appointment ADD started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD delay_minutes INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE doctor DROP FOREIGN KEY FK_DOCTOR_USER');
        $this->addSql('ALTER TABLE doctor DROP INDEX UNIQ_1FC0F36AA76ED395');
        $this->addSql('ALTER TABLE doctor DROP user_id');

        $this->addSql('ALTER TABLE appointment DROP started_at, DROP completed_at, DROP delay_minutes');
    }
}
