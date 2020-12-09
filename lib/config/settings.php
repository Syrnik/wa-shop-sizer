<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2020
 * @license Webasyst
 */

return array(
    'default_size'       => [
        'title'        => 'Размер упаковки по умолчанию',
        'description'  => 'На случай, если ни одно из правил не подойдёт',
        'control_type' => 'DimensionInput',
        'value'        => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm']
    ],
    'default_add_weight' => [
        'title'        => 'Вес упаковки по умолчанию',
        'description'  => 'Какой вес добавить к общему весу заказа, если ни одно из правил не подойдёт',
        'control_type' => 'WeightInput',
        'value'        => ['value' => 0, 'unit' => 'kg']
    ],

    'sizes' => [
        'title'        => 'Размеры упаковок',
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
