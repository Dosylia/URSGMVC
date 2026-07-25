<?php

namespace traits;

trait MobileDeepLinkResponder
{
    abstract protected function mobileFlowSessionKey(): string;

    abstract protected function mobileDeepLinkCallback(): string;

    public function handleMobileFlowFailure($error)
    {
        unset($_SESSION['phoneData']);
        unset($_SESSION[$this->mobileFlowSessionKey()]);

        $response = array(
            'status' => 'failure',
            'message' => $error
        );
        $responseJson = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        error_log(print_r('Error ' . $error, true));
        $redirectUrl = "intent://" . $this->mobileDeepLinkCallback() . "?response=" . rawurlencode($responseJson) . "#Intent;scheme=com.dosylia.URSG;package=com.dosylia.URSG;end;";
        $this->outputMobileFlowHtml($redirectUrl, false);
    }

    public function handleMobileFlowSuccess($message, $response)
    {
        unset($_SESSION['phoneData']);
        unset($_SESSION[$this->mobileFlowSessionKey()]);

        $responseJson = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $redirectUrl = "intent://" . $this->mobileDeepLinkCallback() . "?response=" . rawurlencode($responseJson) . "#Intent;scheme=com.dosylia.URSG;package=com.dosylia.URSG;end;";
        $this->outputMobileFlowHtml($redirectUrl, true);
    }

    private function outputMobileFlowHtml($redirectUrl, $success = true)
    {
        $title = $success ? 'Authentication Successful' : 'Authentication Failed';
        $message = $success ? 'Redirecting you back to the URSG app...' : 'There was a problem. Redirecting you back to the URSG app...';
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Return to URSG App</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <script>
                function openApp() {
                    window.location.href = "' . $redirectUrl . '";
                    setTimeout(function() {
                        if (!document.webkitHidden && !document.hidden) {
                            document.getElementById("fallbackButton").style.display = "block";
                            document.getElementById("appStoreButton").style.display = "block";
                        }
                    }, 1000);
                }
                window.onload = function() { openApp(); };
            </script>
        </head>
        <body style="font-family: Arial, sans-serif; text-align: center; padding: 40px;">
            <h2>' . $title . '</h2>
            <p>' . $message . '</p>
            <div id="fallbackButton" style="display: none;">
                <p>If you werent redirected automatically, click below:</p>
                <a href="' . htmlspecialchars($redirectUrl) . '" style="padding: 15px 30px; background: #e74057; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; display: inline-block;">
                    Open URSG App
                </a>
            </div>
            <div id="appStoreButton" style="display: none; margin-top: 20px;">
                <p>Dont have the app?</p>
                <a href="https://play.google.com/store/apps/details?id=com.dosylia.URSG" style="padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
                    Get on Google Play
                </a>
                <a href="https://apps.apple.com/app/" style="padding: 10px 20px; background: #007AFF; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Get on App Store
                </a>
            </div>
        </body>
        </html>';
        return;
    }
}
