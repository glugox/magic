<?php

use Glugox\ModelMeta\ModelMetaResolver;

require_once __DIR__ . '/Dummies/Modules/Crm/Models/Customer.php';
require_once __DIR__ . '/Dummies/Modules/Crm/Meta/Models/CustomerMeta.php';

it('resolves module model meta classes based on the model namespace', function () {
    ModelMetaResolver::setDefaultNamespace('App\\Meta\\Models');

    expect(ModelMetaResolver::resolve(Modules\Crm\Models\Customer::class))
        ->toBe(Modules\Crm\Meta\Models\CustomerMeta::class);
});
