<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar_path to doctor and logo_path to clinic';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE doctor ADD avatar_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE clinic ADD logo_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE doctor DROP COLUMN avatar_path');
        $this->addSql('ALTER TABLE clinic DROP COLUMN logo_path');
    }
}
