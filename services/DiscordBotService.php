<?php

namespace services;

class DiscordBotService
{
    private static function getBotPath()
    {
        return realpath(__DIR__ . '/../ursg-bot/bot.js');
    }

    public static function start()
    {
        $botFile = self::getBotPath();
        $botDir = dirname($botFile);
        $pm2 = "$botDir/node_modules/.bin/pm2";
        $output = shell_exec("sudo $botDir/start-bot.sh 2>&1");
        return ['success' => true, 'message' => 'Bot started', 'output' => $output];
    }

    public static function stop()
    {
        $botFile = self::getBotPath();
        $botDir = dirname($botFile);
        $pm2 = "$botDir/node_modules/.bin/pm2";
        $output = shell_exec("sudo $botDir/stop-bot.sh 2>&1");
        return ['success' => true, 'message' => 'Bot stopped', 'output' => $output];
    }

    public static function restart()
    {
        $botFile = self::getBotPath();
        $botDir = dirname($botFile);
        $pm2 = "$botDir/node_modules";
        $output = shell_exec("sudo $botDir/stop-bot.sh && sleep 2 && sudo $botDir/start-bot.sh 2>&1");
        return ['success' => true, 'message' => 'Bot restarted', 'output' => $output];
    }

    public static function sendCommand($cmd)
    {
        $botFile = self::getBotPath();
        $botDir = dirname($botFile);
        $pm2 = "$botDir/node_modules";
        $output = shell_exec("sudo $pm2 send bot $cmd 2>&1");
        return ['success' => true, 'message' => 'Command sent', 'output' => $output];
    }

    public static function status()
    {
        $botFile = self::getBotPath();
        $botDir = dirname($botFile);
        $pm2 = "$botDir/node_modules/pm2";
        $output = shell_exec("sudo $botDir/status-bot.sh 2>&1");
        if (strpos($output, 'online') !== false) {
            return ['success' => true, 'status' => 'online', 'output' => $output];
        } elseif (strpos($output, 'stopped') !== false) {
            return ['success' => true, 'status' => 'stopped', 'output' => $output];
        } else {
            return ['success' => false, 'status' => 'unknown', 'output' => $output];
        }
    }
}