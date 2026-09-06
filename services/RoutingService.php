<?php

declare(strict_types=1);

namespace services;

use enums\GameSlug;
use traits\SecurityController;

// Decides whether the page a controller is about to render is the right one for the current
// session, and if not, returns the destination it should serve instead.
//
// Returns [] when the caller is clear to render whatever it intended (the caller builds that
// page's own data itself). Returns a non-empty array when the caller needs to go somewhere
// else instead - either ['redirectTo' => ...] or a full destination
// (layout/template/current_url/page_title/picture/title/page_css) ready for
// traits\PageRenderer::dispatch().
//
// Game checks only run when $gameSlug is passed, so a page unrelated to games gets back []
// (or an early redirect if the session itself is missing) without touching any game logic.
class RoutingService
{
    use SecurityController;

    // Ordered stages of the game-signup funnel: a session is at exactly one of these at any
    // time, and each stage only becomes reachable once every stage before it is done.
    private const STEP_GAME_SIGNUP = 'gameSignup';
    private const STEP_LOOKING_FOR = 'lookingFor';
    private const STEP_SWIPING_MAIN = 'swipingMain';

    /**
     * @param array<string, mixed> $expected declares which step the caller is trying to serve,
     *        via $expected['step'] (one of the STEP_* constants above). Only read when
     *        $gameSlug is also passed.
     */
    public function routeUser(array $expected, bool $requireFullSession = false, ?string $gameSlug = null): array
    {
        if ($requireFullSession) {
            $this->requireUserSessionOrRedirect('/');
        }

        if (!$this->isConnectGoogle()) {
            return ['redirectTo' => '/'];
        }

        if (!$this->isConnectWebsite()) {
            return $this->basicInfoDestination();
        }

        // A user_id in the URL must match the logged-in session.
        if (isset($_GET['user_id']) && $_GET['user_id'] != $_SESSION['userId']) {
            return ['redirectTo' => '/?message=This is not your account'];
        }

        $expectedStep = $expected['step'] ?? null;

        if ($gameSlug === null || $expectedStep === null) {
            return [];
        }

        $actualStep = $this->resolveStep($gameSlug);

        if ($actualStep === $expectedStep) {
            return [];
        }

        return $this->destinationForStep($actualStep, $gameSlug);
    }

    // Where this session actually stands in the funnel, independent of what the caller expected.
    private function resolveStep(string $gameSlug): string
    {
        if (!$this->hasGameAccount($gameSlug)) {
            return self::STEP_GAME_SIGNUP;
        }

        if (!$this->isConnectLf()) {
            return self::STEP_LOOKING_FOR;
        }

        return self::STEP_SWIPING_MAIN;
    }

    private function destinationForStep(string $step, string $gameSlug): array
    {
        return match ($step) {
            self::STEP_GAME_SIGNUP => $this->gameSignupDestination($gameSlug),
            self::STEP_LOOKING_FOR => $this->lookingForDestination($gameSlug),
            self::STEP_SWIPING_MAIN => $this->swipingMainDestination(),
        };
    }

    // Redirects rather than rendering directly: the signup form needs a lot more than this
    // service tracks (picker images, rank/role lists, JS files - see
    // UserGamesController::getGameSignupFormConfig), so this just points the browser at the
    // route that builds all of that (UserGamesController::pageLeagueUser/pageValorantUser).
    public function gameSignupDestination(string $gameSlug): array
    {
        $config = $this->getGameSignupConfig($gameSlug);

        return ['redirectTo' => $config['signupUrl']];
    }

    public function lookingForDestination(string $gameSlug): array
    {
        $config = $this->getGameSignupConfig($gameSlug);

        return [
            'layout' => 'views/layoutSignup.phtml',
            'template' => $config['lookingForTemplate'],
            'current_url' => $config['lookingForUrl'],
            'page_title' => 'URSG - Looking for',
            'title' => 'What are you looking for?',
            'picture' => 'ursg-preview-small',
        ];
    }

    public function swipingMainDestination(): array
    {
        return [
            'layout' => 'views/layoutSwiping.phtml',
            'template' => 'views/swiping/swiping_main',
            'current_url' => 'https://ur-sg.com/swiping',
            'page_title' => 'URSG - Swiping',
            'title' => 'Swipe',
            'picture' => 'ursg-preview-small',
            'page_css' => ['swiping'],
        ];
    }

    private function basicInfoDestination(): array
    {
        return [
            'layout' => 'views/layoutSignup.phtml',
            'template' => 'views/signup/basicinfo',
            'current_url' => 'https://ur-sg.com/basicinfo',
            'page_title' => 'URSG - Sign',
            'title' => 'Sign up',
            'picture' => 'ursg-preview-small',
        ];
    }

    // Checks session state for the given game via isConnectLeague()/isConnectValorant()
    // (traits/SecurityController.php), which each read a hardcoded session key
    // ($_SESSION['lol_id']/['valorant_id']).
    public function hasGameAccount(string $gameSlug): bool
    {
        return match ($gameSlug) {
            GameSlug::LEAGUE_OF_LEGENDS->value => $this->isConnectLeague(),
            GameSlug::VALORANT->value => $this->isConnectValorant(),
            default => false,
        };
    }

    private function getGameSignupConfig(string $gameSlug): array
    {
        return match ($gameSlug) {
            GameSlug::LEAGUE_OF_LEGENDS->value => [
                'lookingForTemplate' => 'views/signup/lookingforlol',
                'lookingForUrl' => 'https://ur-sg.com/lookingforuserlol',
                'signupUrl' => 'https://ur-sg.com/leagueuser',
            ],
            GameSlug::VALORANT->value => [
                'lookingForTemplate' => 'views/signup/lookingforvalorant',
                'lookingForUrl' => 'https://ur-sg.com/lookingforuservalorant',
                'signupUrl' => 'https://ur-sg.com/valorantuser',
            ],
            default => throw new \InvalidArgumentException("Unknown game slug: {$gameSlug}"),
        };
    }
}
