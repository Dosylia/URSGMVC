<?php

namespace services;

class LoginOutcome
{
    public bool $newUser;
    public bool $userExists;
    public ?array $identityRow;
    public ?array $userRow;
    public ?string $game;
    public ?array $gameProfile;
    public ?array $lookingForRow;
    public string $masterToken;
    public string $destination;

    public function __construct(array $props = [])
    {
        $this->newUser = $props['newUser'] ?? false;
        $this->userExists = $props['userExists'] ?? false;
        $this->identityRow = $props['identityRow'] ?? null;
        $this->userRow = $props['userRow'] ?? null;
        $this->game = $props['game'] ?? null;
        $this->gameProfile = $props['gameProfile'] ?? null;
        $this->lookingForRow = $props['lookingForRow'] ?? null;
        $this->masterToken = $props['masterToken'] ?? '';
        $this->destination = $props['destination'] ?? LoginDestination::NO_USER_ROW;
    }
}
