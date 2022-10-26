<?php

// Create Twig
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
    'minify'        => 0

]));
[$mem, $load, $cpu, $temp] = SysInfo::getAll();
$twigEnv = $twig->getEnvironment();
$twigEnv->addGlobal('hostname', 'Bababerry');
$twigEnv->addGlobal('DockerVersion', $versions['DOCKER_VERSION'] ?? false);
$twigEnv->addGlobal('CoreVersion', $versions['CORE_VERSION']);
$twigEnv->addGlobal('FTLVersion', $versions['FTL_VERSION']);
$twigEnv->addGlobal('WebVersion', "1.0.0");
$twigEnv->addGlobal('Load0', number_format($load[0], 2));
$twigEnv->addGlobal('Load1', number_format($load[1], 2));
$twigEnv->addGlobal('Load2', number_format($load[2], 2));
$twigEnv->addGlobal('MemUse', number_format($mem, 4));
$twigEnv->addGlobal('CPUCount', $cpu);
$twigEnv->addGlobal('Temperature', $temp);

return $twig;