<?php

namespace services;

/**
 * The set of onboarding steps a LoginOutcome can resolve to.
 * Plain constants (PHP 7.4 predates native enums).
 */
class LoginDestination
{
    public const ONBOARDED = 'onboarded';
    public const NEEDS_LOOKING_FOR = 'needs_looking_for';
    public const NEEDS_GAME_ACCOUNT = 'needs_game_account';
    public const NO_USER_ROW = 'no_user_row';
}
