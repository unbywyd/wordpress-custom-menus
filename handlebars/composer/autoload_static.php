<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit90c9c9b25f9506d656df52fba1c63a9d
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'LightnCandy\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'LightnCandy\\' => 
        array (
            0 => __DIR__ . '/..' . '/zordius/lightncandy/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit90c9c9b25f9506d656df52fba1c63a9d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit90c9c9b25f9506d656df52fba1c63a9d::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
