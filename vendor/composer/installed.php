<?php return array(
    'root' => array(
        'name' => 'pw/ofertas-avanzadas',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => 'c53dd665cdd20694839fbe5b3bf4abda67eb3cce',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'pw/backend-ui' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '00adc8bbcff939adab37c0dbc874029034aac82c',
            'type' => 'library',
            'install_path' => __DIR__ . '/../pw/backend-ui',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
        'pw/ofertas-avanzadas' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => 'c53dd665cdd20694839fbe5b3bf4abda67eb3cce',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
