<?php

namespace database;

interface Migration
{
    public function up(\PDO $bdd): void;

    public function down(\PDO $bdd): void;
}
