<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7db6b1690e2587fc24663a5507141723
{
    public static $prefixLengthsPsr4 = array (
        'B' => 
        array (
            'BeGateway\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'BeGateway\\' => 
        array (
            0 => __DIR__ . '/..' . '/begateway/begateway-api-php/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7db6b1690e2587fc24663a5507141723::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7db6b1690e2587fc24663a5507141723::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit7db6b1690e2587fc24663a5507141723::$classMap;

        }, null, ClassLoader::class);
    }
}
