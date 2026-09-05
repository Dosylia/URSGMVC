<?php

namespace database;

class Migrator
{
    private \PDO $bdd;
    private string $migrationsPath;

    public function __construct(\PDO $bdd, string $migrationsPath)
    {
        $this->bdd = $bdd;
        $this->migrationsPath = rtrim($migrationsPath, '/');
    }

    public function up(): void
    {
        $this->ensureMigrationsTable();

        $pending = $this->getPendingFiles();

        if (empty($pending)) {
            echo "Nothing to migrate.\n";
            return;
        }

        $batch = $this->getNextBatch();

        foreach ($pending as $file) {
            $className = $this->classNameFromFile($file);

            require_once $file;

            $fqcn = "\\database\\migrations\\{$className}";
            $migration = new $fqcn();

            echo "Migrating: {$className}\n";

            try {
                $migration->up($this->bdd);
                $this->recordApplied(basename($file, '.php'), $batch);
                echo "Migrated:  {$className}\n";
            } catch (\Throwable $e) {
                echo "Migration failed: {$className}\n";
                echo $e->getMessage() . "\n";
                echo "Stopping here. Already-applied migrations were recorded and will not run again on the next 'up'.\n";
                return;
            }
        }
    }

    public function down(int $steps = 1): void
    {
        $this->ensureMigrationsTable();

        $applied = $this->getLastAppliedNames($steps);

        if (empty($applied)) {
            echo "Nothing to roll back.\n";
            return;
        }

        foreach ($applied as $name) {
            $file = $this->findFileByName($name);

            if ($file === null) {
                echo "Migration file for '{$name}' not found on disk, skipping. Remove it from the migrations table manually if this is expected.\n";
                continue;
            }

            $className = $this->classNameFromFile($file);
            require_once $file;

            $fqcn = "\\database\\migrations\\{$className}";
            $migration = new $fqcn();

            echo "Rolling back: {$className}\n";
            $migration->down($this->bdd);
            $this->recordRolledBack($name);
            echo "Rolled back:  {$className}\n";
        }
    }

    public function status(): void
    {
        $this->ensureMigrationsTable();

        $applied = $this->getAppliedNames();

        foreach ($this->getAllFiles() as $file) {
            $name = basename($file, '.php');
            $mark = in_array($name, $applied, true) ? '[x]' : '[ ]';
            echo "{$mark} {$name}\n";
        }
    }

    // Runs before every command instead of shipping as its own migration file.
    // Avoids the chicken-and-egg problem of the tracking table needing itself to exist
    // before the runner can even check what has been applied.
    private function ensureMigrationsTable(): void
    {
        $this->bdd->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                migration_id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_migration_name (migration_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function getAllFiles(): array
    {
        $files = glob("{$this->migrationsPath}/*.php");
        sort($files);
        return $files;
    }

    private function getPendingFiles(): array
    {
        $applied = $this->getAppliedNames();

        return array_values(array_filter($this->getAllFiles(), function (string $file) use ($applied) {
            return !in_array(basename($file, '.php'), $applied, true);
        }));
    }

    private function getAppliedNames(): array
    {
        $stmt = $this->bdd->query("SELECT migration_name FROM migrations");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getLastAppliedNames(int $steps): array
    {
        $stmt = $this->bdd->prepare("
            SELECT migration_name FROM migrations
            ORDER BY migration_id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $steps, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getNextBatch(): int
    {
        $stmt = $this->bdd->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations");
        return (int) $stmt->fetchColumn();
    }

    private function recordApplied(string $name, int $batch): void
    {
        $stmt = $this->bdd->prepare("INSERT INTO migrations (migration_name, batch) VALUES (?, ?)");
        $stmt->execute([$name, $batch]);
    }

    private function recordRolledBack(string $name): void
    {
        $stmt = $this->bdd->prepare("DELETE FROM migrations WHERE migration_name = ?");
        $stmt->execute([$name]);
    }

    private function findFileByName(string $name): ?string
    {
        $file = "{$this->migrationsPath}/{$name}.php";
        return file_exists($file) ? $file : null;
    }

    // Strips the leading date/sequence prefix (e.g. "20260905_000001_") and
    // turns the rest into a StudlyCase class name, e.g. "create_games_table" -> "CreateGamesTable".
    private function classNameFromFile(string $file): string
    {
        $name = basename($file, '.php');
        $parts = explode('_', $name);

        while (!empty($parts) && ctype_digit($parts[0])) {
            array_shift($parts);
        }

        return implode('', array_map('ucfirst', $parts));
    }
}
