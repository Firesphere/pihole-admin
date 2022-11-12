<?php

// Create Twig
use App\API\FTL;
use App\API\PiHole;
use App\SysInfo;
use Odan\Twig\TwigAssetsExtension;
use Slim\Views\Twig;

$versions = (new PiHole())->getParsedVersions();
foreach ($versions as $key => &$value) {
    $val = explode('-', $value);
    $value = $val[0];
}
unset($value);
$twig = Twig::create(__DIR__ . '/../templates');
$twig->addExtension(new TwigAssetsExtension($twig->getEnvironment(), [
    'path'          => __DIR__ . '/../public/cache',
    // The public url base path
    'url_base_path' => 'cache/',
    'minify'        => 0,
]));

[$mem, $load, $cpu, $temp] = SysInfo::getAll();
$globals = [
    'baseHref'      => '/',
    'hostname'      => gethostname(),
    'PiHoleStatus'  => FTL::getFTLStatus(),
    'DockerVersion' => $versions['DOCKER_VERSION'] ?? false,
    'CoreVersion'   => $versions['CORE_VERSION'],
    'FTLVersion'    => $versions['FTL_VERSION'],
    'WebVersion'    => "1.0.0",
    'Load0'         => number_format($load[0], 2),
    'Load1'         => number_format($load[1], 2),
    'Load2'         => number_format($load[2], 2),
    'MemUse'        => number_format($mem, 4),
    'CPUCount'      => $cpu,
    'Temperature'   => $temp,
    'Menu'          => (require __DIR__ . '/menu.php')
];
$twigEnv = $twig->getEnvironment();
foreach ($globals as $key => $value) {
    $twigEnv->addGlobal($key, $value);
}
$twigEnv->addGlobal('Theme', (new \App\Helper\Config())->get('pihole')['WEBTHEME'] ?? 'default-auto');
return $twig;
