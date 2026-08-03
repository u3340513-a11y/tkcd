<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Core\Config;
use App\Core\Http\Request;
use App\Core\View\ViewRendererInterface;

/**
 * Her sayfada tekrarlanan yerleşim verisini şablonlara aktarır.
 *
 * Neden: Site kimliği, menü ve sosyal medya bağlantıları gibi veriler her
 * denetleyicide tekrar tekrar hazırlanmak yerine tek noktadan paylaşılır.
 */
final class LayoutDataComposer
{
    public function __construct(
        private readonly Config $config,
        private readonly NavigationProvider $navigation,
        private readonly ViewRendererInterface $view,
    ) {
    }

    public function compose(Request $request): void
    {
        $this->view->share('site', $this->config->array('site'));
        $this->view->share('socials', $this->config->array('social'));
        $this->view->share('navigation', $this->navigation->build($request->path));
        $this->view->share('currentPath', $request->path);
        $this->view->share('currentYear', (int) date('Y'));
        $this->view->share('analyticsId', $this->config->string('app.analytics.measurement_id'));
        $this->view->share('language', $this->config->string('app.language', 'tr'));
    }
}
