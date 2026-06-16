<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2020
 * @license Webasyst
 */

return array(
    'default_size'       => [
        'title'        => /*_wp*/('Размер упаковки по умолчанию'),
        'description'  => /*_wp*/('На случай, если ни одно из правил не подойдёт'),
        'control_type' => 'DimensionInput',
        'value'        => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm']
    ],
    'default_add_weight' => [
        'title'        => /*_wp*/('Вес упаковки по умолчанию'),
        'description'  => /*_wp*/('Какой вес добавить к общему весу заказа, если ни одно из правил не подойдёт'),
        'control_type' => 'WeightInput',
        'value'        => ['value' => 0, 'unit' => 'kg']
    ],

    'sizes' => [
        'title'        => /*_wp*/('Размеры упаковок'),
        'value'        => [
            'weight_unit' => 'kg',
            'packs'       => [
                [
                    'weight'          => 1,
                    'width'           => 10,
                    'height'          => 10,
                    'length'          => 10,
                    'unit'            => 'cm',
                    'add_weight'      => 30,
                    'add_weight_unit' => 'g'
                ]
            ]
        ],
        'control_type' => 'PackageDimensions'
    ]
);
