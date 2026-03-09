<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-doctor working hours (work_days, start_hour, end_hour)';
    }

    public function up(Schema $schema): void
    {
        // Add nullable first so existing rows can be populated
        $this->addSql('ALTER TABLE doctor ADD work_days JSON DEFAULT NULL');
        $this->addSql("UPDATE doctor SET work_days = '[1,2,3,4,5]'");
        $this->addSql('ALTER TABLE doctor MODIFY work_days JSON NOT NULL');

        $this->addSql('ALTER TABLE doctor ADD start_hour SMALLINT NOT NULL DEFAULT 9');
        $this->addSql('ALTER TABLE doctor ADD end_hour SMALLINT NOT NULL DEFAULT 17');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE doctor DROP COLUMN work_days');
        $this->addSql('ALTER TABLE doctor DROP COLUMN start_hour');
        $this->addSql('ALTER TABLE doctor DROP COLUMN end_hour');
    }
}
