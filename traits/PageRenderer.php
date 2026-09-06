<?php

declare(strict_types=1);

namespace traits;

// Assigns a page's layout variables and requires the layout file. Parameter names match the
// variable names the layout files (layout.phtml, layoutSignup.phtml, layoutSwiping.phtml,
// etc.) read via shared scope, since `require` exposes the calling scope's locals - renaming
// them would leave the layout unable to see them. $data holds anything else the specific page
// template needs (e.g. $user), extracted into scope before the require.
trait PageRenderer
{
    protected function renderPage(
        string $layout,
        string $template,
        string $current_url,
        string $page_title,
        string $picture,
        ?string $title = null,
        array $page_css = [],
        array $data = []
    ): void {
        extract($data, EXTR_SKIP);
        require $layout;
    }

    // Redirects if $destination carries a redirectTo, otherwise renders it via renderPage().
    protected function dispatch(array $destination, array $data = []): void
    {
        if (isset($destination['redirectTo'])) {
            header("Location: {$destination['redirectTo']}");
            return;
        }

        $this->renderPage(
            layout: $destination['layout'],
            template: $destination['template'],
            current_url: $destination['current_url'],
            page_title: $destination['page_title'],
            picture: $destination['picture'],
            title: $destination['title'] ?? null,
            page_css: $destination['page_css'] ?? [],
            data: $data,
        );
    }
}
