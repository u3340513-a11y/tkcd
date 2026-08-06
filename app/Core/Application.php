<?php

declare(strict_types=1);

namespace App\Core;

use App\Application\Service\LayoutDataComposer;
use App\Core\Exception\HttpNotFoundException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\SecurityHeaders;
use App\Core\Log\FileLogger;
use App\Core\Log\LoggerInterface;
use App\Core\Routing\Router;
use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;
use App\Core\View\ViewRendererInterface;
use App\Domain\Content\Repository\ActivityAreaRepositoryInterface;
use App\Domain\Content\Repository\AnnouncementRepositoryInterface;
use App\Domain\Content\Repository\DistrictRepositoryInterface;
use App\Domain\Content\Repository\EventRepositoryInterface;
use App\Domain\Content\Repository\MilestoneRepositoryInterface;
use App\Domain\Content\Repository\StatisticRepositoryInterface;
use App\Domain\Membership\MembershipRepositoryInterface;
use App\Infrastructure\Content\PhpFileActivityAreaRepository;
use App\Infrastructure\Content\PhpFileAnnouncementRepository;
use App\Infrastructure\Content\PhpFileDistrictRepository;
use App\Infrastructure\Content\PhpFileEventRepository;
use App\Infrastructure\Content\PhpFileMilestoneRepository;
use App\Infrastructure\Content\PhpFileStatisticRepository;
use App\Infrastructure\Membership\PdoMembershipRepository;
use PDO;
use Throwable;

/**
 * Uygulama önyükleyicisi ve istek yaşam döngüsünün sahibi.
 *
 * Sorumluluğu: bağımlılıkları bağlamak, isteği yönlendiriciye devretmek ve
 * beklenmeyen durumları kullanıcıya sızdırmadan yönetmek.
 */
final class Application
{
    private readonly Config $config;
    private readonly Container $container;
    private ?Router $router = null;

    public function __construct(private readonly string $basePath)
    {
        $this->config = new Config($this->basePath . '/config');
        $this->container = new Container();

        $this->configureRuntime();
        $this->registerBindings();
    }

    public function run(Request $request): void
    {
        // Request'i container'a bağla; denetleyiciler kurucu enjeksiyonuyla alabilir.
        $this->container->instance(Request::class, $request);

        (new SecurityHeaders())->apply();

        $this->handle($request)->send();
    }

    private function handle(Request $request): Response
    {
        try {
            $this->composeLayoutData($request);

            return $this->router()->dispatch($request);
        } catch (HttpNotFoundException $exception) {
            return $this->errorResponse(404, 'Sayfa Bulunamadı', $exception);
        } catch (Throwable $exception) {
            return $this->errorResponse(500, 'Beklenmeyen Bir Hata Oluştu', $exception);
        }
    }

    private function errorResponse(int $status, string $title, Throwable $exception): Response
    {
        if ($status >= 500) {
            $this->logger()->exception($exception);
        }

        try {
            $seo = new SeoMeta(
                title: $title,
                description: 'Aradığınız sayfaya ulaşılamadı. Ana sayfadan gezinmeye devam edebilirsiniz.',
                canonicalPath: '/',
                indexable: false,
            );

            $body = $this->view()->renderPage('pages/errors/error', $seo, [
                'status' => $status,
                'title' => $title,
            ]);

            return Response::html($body, $status);
        } catch (Throwable $renderFailure) {
            // Şablon da işlenemiyorsa en az düz metin bir yanıt döndürülür.
            $this->logger()->exception($renderFailure);

            return Response::html('<h1>' . Support\Html::escape($title) . '</h1>', $status);
        }
    }

    private function configureRuntime(): void
    {
        date_default_timezone_set($this->config->string('app.timezone', 'Europe/Istanbul'));
        mb_internal_encoding('UTF-8');

        $debug = $this->config->bool('app.debug');

        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    }

    private function registerBindings(): void
    {
        $this->container->instance(Config::class, $this->config);
        $this->container->instance(Container::class, $this->container);

        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $this->container->bind(ViewRendererInterface::class, PhpViewRenderer::class);

        // Şu an içerik PHP veri dosyalarından okunuyor. Admin panel fazında
        // yalnızca aşağıdaki bağlamalar PDO tabanlı sınıflarla değiştirilecek.
        $this->container->bind(AnnouncementRepositoryInterface::class, PhpFileAnnouncementRepository::class);
        $this->container->bind(EventRepositoryInterface::class, PhpFileEventRepository::class);
        $this->container->bind(StatisticRepositoryInterface::class, PhpFileStatisticRepository::class);
        $this->container->bind(ActivityAreaRepositoryInterface::class, PhpFileActivityAreaRepository::class);
        $this->container->bind(DistrictRepositoryInterface::class, PhpFileDistrictRepository::class);
        $this->container->bind(MilestoneRepositoryInterface::class, PhpFileMilestoneRepository::class);

        // Üyelik başvurusu: PDO bağlantısı yalnızca form gönderildiğinde
        // (save() çağrısında) kurulur; GET isteğinde bağlantı denenmez.
        $this->container->factory(
            MembershipRepositoryInterface::class,
            static function (): PdoMembershipRepository {
                $pdoFactory = static function (): PDO {
                    $host    = Env::string('DB_HOST',     '127.0.0.1');
                    $port    = Env::string('DB_PORT',     '3306');
                    $dbname  = Env::string('DB_DATABASE', '');
                    $user    = Env::string('DB_USERNAME', '');
                    $pass    = Env::string('DB_PASSWORD', '');
                    $charset = Env::string('DB_CHARSET',  'utf8mb4');

                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

                    return new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]);
                };

                return new PdoMembershipRepository($pdoFactory);
            }
        );
    }

    private function composeLayoutData(Request $request): void
    {
        /** @var LayoutDataComposer $composer */
        $composer = $this->container->get(LayoutDataComposer::class);
        $composer->compose($request);
    }

    private function router(): Router
    {
        if ($this->router !== null) {
            return $this->router;
        }

        /** @var Router $router */
        $router = $this->container->get(Router::class);

        /** @var list<array{0: string, 1: string, 2: class-string, 3: string, 4: string}> $routes */
        $routes = require $this->basePath . '/config/routes.php';

        foreach ($routes as [$method, $path, $controller, $action, $name]) {
            $method === 'POST'
                ? $router->post($path, $controller, $action, $name)
                : $router->get($path, $controller, $action, $name);
        }

        return $this->router = $router;
    }

    private function view(): PhpViewRenderer
    {
        /** @var PhpViewRenderer $view */
        $view = $this->container->get(ViewRendererInterface::class);

        return $view;
    }

    private function logger(): LoggerInterface
    {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);

        return $logger;
    }
}
