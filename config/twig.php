<?php

// Create Twig
use App\API\PiHole;
use Slim\Views\Twig;

$versions = (new PiHole())->getParsedVersions();
foreach ($versions as $key => &$value) {
    $val = explode('-', $value);
    $value = $val[0];
}
unset($value);
$twig = Twig::create(__DIR__ . '/../templates');

$twigEnv = $twig->getEnvironment();
$twigEnv->addGlobal('hostname', 'Bababerry');
$twigEnv->addGlobal('DockerVersion', $versions['DOCKER_VERSION'] ?? false);
$twigEnv->addGlobal('CoreVersion', $versions['CORE_VERSION']);
$twigEnv->addGlobal('FTLVersion', $versions['FTL_VERSION']);
$twigEnv->addGlobal('WebVersion', "1.0.0");

return $twig;