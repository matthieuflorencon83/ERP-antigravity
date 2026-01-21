<?php
// tests/test_json_builder.php
require_once __DIR__ . '/../classes/MetrageJsonBuilder.php';

$builder = new MetrageJsonBuilder();
$builder->setMeta('garage_test', 'Garage Test', 1);

$mockPost = [
    'largeur_tableau' => '2400',
    'hauteur_tableau' => '2150',
    'retombee_linteau' => '300',
    'pose' => 'TUNNEL',
    'support' => 'BETON',
    'moteur' => 'SOMFY',
    'couleur' => 'RAL 7016',
    'photo_facade' => 'data:image/png;base64,xxxx'
];

$builder->setDimensions($mockPost);
$builder->setEnvironnement($mockPost);
$builder->setSpecs($mockPost);
$builder->setMedia($mockPost);

echo "<pre>" . $builder->build() . "</pre>";
?>
